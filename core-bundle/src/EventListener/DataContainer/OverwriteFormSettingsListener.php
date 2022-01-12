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

use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\DataContainer;
use Contao\FormModel;
use Contao\System;

class OverwriteFormSettingsListener
{
    private ContaoFramework $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @Hook("loadDataContainer")
     */
    public function addOverwritableFields(string $table): void
    {
        if (!\in_array($table, ['tl_content', 'tl_module'], true)) {
            return;
        }

        $this->framework->getAdapter(Controller::class)->loadDataContainer('tl_form');
        $this->framework->getAdapter(System::class)->loadLanguageFile('tl_form');

        $pm = PaletteManipulator::create();
        $fields = $this->getOverwritableFields();

        foreach ($fields as $field => $config) {
            $targetField = 'form_'.$field;

            $this->copyFieldConfig($table, $targetField, $config);
            $this->registerFieldLoadCallback($table, $targetField);

            $pm->addField($targetField, 'form_legend');
        }

        $pm->applyToSubpalette('formSettings', $table);
    }

    /**
     * This `load_callback` gets the field value from the parent form.
     * It is registered dynamically in `$this->addOverwritableFields()`.
     *
     * @param mixed $varValue
     *
     * @return mixed
     */
    public function getPlaceholderFromForm($varValue, DataContainer $dc)
    {
        $formId = $dc->activeRecord->form;
        $formModel = $this->framework->getAdapter(FormModel::class);

        if (!$formId) {
            return $varValue;
        }

        $form = $formModel->findById($formId);
        $formField = str_replace('form_', '', $dc->field);

        if (isset($form->{$formField})) {
            $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['placeholder'] = $form->{$formField};
        }

        return $varValue;
    }

    private function getOverwritableFields(): array
    {
        $overwritable = [];

        foreach ($GLOBALS['TL_DCA']['tl_form']['fields'] as $field => $config) {
            if (isset($config['eval']['formOverwritable']) && true === $config['eval']['formOverwritable']) {
                $overwritable[$field] = $config;
            }
        }

        return $overwritable;
    }

    private function copyFieldConfig(string $table, string $targetField, array $dca): void
    {
        $GLOBALS['TL_DCA'][$table]['fields'][$targetField] = $dca;
        $GLOBALS['TL_DCA'][$table]['fields'][$targetField]['eval']['mandatory'] = false;
    }

    private function registerFieldLoadCallback(string $table, string $targetField): void
    {
        if (!isset($GLOBALS['TL_DCA'][$table]['fields'][$targetField]['load_callback'])) {
            $GLOBALS['TL_DCA'][$table]['fields'][$targetField]['load_callback'] = [];
        }

        $GLOBALS['TL_DCA'][$table]['fields'][$targetField]['load_callback'][] = [
            'contao.listener.data_container.overwrite_form_settings', 'getPlaceholderFromForm',
        ];
    }
}
