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
 * Provide methods to handle CHMOD tables.
 */
class ChmodTable extends Widget
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
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		return System::getContainer()->get('twig')->render('@Contao/backend/widget/chmod_table.html.twig', array(
			'id' => $this->strId,
			'name' => $this->strName,
			'objects' => array('u' => 'cuser', 'g' => 'cgroup', 'w' => 'cworld'),
			'values' => $this->varValue,
			'attributes' => $this->getAttributes(),
		));
	}
}
