<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\Job;

use Contao\CoreBundle\Job\Jobs;
use Contao\CoreBundle\Job\Owner;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class JobsTest extends TestCase
{
    public static function createJobProvider(): \Generator
    {
        yield 'No logged in user' => [false];

        yield 'Logged in back end user' => [true];
    }

    #[DataProvider('createJobProvider')]
    public function testCreateJob(bool $userLoggedIn): void
    {
        $username = 'foobar';
        $userMock = $this->createMock(UserInterface::class);
        $userMock
            ->expects($userLoggedIn ? $this->once() : $this->never())
            ->method('getUserIdentifier')
            ->willReturn($username)
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($userLoggedIn ? $userMock : null)
        ;

        $jobs = $this->getJobs($security);
        $job = $jobs->createJob('job-type');

        $this->assertSame($userLoggedIn ? $username : Owner::SYSTEM, $job->getOwner()->getIdentifier());
    }

    public function testCreateSystemJob(): void
    {
        $jobs = $this->getJobs();
        $job = $jobs->createSystemJob('my-type');

        $this->assertSame(Owner::SYSTEM, $job->getOwner()->getIdentifier());
    }

    public function testCreateUserJobThrowsExceptionIfNoUser(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot create a user job without having a user id.');

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $jobs = $this->getJobs($security);

        $jobs->createUserJob('job-type');
    }

    private function getJobs(Security|null $security = null): Jobs
    {
        $connection = $this->createInMemorySQLiteConnection(
            [
                new Table('tl_job', [
                    new Column('id', Type::getType(Types::INTEGER), ['autoIncrement' => true]),
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
            $connection ?? $this->createMock(Connection::class),
            $security ?? $this->createMock(Security::class),
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
