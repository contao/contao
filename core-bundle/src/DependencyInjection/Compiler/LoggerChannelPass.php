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

/**
 * @internal
 */
class LoggerChannelPass implements CompilerPassInterface
{
    public const LEGACY_ACTIONS = [
        'error',
        'access',
        'general',
        'files',
        'cron',
        'forms',
        'email',
        'configuration',
        'newsletter',
    ];

    private array $loggers = [];

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('monolog.logger')) {
            return;
        }

        if ($container->hasParameter('contao.monolog.default_channels')) {
            foreach ($container->getParameter('contao.monolog.default_channels') as $action) {
                $id = 'monolog.logger.contao.'.$action;

                $logger = clone $container->getDefinition('monolog.logger');
                $logger->replaceArgument(0, 'contao.'.$action);

                $container->setDefinition(
                    $id,
                    (new Definition(SystemLogger::class, [$logger, $this->transformContaoAction($action)]))
                        // Public service for legacy use without dependency injection
                        ->setPublic(true)
                );

                $this->loggers[] = $id;
            }
        }

        foreach ($container->getDefinitions() as $id => $definition) {
            if (!\in_array($id, $this->loggers, true) && $this->isContaoChannelLoggerDefinition($definition)) {
                $container->setDefinition(
                    $id,
                    new Definition(SystemLogger::class, [$definition, $this->getContaoActionFromChannel($definition->getArgument(0))])
                );
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
        return
            $definition instanceof ChildDefinition &&
            'monolog.logger_prototype' === $definition->getParent() &&
            str_starts_with($definition->getArgument(0), 'contao.')
        ;
    }
}
