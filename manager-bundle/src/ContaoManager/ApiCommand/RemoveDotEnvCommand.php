<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\ContaoManager\ApiCommand;

use Contao\ManagerBundle\Api\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class RemoveDotEnvCommand extends Command
{
    /**
     * @var string
     */
    private $projectDir;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        parent::__construct();

        $this->projectDir = $application->getProjectDir();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('dot-env:remove')
            ->setDescription('Removes a parameter from the .env file.')
            ->addArgument('key', InputArgument::REQUIRED, 'The variable name')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $fs = new Filesystem();
        $path = $this->projectDir.'/.env';

        if (!$fs->exists($path)) {
            return;
        }

        $content = '';
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if (false === $lines) {
            throw new \RuntimeException(sprintf('Could not read "%s" file.', $path));
        }

        $key = $input->getArgument('key');

        foreach ($lines as $line) {
            if (0 === strpos($line, $key.'=')) {
                continue;
            }

            $content .= $line."\n";
        }

        if (empty($content)) {
            $fs->remove($path);
        } else {
            $fs->dumpFile($path, $content);
        }
    }
}
