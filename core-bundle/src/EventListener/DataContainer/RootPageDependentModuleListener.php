<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RootPageDependentModuleListener
{
    private Connection $connection;
    private TranslatorInterface $translator;
    private ScopeMatcher $scopeMatcher;
    private RequestStack $requestStack;
    private CsrfTokenManagerInterface $csrfTokenManager;
    private string $csrfTokenName;

    public function __construct(Connection $connection, TranslatorInterface $translator, ScopeMatcher $scopeMatcher, RequestStack $requestStack, CsrfTokenManagerInterface $csrfTokenManager, string $csrfTokenName)
    {
        $this->connection = $connection;
        $this->translator = $translator;
        $this->scopeMatcher = $scopeMatcher;
        $this->requestStack = $requestStack;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->csrfTokenName = $csrfTokenName;
    }

    public function onLoadDataContainer(string $table): void
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request || !$this->scopeMatcher->isBackendRequest($request)) {
            return;
        }

        $fields = $GLOBALS['TL_DCA'][$table]['fields'];

        if (
            !\is_array($fields) ||
            !\in_array('rootPageDependentModule', array_column($fields, 'inputType'), true)
        ) {
            return;
        }

        foreach ($fields as $key => $field) {
            if (!\array_key_exists('inputType', $field)) {
                unset($fields[$key]);
            }
        }

        $affectedFields = array_keys(
            array_column($fields, 'inputType'),
            'rootPageDependentModule',
            true
        );

        foreach ($affectedFields as $affectedField) {
            $field = \array_slice($fields, $affectedField, 1);
            $keys = array_keys($field);

            if (!\count($keys)) {
                continue;
            }

            $key = $keys[0];
            $fieldConfig = $GLOBALS['TL_DCA'][$table]['fields'][$key];

            if (!\array_key_exists('eval', $fieldConfig)) {
                $fieldConfig['eval'] = [];
            }

            if (!\array_key_exists('save_callback', $fieldConfig)) {
                $fieldConfig['save_callback'] = [];
            }

            if (!\array_key_exists('wizard', $fieldConfig)) {
                $fieldConfig['wizard'] = [];
            }

            if (
                !\array_key_exists('options', $fieldConfig) &&
                !\array_key_exists('options_callback', $fieldConfig)
            ) {
                $types = [];

                if (\array_key_exists('modules', $fieldConfig['eval'])) {
                    $types = $fieldConfig['eval']['modules'];
                }

                $fieldConfig['options'] = $this->getFrontendModules($types);
            }

            if (!\array_key_exists('rootPages', $fieldConfig['eval'])) {
                $fieldConfig['eval']['rootPages'] = $this->getRootPages();
            }

            if (!\array_key_exists('blankOptionLabel', $fieldConfig['eval'])) {
                $fieldConfig['eval']['blankOptionLabel'] = $this->translator->trans(
                    sprintf('tl_module.%sBlankOptionLabel', $key),
                    [],
                    'contao_module'
                );
            }

            $fieldConfig['eval']['includeBlankOption'] = true;

            $fieldConfig['save_callback'][] = [
                static::class,
                'onSaveCallback',
            ];

            $fieldConfig['wizard'][] = [
                static::class,
                'onEditModule',
            ];

            // Save modified configuration back to global DCA
            $GLOBALS['TL_DCA'][$table]['fields'][$key] = $fieldConfig;
        }
    }

    public function onSaveCallback($value, DataContainer $dataContainer)
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

    public function onEditModule(DataContainer $dc): string
    {
        $wizards = [];
        $values = StringUtil::deserialize($dc->value, true);

        if (empty($values)) {
            return '';
        }

        System::loadLanguageFile('tl_content');

        foreach ($values as $rootPage => $id) {
            if ('' === $id) {
                continue;
            }

            $title = $this->translator->trans('tl_content.editalias', [$id], 'contao_content');

            $wizards[$rootPage] = ' <a href="contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$id.'&amp;popup=1&amp;nb=1&amp;rt='.$this->csrfTokenManager->getToken($this->csrfTokenName)->getValue().'"
                    title="'.StringUtil::specialchars($title).'"
                    onclick="Backend.openModalIframe({\'title\':\''.StringUtil::specialchars(str_replace("'", "\\'", $title)).'\',\'url\':this.href});return false">'.Image::getHtml('alias.svg', $title).'</a>';
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

        $rootPages = $statement->executeQuery()->fetchAllAssociative();

        $pages = [];

        foreach ($rootPages as $rootPage) {
            $pages[$rootPage['id']] = sprintf('%s (%s)', $rootPage['title'], $rootPage['language']);
        }

        return $pages;
    }

    private function getFrontendModules(array $types = []): array
    {
        $statement = $this->connection->executeQuery("
            SELECT m.id, m.name
            FROM tl_module m
            WHERE m.type <> 'root_page_dependent_module'
            ORDER BY m.name
        ");

        if (\count($types)) {
            $statement = $this->connection->executeQuery(
                "SELECT m.id, m.name
                    FROM tl_module m
                    WHERE m.type IN (?) AND m.type <> 'root_page_dependent_module'
                    ORDER BY m.name",
                [$types],
                [Connection::PARAM_STR_ARRAY]
            );
        }

        $modules = $statement->fetchAllAssociative();

        $list = [];

        foreach ($modules as $module) {
            $list[$module['id']] = $module['name'];
        }

        return $list;
    }
}
