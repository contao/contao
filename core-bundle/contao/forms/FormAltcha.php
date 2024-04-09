<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Altcha\Altcha;
use Contao\CoreBundle\Controller\AltchaController;
use Symfony\Component\Routing\RouterInterface;

class FormAltcha extends Widget
{
	/**
	 * Use raw request data
	 *
	 * @var bool
	 */
	protected $useRawRequestData = true;

	/**
	 * Do not submit user input
	 *
	 * @var boolean
	 */
	protected $blnSubmitInput = false;

	/**
	 * Add a for attribute
	 *
	 * @var boolean
	 */
	protected $blnForAttribute = true;

	/**
	 * Template
	 *
	 * @var string
	 */
	protected $strTemplate = 'form_altcha';

	/**
	 * The CSS class prefix
	 *
	 * @var string
	 */
	protected $prefix = 'widget widget-altcha';

	/**
	 * The ALTCHA attributes as a string
	 *
	 * @var string
	 */
	protected string $strAltchaAttributes = '';

	/**
	 * ALTCHA mode: "onload" or "onsubmit"
	 */
	protected string $altchaAuto = '';

	/**
	 * Hide the ALTCHA logo.
	 */
	protected bool $altchaHideLogo;

	/**
	 * Hide the ALTCHA footer.
	 */
	protected bool $altchaHideFooter;

	/**
	 * ALTCHA max number
	 */
	protected int $altchaMaxNumber = 10000000;

	/**
	 * Add specific attributes.
	 *
	 * @param string $strKey   The attribute key
	 * @param mixed  $varValue The attribute value
	 */
	public function __set($strKey, $varValue): void
	{
		switch ($strKey)
		{
			case 'minlength':
			case 'maxlength':
			case 'minval':
			case 'maxval':
				// Ignore
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

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
			return $this->getAltchaAttributesAsString();
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
		return sprintf(
			'<altcha-widget %s></altcha-widget>',
			$this->getAltchaAttributesAsString(),
		);
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

		$this->strAltchaAttributes = $this->getAltchaAttributesAsString();

		return parent::parse($arrAttributes);
	}

	protected function getAltchaAttributesAsArray(): array
	{
		/** @var RouterInterface $router */
		$router = $this->getContainer()->get('router');

		$challengeUrl = sprintf('challengeurl="%s"', $router->generate(AltchaController::class));

		$attributes = array();
		$attributes[] = $challengeUrl;

		$attributes[] = sprintf('name="%s"', $this->name);

		if (!empty($this->altchaAuto) && \in_array($this->altchaAuto, array('onload', 'onsubmit'), true))
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

		$attributes[] = sprintf('maxnumber="%d"', $this->altchaMaxNumber);

		$localization = StringUtil::specialchars(json_encode($this->getLocalization()));
		$attributes[] = sprintf('strings="%s"', $localization);

		return $attributes;
	}

	protected function getAltchaAttributesAsString(): string
	{
		return implode(' ', $this->getAltchaAttributesAsArray());
	}

	/**
	 * @param mixed $varInput
	 */
	protected function validator($varInput): mixed
	{
		/** @var Altcha $altcha */
		$altcha = $this->getContainer()->get('contao.altcha.altcha');

		if (!$varInput || !$altcha->validate($varInput))
		{
			$this->addError($GLOBALS['TL_LANG']['ERR']['altchaVerificationFailed']);
		}

		return $varInput;
	}

	protected function getLocalization(): array
	{
		return array(
			'error'     => $GLOBALS['TL_LANG']['ERR']['altchaWidgetError'],
			'footer'    => $GLOBALS['TL_LANG']['MSC']['altchaFooter'],
			'label'     => $GLOBALS['TL_LANG']['MSC']['altchaLabel'],
			'verified'  => $GLOBALS['TL_LANG']['MSC']['altchaVerified'],
			'verifying' => $GLOBALS['TL_LANG']['MSC']['altchaVerifying'],
			'waitAlert' => $GLOBALS['TL_LANG']['MSC']['altchaWaitAlert'],
		);
	}
}
