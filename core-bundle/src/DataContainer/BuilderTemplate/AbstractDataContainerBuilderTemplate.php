<?php

namespace Contao\CoreBundle\DataContainer\BuilderTemplate;

abstract class AbstractDataContainerBuilderTemplate implements DataContainerBuilderTemplateInterface
{
    public function getName(): string
    {
        return static::class;
    }
}
