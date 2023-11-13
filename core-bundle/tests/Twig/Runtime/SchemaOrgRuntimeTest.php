<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Runtime;

use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\SchemaOrgRuntime;

class SchemaOrgRuntimeTest extends TestCase
{
    public function testAddsSchemaData(): void
    {
        $context = new ResponseContext();
        $manager = new JsonLdManager($context);
        $context->add($manager);

        $accessor = $this->createMock(ResponseContextAccessor::class);
        $accessor
            ->method('getResponseContext')
            ->willReturn($context)
        ;

        (new SchemaOrgRuntime($accessor))->add([
            '@type' => 'ImageObject',
            'identifier' => 'https://assets.url/files/public/foo.jpg',
        ]);

        $graph = $manager->getGraphForSchema(JsonLdManager::SCHEMA_ORG)->toArray();

        $this->assertSame(
            [
                '@context' => 'https://schema.org',
                '@graph' => [[
                    '@type' => 'ImageObject',
                    '@id' => 'https://assets.url/files/public/foo.jpg',
                ]],
            ],
            $graph,
        );
    }

    public function testToleratesMissingResponseContext(): void
    {
        $accessor = $this->createMock(ResponseContextAccessor::class);
        $accessor
            ->expects($this->once())
            ->method('getResponseContext')
            ->willReturn(null)
        ;

        (new SchemaOrgRuntime($accessor))->add(['foo']);
    }

    public function testToleratesMissingJsonLdManager(): void
    {
        $accessor = $this->createMock(ResponseContextAccessor::class);
        $accessor
            ->expects($this->once())
            ->method('getResponseContext')
            ->willReturn(new ResponseContext())
        ;

        (new SchemaOrgRuntime($accessor))->add(['foo']);
    }
}
