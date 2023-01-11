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

use Contao\CoreBundle\Monolog\SystemLogger;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
class LoggerChannelPass implements CompilerPassInterface
{
    final public const LEGACY_ACTIONS = [
        'access',
        'configuration',
        'cron',
        'email',
        'error',
        'files',
        'forms',
        'general',
    ];

    private array $loggers = [];

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('monolog.logger')) {
            return;
        }

        foreach ($container->getDefinitions() as $id => $definition) {
            if (!\in_array($id, $this->loggers, true) && $this->isContaoChannelLoggerDefinition($definition)) {
                $container
                    ->register("contao._logger.$id", SystemLogger::class)
                    ->setDecoratedService($id)
                    ->setArguments([new Reference("contao._logger.$id.inner"), $this->getContaoActionFromChannel($definition->getArgument(0))])
                ;
            }
        }
    }

    private function getContaoActionFromChannel(string $channel): string
    {
        $action = preg_replace('/^contao\./', '', $channel);

        return $this->transformContaoAction($action);
    }

    private function transformContaoAction(string $action): string
    {
        if (\in_array($action, self::LEGACY_ACTIONS, true)) {
            $action = strtoupper($action);
        }

        return $action;
    }

    private function isContaoChannelLoggerDefinition(Definition $definition): bool
    {
        return $definition instanceof ChildDefinition
            && 'monolog.logger_prototype' === $definition->getParent()
            && str_starts_with($definition->getArgument(0), 'contao.');
    }
}
