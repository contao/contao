<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\BackendModule;

use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Fragment\FragmentOptionsAwareInterface;

/**
 * Abstract Controller to render a backend module.
 * To register a backend module, use the `contao.backend_module` service tag.
 *
 * @author Richard Henkenjohann <https://github.com/richardhj>
 */
abstract class AbstractBackendModuleController extends AbstractController implements FragmentOptionsAwareInterface
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
