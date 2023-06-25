<?php

namespace Contao\CoreBundle\DataContainer\BuilderTemplate;

interface DataContainerBuilderTemplateInterface
{
    public function getName(): string;

    public function getConfig(): array;
}
