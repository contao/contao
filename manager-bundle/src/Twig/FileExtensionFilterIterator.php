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

class FileExtensionFilterIterator implements \IteratorAggregate
{
    private \Traversable $iterator;

    /**
     * @internal
     */
    public function __construct(\IteratorAggregate $templateIterator)
    {
        $this->iterator = $templateIterator->getIterator();
    }

    public function getIterator(): \CallbackFilterIterator
    {
        return new \CallbackFilterIterator(
            new \IteratorIterator($this->iterator),
            static fn ($path): bool => 0 === strncmp($path, '@', 1) || 'twig' === Path::getExtension($path, true)
        );
    }
}
