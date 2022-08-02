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
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaLoader;
use Contao\System;
use Symfony\Component\Filesystem\Filesystem;

class DcaLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_MIME']);

        $this->resetStaticProperties([System::class, Config::class, DcaLoader::class]);

        parent::tearDown();

        (new Filesystem())->remove($this->getTempDir());
    }

    public function testThrowsTheSameExceptionWhenLoadingTwice(): void
    {
        // Loading this file twice would cause a "Cannot declare class …,
        // because the name is already in use" error
        (new Filesystem())->dumpFile(
            $this->getTempDir().'/var/cache/contao/dca/tl_foo.php',
            sprintf(
                <<<'EOD'
                    <?php
                        class tl_foo_%s {}
                        throw new Exception('From tl_foo');
                    EOD,
                bin2hex(random_bytes(16)),
            ),
        );

        $firstException = $secondException = null;

        try {
            (new DcaLoader('tl_foo'))->load();
        } catch (\Throwable $exception) {
            $firstException = $exception;
        }

        try {
            (new DcaLoader('tl_foo'))->load();
        } catch (\Throwable $exception) {
            $secondException = $exception;
        }

        $this->assertSame($firstException, $secondException);
    }
}
