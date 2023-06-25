<?php

namespace Contao\CoreBundle\DataContainer;

interface DataContainerBuilderFactoryInterface
{
    public function __invoke(string $name): DataContainerBuilderInterface;
}
