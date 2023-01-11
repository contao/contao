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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Share a page via a social network.
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
		$url = Input::get('u', true);

		if (!$url || !\is_string($url))
		{
			throw new BadRequestHttpException('Parameter "u" missing');
		}

		if (Input::get('p') == 'facebook')
		{
			return new RedirectResponse('https://www.facebook.com/sharer/sharer.php?p[url]=' . rawurlencode($url));
		}

		if (Input::get('p') == 'twitter')
		{
			$text = Input::get('t', true);

			if (!$text || !\is_string($text))
			{
				return new RedirectResponse('https://twitter.com/intent/tweet?url=' . rawurlencode($url));
			}

			return new RedirectResponse('https://twitter.com/intent/tweet?url=' . rawurlencode($url) . '&text=' . rawurlencode($text));
		}

		throw new BadRequestHttpException(sprintf('Invalid action "%s"', Input::get('p')));
	}
}
