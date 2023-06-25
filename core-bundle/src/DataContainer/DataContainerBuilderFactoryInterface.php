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

interface DataContainerBuilderFactoryInterface
{
    public function __invoke(string $name): DataContainerBuilderInterface;
}
