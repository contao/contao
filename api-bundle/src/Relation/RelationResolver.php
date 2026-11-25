<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Relation;

final readonly class RelationResolver implements RelationResolverInterface
{
    /**
     * @var iterable<RelationResolverInterface>
     */
    private iterable $resolvers;

    /**
     * @param iterable<RelationResolverInterface> $resolvers In descending service tag priority
     */
    public function __construct(iterable $resolvers)
    {
        // Keep repeated lookups valid for iterators such as generators
        $this->resolvers = $resolvers instanceof \Iterator ? iterator_to_array($resolvers, false) : $resolvers;
    }

    public function supports(object $relation): bool
    {
        return null !== $this->getResolver($relation);
    }

    public function resolveToReference(mixed $value, object $relation): mixed
    {
        $resolver = $this->getResolver($relation);

        return $resolver ? $resolver->resolveToReference($value, $relation) : $value;
    }

    public function resolveToIdentifier(mixed $value, object $relation): mixed
    {
        $resolver = $this->getResolver($relation);

        return $resolver ? $resolver->resolveToIdentifier($value, $relation) : $value;
    }

    private function getResolver(object $relation): RelationResolverInterface|null
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($relation)) {
                return $resolver;
            }
        }

        return null;
    }
}
