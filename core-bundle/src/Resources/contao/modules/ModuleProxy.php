<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;

/**
 * Proxy for new front end module fragments so they are accessible via $GLOBALS['FE_MOD'].
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class ModuleProxy extends Module
{
	/**
	 * {@inheritdoc}
	 */
	public function generate()
	{
		$reference = new FrontendModuleReference($this->objModel, $this->strColumn);

		if ('BE' === TL_MODE)
		{
			$reference->setBackendScope();
		}

		return \System::getContainer()->get('contao.fragment.renderer')->render($reference);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function compile()
	{
		// noop
	}
}
