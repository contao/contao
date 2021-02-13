<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Controller\BackendModule\BackendModuleController;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Listener to set the fragment options from $GLOBALS['BE_MOD'] to the current backend module fragment controller.
 * This is required because legacy backend modules cannot be registered as fragments in the container.
 *
 * @internal
 */
class BackendModuleControllerListener
{
    public function __invoke(ControllerEvent $event): void
    {
        $controller = $event->getController();
        $request = $event->getRequest();

        if (!$controller instanceof BackendModuleController || !$request->query->has('do')) {
            return;
        }

        $name = $request->query->get('do');

        foreach ($GLOBALS['BE_MOD'] as $group) {
            if (isset($group[$name])) {
                $config = $group[$name];
                $options = array_merge($config, ['type' => $name]);

                $controller->setFragmentOptions($options);

                return;
            }
        }
    }
}
