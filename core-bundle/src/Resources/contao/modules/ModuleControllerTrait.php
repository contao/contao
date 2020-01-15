<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use \Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait ModuleControllerTrait
{
	public function __construct()
	{
		// Do not call parent
	}

	public function __invoke(Request $request, ModuleModel $model, string $section): Response
	{
		parent::__construct($model, $section);

		return new Response($this->generate());
	}
}
