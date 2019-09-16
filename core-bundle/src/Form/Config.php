<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Form;

use Contao\CoreBundle\Framework\Adapter;

class Config
{
    /**
     * @var Adapter
     */
    private $formModel;

    /**
     * @var Adapter
     */
    private $formFieldModel;

    /**
     * @var array
     */
    private $forms;

    /**
     * Constructor.
     */
    public function __construct(Adapter $formModel, Adapter $formFieldModel, array $forms)
    {
        $this->formModel = $formModel;
        $this->formFieldModel = $formFieldModel;
        $this->forms = $forms;
    }

    public function getList(): array
    {
        $list = [];
        foreach ($this->forms as $key => $form) {
            $list[$key] = $form['title'] . ' (ID ' . $key . ')';
        }

        return $list;
    }

    public function getFormData($key): ?array
    {
        if (!isset($this->forms[$key])) {
            return null;
        }

        $formData = $this->forms[$key];
        unset($formData['fields']);
        $pk = $this->formModel->getPk();
        $formData[$pk] = $key;

        return $formData;
    }

    public function getFormFieldRows($key): ?array
    {
        if (!isset($this->forms[$key])) {
            return null;
        }

        $pk = $this->formFieldModel->getPk();
        $rows = $this->forms[$key]['fields'];
        foreach ($rows as $rkey => $fieldData) {
            $rows[$rkey][$pk] = $rkey;
        }

        return $rows;
    }
}
