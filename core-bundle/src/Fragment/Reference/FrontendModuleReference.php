<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Fragment\Reference;

use Contao\ModuleModel;

class FrontendModuleReference extends FragmentReference
{
    public const TAG_NAME = 'contao.frontend_module';

    /**
     * @param ModuleModel $model
     * @param string      $section
     */
    public function __construct(ModuleModel $model, string $section = 'main')
    {
        parent::__construct(self::TAG_NAME.'.'.$model->type);

        $this->attributes['moduleModel'] = $model->id;
        $this->attributes['section'] = $section;
    }
}
