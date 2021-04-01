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

    public function __construct(ModuleModel $model, /* array */ $templateProps = ['section' => 'main'])
    {
        parent::__construct(self::TAG_NAME.'.'.$model->type);

        if (!\is_array($templateProps)) {
            @trigger_error('The second argument to '.__METHOD__.' must be an array of template properties since Contao 4.9.14');

            $templateProps = ['section' => (string) $templateProps];
        }

        $this->attributes['moduleModel'] = $model->id;
        $this->attributes['templateProps'] = $templateProps;
        $this->attributes['section'] = $templateProps['section'];
        $this->attributes['classes'] = $model->classes;
    }
}
