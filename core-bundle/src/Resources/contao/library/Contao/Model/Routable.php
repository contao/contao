<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Model;

interface Routable
{
	/**
	 * Generate a relative front end URL
	 *
	 * @return string
	 */
	public function getFrontendUrl();

	/**
	 * Generate an absolute front end URL
	 *
	 * @return string
	 */
	public function getAbsoluteUrl();
}
