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

use Contao\ContentModel;
use Contao\ContentProxy;

class ContentElementReference extends FragmentReference
{
    public const TAG_NAME = 'contao.content_element';
    public const GLOBALS_KEY = 'TL_CTE';
    public const PROXY_CLASS = ContentProxy::class;

    public function __construct(ContentModel $model, /* array */ $templateProps = ['section' => 'main'])
    {
        parent::__construct(self::TAG_NAME.'.'.$model->type);

        if (!\is_array($templateProps)) {
            @trigger_error('The second argument to '.__METHOD__.' should be an array of template properties since Contao 4.9.14');

            $templateProps = ['section' => (string) $templateProps];
        }

        $this->attributes['contentModel'] = $model->id;
        $this->attributes['templateProps'] = $templateProps;
        $this->attributes['classes'] = $model->classes;
    }
}
