<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\TestCase;

use PHPUnit\Event\TestRunner\Finished;
use PHPUnit\Event\TestRunner\FinishedSubscriber;
use PHPUnit\Event\TestRunner\Started;
use PHPUnit\Event\TestRunner\StartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Symfony\Component\Filesystem\Filesystem;

class ClearCachePhpunitExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(
            new class($this) implements StartedSubscriber {
                public function __construct(private readonly ClearCachePhpunitExtension $extension)
                {
                }

                public function notify(Started $event): void
                {
                    $this->extension->clear();
                }
            },
        );

        $facade->registerSubscriber(
            new class($this) implements FinishedSubscriber {
                public function __construct(private readonly ClearCachePhpunitExtension $extension)
                {
                }

                public function notify(Finished $event): void
                {
                    $this->extension->clear();
                }
            },
        );
    }

    public function clear(): void
    {
        (new Filesystem())->remove([
            __DIR__.'/../var/cache',
            __DIR__.'/../../var/cache',
            __DIR__.'/../../core-bundle/var/cache',
        ]);
    }
}
