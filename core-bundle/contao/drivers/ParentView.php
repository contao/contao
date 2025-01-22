<?php

namespace Contao;

class ParentView extends View
{
	protected string $mode = 'parent_view';

	public function render($table, $return, $blnHasSorting)
	{
		// Form
		if (Input::get('act') == 'select') {
			$strButtons = System::getContainer()->get('contao.data_container.buttons_builder')->generateSelectButtons($table, $blnHasSorting, $this->dc);

			$return = $this->renderSelectForm($return, $strButtons, $this->getContext());
		}

		return $return;
	}
}
