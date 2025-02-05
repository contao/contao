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
 * Class ContentComments
 *
 * @property Comments $Comments
 */
class ContentComments extends ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_comments';

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
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['comments'][0] . ' ###';
			$objTemplate->title = $this->headline;

			return $objTemplate->parse();
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$objConfig = new \stdClass();
		$objConfig->perPage = $this->com_perPage;
		$objConfig->order = $this->com_order;
		$objConfig->template = $this->com_template;
		$objConfig->requireLogin = $this->com_requireLogin;
		$objConfig->disableCaptcha = $this->com_disableCaptcha;
		$objConfig->bbcode = $this->com_bbcode;
		$objConfig->moderate = $this->com_moderate;

		(new Comments())->addCommentsToTemplate($this->Template, $objConfig, 'tl_content', $this->id, $GLOBALS['TL_ADMIN_EMAIL'] ?? null);
	}
}
