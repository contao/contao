<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fragment\Reference;

use Contao\ModuleModel;
use Contao\ModuleProxy;

class FrontendModuleReference extends FragmentReference
{
    public const TAG_NAME = 'contao.frontend_module';
    public const GLOBALS_KEY = 'FE_MOD';
    public const PROXY_CLASS = ModuleProxy::class;

    public function __construct(ModuleModel $model, string $section = 'main', array $templateProperties = [], bool $inline = false)
    {
        parent::__construct(self::TAG_NAME.'.'.$model->type);

        $this->attributes['moduleModel'] = $inline ? $model : $model->id;
        $this->attributes['section'] = $section;
        $this->attributes['classes'] = $model->classes;
        $this->attributes['templateProperties'] = $templateProperties;
    }
}
