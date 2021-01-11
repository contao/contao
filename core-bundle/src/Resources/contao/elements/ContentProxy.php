<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Fragment\Reference\ContentElementReference;

/**
 * Proxy for new content element fragments so they are accessible via $GLOBALS['TL_CTE'].
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class ContentProxy extends ContentElement
{
	/**
	 * {@inheritdoc}
	 */
	public function generate()
	{
		$reference = new ContentElementReference($this->objModel, $this->strColumn);
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$reference->setBackendScope();
		}

		return System::getContainer()->get('fragment.handler')->render($reference);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function compile()
	{
		// noop
	}
}

class_alias(ContentProxy::class, 'ContentProxy');
