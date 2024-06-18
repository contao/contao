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
 * Parent class for back end modules that are not using the default engine.
 *
 * @property string $table
 */
abstract class BackendModule extends Backend
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate;

	/**
	 * Data container object
	 * @var object
	 */
	protected $objDc;

	/**
	 * Current record
	 * @var array
	 */
	protected $arrData = array();

	/**
	 * Initialize the object
	 *
	 * @param DataContainer $dc
	 */
	public function __construct(DataContainer|null $dc=null)
	{
		parent::__construct();
		$this->objDc = $dc;
	}

	/**
	 * Set an object property
	 *
	 * @param string $strKey
	 * @param mixed  $varValue
	 */
	public function __set($strKey, $varValue)
	{
		$this->arrData[$strKey] = $varValue;
	}

	/**
	 * Return an object property
	 *
	 * @param string $strKey
	 *
	 * @return mixed
	 */
	public function __get($strKey)
	{
		return $this->arrData[$strKey] ?? $this->objDc->$strKey ?? parent::__get($strKey);
	}

	/**
	 * Parse the template
	 *
	 * @return string
	 */
	public function generate()
	{
		$this->Template = new BackendTemplate($this->strTemplate);
		$this->compile();

		return $this->Template->parse();
	}

	/**
	 * Compile the current element
	 */
	abstract protected function compile();
}
