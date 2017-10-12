<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Monolog;

use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Statement;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ContaoTableHandler extends AbstractProcessingHandler implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    private $dbalServiceName = 'doctrine.dbal.default_connection';

    /**
     * @var Statement
     */
    private $statement;

    /**
     * Returns the service name for the database connection.
     *
     * @return string
     */
    public function getDbalServiceName(): string
    {
        return $this->dbalServiceName;
    }

    /**
     * Sets the service name for the database connection.
     *
     * @param string $name
     */
    public function setDbalServiceName(string $name): void
    {
        $this->dbalServiceName = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $record = $this->processRecord($record);

        $record['formatted'] = $this->getFormatter()->format($record);

        if (!isset($record['extra']['contao']) || !($record['extra']['contao'] instanceof ContaoContext)) {
            return false;
        }

        try {
            $this->write($record);
        } catch (\Exception $e) {
            return false;
        }

        $this->executeHook($record['message'], $record['extra']['contao']);

        return false === $this->bubble;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        $this->createStatement();

        /** @var \DateTime $date */
        $date = $record['datetime'];

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->statement->execute([
            'tstamp' => $date->format('U'),
            'text' => StringUtil::specialchars((string) $record['formatted']),
            'source' => (string) $context->getSource(),
            'action' => (string) $context->getAction(),
            'username' => (string) $context->getUsername(),
            'func' => (string) $context->getFunc(),
            'ip' => (string) $context->getIp(),
            'browser' => (string) $context->getBrowser(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFormatter(): LineFormatter
    {
        return new LineFormatter('%message%');
    }

    /**
     * Verifies the database connection and prepares the statement.
     *
     * @throws \RuntimeException
     */
    private function createStatement(): void
    {
        if (null !== $this->statement) {
            return;
        }

        if (null === $this->container || !$this->container->has($this->dbalServiceName)) {
            throw new \RuntimeException('The container has not been injected or the database service is missing');
        }

        $this->statement = $this->container->get($this->dbalServiceName)->prepare('
            INSERT INTO
                tl_log
                    (tstamp, source, action, username, text, func, ip, browser)
                VALUES
                    (:tstamp, :source, :action, :username, :text, :func, :ip, :browser)
        ');
    }

    /**
     * Executes the legacy hook if the Contao framework is booted.
     *
     * @param string        $message
     * @param ContaoContext $context
     */
    private function executeHook(string $message, ContaoContext $context): void
    {
        if (null === $this->container || !$this->container->has('contao.framework')) {
            return;
        }

        $framework = $this->container->get('contao.framework');

        if (!$framework->isInitialized()
            || !isset($GLOBALS['TL_HOOKS']['addLogEntry'])
            || !\is_array($GLOBALS['TL_HOOKS']['addLogEntry'])
        ) {
            return;
        }

        @trigger_error('Using the addLogEntry hook has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

        /** @var System $system */
        $system = $framework->getAdapter(System::class);

        // Must create variables to allow modification-by-reference in hook
        $func = $context->getFunc();
        $action = $context->getAction();

        foreach ($GLOBALS['TL_HOOKS']['addLogEntry'] as $callback) {
            $system->importStatic($callback[0])->{$callback[1]}($message, $func, $action);
        }
    }
}
