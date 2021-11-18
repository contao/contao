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
 * Widget can be finalized after a form was submitted and all other widgets of a form were validated.
 */
interface FinalizableWidgetInterface
{
	public function finalize(): void;
}
