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

use Contao\CoreBundle\Filesystem\Dbafs\ChangeSet;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'contao:filesync',
    description: 'Synchronizes the registered DBAFS with the virtual filesystem.'
)]
class FilesyncCommand extends Command
{
    public function __construct(private DbafsManager $dbafsManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('paths', InputArgument::IS_ARRAY, 'Optional path(s) for partial synchronization.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Synchronizing…');

        $time = microtime(true);
        $changeSet = $this->dbafsManager->sync(...$input->getArgument('paths'));
        $timeTotal = round(microtime(true) - $time, 2);

        $this->renderStats($changeSet, $output);

        (new SymfonyStyle($input, $output))->success("Synchronization complete in {$timeTotal}s.");

        return Command::SUCCESS;
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
                $change = "$path → <newpath>$newPath</newpath>";
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

        foreach (array_keys($changeSet->getItemsToDelete()) as $path) {
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
