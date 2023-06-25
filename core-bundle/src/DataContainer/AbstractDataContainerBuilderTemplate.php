<?php

namespace Contao\CoreBundle\DataContainer;

abstract class AbstractDataContainerBuilderTemplate implements DataContainerBuilderTemplateInterface
{
    public function getName(): string
    {
        return static::class;
    }
}
