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

class DashboardWidgetReference extends FragmentReference
{
    public const TAG_NAME = 'contao.dashboard_widget';

    public function __construct(string $name)
    {
        parent::__construct(self::TAG_NAME.'.'.$name);

        $this->setBackendScope();
    }
}
