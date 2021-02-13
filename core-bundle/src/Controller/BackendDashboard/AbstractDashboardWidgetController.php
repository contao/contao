<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\BackendDashboard;

use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Fragment\FragmentOptionsAwareInterface;

/**
 * Abstract Controller used to display a widget in the backend dashboard.
 *
 * @author Richard Henkenjohann <https://github.com/richardhj>
 */
abstract class AbstractDashboardWidgetController extends AbstractController implements FragmentOptionsAwareInterface
{
    /**
     * @var array
     */
    protected $options = [];

    public function setFragmentOptions(array $options): void
    {
        $this->options = $options;
    }
}
