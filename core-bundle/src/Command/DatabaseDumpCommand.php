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

use Contao\CoreBundle\Doctrine\Dumper\DumperException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Dumps the database.
 *
 * @internal
 */
class DatabaseDumpCommand extends AbstractDatabaseCommand
{
    protected static $defaultName = 'contao:database:dump';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('buffer-size', 'b', InputOption::VALUE_OPTIONAL, 'Maximum length of a single SQL statement generated. Requires said amount of RAM. Defaults to "100MB".')
            ->setDescription('Dumps an database to a given target file.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $config = $this->databaseDumper->createDefaultImportConfig();
        $config = $this->handleCommonConfig($input, $config);

        if ($bufferSize = $input->getOption('buffer-size')) {
            $bufferSize = $this->parseBufferSize($bufferSize);
            $config = $config->withBufferSize($bufferSize);
        }

        try {
            $this->databaseDumper->dump($config);
        } catch (DumperException $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $io->success(
            sprintf(
                'Successfully created an SQL dump at "%s" while ignoring following tables: %s.',
                $config->getFilePath(),
                implode(', ', $config->getTablesToIgnore())
            )
        );

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
