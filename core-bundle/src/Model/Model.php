<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Model;

use Contao\Model as ContaoModel;

class Model
{
    public const TAG_NAME = 'contao.models';

    /**
     * @var array<ContaoModel>
     */
    private array $models = [];

    public function addModels(array $models): void
    {
        $this->models = $models;
    }

    /**
     * @return list<ContaoModel>
     */
    public function getModels(): array
    {
        return $this->models;
    }
}
