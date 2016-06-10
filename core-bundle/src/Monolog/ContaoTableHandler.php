<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Monolog;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Statement;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * ContaoLogHandler
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoTableHandler extends AbstractProcessingHandler
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
     * Gets the service name for the DBAL database connection.
     *
     * @return string
     */
    public function getDbalServiceName()
    {
        return $this->dbalServiceName;
    }

    /**
     * Sets the service name for the DBAL database connection.
     *
     * @param string $name
     */
    public function setDbalServiceName($name)
    {
        $this->dbalServiceName = $name;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $record)
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $record = $this->processRecord($record);

        $record['formatted'] = $this->getFormatter()->format($record);

        if (!isset($record['extra']['contao']) || !$record['extra']['contao'] instanceof ContaoContext) {
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
     * @inheritdoc
     *
     * @throws DBALException
     */
    protected function write(array $record)
    {
        $this->createStatement();

        /** @var \DateTime $date */
        $date = $record['datetime'];

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->statement->execute(
            [
                'tstamp'   => $date->format('U'),
                'text'     => specialchars((string) $record['formatted']),
                'source'   => (string) $context->getSource(),
                'action'   => (string) $context->getAction(),
                'username' => (string) $context->getUsername(),
                'func'     => (string) $context->getFunc(),
                'ip'       => (string) $context->getIp(),
                'browser'  => (string) $context->getBrowser(),
            ]
        );
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultFormatter()
    {
        return new LineFormatter('%message%');
    }

    /**
     * Verifies database connection and prepares the statement.
     *
     * @throws \RuntimeException if the container has not been injected or DBAL service is missing
     * @throws DBALException
     */
    private function createStatement()
    {
        if (null !== $this->statement) {
            return;
        }

        if (null === $this->container || !$this->container->has($this->dbalServiceName)) {
            throw new \RuntimeException('Cannot create database statement.');
        }

        $this->statement = $this->container->get($this->dbalServiceName)->prepare('
            INSERT INTO tl_log (tstamp, source, action, username, text, func, ip, browser)
            VALUES (:tstamp, :source, :action, :username, :text, :func, :ip, :browser)
        ');
    }

    /**
     * Executes the legacy hook if the Contao framework is booted.
     *
     * @param string        $message
     * @param ContaoContext $context
     */
    private function executeHook($message, ContaoContext $context)
    {
        if (null === $this->container || !$this->container->has('contao.framework')) {
            return;
        }

        $framework = $this->container->get('contao.framework');

        // HOOK: allow to add custom loggers
        if (!$framework->isInitialized()
            || !isset($GLOBALS['TL_HOOKS']['addLogEntry'])
            || !is_array($GLOBALS['TL_HOOKS']['addLogEntry'])
        ) {
            return;
        }

        trigger_error(
            "\$GLOBALS['TL_HOOKS']['addLogEntry'] is deprecated in Contao 4.2 and will be removed in Contao 5.",
            E_USER_DEPRECATED
        );

        /** @var \Contao\System $system */
        $system = $framework->getAdapter('Contao\System');

        // Must create variable to allow modification-by-reference in hook
        $func = $context->getFunc();
        $action = $context->getAction();

        foreach ($GLOBALS['TL_HOOKS']['addLogEntry'] as $callback) {
            $system->importStatic($callback[0])->{$callback[1]}(
                $message,
                $func,
                $action
            );
        }
    }
}
