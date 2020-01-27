<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\PageType;

use Contao\PageModel;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PageTypeRegistry implements \IteratorAggregate
{
    /** @var PageTypeInterface[] */
    private $pageTypes = [];

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function register(PageTypeInterface $pageType): self
    {
        $this->pageTypes[$pageType->getName()] = $pageType;

        return $this;
    }

    public function has(string $type): bool
    {
        return array_key_exists($type, $this->pageTypes);
    }

    public function get(string $type): PageTypeInterface
    {
        if (!$this->has($type)) {
            throw UnknownPageTypeException::ofType($type);
        }

        return $this->pageTypes[$type];
    }

    public function supportsFeature(string $type, string $feature): bool
    {
        if (!$this->has($type)) {
            return false;
        }

        return $this->get($type)->supportsFeature($feature);
    }

    public function getRoutes(PageModel $pageModel, bool $prependLocale, string $urlSuffix): iterable
    {
        if (!$this->has($pageModel->type)) {
            return [];
        }

        return $this->get($pageModel->type)->getRoutes($pageModel, $prependLocale, $urlSuffix);
    }

    /**
     * @return \Traversable|PageTypeInterface[]
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->pageTypes);
    }

    /**
     * @return \Traversable|PageTypeInterface[]
     */
    public function getPageTypesWithDynamicAliases(): \Traversable
    {
        return new \CallbackFilterIterator(
            $this->getIterator(),
            static function (PageTypeInterface $pageType) {
                return count($pageType->getAvailableAliasParameters()) > 0;
            }
        );
    }
}
