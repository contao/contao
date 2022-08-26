<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\ContaoCoreBundle;
use Symfony\Component\HttpFoundation\Response;

/**
 * Back end custom controller.
 */
class BackendCustom extends BackendMain
{
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		// Initialize the template in the constructor, so it is available in
		// the getTemplateObject() method
		$this->Template = new BackendTemplate('be_main');
	}

	/**
	 * Return the template object
	 *
	 * @return BackendTemplate
	 */
	public function getTemplateObject()
	{
		return $this->Template;
	}

	/**
	 * Run the controller and parse the template
	 *
	 * @return Response
	 */
	public function run()
	{
		$this->Template->version = $GLOBALS['TL_LANG']['MSC']['version'] . ' ' . ContaoCoreBundle::getVersion();

		// Ajax request
		if ($_POST && Environment::get('isAjaxRequest'))
		{
			$this->objAjax = new Ajax(Input::post('action'));
			$this->objAjax->executePreActions();
		}

		return $this->output();
	}
}

class_alias(BackendCustom::class, 'BackendCustom');
