<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;
use Webmozart\PathUtil\Path;

/**
 * @deprecated Deprecated since Contao 4.7, to be removed in Contao 5.0; use
 *             the Symfony Lock component instead
 */
abstract class AbstractLockedCommand extends ContainerAwareCommand
{
    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        trigger_deprecation('contao/core-bundle', '4.7', 'Using the "AbstractLockedCommand" has been deprecated and will no longer work in Contao 5.0. Use the Symfony Lock component instead.');

        $store = new FlockStore($this->getTempDir());
        $factory = new Factory($store);
        $lock = $factory->createLock($this->getName());

        if (!$lock->acquire()) {
            $output->writeln('The command is already running in another process.');

            return 1;
        }

        $errorCode = $this->executeLocked($input, $output);
        $lock->release();

        if ($errorCode > 0) {
            return $errorCode;
        }

        return 0;
    }

    /**
     * @return int
     */
    abstract protected function executeLocked(InputInterface $input, OutputInterface $output);

    /**
     * Creates an installation specific folder in the temporary directory and returns its path.
     */
    private function getTempDir(): string
    {
        $container = $this->getContainer();
        $tmpDir = Path::join(sys_get_temp_dir(), md5($container->getParameter('kernel.project_dir')));

        if (!is_dir($tmpDir)) {
            $container->get('filesystem')->mkdir($tmpDir);
        }

        return $tmpDir;
    }
}
