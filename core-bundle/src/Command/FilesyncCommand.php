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

use Contao\CoreBundle\Filesystem\ChangeSet;
use Contao\CoreBundle\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Synchronizes the file system with the database.
 *
 * @internal
 */
class FilesyncCommand extends Command
{
    protected static $defaultName = 'contao:filesync';

    private Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Synchronizes the file system with the database.')
            ->addArgument('dir', InputArgument::IS_ARRAY, 'Optional subdirectory for partial synchronization.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Synchronizing…');

        $time = microtime(true);
        $changeSet = $this->filesystem->sync(...$input->getArgument('dir'));
        $timeTotal = round(microtime(true) - $time, 2);

        $this->renderStats($changeSet, $output);
        (new SymfonyStyle($input, $output))->success("Synchronization complete in {$timeTotal}s.");

        return 0;
    }

    private function renderStats(ChangeSet $changeSet, OutputInterface $output): void
    {
        if ($changeSet->isEmpty()) {
            $output->writeln('No changes.');

            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Action', 'Resource / Change']);

        $output->getFormatter()->setStyle('hash', new OutputFormatterStyle('yellow'));
        $output->getFormatter()->setStyle('newpath', new OutputFormatterStyle('green'));
        $output->getFormatter()->setStyle('oldpath', new OutputFormatterStyle('red'));

        foreach ($changeSet->getItemsToCreate() as $attributes) {
            $table->addRow([
                'add',
                "<newpath>{$attributes[ChangeSet::ATTR_PATH]}</newpath> (new hash: <hash>{$attributes[ChangeSet::ATTR_HASH]}</hash>)",
            ]);
        }

        foreach ($changeSet->getItemsToUpdate() as $path => $attributes) {
            if (null !== ($newPath = $attributes[ChangeSet::ATTR_PATH] ?? null)) {
                $change = "$path ⟶ <newpath>$newPath</newpath>";
                $action = 'move';
            } else {
                $change = $path;
                $action = 'update';
            }

            if (null !== ($hash = $attributes[ChangeSet::ATTR_HASH] ?? null)) {
                $change .= " (updated hash: <hash>$hash</hash>)";
            }

            $table->addRow([$action, $change]);
        }

        foreach ($changeSet->getItemsToDelete() as $path) {
            $table->addRow(['delete', "<oldpath>$path</oldpath>"]);
        }

        $table->render();

        $output->writeln(
            sprintf(
                ' Total items added: %s | updated/moved: %s | deleted: %s',
                \count($changeSet->getItemsToCreate()),
                \count($changeSet->getItemsToUpdate()),
                \count($changeSet->getItemsToDelete())
            )
        );
    }
}
