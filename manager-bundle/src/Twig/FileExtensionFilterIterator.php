<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Twig;

use Symfony\Component\Filesystem\Path;

/**
 * @template TKey of array-key
 *
 * @implements \IteratorAggregate<TKey, string>
 */
class FileExtensionFilterIterator implements \IteratorAggregate
{
    /**
     * @var \Traversable<TKey, string>
     */
    private readonly \Traversable $iterator;

    /**
     * @param \IteratorAggregate<TKey, string> $templateIterator
     *
     * @internal
     */
    public function __construct(\IteratorAggregate $templateIterator)
    {
        $this->iterator = $templateIterator->getIterator();
    }

    /**
     * @return \CallbackFilterIterator<TKey, string, \IteratorIterator<TKey, string, \Traversable<TKey, string>>>
     */
    public function getIterator(): \CallbackFilterIterator
    {
        return new \CallbackFilterIterator(
            new \IteratorIterator($this->iterator),
            static fn ($path): bool => str_starts_with($path, '@') || 'twig' === Path::getExtension($path, true),
        );
    }
}
