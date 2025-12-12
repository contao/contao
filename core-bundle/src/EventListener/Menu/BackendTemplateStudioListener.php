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

use Contao\CoreBundle\Controller\BackendTemplateStudioController;
use Contao\CoreBundle\Event\MenuEvent;
use Knp\Menu\Util\MenuManipulator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[AsEventListener]
class BackendTemplateStudioListener
{
    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly bool $templateStudioEnabled,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        if (!$this->templateStudioEnabled) {
            return;
        }

        // At the moment, the Template Studio is available for admins only.
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $name = $event->getTree()->getName();

        if ('mainMenu' !== $name) {
            return;
        }

        $categoryNode = $event->getTree()->getChild('design');

        if (!$categoryNode || (!$request = $this->requestStack->getCurrentRequest())) {
            return;
        }

        $templateStudioNode = $event->getFactory()
            ->createItem('template-studio')
            ->setLabel('MOD.template_studio.0')
            ->setExtra('translation_domain', 'contao_modules')
            ->setUri($this->router->generate('contao_template_studio'))
            ->setLinkAttribute('class', 'navigation template-studio')
            ->setLinkAttribute('title', $this->translator->trans('MOD.template_studio.1', [], 'contao_modules'))
            // FIXME: Use ->query, ->request or ->attributes
            ->setCurrent(BackendTemplateStudioController::class === $request->get('_controller'))
        ;

        $categoryNode->addChild($templateStudioNode);

        // Add the node before the legacy templates module.
        (new MenuManipulator())->moveToPosition($templateStudioNode, 1);
    }
}
