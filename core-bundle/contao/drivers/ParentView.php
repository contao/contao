<?php

namespace Contao;

class ParentView extends View
{
	protected string $mode = 'parent';

	public function render($return, $blnHasSorting)
	{
		// Form
		if (Input::get('act') == 'select') {
			$strButtons = System::getContainer()->get('contao.data_container.buttons_builder')->generateSelectButtons($this->table, $blnHasSorting, $this->dc);

			$return = $this->renderSelectForm($return, $strButtons);
		}

		return $return;
	}
}
