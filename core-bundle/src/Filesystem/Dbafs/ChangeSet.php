<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Dbafs;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeSet
{
    public const ATTRIBUTE_HASH = 'hash';
    public const ATTRIBUTE_PATH = 'path';

    /** @var array */
    private $itemsToCreate;

    /** @var array */
    private $itemsToUpdate;

    /** @var array */
    private $itemsToDelete;

    public function __construct(array $itemsToCreate, array $itemsToUpdate, array $itemsToDelete)
    {
        $this->itemsToCreate = $itemsToCreate;
        $this->itemsToUpdate = $itemsToUpdate;
        $this->itemsToDelete = $itemsToDelete;
    }

    /**
     * @return array A sorted array of value sets [[attribute => newValue, ...], ...].
     */
    public function getItemsToCreate(): array
    {
        return $this->itemsToCreate;
    }

    /**
     * @return array A sorted mapping of value sets [path => [attribute => updatedValue, ...], ...].
     */
    public function getItemsToUpdate(): array
    {
        return $this->itemsToUpdate;
    }

    /**
     * @return string[] a list of paths to delete
     */
    public function getItemsToDelete(): array
    {
        return $this->itemsToDelete;
    }

    public function isEmpty(): bool
    {
        return empty($this->itemsToCreate) &&
            empty($this->itemsToUpdate) &&
            empty($this->itemsToDelete);
    }

    public function renderStats(OutputInterface $output): void
    {
        if ($this->isEmpty()) {
            $output->writeln('No changes.');

            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Action', 'Resource / Change']);

        $output->getFormatter()->setStyle('hash', new OutputFormatterStyle('yellow'));
        $output->getFormatter()->setStyle('newpath', new OutputFormatterStyle('green'));
        $output->getFormatter()->setStyle('oldpath', new OutputFormatterStyle('red'));

        foreach ($this->itemsToCreate as $itemToCreate) {
            $table->addRow([
                'add', "<newpath>'{$itemToCreate[self::ATTRIBUTE_PATH]}'</newpath> (new hash: <hash>{$itemToCreate[self::ATTRIBUTE_HASH]}</hash>)",
            ]);
        }

        foreach ($this->itemsToUpdate as $identifierPath => $itemToUpdate) {
            $action = 'update';
            if (isset($itemToUpdate[self::ATTRIBUTE_PATH])) {
                $change = "'{$identifierPath}' ⟶ <newpath>'{$itemToUpdate[self::ATTRIBUTE_PATH]}'</newpath>";
                $action = 'move';
            } else {
                $change = "'$identifierPath'";
            }
            if (isset($itemToUpdate[self::ATTRIBUTE_HASH])) {
                $change .= " (updated hash: <hash>{$itemToUpdate[self::ATTRIBUTE_HASH]}</hash>)";
            }
            $table->addRow([$action, $change]);
        }

        foreach ($this->itemsToDelete as $itemToDelete) {
            $table->addRow(['delete', "<oldpath>'$itemToDelete'</oldpath>"]);
        }

        $table->render();

        $output->writeln(sprintf(' Total items added: %s | updated/moved: %s | deleted: %s',
            \count($this->itemsToCreate), \count($this->itemsToUpdate), \count($this->itemsToDelete)));
    }
}
