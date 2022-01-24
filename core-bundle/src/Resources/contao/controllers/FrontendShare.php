<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Share a page via a social network.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FrontendShare extends Frontend
{
	/**
	 * Run the controller
	 *
	 * @return RedirectResponse
	 */
	public function run()
	{
		if (($url = Input::get('u', true)) && !\is_string($url))
		{
			return new RedirectResponse('../');
		}

		if (($text = Input::get('t', true)) && !\is_string($text))
		{
			return new RedirectResponse('../');
		}

		switch (Input::get('p'))
		{
			case 'facebook':
				return new RedirectResponse(
					'https://www.facebook.com/sharer/sharer.php'
						. '?p[url]=' . rawurlencode($url)
				);

			case 'twitter':
				return new RedirectResponse(
					'https://twitter.com/intent/tweet'
						. '?url=' . rawurlencode($url)
						. '&text=' . rawurlencode($text)
				);
		}

		return new RedirectResponse('../');
	}
}

class_alias(FrontendShare::class, 'FrontendShare');
