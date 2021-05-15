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

use Contao\CoreBundle\Twig\Loader\FailTolerantFilesystemLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @internal
 */
class TwigPathsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->migrateSymfonyTwigPaths(
            $container->getDefinition('twig.loader.native_filesystem'),
            $container->getDefinition(FailTolerantFilesystemLoader::class)
        );
    }

    /**
     * Rewires Symfony's "addPath" method calls to our fail tolerant version
     * of a filesystem loader which tolerates missing paths that will occur
     * as soon as a user removes registered directories (e.g. from within the
     * backend) and that would otherwise require the container to be rebuild.
     */
    private function migrateSymfonyTwigPaths(Definition $from, Definition $to): void
    {
        $calls = array_filter(
            $from->getMethodCalls(),
            static function (array $call): bool {
                return 'addPath' === $call[0];
            }
        );

        if (empty($calls)) {
            return;
        }

        $from->removeMethodCall('addPath');

        foreach ($calls as $call) {
            $to->addMethodCall(...$call);
        }
    }
}
