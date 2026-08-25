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
use Contao\CoreBundle\Menu\BackendMenuBuilder;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Make sure this listener comes before the other ones adding to its tree.
 *
 * @internal
 */
#[AsEventListener(priority: 10)]
class BackendMainListener
{
    public function __construct(private readonly Security $security)
    {
    }

    public function __invoke(MenuEvent $event): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return;
        }

        $name = $event->getTree()->getName();

        if ('mainMenu' !== $name) {
            return;
        }

        $factory = $event->getFactory();
        $tree = $event->getTree();
        $modules = $user->navigation();

        foreach ($modules as $categoryName => $categoryData) {
            $categoryNode = $tree->getChild($categoryName);

            if (!$categoryNode) {
                $categoryNode = $this->createCategory($factory, $categoryName, $categoryData);
                $tree->addChild($categoryNode);
            }

            $this->addModules($factory, $categoryNode, $categoryData['modules']);
        }
    }

    private function createCategory(FactoryInterface $factory, string $name, array $data): ItemInterface
    {
        $node = $factory
            ->createItem($name)
            ->setLabel($data['label'])
            ->setUri($data['href'])
            ->setExtra('translation_domain', false)
        ;

        if ($class = $this->getCustomClass($data, ['group-'.$name])) {
            $node->setLinkAttribute('class', $class);
        }

        return $node;
    }

    private function addModules(FactoryInterface $factory, ItemInterface $category, array $modules): void
    {
        // Create the child nodes
        foreach ($modules as $name => $data) {
            $node = $factory
                ->createItem($name)
                ->setLabel($data['label'])
                ->setUri($data['href'])
                ->setCurrent((bool) $data['isActive'])
                ->setExtra(BackendMenuBuilder::EXTRA_ICON, $name)
                ->setExtra('title', $data['title'])
                ->setExtra('translation_domain', false)
            ;

            if ($class = $this->getCustomClass($data, ['navigation', $name])) {
                $node->setLinkAttribute('class', $class);
            }

            $category->addChild($node);
        }
    }

    private function getCustomClass(array $attributes, array $defaultClasses): string
    {
        $classes = [];

        // Remove the default CSS classes and keep potentially existing custom ones (see #1357)
        if (isset($attributes['class'])) {
            $classes = array_flip(array_filter(explode(' ', (string) $attributes['class'])));

            foreach (['node-expanded', 'node-collapsed', 'trail', ...$defaultClasses] as $class) {
                unset($classes[$class]);
            }
        }

        return implode(' ', array_keys($classes));
    }
}
