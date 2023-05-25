<?php

declare(strict_types=1);

namespace Okvpn\Expression;

use Okvpn\Expression\Extension\CoreLangExtension;
use Twig\Cache\CacheInterface;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;
use Twig\Source;
use Twig\TokenStream;

class TwigLanguage extends Environment
{
    protected $initialized = false;
    /** @var \Twig\ExtensionSet */
    protected $extensionSet;
    /** @var CacheInterface */
    protected $cache;
    protected $templateClassPrefix = '__TwigTemplate_';
    protected $loadedTemplates = [];
    protected $loadedExpressions = [];
    protected $optionsHash;
    protected $debug;
    protected $strictVariables;

    protected $logHandler;

    public function __construct(protected iterable $extensions = [], LoaderInterface $loader = null, array $options = [])
    {
        $get = \Closure::bind(fn($env, $prop) => $env->{$prop}, $this, Environment::class);

        parent::__construct($loader ?: new ArrayLoader(), $options + ['autoescape' => false, 'cache' => sys_get_temp_dir() . '/ygg']);

        $this->cache = $get($this, 'cache');
        $this->extensionSet = $get($this, 'extensionSet');
        $this->debug = $get($this, 'debug');
        $this->strictVariables = $get($this, 'strictVariables');

        $this->addExtension(new CoreLangExtension());
    }

    /**
     * {@inheritdoc}
     */
    public function compileSource(Source $source): string
    {
        $steam = $this->tokenize($source);
        $tokens = \Closure::bind(static fn($steam) => $steam->tokens, null, TokenStream::class)($steam);
        $tokens = array_filter($tokens, fn($token) => $token->getType() !== 0);
        $stream = new TokenStream(array_values($tokens), $steam->getSourceContext());

        $nodes = $this->parse($stream);

        try {
            return $this->replaceCompileSource($this->compile($nodes));
        } catch (Error $e) {
            $e->setSourceContext($source);
            throw $e;
        } catch (\Exception $e) {
            throw new SyntaxError(sprintf('An exception has been thrown during the compilation of a template ("%s").', $e->getMessage()), -1, $source, $e);
        }
    }

    protected function replaceCompileSource(string $code): string
    {
        $code = preg_replace('#\sextends\sTemplate#', ' extends \\Okvpn\\Expression\\EvalTemplate', $code, 1);
        return preg_replace('#\sfunction\sdoDisplay\(array\s\$#', ' function doEval(array &$', $code, 1);
    }

    public function setLogHandler(callable|\Closure|null $logHandler): void
    {
        $this->logHandler = $logHandler;
    }

    /**
     * Execute a twig expression script.
     *
     * @param string $name The template name or content
     * @param array|\ArrayAccess $context
     * @param bool|null $asString
     *
     * @return mixed
     * @throws LoaderError When the template cannot be found
     * @throws RuntimeError When a previously generated cache is corrupted
     * @throws SyntaxError When an error occurred during compilation
     */
    public function execute($name, array|\ArrayAccess $context = [], ?bool $asString = null): mixed
    {
        $template = $this->loadTemplate($this->getTemplateClass($name), $name, null, $asString);
        $template->setLogHandler($this->logHandler);

        return $template->script($context);
    }

    /**
     * Evaluate an expression.
     *
     * @param string $name The template name or content
     * @param array|\ArrayAccess $context
     *
     * @return mixed
     */
    public function evaluate(string $expression, array|\ArrayAccess $context = []): mixed
    {
        $template = $this->loadedExpressions[$expression] ??=
            $this->loadTemplate($this->getTemplateClass($wrap = '{% return ' . $expression . ' %}'), $wrap, null, true);

        return $template->evalFast($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateClass(string $name, int $index = null): string
    {
        if (false === $this->initialized) {
            foreach ($this->extensions as $extension) {
                $this->addExtension($extension);
            }
            $this->updateOptionsHash();
            $this->initialized = true;
        }

        if (str_starts_with($name, $this->templateClassPrefix)) {
            return $name;
        }

        $key = $name.$this->optionsHash;

        return $this->templateClassPrefix.hash('xxh128', $key).(null === $index ? '' : '___'.$index);
    }

    /**
     * {@inheritdoc}
     */
    public function loadTemplate(string $cls, string $nameOrContent, int $index = null, ?bool $asString = null): EvalTemplate
    {
        $mainCls = $cls;
        if (null !== $index) {
            $cls .= '___'.$index;
        }

        if (isset($this->loadedTemplates[$cls])) {
            return $this->loadedTemplates[$cls];
        }

        if (!class_exists($cls, false)) {
            $loader = $this->getLoader();
            $key = $this->cache->generateKey($nameOrContent, $mainCls);

            $asString ??= ($loader instanceof ArrayLoader && !$loader->exists($nameOrContent)) || str_contains($nameOrContent, " ");

            if (!$this->isAutoReload() || $asString || $this->isTemplateFresh($nameOrContent, $this->cache->getTimestamp($key))) {
                $this->cache->load($key);
            }

            if (!class_exists($cls, false)) {
                $source = $asString ? new Source($nameOrContent, $cls) : $loader->getSourceContext($nameOrContent);
                $content = $this->compileSource($source);
                $this->cache->write($key, $content);
                $this->cache->load($key);

                if (!class_exists($mainCls, false)) {
                    /* Last line of defense if either $this->bcWriteCacheFile was used,
                     * $this->cache is implemented as a no-op or we have a race condition
                     * where the cache was cleared between the above calls to write to and load from
                     * the cache.
                     */
                    eval('?>'.$content);
                }

                if (!class_exists($cls, false)) {
                    throw new RuntimeError(sprintf('Failed to load Twig template "%s", index "%s": cache might be corrupted.', $nameOrContent, $index), -1, $source);
                }
            }
        }

        $this->extensionSet->initRuntime();

        return $this->loadedTemplates[$cls] = new $cls($this);
    }

    /**
     * {@inheritdoc}
     */
    public function enableStrictVariables(): void
    {
        parent::enableStrictVariables();

        $this->strictVariables = true;
        $this->updateOptionsHash();
    }

    /**
     * {@inheritdoc}
     */
    public function disableStrictVariables(): void
    {
        parent::disableStrictVariables();

        $this->strictVariables = false;
        $this->updateOptionsHash();
    }

    /**
     * {@inheritdoc}
     */
    public function enableDebug(): void
    {
        parent::enableDebug();

        $this->debug = true;
        $this->updateOptionsHash();
    }

    /**
     * {@inheritdoc}
     */
    public function disableDebug(): void
    {
        parent::disableDebug();

        $this->debug = false;
        $this->updateOptionsHash();
    }

    private function updateOptionsHash(): void
    {
        $this->optionsHash = implode(':', [
            $this->extensionSet->getSignature(),
            self::VERSION,
            (int) $this->debug,
            (int) $this->strictVariables,
        ]);
    }
}
