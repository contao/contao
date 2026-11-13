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

use Contao\CoreBundle\Crawl\Escargot\Factory;
use Contao\CoreBundle\Doctrine\Schema\EscargotSchemaProvider;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Nyholm\Psr7\Uri;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;
use Terminal42\Escargot\BaseUriCollection;

class EscargotSchemaProviderTest extends TestCase
{
    public function testCrawlQueueCanUseGeneratedSchema(): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $factory = new Factory($connection, $this->mockContaoFramework(), $this->createStub(ContentUrlGenerator::class), new RequestStack());
        $provider = new EscargotSchemaProvider($factory);
        $schema = new Schema();

        $provider->appendToSchema($schema);

        foreach ($schema->toSql($connection->getDatabasePlatform()) as $sql) {
            $connection->executeStatement($sql);
        }

        $queue = $factory->createDoctrineQueue();
        $uri = new Uri('https://contao.org/');
        $jobId = $queue->createJobId(new BaseUriCollection([$uri]));

        $this->assertTrue(Uuid::isValid($jobId));
        $this->assertSame(1, $queue->countPending($jobId));
        $this->assertSame((string) $uri, (string) $queue->getNext($jobId)?->getUri());
        $this->assertTrue($factory->createLazyQueue()->isJobIdValid($jobId));
    }
}
