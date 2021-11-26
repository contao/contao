<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\Backend;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Image;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

class UndoOperationsListener
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Controller
     */
    private $controller;

    /**
     * @var Backend
     */
    private $backend;

    /**
     * @var Image
     */
    private $image;

    public function __construct(ContaoFramework $framework, Connection $connection, TranslatorInterface $translator)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->translator = $translator;

        /** @var Controller $controller */
        $controller = $framework->getAdapter(Controller::class);
        $this->controller = $controller;

        /** @var Backend $backend */
        $backend = $framework->getAdapter(Backend::class);
        $this->backend = $backend;

        /** @var Image $image */
        $image = $this->framework->getAdapter(Image::class);
        $this->image = $image;
    }

    /**
     * @Callback(table="tl_undo", target="list.operations.undo.button")
     */
    public function onRenderUndoButton(array $row, ?string $href, string $label, string $title, ?string $icon, string $attributes = ''): string
    {
        // Check if row has a parent
        $fromTable = $row['fromTable'];
        $this->controller->loadDataContainer($fromTable);
        $data = StringUtil::deserialize($row['data']);
        $originalRow = $data[$fromTable][0];
        $parent = $this->getParentTableForRow($fromTable, $originalRow);

        if ($parent && !$this->parentExists($parent)) {
            $parentUndo = $this->getParentUndoRecord($parent);

            if (!$parentUndo) {
                // If the parent record cannot be restored, we cannot restore the actual row.
                return $this->image->getHtml('undo_.svg', $label, 'title="'.$this->translator->trans('MSC.cannotBeRestored', [], 'contao_default').'"').' ';
            }
            // Add confirm box to inform user about restoring the parent record, too.
            $message = $this->translator->trans('MSC.restoreParentConfirm', [
                $row['fromTable'],
                $row['originalId'],
                $parentUndo['fromTable'],
                $parentUndo['originalId'],
            ], 'contao_default');
            $attributes .= ' onclick="if(!confirm(\''.$message.'\'))return false;Backend.getScrollOffset()"';
        }

        return sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            $this->backend->addToUrl($href.'&amp;id='.$row['id']),
            StringUtil::specialchars($title),
            $attributes,
            $this->image->getHtml($icon, $label)
        );
    }

    private function getParentTableForRow(string $table, array $data): ?array
    {
        if (isset($GLOBALS['TL_DCA'][$table]['config']['dynamicPtable']) && true === $GLOBALS['TL_DCA'][$table]['config']['dynamicPtable']) {
            return ['table' => $data['ptable'], 'id' => $data['pid']];
        }

        if (isset($GLOBALS['TL_DCA'][$table]['config']['ptable'])) {
            return ['table' => $GLOBALS['TL_DCA'][$table]['config']['ptable'], 'id' => $data['pid']];
        }

        return null;
    }

    private function parentExists(array $parent): bool
    {
        $parentExists = $this->connection
            ->prepare('SELECT COUNT(*) FROM '.$parent['table'].' WHERE id = :id')
            ->executeQuery([
                'id' => $parent['id'],
            ])
            ->fetchOne()
        ;

        return 0 < (int) $parentExists;
    }

    private function getParentUndoRecord(array $parent): ?array
    {
        $row = $this->connection->prepare('SELECT * FROM tl_undo WHERE fromTable = :table AND originalId = :id LIMIT 1')
            ->executeQuery([
                'table' => $parent['table'],
                'id' => $parent['id'],
            ])->fetchAssociative();

        return $row ? $row : null;
    }
}
