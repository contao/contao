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

use PHPUnit\Event\Application\Started;
use PHPUnit\Event\Application\StartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class WarnXdebugPhpunitExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(
            new class($this) implements StartedSubscriber {
                public function __construct(private readonly WarnXdebugPhpunitExtension $extension)
                {
                }

                public function notify(Started $event): void
                {
                    $this->extension->executeBeforeFirstTest();
                }
            },
        );
    }

    public function executeBeforeFirstTest(): void
    {
        if (\is_callable('xdebug_info') && [] !== xdebug_info('mode') && ['off'] !== xdebug_info('mode')) {
            fwrite(STDERR, "XDebug is enabled, consider disabling it to speed up the unit tests.\n\n");
        }
    }
}
