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

use Contao\ApiBundle\Relation\RelationResolver;
use Contao\ApiBundle\Relation\RelationResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;

final class RelationResolverTest extends TestCase
{
    public function testDelegatesToTheFirstSupportingResolver(): void
    {
        $relation = new \stdClass();

        $first = $this->createMock(RelationResolverInterface::class);
        $first
            ->expects($this->exactly(2))
            ->method('supports')
            ->with($relation)
            ->willReturn(true)
        ;
        $first
            ->expects($this->once())
            ->method('resolveToReference')
            ->with('id', $relation)
            ->willReturn(null)
        ;
        $first
            ->expects($this->once())
            ->method('resolveToIdentifier')
            ->with(['iri' => '/custom/id'], $relation)
            ->willReturn('id')
        ;

        $second = $this->createMock(RelationResolverInterface::class);
        $second
            ->expects($this->never())
            ->method('supports')
        ;

        $resolver = new RelationResolver([$first, $second]);

        $this->assertNull($resolver->resolveToReference('id', $relation));
        $this->assertSame('id', $resolver->resolveToIdentifier(['iri' => '/custom/id'], $relation));
    }

    public function testSupportsRepeatedLookupWithAGenerator(): void
    {
        $relation = new \stdClass();

        $unsupported = $this->createStub(RelationResolverInterface::class);
        $unsupported
            ->method('supports')
            ->willReturn(false)
        ;

        $supported = $this->createStub(RelationResolverInterface::class);
        $supported
            ->method('supports')
            ->willReturn(true)
        ;
        $supported
            ->method('resolveToReference')
            ->willReturn(['iri' => '/custom/12'])
        ;
        $supported
            ->method('resolveToIdentifier')
            ->willReturn(12)
        ;

        $resolvers = (
            static function () use ($unsupported, $supported): \Generator {
                yield $unsupported;
                yield $supported;
            }
        )();

        $resolver = new RelationResolver($resolvers);

        $this->assertTrue($resolver->supports($relation));
        $this->assertSame(['iri' => '/custom/12'], $resolver->resolveToReference(12, $relation));
        $this->assertSame(12, $resolver->resolveToIdentifier(['iri' => '/custom/12'], $relation));
    }

    public function testKeepsTaggedServicesLazy(): void
    {
        $relation = new \stdClass();

        $first = $this->createStub(RelationResolverInterface::class);
        $first
            ->method('supports')
            ->willReturn(true)
        ;

        $initialized = new class {
            /**
             * @var list<string>
             */
            private array $names = [];

            public function record(string $name): void
            {
                $this->names[] = $name;
            }

            /**
             * @return list<string>
             */
            public function names(): array
            {
                return $this->names;
            }
        };

        $services = $this->createTaggedServices($first, $initialized->record(...));
        $resolver = new RelationResolver($services);

        $this->assertSame([], $initialized->names());
        $this->assertTrue($resolver->supports($relation));
        $this->assertTrue($resolver->supports($relation));
        $this->assertSame(['first', 'first'], $initialized->names());
    }

    public function testLeavesUnsupportedValuesUnchanged(): void
    {
        $relation = new \stdClass();
        $resolver = new RelationResolver([]);

        $this->assertFalse($resolver->supports($relation));
        $this->assertSame('id', $resolver->resolveToReference('id', $relation));
        $this->assertSame(['iri' => '/custom/id'], $resolver->resolveToIdentifier(['iri' => '/custom/id'], $relation));
    }

    public function testSkipsUnsupportedResolvers(): void
    {
        $relation = new \stdClass();

        $unsupported = $this->createMock(RelationResolverInterface::class);
        $unsupported
            ->expects($this->once())
            ->method('supports')
            ->with($relation)
            ->willReturn(false)
        ;
        $unsupported
            ->expects($this->never())
            ->method('resolveToReference')
        ;

        $supported = $this->createMock(RelationResolverInterface::class);
        $supported
            ->expects($this->once())
            ->method('supports')
            ->with($relation)
            ->willReturn(true)
        ;
        $supported
            ->expects($this->once())
            ->method('resolveToReference')
            ->with(7, $relation)
            ->willReturn(['iri' => '/articles/7'])
        ;

        $resolver = new RelationResolver([$unsupported, $supported]);

        $this->assertSame(['iri' => '/articles/7'], $resolver->resolveToReference(7, $relation));
    }

    /**
     * @param \Closure(string): void $record
     *
     * @return iterable<RelationResolverInterface>
     */
    private function createTaggedServices(RelationResolverInterface $first, \Closure $record): iterable
    {
        return new RewindableGenerator(
            static function () use ($first, $record): \Generator {
                $record('first');

                yield $first;

                $record('unused');

                throw new \LogicException('An unused resolver must not be initialized.');
            },
            2,
        );
    }
}
