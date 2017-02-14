<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Database;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Parent class for version update classes.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
abstract class AbstractVersionUpdate
{
    use ContainerAwareTrait;

    /**
     * @var Connection
     */
    protected $connection;

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
     * Checks whether the update should be run.
     *
     * @return bool
     */
    abstract public function shouldBeRun();

    /**
     * Runs the update.
     */
    abstract public function run();
}
