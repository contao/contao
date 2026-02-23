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
 * Provide methods to handle sections of a page layout.
 */
class SectionWizard extends Widget
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
	 * Standardize the ID
	 *
	 * @param mixed $varInput
	 *
	 * @return mixed
	 */
	protected function validator($varInput)
	{
		$arrTitles = array();
		$arrIds = array();
		$arrSections = array();

		foreach ($varInput as $arrSection)
		{
			// Title and ID are required
			if ((!empty($arrSection['title']) && empty($arrSection['id'])) || (empty($arrSection['title']) && !empty($arrSection['id'])))
			{
				$this->addError($GLOBALS['TL_LANG']['ERR']['emptyTitleOrId']);
			}

			// Check for duplicate section titles
			if (\in_array($arrSection['title'], $arrTitles))
			{
				$this->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['duplicateSectionTitle'], $arrSection['title']));
			}

			$arrSection['id'] = StringUtil::standardize($arrSection['id'], true);

			// Add a suffix to reserved names (see #301)
			if (\in_array($arrSection['id'], array('top', 'wrapper', 'header', 'container', 'main', 'left', 'right', 'footer')))
			{
				$arrSection['id'] .= '-custom';
			}

			// Check for duplicate section IDs
			if (\in_array($arrSection['id'], $arrIds))
			{
				$this->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['duplicateSectionId'], $arrSection['id']));
			}

			$arrTitles[] = $arrSection['title'];
			$arrIds[] = $arrSection['id'];
			$arrSections[] = $arrSection;
		}

		return $arrSections;
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		// Make sure there is at least an empty array
		if (!\is_array($this->varValue) || !$this->varValue[0])
		{
			$this->varValue = array(array(''));
		}

		$rows = array();

		// Compile rows
		for ($i=0, $c=\count($this->varValue); $i<$c; $i++)
		{
			$templateOptions = array();

			foreach (Controller::getTemplateGroup('block_section') as $k => $v)
			{
				$templateOptions[] = array(
					'value' => self::specialcharsValue($k),
					'label' => $v,
					'selected' => '' !== static::optionSelected($k, $this->varValue[$i]['template'] ?? null),
				);
			}

			$positionOptions = array();

			foreach (array('top', 'before', 'main', 'after', 'bottom', 'manual') as $v)
			{
				$positionOptions[] = array(
					'value' => self::specialcharsValue($v),
					'label' => $GLOBALS['TL_LANG']['SECTIONS'][$v],
					'selected' => '' !== static::optionSelected($v, $this->varValue[$i]['position'] ?? null),
				);
			}

			$rows[] = array(
				'title' => self::specialcharsValue($this->varValue[$i]['title'] ?? ''),
				'id' => self::specialcharsValue($this->varValue[$i]['id'] ?? ''),
				'template_options' => $templateOptions,
				'position_options' => $positionOptions,
			);
		}

		return System::getContainer()->get('twig')->render('@Contao/backend/widget/section_wizard.html.twig', array(
			'id' => $this->strId,
			'rows' => $rows,
		));
	}
}
