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

        $rows = $this->connection->executeQuery(
            "SELECT m.id, m.name, m.type
            FROM tl_module m
            WHERE m.type != 'root_page_dependent_modules' AND m.pid = ?
            ORDER BY m.name",
            [$pid],
        );

        foreach ($rows->iterateAssociative() as $module) {
            if ($hasTypes && !\in_array($module['type'], $types, true)) {
                continue;
            }

            $options[$module['id']] = $module['name'];
        }

        return $options;
    }

    #[AsCallback(table: 'tl_module', target: 'fields.rootPageDependentModules.save')]
    public function saveCallback(mixed $value): string
    {
        $values = StringUtil::deserialize($value);

        if (!\is_array($values)) {
            return $value;
        }

        $newValues = [];
        $availableRootPages = array_keys($this->getRootPages());

        foreach ($values as $v) {
            $newValues[array_shift($availableRootPages)] = $v;
        }

        return serialize($newValues);
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

            $title = $this->translator->trans('tl_content.editalias', [$id], 'contao_content');
            $href = $this->router->generate('contao_backend', ['do' => 'themes', 'table' => 'tl_module', 'act' => 'edit', 'id' => $id, 'popup' => '1', 'nb' => '1']);

            $wizards[$rootPage] = \sprintf(
                ' <a href="%s" title="%s" onclick="Backend.openModalIframe({\'title\':\'%s\',\'url\':this.href});return false">%s</a>',
                StringUtil::specialcharsUrl($href),
                StringUtil::specialchars($title),
                StringUtil::specialchars(str_replace("'", "\\'", $title)),
                Image::getHtml('edit.svg', $title),
            );
        }

        return serialize($wizards);
    }

    private function getRootPages(): array
    {
        $statement = $this->connection->prepare('
            SELECT p.id, p.title, p.language
            FROM tl_page p
            WHERE p.pid = 0
            ORDER BY p.sorting ASC
        ');

        $rows = $statement->executeQuery();
        $pages = [];

        foreach ($rows->iterateAssociative() as $rootPage) {
            $pages[$rootPage['id']] = \sprintf('%s (%s)', $rootPage['title'], $rootPage['language']);
        }

        return $pages;
    }
}
