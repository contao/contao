<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\Menu;

use Contao\CoreBundle\Controller\BackendFileManagerController;
use Contao\CoreBundle\Event\MenuEvent;
use Knp\Menu\Util\MenuManipulator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[AsEventListener]
class BackendFileManagerListener
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly bool $fileManagerEnabled,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        if (!$this->fileManagerEnabled) {
            return;
        }

        $name = $event->getTree()->getName();

        if ('mainMenu' !== $name) {
            return;
        }

        $categoryNode = $event->getTree()->getChild('content');

        if (!$categoryNode || (!$request = $this->requestStack->getCurrentRequest())) {
            return;
        }

        $fileManagerNode = $event->getFactory()
            ->createItem('file-manager')
            ->setLabel($this->translator->trans('MOD.files.0', [], 'contao_modules'))
            ->setUri($this->router->generate('contao_file_manager'))
            ->setLinkAttribute('class', 'navigation file-manager')
            ->setLinkAttribute('title', $this->translator->trans('MOD.files.1', [], 'contao_modules'))
            ->setCurrent(BackendFileManagerController::class === $request->get('_controller'))
        ;

        $categoryNode->addChild($fileManagerNode);
        $categoryNode->removeChild('files');

        // Add the node in place of the legacy files module.
        (new MenuManipulator())->moveToPosition($fileManagerNode, 2);
    }
}
