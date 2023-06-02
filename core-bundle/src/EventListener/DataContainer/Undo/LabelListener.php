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
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\StringUtil;
use Contao\UserModel;
use Twig\Environment;

/**
 * @internal
 */
#[AsCallback(table: 'tl_undo', target: 'list.label.label')]
class LabelListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Environment $twig,
    ) {
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
        $user = $this->framework->getAdapter(UserModel::class)->findById($row['pid']);
        $config = $this->framework->getAdapter(Config::class);

        $parent = null;

        if (true === ($GLOBALS['TL_DCA'][$table]['config']['dynamicPtable'] ?? null)) {
            $parent = ['table' => $originalRow['ptable'], 'id' => $originalRow['pid']];
        }

        $preview = StringUtil::deserialize($row['preview']);

        if (\is_array($preview) && !($GLOBALS['TL_DCA'][$table]['list']['label']['showColumns'] ?? false)) {
            $preview = implode(' ', $preview);
        }

        return [
            'preview' => $preview,
            'user' => $user,
            'row' => $row,
            'fromTable' => $table,
            'parent' => $parent,
            'originalRow' => $originalRow,
            'dateFormat' => $config->get('dateFormat'),
            'timeFormat' => $config->get('timeFormat'),
        ];
    }
}
