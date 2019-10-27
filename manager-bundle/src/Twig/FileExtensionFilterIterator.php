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

class FileExtensionFilterIterator implements \IteratorAggregate
{
    /** @var \Traversable */
    private $iterator;

    public function __construct(\IteratorAggregate $templateIterator)
    {
        $this->iterator = $templateIterator->getIterator();
    }

    public function getIterator()
    {
        return new \CallbackFilterIterator(
            new \IteratorIterator($this->iterator),
            function ($path) {
                return $this->acceptsPath($path);
            }
        );
    }

    /**
     * Filter files by extension (in the root namespace).
     */
    private function acceptsPath(string $path): bool
    {
        return 0 === strncmp($path, '@', 1) || '.sql' !== substr($path, -4);
    }
}
