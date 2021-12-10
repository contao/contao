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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

/**
 * @deprecated Deprecated since Contao 4.7, to be removed in Contao 5.0; use
 *             the Symfony Lock component instead
 */
abstract class AbstractLockedCommand extends Command implements ContainerAwareInterface
{
    private ?ContainerInterface $container = null;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    protected function getContainer(): ContainerInterface
    {
        if (null === $this->container) {
            throw new \LogicException('The container needs to be set before it can be retrieved.');
        }

        return $this->container;
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        trigger_deprecation('contao/core-bundle', '4.7', 'Using the "AbstractLockedCommand" has been deprecated and will no longer work in Contao 5.0. Use the Symfony Lock component instead.');

        $store = new FlockStore($this->getTempDir());
        $factory = new LockFactory($store);
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
        $tmpDir = Path::join(sys_get_temp_dir(), md5((string) $container->getParameter('kernel.project_dir')));

        if (!is_dir($tmpDir)) {
            $container->get('filesystem')->mkdir($tmpDir);
        }

        return $tmpDir;
    }
}
