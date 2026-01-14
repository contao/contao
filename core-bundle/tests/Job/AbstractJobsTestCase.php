<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\Job;

use Contao\BackendUser;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Job\Jobs;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractJobsTestCase extends ContaoTestCase
{
    protected VirtualFilesystemInterface $vfs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vfs = new VirtualFilesystem(
            (new MountManager())->mount(new InMemoryFilesystemAdapter()),
            $this->createStub(DbafsManager::class),
        );
    }

    protected function mockSecurity(int|null $userId = null): Security&MockObject
    {
        $userMock = $this->createClassWithPropertiesStub(BackendUser::class, ['id' => $userId]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($userId ? $userMock : null)
        ;

        return $security;
    }

    protected function getJobs(Security|null $security = null, ClockInterface $clock = new NativeClock(), RouterInterface|null $router = null, MessageBusInterface|null $messageBus = null): Jobs
    {
        $connection = $this->createInMemorySQLiteConnection(
            [
                new Table('tl_job', [
                    new Column('id', Type::getType(Types::INTEGER), ['autoIncrement' => true]),
                    new Column('createdAt', Type::getType(Types::INTEGER)),
                    new Column('tstamp', Type::getType(Types::INTEGER)),
                    new Column('pid', Type::getType(Types::INTEGER), ['default' => 0]),
                    new Column('type', Type::getType(Types::STRING)),
                    new Column('uuid', Type::getType(Types::STRING)),
                    new Column('owner', Type::getType(Types::STRING)),
                    new Column('status', Type::getType(Types::STRING)),
                    new Column('public', Type::getType(Types::BOOLEAN)),
                    new Column('jobData', Type::getType(Types::TEXT), ['notnull' => false]),
                ]),
            ],
        );

        return new Jobs(
            $connection,
            $security ?? $this->createStub(Security::class),
            $this->vfs,
            $router ?? $this->createStub(RouterInterface::class),
            $messageBus ?? $this->createStub(MessageBusInterface::class),
            $clock,
        );
    }

    /**
     * @param array<Table> $tables
     */
    private function createInMemorySQLiteConnection(array $tables): Connection
    {
        $dsnParser = new DsnParser();
        $connectionParams = $dsnParser->parse('pdo-sqlite:///:memory:');

        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        try {
            $connection = DriverManager::getConnection($connectionParams, $configuration);

            foreach ($tables as $table) {
                $connection->createSchemaManager()->createTable($table);
            }
        } catch (\Exception) {
            $this->markTestSkipped('This test requires SQLite to be executed properly.');
        }

        return $connection;
    }
}
