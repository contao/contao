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
 * Provide methods to handle CUD tables.
 */
class CudTable extends Widget
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
		$tables = array();
		$hasAdditional = false;

		foreach ($this->options as $table => $operations)
		{
			$tables[$table] = array_column($operations, 'value');
			$hasAdditional = $hasAdditional ?: !empty(array_diff($tables[$table], array('create', 'update', 'delete')));
		}

		ksort($tables);

		return System::getContainer()->get('twig')->render('@Contao/backend/widget/cud_table.html.twig', array(
			'id' => $this->strId,
			'name' => $this->strName,
			'tables' => $tables,
			'values' => $this->varValue,
			'attributes' => $this->getAttributes(),
			'has_additional' => $hasAdditional,
		));
	}
}
