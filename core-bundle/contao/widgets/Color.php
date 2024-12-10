<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

class Color extends TextField
{
	public function generate(): string
	{
		if (!$this->multiple)
		{
			return \sprintf(
				'<input type="color" name="%s" id="ctrl_%s" class="tl_text%s" value="%s"%s data-action="focus->contao--scroll-offset#store" data-contao--scroll-offset-target="autoFocus">',
				$this->strName,
				$this->strId,
				$this->strClass ? ' ' . $this->strClass : '',
				self::specialcharsValue($this->varValue),
				$this->getAttributes()
			);
		}

		// Return if field size is missing
		if (!$this->size)
		{
			return '';
		}

		if (!\is_array($this->varValue))
		{
			$this->varValue = array($this->varValue);
		}

		$arrFields = array();

		for ($i=0; $i<$this->size; $i++)
		{
			$arrFields[] = \sprintf(
				'<input type="color" name="%s[]" id="ctrl_%s" class="tl_text_%s" value="%s"%s data-action="focus->contao--scroll-offset#store">',
				$this->strName,
				$this->strId . '_' . $i,
				$this->size,
				self::specialcharsValue(@$this->varValue[$i]), // see #4979
				$this->getAttributes()
			);
		}

		return \sprintf(
			'<div id="ctrl_%s" class="tl_text_field%s">%s</div>',
			$this->strId,
			$this->strClass ? ' ' . $this->strClass : '',
			implode(' ', $arrFields),
		);
	}
}
