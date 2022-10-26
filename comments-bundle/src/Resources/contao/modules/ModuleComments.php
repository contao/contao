<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Patchwork\Utf8;

/**
 * Class ModuleComments
 *
 * @property Comments $Comments
 * @property bool     $com_moderate
 * @property bool     $com_bbcode
 * @property bool     $com_disableCaptcha
 * @property bool     $com_requireLogin
 * @property string   $com_order
 * @property string   $com_template
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleComments extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_comments';

	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['comments'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/** @var PageModel $objPage */
		global $objPage;

		$this->import(Comments::class, 'Comments');
		$objConfig = new \stdClass();

		$objConfig->perPage = $this->perPage;
		$objConfig->order = $this->com_order;
		$objConfig->template = $this->com_template;
		$objConfig->requireLogin = $this->com_requireLogin;
		$objConfig->disableCaptcha = $this->com_disableCaptcha;
		$objConfig->bbcode = $this->com_bbcode;
		$objConfig->moderate = $this->com_moderate;

		$this->Comments->addCommentsToTemplate($this->Template, $objConfig, 'tl_page', $objPage->id, $GLOBALS['TL_ADMIN_EMAIL'] ?? null);
	}
}

class_alias(ModuleComments::class, 'ModuleComments');
