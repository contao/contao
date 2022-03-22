<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

interface ListableDataContainerInterface
{
	public function delete();

	public function show();

	public function showAll();

	public function undo();
}
