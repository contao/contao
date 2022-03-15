<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Doctrine\Schema;

use Contao\CoreBundle\Doctrine\Schema\SchemaProvider;
use Contao\CoreBundle\Tests\Doctrine\DoctrineTestCase;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\DocParser;

class SchemaProviderTest extends DoctrineTestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([[AnnotationRegistry::class, ['failedToAutoload']], DocParser::class]);

        parent::tearDown();
    }

    public function testCreateSchemaGetsSchemaFromMetadata(): void
    {
        $schemaProvider = new SchemaProvider($this->getTestEntityManager());
        $schema = $schemaProvider->createSchema();

        $this->assertTrue($schema->hasTable('foo'));
    }
}
