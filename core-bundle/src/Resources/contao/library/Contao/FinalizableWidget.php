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
 * Widget can be finalized after all other widgets were validated.
 */
interface FinalizableWidget
{
	public function finalize(): void;
}
