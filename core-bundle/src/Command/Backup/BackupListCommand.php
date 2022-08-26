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

use Contao\CoreBundle\Doctrine\Backup\Backup;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
class BackupListCommand extends AbstractBackupCommand
{
    protected static $defaultName = 'contao:backup:list';
    protected static $defaultDescription = 'Lists the existing database backups.';

    public static function getFormattedTimeZoneOffset(\DateTimeZone $timeZone): string
    {
        $offset = $timeZone->getOffset(new \DateTime('now', new \DateTimeZone('UTC'))) / 3600;
        $formatted = str_pad(str_replace(['.', '-', '+'], [':', '', ''], sprintf('%05.2F', $offset)), 5, '0', STR_PAD_LEFT);

        return ($offset >= 0 ? '+' : '-').$formatted;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->isJson($input)) {
            $io->writeln($this->formatForJson($this->backupManager->listBackups()));

            return 0;
        }

        $timeZone = new \DateTimeZone(date_default_timezone_get());

        $io->table(
            [sprintf('Created (%s)', self::getFormattedTimeZoneOffset($timeZone)), 'Size', 'Name'],
            $this->formatForTable($this->backupManager->listBackups(), $timeZone)
        );

        return 0;
    }

    /**
     * @param array<Backup> $backups
     */
    private function formatForTable(array $backups, \DateTimeZone $timeZone): array
    {
        $formatted = [];

        foreach ($backups as $backup) {
            // TODO: Change this to \DateTime::createFromInterface($backup->getCreatedAt()) as soon as we require PHP >=8.0
            $localeDateTime = new \DateTime('@'.$backup->getCreatedAt()->getTimestamp(), $backup->getCreatedAt()->getTimezone());
            $localeDateTime->setTimezone($timeZone);

            $formatted[] = [
                $localeDateTime->format('Y-m-d H:i:s'),
                $this->getHumanReadableSize($backup),
                $backup->getFilename(),
            ];
        }

        return $formatted;
    }

    /**
     * @param array<Backup> $backups
     */
    private function formatForJson(array $backups): string
    {
        $json = [];

        foreach ($backups as $backup) {
            $json[] = $backup->toArray();
        }

        return json_encode($json);
    }

    /**
     * @todo Might want to replace this with the successor of System::getReadableSize() once this is a proper service
     */
    private function getHumanReadableSize(Backup $backup): string
    {
        if (0 === $backup->getSize()) {
            return '0 B';
        }

        $base = log($backup->getSize()) / log(1024);
        $suffix = ['B', 'KiB', 'MiB', 'GiB', 'TiB'][(int) floor($base)];

        return round(1024 ** ($base - floor($base)), 2).' '.$suffix;
    }
}
