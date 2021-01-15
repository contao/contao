<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Contao\CoreBundle\Twig\FailTolerantFilesystemLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
class RewireTwigPathsPass implements CompilerPassInterface
{
    /**
     * Rewires the registered "addPath" method calls to our decorated service.
     */
    public function process(ContainerBuilder $container): void
    {
        $original = $container->getDefinition('twig.loader.native_filesystem');

        $calls = array_filter(
            $original->getMethodCalls(),
            static function (array $call): bool {
                return 'addPath' === $call[0];
            }
        );

        if (empty($calls)) {
            return;
        }

        do {
            $original->removeMethodCall('addPath');
        } while ($original->hasMethodCall('addPath'));

        $decorated = $container->getDefinition(FailTolerantFilesystemLoader::class);

        foreach ($calls as $call) {
            $decorated->addMethodCall(...$call);
        }
    }
}
