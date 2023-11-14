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

use Contao\CoreBundle\Filesystem\Dbafs\ChangeSet\ChangeSet;
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
    description: 'Synchronizes the registered DBAFS with the virtual filesystem.',
)]
class FilesyncCommand extends Command
{
    public function __construct(private readonly DbafsManager $dbafsManager)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument('paths', InputArgument::IS_ARRAY, 'Optional path(s) for partial synchronization.');
    }

    #[\Override]
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

        foreach ($changeSet->getItemsToCreate() as $itemToCreate) {
            $table->addRow([
                'add',
                "<newpath>{$itemToCreate->getPath()}</newpath> (new hash: <hash>{$itemToCreate->getHash()}</hash>)",
            ]);
        }

        foreach ($changeSet->getItemsToUpdate() as $itemToUpdate) {
            if ($itemToUpdate->updatesPath()) {
                $change = "{$itemToUpdate->getExistingPath()} → <newpath>{$itemToUpdate->getNewPath()}</newpath>";
                $action = 'move';
            } else {
                $change = $itemToUpdate->getExistingPath();
                $action = 'update';
            }

            if ($itemToUpdate->updatesHash()) {
                $change .= " (updated hash: <hash>{$itemToUpdate->getNewHash()}</hash>)";
            }

            $table->addRow([$action, $change]);
        }

        foreach ($changeSet->getItemsToDelete() as $itemToDelete) {
            $table->addRow(['delete', "<oldpath>{$itemToDelete->getPath()}</oldpath>"]);
        }

        $table->render();

        $output->writeln(
            sprintf(
                ' Total items added: %s | updated/moved: %s | deleted: %s',
                \count($changeSet->getItemsToCreate()),
                \count($changeSet->getItemsToUpdate()),
                \count($changeSet->getItemsToDelete()),
            ),
        );
    }
}
