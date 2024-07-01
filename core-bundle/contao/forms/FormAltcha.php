<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Controller\AltchaController;
use Contao\CoreBundle\String\HtmlAttributes;

class FormAltcha extends Widget
{
	public HtmlAttributes $altchaAttributes;

	/**
	 * @var boolean
	 */
	protected $blnForAttribute = true;

	/**
	 * @var string
	 */
	protected $strTemplate = 'form_altcha';

	/**
	 * @var string
	 */
	protected $prefix = 'widget widget-altcha';

	/**
	 * Use the raw request data.
	 *
	 * @param array $arrAttributes An optional attributes array
	 */
	public function __construct($arrAttributes=null)
	{
		parent::__construct($arrAttributes);

		$this->useRawRequestData = true;
	}

	/**
	 * Generate the widget and return it as string.
	 *
	 * @return string The widget markup
	 */
	public function generate(): string
	{
		return sprintf('<altcha-widget%s></altcha-widget>', $this->getAltchaAttributes());
	}

	/**
	 * Parse the template file and return it as string.
	 *
	 * @param array $arrAttributes An optional attributes array
	 *
	 * @return string The template markup
	 */
	public function parse($arrAttributes = null): string
	{
		$request = $this->getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && $this->getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->title = $this->label;

			return $objTemplate->parse();
		}

		$this->altchaAttributes = new HtmlAttributes();
		$this->altchaAttributes->set('name', $this->name);
		$this->altchaAttributes->set('maxnumber', $this->getContainer()->get('contao.altcha')->getRangeMax());
		$this->altchaAttributes->set('challengeurl', $this->getContainer()->get('router')->generate(AltchaController::class));
		$this->altchaAttributes->set('strings', $this->getLocalization());
		$this->altchaAttributes->setIfExists('auto', $this->altchaAuto);
		$this->altchaAttributes->setIfExists('hidelogo', $this->altchaHideLogo);
		$this->altchaAttributes->setIfExists('hidefooter', $this->altchaHideFooter);

		return parent::parse($arrAttributes);
	}

	/**
	 * @param mixed $varInput
	 */
	protected function validator($varInput): mixed
	{
		$altcha = $this->getContainer()->get('contao.altcha');

		if (!$varInput || !$altcha->validate($varInput))
		{
			$this->addError($GLOBALS['TL_LANG']['ERR']['altchaVerificationFailed']);
		}

		return $varInput;
	}

	protected function getLocalization(): string
	{
		return StringUtil::specialchars(json_encode(array(
			'error' => $GLOBALS['TL_LANG']['ERR']['altchaWidgetError'],
			'footer' => $GLOBALS['TL_LANG']['MSC']['altchaFooter'],
			'label' => $GLOBALS['TL_LANG']['MSC']['altchaLabel'],
			'verified' => $GLOBALS['TL_LANG']['MSC']['altchaVerified'],
			'verifying' => $GLOBALS['TL_LANG']['MSC']['altchaVerifying'],
			'waitAlert' => $GLOBALS['TL_LANG']['MSC']['altchaWaitAlert'],
		)));
	}
}
