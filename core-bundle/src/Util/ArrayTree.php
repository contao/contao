<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Util;

/**
 * @internal
 *
 * @implements \IteratorAggregate<string, mixed>
 */
final class ArrayTree implements \IteratorAggregate
{
    private array $rootNode = [];

    private array $currentNode;

    private array $parentNodes = [];

    public function __construct()
    {
        $this->currentNode = &$this->rootNode;
    }

    public function addContentNode(mixed $content): void
    {
        $this->currentNode[] = $content;
    }

    public function enterChildNode(string|null $key = null): void
    {
        $this->parentNodes[] = &$this->currentNode;

        if (\array_key_exists($key, $this->currentNode)) {
            $childNode = &$this->currentNode[$key];
        } else {
            $childNode = [];
        }

        if (null !== $key) {
            $this->currentNode[$key] = &$childNode;
        } else {
            $this->currentNode[] = &$childNode;
        }

        $this->currentNode = &$childNode;
    }

    public function up(): void
    {
        if ([] === $this->parentNodes) {
            throw new \OutOfBoundsException('Cannot go up - already at root level.');
        }

        $this->currentNode = &$this->parentNodes[array_key_last($this->parentNodes)];

        array_pop($this->parentNodes);
    }

    public function &current(): self
    {
        $subTree = new self();
        $subTree->rootNode = &$this->currentNode;

        return $subTree;
    }

    public function toArray(): array
    {
        return $this->rootNode;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->rootNode);
    }
}
