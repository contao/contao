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
use Contao\Model\Collection;
use Contao\Model\Registry;

/**
 * Proxy for new content element fragments, so they are accessible via $GLOBALS['TL_CTE'].
 */
class ContentProxy extends ContentElement
{
	/**
	 * @var ContentElementReference
	 */
	private $reference;

	/**
	 * @param ContentModel|Collection|ContentElementReference $objElement
	 */
	public function __construct($objElement, $strColumn = 'main', array $nestedFragments = array())
	{
		if ($objElement instanceof ContentElementReference)
		{
			$this->reference = $objElement;
		}
		else
		{
			if ($objElement instanceof Collection)
			{
				$objElement = $objElement->current();
			}

			if (!$objElement instanceof ContentModel)
			{
				throw new \RuntimeException('ContentProxy must be constructed with a ContentModel');
			}

			$this->reference = new ContentElementReference($objElement, $strColumn, array(), !Registry::getInstance()->isRegistered($objElement));
			$this->reference->setNestedFragments($nestedFragments);
		}

		$this->strColumn = $strColumn;

		// Do not call parent constructor
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate()
	{
		if ($this->isHidden())
		{
			return '';
		}

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
