<?php
declare(strict_types=1);

namespace Contao\CoreBundle\Util;

class CachingTraversable implements \IteratorAggregate
{
    /**
     * @var list<array{0:mixed,1:mixed}>
     */
    private array $items = [];

    private \IteratorIterator $iterator;

    public function __construct(\Traversable $traversable)
    {
        $this->iterator = new \IteratorIterator($traversable);
    }

    public function getIterator(): \Generator
    {
        $current = 0;

        while (true) {
            if (isset($this->items[$current])) {
                yield $this->items[$current][0] => $this->items[$current][1];
                ++$current;
                continue;
            }

            if ($current === 0) {
                $this->iterator->rewind();
            } else {
                $this->iterator->next();
            }

            if (!$this->iterator->valid()) {
                return;
            }

            $this->items[$current] = [$this->iterator->key(), $this->iterator->current()];
        }
    }
}
