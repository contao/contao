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
use Symfony\Component\Routing\RouterInterface;

class FormAltcha extends Widget
{
	/**
	 * @var bool
	 */
	protected $useRawRequestData = true;

	/**
	 * @var boolean
	 */
	protected $blnSubmitInput = false;

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

	protected string $strAltchaAttributes = '';
	protected string $altchaAuto = '';
	protected bool $altchaHideLogo = false;
	protected bool $altchaHideFooter = false;
	protected int $altchaMaxNumber = 10000000;

	/**
	 * Return a parameter.
	 *
	 * @param string $strKey The parameter name
	 *
	 * @return mixed The parameter value
	 */
	public function __get($strKey)
	{
		if ('altchaAttributes' === $strKey)
		{
			return $this->getAltchaAttributes();
		}

		return parent::__get($strKey);
	}

	/**
	 * Generate the widget and return it as string.
	 *
	 * @return string The widget markup
	 */
	public function generate(): string
	{
		return sprintf('<altcha-widget %s></altcha-widget>', $this->getAltchaAttributes());
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

		$this->strAltchaAttributes = $this->getAltchaAttributes();

		// TODO: Use ResponseContext, once it supports appending to <head>
		$GLOBALS['TL_HEAD'][] = '<script async defer src="' . $this->asset('js/altcha.min.js', 'contao-components/altcha') . '" type="module"></script>';

		return parent::parse($arrAttributes);
	}

	protected function getAltchaAttributes(): string
	{
		/** @var RouterInterface $router */
		$router = $this->getContainer()->get('router');

		$attributes = array();
		$attributes[] = sprintf('challengeurl="%s"', $router->generate(AltchaController::class));
		$attributes[] = sprintf('name="%s"', $this->name);

		if (\in_array($this->altchaAuto, array('onfocus', 'onload', 'onsubmit'), true))
		{
			$attributes[] = sprintf('auto="%s"', StringUtil::specialchars($this->altchaAuto));
		}

		if ($this->altchaHideLogo)
		{
			$attributes[] = 'hidelogo';
		}

		if ($this->altchaHideFooter)
		{
			$attributes[] = 'hidefooter';
		}

		$attributes[] = sprintf('strings="%s"', $this->getLocalization());

		return implode(' ', $attributes);
	}

	/**
	 * @param mixed $varInput
	 */
	protected function validator($varInput): mixed
	{
		$altcha = $this->getContainer()->get('contao.altcha.altcha');

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
