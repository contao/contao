<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\Model\Collection;
use Contao\Model\Registry;

/**
 * Proxy for new front end module fragments, so they are accessible via $GLOBALS['FE_MOD'].
 */
class ModuleProxy extends Module
{
	/**
	 * @var FrontendModuleReference
	 */
	private $reference;

	/**
	 * @param ModuleModel|Collection $objElement
	 */
	public function __construct($objElement, $strColumn = 'main')
	{
		if ($objElement instanceof Collection)
		{
			$objElement = $objElement->current();
		}

		if (!$objElement instanceof ModuleModel)
		{
			throw new \RuntimeException('ModuleProxy must be constructed with a ModuleModel');
		}

		$this->reference = new FrontendModuleReference($objElement, $strColumn, array(), !Registry::getInstance()->isRegistered($objElement));
		$this->strColumn = $strColumn;

		// Do not call parent constructor
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$this->reference->setBackendScope();
		}

		return System::getContainer()->get('fragment.handler')->render($this->reference);
	}

	public function __set($strKey, $varValue)
	{
		$this->reference->attributes['templateProperties'][$strKey] = $varValue;
	}

	public function __get($strKey)
	{
		return $this->reference->attributes['templateProperties'][$strKey] ?? null;
	}

	public function __isset($strKey)
	{
		return isset($this->reference->attributes['templateProperties'][$strKey]);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function compile()
	{
		// noop
	}
}

class_alias(ModuleProxy::class, 'ModuleProxy');
