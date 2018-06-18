<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Api\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class SetAccesskeyCommand extends Command
{
    /**
     * @var string
     */
    private $projectDir;

    /**
     * @param string $projectDir
     */
    public function __construct(string $projectDir)
    {
        parent::__construct();

        $this->projectDir = $projectDir;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('access-key:set')
            ->setDescription('Sets the debug access key.')
            ->addArgument('value', InputArgument::REQUIRED, 'The access key')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $fs = new Filesystem();
        $path = $this->projectDir.'/.env';
        $content = '';

        if ($fs->exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES);

            if (false === $lines) {
                throw new \RuntimeException(sprintf('Could not read "%s" file.', $path));
            }

            foreach ($lines as $line) {
                if (0 === strncmp($line, 'APP_DEV_ACCESSKEY=', 18)) {
                    continue;
                }

                $content .= $line."\n";
            }
        }

        $content .= 'APP_DEV_ACCESSKEY='.escapeshellarg($input->getArgument('value'))."\n";

        $fs->dumpFile($path, $content);
    }
}
