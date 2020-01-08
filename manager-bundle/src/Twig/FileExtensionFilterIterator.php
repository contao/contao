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
    /**
     * @var \Traversable
     */
    private $iterator;

    /**
     * @internal Do not inherit from this class; decorate the "contao_manager.twig.file_extension_filter_iterator" service instead
     */
    public function __construct(\IteratorAggregate $templateIterator)
    {
        $this->iterator = $templateIterator->getIterator();
    }

    public function getIterator(): \CallbackFilterIterator
    {
        return new \CallbackFilterIterator(
            new \IteratorIterator($this->iterator),
            static function ($path): bool {
                return 0 === strncmp($path, '@', 1) || '.twig' === substr($path, -5);
            }
        );
    }
}
