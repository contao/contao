<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\Relation;

use Contao\ApiBundle\Relation\AbstractRelationResolver;
use Contao\ApiBundle\Relation\RelationReference;
use PHPUnit\Framework\TestCase;

final class AbstractRelationResolverTest extends TestCase
{
    public function testPreservesZeroAndEmptyStringIdentifiersForOtherRelations(): void
    {
        $resolver = $this->createResolver();
        $relation = new \stdClass();

        foreach ([0, '0', ''] as $identifier) {
            $this->assertEquals(new RelationReference($identifier, '/items/'.$identifier), $resolver->resolveToReference($identifier, $relation));
            $this->assertSame((string) $identifier, $resolver->resolveToIdentifier('/items/'.$identifier, $relation));
        }

        $this->assertNull($resolver->resolveToReference(null, $relation));
        $this->assertNull($resolver->resolveToIdentifier(null, $relation));
    }

    /**
     * @return AbstractRelationResolver<\stdClass>
     */
    private function createResolver(): AbstractRelationResolver
    {
        return new
            /**
             * @extends AbstractRelationResolver<\stdClass>
             */
            class() extends AbstractRelationResolver {
                public function supports(object $relation): bool
                {
                    return $relation instanceof \stdClass;
                }

                protected function createIri(object $relation, mixed $identifier): string
                {
                    return '/items/'.$identifier;
                }

                protected function getIdentifier(object $relation, mixed $iri): string
                {
                    return substr($iri, \strlen('/items/'));
                }
            };
    }
}
