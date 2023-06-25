<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer;

use Contao\CoreBundle\DataContainer\BuilderTemplate\DataContainerBuilderTemplateInterface;
use Contao\System;

class DataContainerBuilderFactory implements DataContainerBuilderFactoryInterface
{
    /**
     * @param iterable<DataContainerBuilderTemplateInterface> $templates
     */
    public function __construct(private readonly iterable $templates)
    {
    }

    public function __invoke(string $name): DataContainerBuilderInterface
    {
        return new DataContainerBuilder($name, $this->templates);
    }
}

/**
 * Helper function to create a builder instance from within DCA files.
 */
function dca(string|null $name = null): DataContainerBuilderInterface
{
    if (null === $name) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
        $name = pathinfo($trace[0]['file'], PATHINFO_FILENAME);
    }

    return (System::getContainer()->get('contao.data_container.builder_factory'))($name);
}
