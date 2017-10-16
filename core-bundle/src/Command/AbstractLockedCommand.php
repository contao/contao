<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;

abstract class AbstractLockedCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = new FlockStore(
            sys_get_temp_dir().'/'.md5($this->getContainer()->getParameter('kernel.project_dir'))
        );

        $factory = new Factory($store);
        $lock = $factory->createLock($this->getName());

        if (!$lock->acquire()) {
            $output->writeln('The command is already running in another process.');

            return 1;
        }

        if (($errorCode = $this->executeLocked($input, $output)) > 0) {
            $lock->release();

            return $errorCode;
        }

        $lock->release();

        return 0;
    }

    /**
     * Executes the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    abstract protected function executeLocked(InputInterface $input, OutputInterface $output);
}
