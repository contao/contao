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

use Contao\BackendUser;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class BackendMenuListener
{
    /**
     * @var Security
     */
    private $security;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(Security $security, RouterInterface $router, RequestStack $requestStack, TranslatorInterface $translator, ContaoFramework $framework)
    {
        $this->security = $security;
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->framework = $framework;
    }

    public function __invoke(MenuEvent $event): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return;
        }

        $name = $event->getTree()->getName();

        if ('mainMenu' === $name) {
            $this->buildMainMenu($event, $user);
        } elseif ('userMenu' === $name) {
            $this->buildUserMenu($event, $user);
        }
    }

    private function buildMainMenu(MenuEvent $event, BackendUser $user): void
    {
        $factory = $event->getFactory();
        $tree = $event->getTree();
        $modules = $user->navigation();
        $path = $this->router->generate('contao_backend');

        foreach ($modules as $categoryName => $categoryData) {
            $categoryNode = $tree->getChild($categoryName);

            if (!$categoryNode) {
                $categoryNode = $factory
                    ->createItem($categoryName)
                    ->setLabel($categoryData['label'])
                    ->setUri($categoryData['href'])
                    ->setLinkAttribute('class', $this->getClassFromAttributes($categoryData))
                    ->setLinkAttribute('title', $categoryData['title'])
                    ->setLinkAttribute('onclick', "return AjaxRequest.toggleNavigation(this, '".$categoryName."', '".$path."')")
                    ->setChildrenAttribute('id', $categoryName)
                    ->setExtra('translation_domain', false)
                ;

                if (isset($categoryData['class']) && preg_match('/\bnode-collapsed\b/', $categoryData['class'])) {
                    $categoryNode->setAttribute('class', 'collapsed');
                }

                $tree->addChild($categoryNode);
            }

            // Create the child nodes
            foreach ($categoryData['modules'] as $nodeName => $nodeData) {
                $moduleNode = $factory
                    ->createItem($nodeName)
                    ->setLabel($nodeData['label'])
                    ->setUri($nodeData['href'])
                    ->setLinkAttribute('class', $this->getClassFromAttributes($nodeData))
                    ->setLinkAttribute('title', $nodeData['title'])
                    ->setCurrent((bool) $nodeData['isActive'])
                    ->setExtra('translation_domain', false)
                ;

                $categoryNode->addChild($moduleNode);
            }
        }
    }

    private function buildUserMenu(MenuEvent $event, BackendUser $user): void
    {
        $factory = $event->getFactory();
        $tree = $event->getTree();
        $ref = $this->getRefererId();

        $login = $factory
            ->createItem('login')
            ->setLabel($this->trans('MSC.profile'))
            ->setUri($this->router->generate('contao_backend', ['do' => 'login', 'ref' => $ref]))
            ->setExtra('translation_domain', 'contao_default')
        ;

        $tree->addChild($login);

        $security = $factory
            ->createItem('security')
            ->setLabel($this->trans('MSC.security'))
            ->setUri($this->router->generate('contao_backend', ['do' => 'security', 'ref' => $ref]))
            ->setExtra('translation_domain', 'contao_default')
        ;

        $tree->addChild($security);
    }

    private function trans(string $id): string
    {
        return $this->translator->trans($id, [], 'contao_default');
    }

    private function getRefererId(): string
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        return $request->attributes->get('_contao_referer_id');
    }

    private function getClassFromAttributes(array $attributes): string
    {
        $classes = [];

        // Remove the default CSS classes and keep potentially existing custom ones (see #1357)
        if (isset($attributes['class'])) {
            $classes = array_flip(array_filter(explode(' ', $attributes['class'])));
            unset($classes['node-expanded'], $classes['node-collapsed'], $classes['trail']);
        }

        return implode(' ', array_keys($classes));
    }
}
