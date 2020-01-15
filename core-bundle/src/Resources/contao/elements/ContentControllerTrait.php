<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\HttpFoundation\Response;

trait ContentControllerTrait
{
	public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null): Response
	{
		/* @var ContentElement $class */
		$class = $GLOBALS['TL_CTE'][$model->type];

		/* @var ContentElement $element */
		$element = new $class($model, $section);

		return new Response($element->generate());
	}
}
