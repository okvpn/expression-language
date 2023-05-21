<?php

declare(strict_types=1);

namespace Okvpn\Expression;

use Twig\Error\Error;
use Twig\Error\RuntimeError;
use Twig\Template;

abstract class EvalTemplate extends Template
{
    protected $logs = [];

    public function script(array|TwigData $context, &$logs = null): mixed
    {
        $this->logs = [];
        return $this->evalWithErrorHandling($context, $logs);
    }

    public function evalFast(array $context): mixed
    {
        return $this->doEval($context);
    }

    public function reset(): array
    {
        $logs = $this->logs;
        $this->logs = [];

        return $logs;
    }

    protected function log($log): void
    {
        $this->logs[] = $log;
    }

    protected function evalWithErrorHandling(array|TwigData $data, &$logs = null): mixed
    {
        $context = $data instanceof TwigData ? $data->getData() : $data;

        try {
            return $this->doEval($context);
        } catch (Error $e) {
            if (!$e->getSourceContext()) {
                $e->setSourceContext($this->getSourceContext());
            }

            // this is mostly useful for \Twig\Error\LoaderError exceptions
            // see \Twig\Error\LoaderError
            if (-1 === $e->getTemplateLine()) {
                $e->guess();
            }

            throw $e;
        } catch (\Exception $e) {
            $e = new RuntimeError(sprintf('An exception has been thrown during the rendering of a template ("%s").', $e->getMessage()), -1, $this->getSourceContext(), $e);
            $e->guess();

            throw $e;
        } finally {
            $logs = $this->reset();
            if ($data instanceof TwigData) {
                $data->setData($context);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doDisplay(array $context, array $blocks = [])
    {
        $this->doEval($context, $blocks);
    }

    /**
     * Auto-generated method to return the template data.
     *
     * @param array $context An array of parameters to pass to the template
     * @param array $blocks  An array of blocks to pass to the template
     *
     * @return mixed
     */
    abstract protected function doEval(array &$context, array $blocks = []);
}
