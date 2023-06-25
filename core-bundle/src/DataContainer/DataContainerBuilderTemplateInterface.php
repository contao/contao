<?php

namespace Contao\CoreBundle\DataContainer;

interface DataContainerBuilderTemplateInterface
{
    public function getName(): string;

    public function getConfig(): array;
}
