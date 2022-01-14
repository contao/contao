<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer\Undo;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\StringUtil;
use Contao\System;
use Contao\UserModel;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @Callback(target="list.label.label", table="tl_undo")
 *
 * @internal
 */
class LabelListener
{
    use UndoListenerTrait;

    private Environment $twig;

    public function __construct(ContaoFramework $framework, Connection $connection, TranslatorInterface $translator, Environment $twig)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->translator = $translator;
        $this->twig = $twig;
    }

    public function __invoke(array $row, string $label, DataContainer $dc): string
    {
        $this->framework->initialize();

        $table = $row['fromTable'];
        $originalRow = StringUtil::deserialize($row['data'])[$table][0];

        $controller = $this->framework->getAdapter(Controller::class);
        $controller->loadDataContainer($table);

        return $this->twig->render(
            '@ContaoCore/Backend/be_undo_label.html.twig',
            $this->getTemplateData($table, $row, $originalRow)
        );
    }

    private function getTemplateData(string $table, array $row, array $originalRow): array
    {
        $dataContainer = $this->framework->getAdapter(DataContainer::class)->getDriverForTable($table);
        $originalDC = new $dataContainer($table);

        $user = $this->framework->getAdapter(UserModel::class)->findById($row['pid']);
        $config = $this->framework->getAdapter(Config::class);

        $parent = null;

        if (true === ($GLOBALS['TL_DCA'][$table]['config']['dynamicPtable'] ?? null)) {
            $parent = ['table' => $originalRow['ptable'], 'id' => $originalRow['pid']];
        }

        return [
            'preview' => $this->renderPreview($originalRow, $originalDC),
            'user' => $user,
            'row' => $row,
            'fromTable' => $table,
            'parent' => $parent,
            'originalRow' => $originalRow,
            'dateFormat' => $config->get('dateFormat'),
            'timeFormat' => $config->get('timeFormat'),
        ];
    }

    /**
     * @return string|array<string>
     */
    private function renderPreview(array $row, DataContainer $dc)
    {
        if (DataContainer::MODE_PARENT === ($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['mode'] ?? null)) {
            $callback = $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['child_record_callback'] ?? null;

            if (\is_array($callback)) {
                return System::importStatic($callback[0])->{$callback[1]}($row);
            }

            if (\is_callable($callback)) {
                return $callback($row);
            }
        }

        return $dc->generateRecordLabel($row, $dc->table);
    }
}
