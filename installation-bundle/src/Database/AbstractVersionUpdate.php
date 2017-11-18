<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Database;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractVersionUpdate implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * Constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Checks if there is a message.
     *
     * @return bool
     */
    public function hasMessage(): bool
    {
        return !empty($this->messages);
    }

    /**
     * Returns the message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return implode($this->messages);
    }

    /**
     * Checks whether the update should be run.
     *
     * @return bool
     */
    abstract public function shouldBeRun(): bool;

    /**
     * Runs the update.
     */
    abstract public function run();

    /**
     * Adds a message.
     *
     * @param string $message
     */
    protected function addMessage(string $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * Prepends a message.
     *
     * @param string $message
     */
    protected function prependMessage(string $message): void
    {
        array_unshift($this->messages, $message);
    }
}
