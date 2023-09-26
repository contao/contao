<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Controller\BackendCsvImportController;
use Contao\CoreBundle\Exception\ResponseException;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provide methods to handle list items.
 *
 * @property integer $maxlength
 */
class ListWizard extends Widget
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
	 * Add specific attributes
	 *
	 * @param string $strKey
	 * @param mixed  $varValue
	 */
	public function __set($strKey, $varValue)
	{
		if ($strKey == 'maxlength')
		{
			if ($varValue > 0)
			{
				$this->arrAttributes['maxlength'] = $varValue;
			}
		}
		else
		{
			parent::__set($strKey, $varValue);
		}
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$arrButtons = array('copy', 'delete', 'drag');

		// Make sure there is at least an empty array
		if (empty($this->varValue) || !\is_array($this->varValue))
		{
			$this->varValue = array('');
		}

		$return = '<ul id="ctrl_' . $this->strId . '" class="tl_listwizard">';

		// Add input fields
		for ($i=0, $c=\count($this->varValue); $i<$c; $i++)
		{
			$return .= '
    <li><input type="text" name="' . $this->strId . '[]" class="tl_text" value="' . StringUtil::specialchars($this->varValue[$i]) . '"' . $this->getAttributes() . '> ';

			// Add buttons
			foreach ($arrButtons as $button)
			{
				if ($button == 'drag')
				{
					$return .= ' <button type="button" class="drag-handle" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['move']) . '" aria-hidden="true">' . Image::getHtml('drag.svg') . '</button>';
				}
				else
				{
					$return .= ' <button type="button" data-command="' . $button . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['lw_' . $button]) . '">' . Image::getHtml($button . '.svg') . '</button>';
				}
			}

			$return .= '</li>';
		}

		return $return . '
  </ul>
  <script>Backend.listWizard("ctrl_' . $this->strId . '")</script>';
	}

	/**
	 * Return a form to choose a CSV file and import it
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 *
	 * @throws \Exception
	 * @throws ResponseException
	 *
	 * @deprecated Deprecated since Contao 4.3 to be removed in 5.0.
	 *             Use the Contao\CoreBundle\Controller\BackendCsvImportController service instead.
	 */
	public function importList(DataContainer $dc)
	{
		$response = System::getContainer()->get(BackendCsvImportController::class)->importListWizardAction($dc);

		if ($response instanceof RedirectResponse)
		{
			throw new ResponseException($response);
		}

		return $response->getContent();
	}
}

class_alias(ListWizard::class, 'ListWizard');
