<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\Config;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Database;
use Contao\Database\Result;
use Contao\Database\Statement;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\Model;
use Contao\Model\Registry;
use Contao\Module;
use Contao\PageModel;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Filesystem\Filesystem;

class ModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->method('introspectSchema')
            ->willReturn(new Schema())
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('quoteIdentifier')
            ->willReturnArgument(0)
        ;

        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('database_connection', $connection);
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));
        $container->setParameter('contao.resources_paths', $this->getTempDir());
        $container->setParameter('kernel.cache_dir', $this->getTempDir().'/var/cache');

        (new Filesystem())->mkdir($this->getTempDir().'/languages/en');
        (new Filesystem())->dumpFile($this->getTempDir().'/var/cache/contao/sql/tl_page.php', '<?php $GLOBALS["TL_DCA"]["tl_page"] = [];');

        System::setContainer($container);

        $GLOBALS['TL_MODELS']['tl_page'] = PageModel::class;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MODELS'], $GLOBALS['TL_LANG'], $GLOBALS['TL_MIME'], $GLOBALS['TL_DCA']);

        $this->resetStaticProperties([Registry::class, DcaExtractor::class, DcaLoader::class, Database::class, Model::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testGetPublishedSubpagesWithoutGuestsByPid(): void
    {
        $databaseResultFirstQuery = [
            ['id' => 1, 'hasSubpages' => 0],
            ['id' => 2, 'hasSubpages' => 1],
            ['id' => 3, 'hasSubpages' => 1],
        ];

        $databaseResultSecondQuery = [
            ['id' => 1, 'alias' => 'alias1'],
            ['id' => 2, 'alias' => 'alias2'],
            ['id' => 3, 'alias' => 'alias3'],
        ];

        $statement = $this->createMock(Statement::class);
        $statement
            ->method('execute')
            ->willReturnOnConsecutiveCalls(new Result($databaseResultFirstQuery, ''), new Result($databaseResultSecondQuery, ''))
        ;

        $database = $this->createMock(Database::class);
        $database
            ->method('prepare')
            ->willReturn($statement)
        ;

        $this->mockDatabase($database);

        $moduleInstance = new class() extends Module {
            public function __construct()
            {
            }

            protected function compile(): void
            {
            }

            public function execute(): array|null
            {
                return self::getPublishedSubpagesByPid(1);
            }
        };

        $pages = $moduleInstance->execute();

        $this->assertIsArray($pages);
        $this->assertCount(3, $pages);

        $this->assertSame($databaseResultSecondQuery[0]['id'], $pages[0]['page']->id);
        $this->assertSame($databaseResultSecondQuery[0]['alias'], $pages[0]['page']->alias);
        $this->assertFalse($pages[0]['hasSubpages']);
        $this->assertSame($databaseResultSecondQuery[1]['id'], $pages[1]['page']->id);
        $this->assertSame($databaseResultSecondQuery[1]['alias'], $pages[1]['page']->alias);
        $this->assertTrue($pages[1]['hasSubpages']);
        $this->assertSame($databaseResultSecondQuery[2]['id'], $pages[2]['page']->id);
        $this->assertSame($databaseResultSecondQuery[2]['alias'], $pages[2]['page']->alias);
        $this->assertTrue($pages[2]['hasSubpages']);

        // Get from model registry
        $page2 = PageModel::findByPk(2);

        $this->assertSame($databaseResultSecondQuery[1]['id'], $page2->id);
        $this->assertSame($databaseResultSecondQuery[1]['alias'], $page2->alias);
        $this->assertNull($page2->hasSubpages, 'The hasSubpages values should not be set in the model registry as it is contains generated data based on the query');
    }

    private function mockDatabase(Database $database): void
    {
        $property = (new \ReflectionClass($database))->getProperty('objInstance');
        $property->setValue(null, $database);

        $this->assertSame($database, Database::getInstance());
    }
}
