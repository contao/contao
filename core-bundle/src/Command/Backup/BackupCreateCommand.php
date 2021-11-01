<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Command\Backup;

use Contao\CoreBundle\Doctrine\Backup\BackupManagerException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
class BackupCreateCommand extends AbstractBackupCommand
{
    protected static $defaultName = 'contao:backup:create';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('buffer-size', 'b', InputOption::VALUE_OPTIONAL, 'Maximum length of a single SQL statement generated. Requires said amount of RAM. Defaults to "100MB".')
            ->setDescription('Creates a new backup.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $config = $this->backupManager->createCreateConfig();
        $config = $this->handleCommonConfig($input, $config);

        if ($bufferSize = $input->getOption('buffer-size')) {
            $bufferSize = $this->parseBufferSize($bufferSize);
            $config = $config->withBufferSize($bufferSize);
        }

        try {
            $this->backupManager->create($config);
        } catch (BackupManagerException $e) {
            if ($this->isJson($input)) {
                $io->writeln(json_encode(['error' => $e->getMessage()]));
            } else {
                $io->error($e->getMessage());
            }

            return 1;
        }

        if ($this->isJson($input)) {
            $io->writeln(json_encode($config->getBackup()->toArray()));

            return 0;
        }

        $io->success(sprintf(
            'Successfully created an SQL dump at "%s".',
            $config->getBackup()->getFilepath(),
        ));

        return 0;
    }

    private function parseBufferSize(string $bufferSize): ?int
    {
        $match = preg_match('/^(\d+)(KB|MB|GB)?$/', $bufferSize, $matches);

        if (false === $match || 0 === $match) {
            throw new \InvalidArgumentException('The buffer size must be an unsigned integer, optionally ending with KB, MB or GB.');
        }
        $bufferSize = (int) $matches[1];
        $bufferFactor = 1;

        switch ($matches[2]) {
            case 'GB':
                $bufferFactor *= 1024;
            // no break
            case 'MB':
                $bufferFactor *= 1024;
            // no break
            case 'KB':
                $bufferFactor *= 1024;
        }

        return $bufferSize * $bufferFactor;
    }
}
