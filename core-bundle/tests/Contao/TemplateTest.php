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

use Contao\BackendTemplate;
use Contao\Config;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendTemplate;
use Contao\System;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;

class TemplateTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $fs = new Filesystem();
        $fs->mkdir($this->getFixturesDir().'/templates');

        System::setContainer($this->mockContainer($this->getFixturesDir()));
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $fs = new Filesystem();
        $fs->remove($this->getFixturesDir().'/templates');
    }

    public function testReplacesTheVariables(): void
    {
        Config::set('debugMode', false);

        file_put_contents(
            $this->getFixturesDir().'/templates/test_template.html5',
            '<?= $this->value ?>'
        );

        $template = new BackendTemplate('test_template');
        $template->setData(['value' => 'test']);

        $obLevel = ob_get_level();
        $this->assertSame('test', $template->parse());
        $this->assertSame($obLevel, ob_get_level());
    }

    public function testHandlesExceptions(): void
    {
        file_put_contents(
            $this->getFixturesDir().'/templates/test_template.html5',
            'test<?php throw new Exception ?>'
        );

        $template = new BackendTemplate('test_template');
        $obLevel = ob_get_level();

        ob_start();

        try {
            $template->parse();
            $this->fail('Parse should throw an exception');
        } catch (\Exception $e) {
            // Ignore
        }

        $this->assertSame('', ob_get_clean());
        $this->assertSame($obLevel, ob_get_level());
    }

    public function testHandlesExceptionsInsideBlocks(): void
    {
        file_put_contents($this->getFixturesDir().'/templates/test_template.html5', <<<'EOF'
<?php
    echo 'test1';
    $this->block('a');
    echo 'test2';
    $this->block('b');
    echo 'test3';
    $this->block('c');
    echo 'test4';
    throw new Exception;
EOF
        );

        $template = new BackendTemplate('test_template');
        $obLevel = ob_get_level();

        ob_start();

        try {
            $template->parse();
            $this->fail('Parse should throw an exception');
        } catch (\Exception $e) {
            // Ignore
        }

        $this->assertSame('', ob_get_clean());
        $this->assertSame($obLevel, ob_get_level());
    }

    public function testHandlesExceptionsInParentTemplate(): void
    {
        file_put_contents($this->getFixturesDir().'/templates/test_parent.html5', <<<'EOF'
<?php
    echo 'test1';
    $this->block('a');
    echo 'test2';
    $this->endblock();
    $this->block('b');
    echo 'test3';
    $this->endblock();
    $this->block('c');
    echo 'test4';
    $this->block('d');
    echo 'test5';
    $this->block('e');
    echo 'test6';
    throw new Exception;
EOF
        );

        file_put_contents($this->getFixturesDir().'/templates/test_template.html5', <<<'EOF'
<?php
    echo 'test1';
    $this->extend('test_parent');
    echo 'test2';
    $this->block('a');
    echo 'test3';
    $this->parent();
    echo 'test4';
    $this->endblock('a');
    echo 'test5';
    $this->block('b');
    echo 'test6';
    $this->endblock('b');
    echo 'test7';
EOF
        );

        $template = new BackendTemplate('test_template');
        $obLevel = ob_get_level();

        ob_start();

        try {
            $template->parse();
            $this->fail('Parse should throw an exception');
        } catch (\Exception $e) {
            // Ignore
        }

        $this->assertSame('', ob_get_clean());
        $this->assertSame($obLevel, ob_get_level());
    }

    public function testParsesNestedBlocks(): void
    {
        file_put_contents($this->getFixturesDir().'/templates/test_parent.html5', '');

        file_put_contents($this->getFixturesDir().'/templates/test_template.html5', <<<'EOF'
<?php
    echo 'test1';
    $this->extend('test_parent');
    echo 'test2';
    $this->block('a');
    echo 'test3';
    $this->block('b');
    echo 'test4';
    $this->endblock('b');
    echo 'test5';
    $this->endblock('a');
    echo 'test6';
EOF
        );

        $template = new BackendTemplate('test_template');
        $obLevel = ob_get_level();

        ob_start();

        try {
            $template->parse();
            $this->fail('Parse should throw an exception');
        } catch (\Exception $e) {
            // Ignore
        }

        $this->assertSame('', ob_get_clean());
        $this->assertSame($obLevel, ob_get_level());
    }

    public function testLoadsTheAssetsPackages(): void
    {
        $packages = $this->createMock(Packages::class);
        $packages
            ->expects($this->once())
            ->method('getUrl')
            ->with('/path/to/asset', 'package_name')
            ->willReturnArgument(0)
        ;

        $container = $this->mockContainer();
        $container->set('assets.packages', $packages);

        System::setContainer($container);

        $template = new FrontendTemplate();
        $template->asset('/path/to/asset', 'package_name');
    }
}
