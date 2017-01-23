<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Cache;

use Contao\CoreBundle\Cache\ContaoCacheWarmer;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Test\TestCase;
use Doctrine\DBAL\Connection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the ContaoCacheWarmer class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoCacheWarmerTest extends TestCase
{
    /**
     * @var ContaoCacheWarmer
     */
    private $warmer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMock('Doctrine\DBAL\Connection', [], [], '', false);

        $this->warmer = new ContaoCacheWarmer(
            new Filesystem(),
            new ResourceFinder($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao'),
            new FileLocator($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao'),
            $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao',
            $connection,
            $this->mockContaoFramework()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove($this->getCacheDir().'/contao');
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Cache\ContaoCacheWarmer', $this->warmer);
    }

    /**
     * Tests the warmUp() method.
     */
    public function testWarmUp()
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMock(
            'Doctrine\DBAL\Connection',
            ['prepare', 'execute', 'fetch', 'query'],
            [],
            '',
            false
        );

        $connection
            ->expects($this->any())
            ->method('prepare')
            ->willReturn($connection)
        ;

        $class1 = new \stdClass();
        $class1->language = 'en-US';

        $class2 = new \stdClass();
        $class2->language = 'en';

        $connection
            ->expects($this->exactly(3))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls($class1, $class2, false)
        ;

        $warmer = new ContaoCacheWarmer(
            new Filesystem(),
            new ResourceFinder($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao'),
            new FileLocator($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao'),
            $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao',
            $connection,
            $this->mockContaoFramework()
        );

        $warmer->warmUp($this->getCacheDir());

        $this->assertFileExists($this->getCacheDir().'/contao');
        $this->assertFileExists($this->getCacheDir().'/contao/config');
        $this->assertFileExists($this->getCacheDir().'/contao/config/autoload.php');
        $this->assertFileExists($this->getCacheDir().'/contao/config/config.php');
        $this->assertFileExists($this->getCacheDir().'/contao/config/templates.php');
        $this->assertFileExists($this->getCacheDir().'/contao/dca');
        $this->assertFileExists($this->getCacheDir().'/contao/dca/tl_test.php');
        $this->assertFileExists($this->getCacheDir().'/contao/languages');
        $this->assertFileExists($this->getCacheDir().'/contao/languages/en');
        $this->assertFileExists($this->getCacheDir().'/contao/languages/en/default.php');
        $this->assertFileExists($this->getCacheDir().'/contao/sql');
        $this->assertFileExists($this->getCacheDir().'/contao/sql/tl_test.php');

        $this->assertContains(
            "\$GLOBALS['TL_TEST'] = true;",
            file_get_contents($this->getCacheDir().'/contao/config/config.php')
        );

        $this->assertContains(
            sprintf(
                "'dummy' => '%s/vendor/contao/test-bundle/Resources/contao/templates'",
                strtr($this->getRootDir(), '\\', '/')
            ),
            file_get_contents($this->getCacheDir().'/contao/config/templates.php')
        );

        $this->assertContains(
            "\$GLOBALS['TL_DCA']['tl_test'] = [\n",
            file_get_contents($this->getCacheDir().'/contao/dca/tl_test.php')
        );

        $this->assertContains(
            "\$GLOBALS['TL_LANG']['MSC']['first']",
            file_get_contents($this->getCacheDir().'/contao/languages/en/default.php')
        );

        $this->assertContains(
            "\$this->arrFields = array (\n  'id' => 'int(10) unsigned NOT NULL auto_increment',\n);",
            file_get_contents($this->getCacheDir().'/contao/sql/tl_test.php')
        );
    }

    /**
     * Tests the isOptional() method.
     */
    public function testIsOptional()
    {
        $this->assertTrue($this->warmer->isOptional());
    }

    /**
     * Tests that no cache is created if the installation is incomplete.
     */
    public function testIncompleteInstallation()
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMock('Doctrine\DBAL\Connection', ['query'], [], '', false);

        $connection
            ->expects($this->any())
            ->method('query')
            ->willThrowException(new \Exception())
        ;

        $framework = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\ContaoFramework')
            ->setMethods(['initialize'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $warmer = new ContaoCacheWarmer(
            new Filesystem(),
            new ResourceFinder($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao'),
            new FileLocator($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao'),
            $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao',
            $connection,
            $framework
        );

        $warmer->warmUp($this->getCacheDir());

        $this->assertFileNotExists($this->getCacheDir().'/contao');
    }
}
