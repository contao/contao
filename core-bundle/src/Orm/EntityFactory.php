<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Orm;

class EntityFactory
{
    public function generateEntityClasses(array $entities, array $extension): void
    {
        dump($entities, $extension);die();
    }
}
