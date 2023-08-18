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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
class RewireTwigPathsPass implements CompilerPassInterface
{
    /**
     * Rewires Symfony's "addPath" method calls to our fail tolerant version
     * of a filesystem loader which tolerates missing paths. Those will occur
     * as soon as a user removes registered directories (e.g. from within the
     * backend) and that would otherwise require the container to be rebuilt.
     */
    public function process(ContainerBuilder $container): void
    {
        $original = $container->getDefinition('twig.loader.native_filesystem');

        $calls = array_filter(
            $original->getMethodCalls(),
            static fn (array $call): bool => 'addPath' === $call[0]
        );

        if (!$calls) {
            return;
        }

        $original->removeMethodCall('addPath');

        $replaced = $container->getDefinition('contao.twig.fail_tolerant_filesystem_loader');

        foreach ($calls as $call) {
            $replaced->addMethodCall(...$call);
        }
    }
}
