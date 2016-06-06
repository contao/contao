<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Monolog;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Statement;
use Monolog\Handler\AbstractHandler;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * ContaoLogHandler
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoTableHandler extends AbstractHandler
{
    use ContainerAwareTrait;

    /**
     * @var callable
     */
    private $processor;

    /**
     * @var Statement
     */
    private $statement;

    /**
     * Constructor.
     *
     * @param callable $processor
     * @param int      $level
     * @param bool     $bubble
     */
    public function __construct(callable $processor, $level = Logger::DEBUG, $bubble = false)
    {
        parent::__construct($level, $bubble);

        $this->processor = $processor;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $record)
    {
        try {
            $record = call_user_func($this->processor, $record);

            if (!isset($record['extra']['contao']) || !$record['extra']['contao'] instanceof ContaoContext) {
                return false;
            }

            if (!$this->canWriteToDb()) {
                return false;
            }

            /** @var \DateTime $date */
            $date = $record['datetime'];

            /** @var ContaoContext $context */
            $context = $record['extra']['contao'];

            $this->statement->execute(
                [
                    'tstamp'   => $date->format('U'),
                    'text'     => specialchars($record['message']),
                    'source'   => (string) $context->getSource(),
                    'action'   => (string) $context->getAction(),
                    'username' => (string) $context->getUsername(),
                    'func'     => (string) $context->getFunc(),
                    'ip'       => (string) $context->getIp(),
                    'browser'  => (string) $context->getBrowser(),
                ]
            );
        } catch (DBALException $e) {
            return false;
        }

        $this->executeHook($record['message'], $context);

        return false === $this->bubble;
    }

    /**
     * @return bool
     */
    private function canWriteToDb()
    {
        if (null !== $this->statement) {
            return true;
        }

        if (null === $this->container || !$this->container->has('doctrine.dbal.default_connection')) {
            return false;
        }

        try {
            $this->statement = $this->container->get('doctrine.dbal.default_connection')->prepare('
                INSERT INTO tl_log (tstamp, source, action, username, text, func, ip, browser)
                VALUES (:tstamp, :source, :action, :username, :text, :func, :ip, :browser)
            ');
        } catch (DBALException $e) {
            // Ignore if table does not exist
            return false;
        }

        return true;
    }

    /**
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
