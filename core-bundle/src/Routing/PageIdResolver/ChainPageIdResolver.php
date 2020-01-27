<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\PageIdResolver;

use Contao\CoreBundle\Routing\PageIdResolverInterface;

class ChainPageIdResolver implements PageIdResolverInterface
{
    /** @var PageIdResolverInterface[] */
    private $resolvers = [];

    /**
     * @param PageIdResolverInterface[] $resolvers
     */
    public function __construct(iterable $resolvers = [])
    {
        foreach ($resolvers as $resolver) {
            $this->register($resolver);
        }
    }

    public function register(PageIdResolverInterface $pageIdResolver): void
    {
        $this->resolvers[] = $pageIdResolver;
    }

    public function resolvePageIds(?array $names): array
    {
        if (count($this->resolvers) === 0) {
            return [];
        }

        $resolvers = array_map(
            static function (PageIdResolverInterface $pageIdResolver) use ($names): array {
                return $pageIdResolver->resolvePageIds($names);
            },
            $this->resolvers
        );

        return array_unique(
            array_merge(...$resolvers)
        );
    }
}
