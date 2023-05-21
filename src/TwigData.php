<?php

declare(strict_types=1);

namespace Okvpn\Expression;

class TwigData implements \ArrayAccess, \IteratorAggregate, \Countable
{
    public function __construct(protected array $data)
    {
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->data);
    }
}
