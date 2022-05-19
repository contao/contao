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
    final public const TAG_NAME = 'contao.content_element';
    final public const GLOBALS_KEY = 'TL_CTE';
    final public const PROXY_CLASS = ContentProxy::class;

    public function __construct(ContentModel $model, string $section = 'main', array $templateProperties = [], bool $inline = false)
    {
        parent::__construct(self::TAG_NAME.'.'.$model->type);

        $this->attributes['contentModel'] = $inline ? $model : $model->id;
        $this->attributes['section'] = $section;
        $this->attributes['classes'] = $model->classes;
        $this->attributes['templateProperties'] = $templateProperties;
    }
}
