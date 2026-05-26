<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\Widget;

use Contao\CoreBundle\DataContainer\RecordLabeler;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class RootPageDependentSelectListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly UrlGeneratorInterface $router,
        private readonly TranslatorInterface $translator,
        private readonly RecordLabeler $recordLabeler,
    ) {
    }

    #[AsCallback(table: 'tl_module', target: 'fields.rootPageDependentModules.options')]
    public function optionsCallback(DataContainer $dc): array
    {
        $options = [];
        $types = $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['modules'] ?? [];
        $hasTypes = \count($types) > 0;
        $pid = $dc->getCurrentRecord()['pid'] ?? null;

        if (null === $pid) {
            return [];
        }

        $elementGroup = $this->translator->trans('MSC.mw_elements', [], 'contao_default');
        $moduleGroup = $this->translator->trans('MSC.mw_modules', [], 'contao_default');

        $elements = $this->connection->executeQuery(
            "SELECT * FROM tl_content WHERE ptable='tl_theme' AND pid=?",
            [$pid],
        );

        foreach ($elements->iterateAssociative() as $element) {
            $options[$elementGroup]['content-'.$element['id']] = $this->recordLabeler->getLabel('contao.db.tl_content.'.$element['id'], $element);
        }

        $modules = $this->connection->executeQuery(
            'SELECT m.id, m.name, m.type FROM tl_module m WHERE m.type != \'root_page_dependent_modules\' AND m.pid = ? ORDER BY m.name',
            [$pid],
        );

        foreach ($modules->iterateAssociative() as $module) {
            if ($hasTypes && !\in_array($module['type'], $types, true)) {
                continue;
            }

            $options[$moduleGroup][$module['id']] = $module['name'];
        }

        return $options;
    }

    #[AsCallback(table: 'tl_module', target: 'fields.rootPageDependentModules.wizard')]
    public function wizardCallback(DataContainer $dc): string
    {
        $wizards = [];
        $values = StringUtil::deserialize($dc->value, true);

        if (empty($values)) {
            return '';
        }

        foreach ($values as $rootPage => $id) {
            if ('' === $id) {
                continue;
            }

            $table = 'tl_module';

            if (str_starts_with($id, 'content-')) {
                $table = 'tl_content';
                $id = substr($id, 8);
            }

            $title = $this->translator->trans('tl_content.editalias', [$id], 'contao_content');
            $href = $this->router->generate('contao_backend', ['do' => 'themes', 'table' => $table, 'act' => 'edit', 'id' => $id, 'popup' => '1', 'nb' => '1']);

            $wizards[$rootPage] = \sprintf(
                ' <a href="%s" onclick="Backend.openModalIframe({\'title\':\'%s\',\'url\':this.href});return false">%s</a>',
                StringUtil::specialcharsUrl($href),
                StringUtil::specialchars(str_replace("'", "\\'", $title)),
                Image::getHtml('edit.svg', $title),
            );
        }

        return serialize($wizards);
    }
}
