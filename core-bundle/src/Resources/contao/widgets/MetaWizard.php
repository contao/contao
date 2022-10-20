<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

/**
 * Provide methods to handle file meta information.
 *
 * @property array $metaFields
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class MetaWizard extends Widget
{
	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';

	/**
	 * Set an object property
	 *
	 * @param string $strKey   The property name
	 * @param mixed  $varValue The property value
	 */
	public function __set($strKey, $varValue)
	{
		if ($strKey == 'metaFields')
		{
			if (!array_is_assoc($varValue))
			{
				$varValue = array_combine($varValue, array_fill(0, \count($varValue), ''));
			}

			foreach ($varValue as $strArrKey => $varArrValue)
			{
				if (!\is_array($varArrValue))
				{
					$varValue[$strArrKey] = array('attributes' => $varArrValue);
				}
			}

			$this->arrConfiguration['metaFields'] = $varValue;
		}
		else
		{
			parent::__set($strKey, $varValue);
		}
	}

	/**
	 * Trim the values and add new languages if necessary
	 *
	 * @param mixed $varInput
	 *
	 * @return mixed
	 */
	public function validator($varInput)
	{
		if (!\is_array($varInput))
		{
			return null; // see #382
		}

		foreach ($varInput as $k=>$v)
		{
			if ($k != 'language')
			{
				if (!empty($v['link']))
				{
					$v['link'] = StringUtil::specialcharsUrl($v['link']);
				}

				$varInput[$k] = array_map('trim', $v);
			}
			else
			{
				if ($v)
				{
					// Take the fields from the DCA (see #4327)
					$varInput[$v] = array_combine(array_keys($this->metaFields), array_fill(0, \count($this->metaFields), ''));
				}

				unset($varInput[$k]);
			}
		}

		return $varInput;
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$count = 0;
		$return = '';

		$this->import(Database::class, 'Database');
		$this->import(BackendUser::class, 'User');

		// Only show the root page languages (see #7112, #7667)
		$objRootLangs = $this->Database->query("SELECT REPLACE(language, '-', '_') AS language FROM tl_page WHERE type='root' AND language != ''");
		$existing = $objRootLangs->fetchEach('language');

		foreach ($existing as $lang)
		{
			if (!isset($this->varValue[$lang]))
			{
				$this->varValue[$lang] = array();
			}
		}

		// No languages defined in the site structure
		if (empty($this->varValue) || !\is_array($this->varValue))
		{
			return '<p class="tl_info">' . $GLOBALS['TL_LANG']['MSC']['metaNoLanguages'] . '</p>';
		}

		$languages = $this->getLanguages(true);

		// Add the existing entries
		if (!empty($this->varValue))
		{
			$return = '<ul id="ctrl_' . $this->strId . '" class="tl_metawizard dcapicker">';

			// Add the input fields
			foreach ($this->varValue as $lang=>$meta)
			{
				$return .= '
    <li class="' . (($count % 2 == 0) ? 'even' : 'odd') . '" data-language="' . $lang . '"><span class="lang">' . ($GLOBALS['TL_LANG']['LNG'][$lang] ?? $lang) . ' <button type="button" title="' . $GLOBALS['TL_LANG']['MSC']['delete'] . '" onclick="Backend.metaDelete(this)">' . Image::getHtml('delete.svg') . '</button></span>';

				// Take the fields from the DCA (see #4327)
				foreach ($this->metaFields as $field=>$fieldConfig)
				{
					$return .= '<label for="ctrl_' . $this->strId . '_' . $field . '_' . $count . '">' . $GLOBALS['TL_LANG']['MSC']['aw_' . $field] . '</label>';

					if (isset($fieldConfig['type']) && 'textarea' === $fieldConfig['type'])
					{
						$return .= '<textarea name="' . $this->strId . '[' . $lang . '][' . $field . ']" id="ctrl_' . $this->strId . '_' . $field . '_' . $count . '" class="tl_textarea"' . (!empty($fieldConfig['attributes']) ? ' ' . $fieldConfig['attributes'] : '') . '>' . $meta[$field] . '</textarea>';
					}
					else
					{
						$return .= '<input type="text" name="' . $this->strId . '[' . $lang . '][' . $field . ']" id="ctrl_' . $this->strId . '_' . $field . '_' . $count . '" class="tl_text" value="' . StringUtil::specialchars($meta[$field]) . '"' . (!empty($fieldConfig['attributes']) ? ' ' . $fieldConfig['attributes'] : '') . '>';
					}

					// DCA picker
					if (isset($fieldConfig['dcaPicker']) && (\is_array($fieldConfig['dcaPicker']) || $fieldConfig['dcaPicker'] === true))
					{
						$return .= Backend::getDcaPickerWizard($fieldConfig['dcaPicker'], $this->strTable, $this->strField, $this->strId . '_' . $field . '_' . $count);
					}

					$return .= '<br>';
				}

				$return .= '
    </li>';

				++$count;
			}

			$return .= '
  </ul>';
		}

		$options = array('<option value="">-</option>');

		// Add the remaining languages
		foreach ($languages as $k=>$v)
		{
			$options[] = '<option value="' . $k . '"' . (isset($this->varValue[$k]) ? ' disabled' : '') . '>' . $v . '</option>';
		}

		$return .= '
  <div class="tl_metawizard_new">
    <select name="' . $this->strId . '[language]" class="tl_select" onchange="Backend.toggleAddLanguageButton(this)">' . implode('', $options) . '</select> <input type="button" class="tl_submit" disabled value="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['aw_new']) . '" onclick="Backend.metaWizard(this, \'ctrl_' . $this->strId . '\')">
  </div>';

		return $return;
	}
}

class_alias(MetaWizard::class, 'MetaWizard');
