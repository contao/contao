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

trait ModuleControllerTrait
{
	public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null): Response
	{
		/* @var Module $class */
		$class = $GLOBALS['FE_MOD'][$model->type];

		/* @var Module $module */
		$module = new $class($model, $section);

		return new Response($module->generate());
	}
}
