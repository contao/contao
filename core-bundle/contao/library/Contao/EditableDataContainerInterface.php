<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

interface EditableDataContainerInterface
{
	public function create();

	public function cut();

	public function copy();

	public function edit();
}
