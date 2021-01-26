<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\HttpFoundation\Response;

/**
 * Provide methods to handle back end templates.
 *
 * @property string $ua
 * @property array  $javascripts
 * @property array  $stylesheets
 * @property string $mootools
 * @property string $attributes
 * @property string $badgeTitle
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BackendTemplate extends Template
{
	/**
	 * Add a hook to modify the template output
	 *
	 * @return string
	 */
	public function parse()
	{
		$strBuffer = parent::parse();

		// HOOK: add custom parse filters
		if (isset($GLOBALS['TL_HOOKS']['parseBackendTemplate']) && \is_array($GLOBALS['TL_HOOKS']['parseBackendTemplate']))
		{
			foreach ($GLOBALS['TL_HOOKS']['parseBackendTemplate'] as $callback)
			{
				$this->import($callback[0]);
				$strBuffer = $this->{$callback[0]}->{$callback[1]}($strBuffer, $this->strTemplate);
			}
		}

		return $strBuffer;
	}

	/**
	 * Return a response object
	 *
	 * @return Response The response object
	 */
	public function getResponse()
	{
		$response = parent::getResponse();
		$response->headers->set('Cache-Control', 'no-cache, no-store');

		return $response->setPrivate();
	}

	/**
	 * Compile the template
	 *
	 * @internal Do not call this method in your code. It will be made private in Contao 5.0.
	 */
	protected function compile()
	{
		// User agent class (see #3074 and #6277)
		$this->ua = Environment::get('agent')->class;

		if (Config::get('fullscreen'))
		{
			$this->ua .= ' fullscreen';
		}

		$this->addBackendConfig();

		// Style sheets
		// TODO: This should become deprecated and be handled in EntrypointLookup::getCssFiles()
		if (!empty($GLOBALS['TL_CSS']) && \is_array($GLOBALS['TL_CSS']))
		{
			$strStyleSheets = '';
			$objCombiner = new Combiner();

			foreach (array_unique($GLOBALS['TL_CSS']) as $stylesheet)
			{
				$options = StringUtil::resolveFlaggedUrl($stylesheet);

				if ($options->static)
				{
					$objCombiner->add($stylesheet, $options->mtime, $options->media);
				}
				else
				{
					$strStyleSheets .= Template::generateStyleTag($this->addStaticUrlTo($stylesheet), $options->media, $options->mtime);
				}
			}

			if ($objCombiner->hasEntries())
			{
				$strStyleSheets = Template::generateStyleTag($objCombiner->getCombinedFile(), 'all') . $strStyleSheets;
			}

			$this->stylesheets .= $strStyleSheets;
		}

		// JavaScripts
		// TODO: This should become deprecated and be handled in EntrypointLookup::getJavaScriptFiles()
		if (!empty($GLOBALS['TL_JAVASCRIPT']) && \is_array($GLOBALS['TL_JAVASCRIPT']))
		{
			$objCombiner = new Combiner();
			$objCombinerAsync = new Combiner();
			$strJavaScripts = '';

			foreach (array_unique($GLOBALS['TL_JAVASCRIPT']) as $javascript)
			{
				$options = StringUtil::resolveFlaggedUrl($javascript);

				if ($options->static)
				{
					$options->async ? $objCombinerAsync->add($javascript, $options->mtime) : $objCombiner->add($javascript, $options->mtime);
				}
				else
				{
					$strJavaScripts .= Template::generateScriptTag($this->addStaticUrlTo($javascript), $options->async, $options->mtime);
				}
			}

			if ($objCombiner->hasEntries())
			{
				$strJavaScripts = Template::generateScriptTag($objCombiner->getCombinedFile()) . $strJavaScripts;
			}

			if ($objCombinerAsync->hasEntries())
			{
				$strJavaScripts = Template::generateScriptTag($objCombinerAsync->getCombinedFile(), true) . $strJavaScripts;
			}

			$this->javascripts .= $strJavaScripts;
		}

		// MooTools scripts (added at the page bottom)
		// TODO: This should become deprecated and removed
		if (!empty($GLOBALS['TL_MOOTOOLS']) && \is_array($GLOBALS['TL_MOOTOOLS']))
		{
			$strMootools = '';

			foreach (array_unique($GLOBALS['TL_MOOTOOLS']) as $script)
			{
				$strMootools .= $script;
			}

			$this->mootools .= $strMootools;
		}

		$strBuffer = $this->parse();
		$strBuffer = static::replaceOldBePaths($strBuffer);

		// HOOK: add custom output filter
		if (isset($GLOBALS['TL_HOOKS']['outputBackendTemplate']) && \is_array($GLOBALS['TL_HOOKS']['outputBackendTemplate']))
		{
			foreach ($GLOBALS['TL_HOOKS']['outputBackendTemplate'] as $callback)
			{
				$this->import($callback[0]);
				$strBuffer = $this->{$callback[0]}->{$callback[1]}($strBuffer, $this->strTemplate);
			}
		}

		$this->strBuffer = $strBuffer;

		parent::compile();
	}

	/**
	 * Return the locale string
	 *
	 * @return string
	 *
	 * @internal Soon to be deprecated
	 */
	public function getLocaleString()
	{
		$container = System::getContainer();

		return
			'var Contao={'
				. 'theme:"' . Backend::getTheme() . '",'
				. 'lang:{'
					. 'close:"' . $GLOBALS['TL_LANG']['MSC']['close'] . '",'
					. 'collapse:"' . $GLOBALS['TL_LANG']['MSC']['collapseNode'] . '",'
					. 'expand:"' . $GLOBALS['TL_LANG']['MSC']['expandNode'] . '",'
					. 'loading:"' . $GLOBALS['TL_LANG']['MSC']['loadingData'] . '",'
					. 'apply:"' . $GLOBALS['TL_LANG']['MSC']['apply'] . '"'
				. '},'
				. 'script_url:"' . $container->get('contao.assets.assets_context')->getStaticUrl() . '",'
				. 'path:"' . Environment::get('path') . '",'
				. 'routes:{'
					. 'backend_picker:"' . $container->get('router')->generate('contao_backend_picker') . '"'
				. '},'
				. 'request_token:"' . REQUEST_TOKEN . '",'
				. 'referer_id:"' . $container->get('request_stack')->getCurrentRequest()->attributes->get('_contao_referer_id') . '"'
			. '};';
	}

	/**
	 * Return the datepicker string
	 *
	 * Fix the MooTools more parsers which incorrectly parse ISO-8601 and do
	 * not handle German date formats at all.
	 *
	 * @return string
	 *
	 * @internal Soon to be deprecated
	 */
	public function getDateString()
	{
		return
			'Locale.define("en-US","Date",{'
				. 'months:["' . implode('","', $GLOBALS['TL_LANG']['MONTHS']) . '"],'
				. 'days:["' . implode('","', $GLOBALS['TL_LANG']['DAYS']) . '"],'
				. 'months_abbr:["' . implode('","', $GLOBALS['TL_LANG']['MONTHS_SHORT']) . '"],'
				. 'days_abbr:["' . implode('","', $GLOBALS['TL_LANG']['DAYS_SHORT']) . '"]'
			. '});'
			. 'Locale.define("en-US","DatePicker",{'
				. 'select_a_time:"' . $GLOBALS['TL_LANG']['DP']['select_a_time'] . '",'
				. 'use_mouse_wheel:"' . $GLOBALS['TL_LANG']['DP']['use_mouse_wheel'] . '",'
				. 'time_confirm_button:"' . $GLOBALS['TL_LANG']['DP']['time_confirm_button'] . '",'
				. 'apply_range:"' . $GLOBALS['TL_LANG']['DP']['apply_range'] . '",'
				. 'cancel:"' . $GLOBALS['TL_LANG']['DP']['cancel'] . '",'
				. 'week:"' . $GLOBALS['TL_LANG']['DP']['week'] . '"'
			. '});';
	}

	/**
	 * Add the contao.backend configuration
	 */
	private function addBackendConfig(): void
	{
		$container = System::getContainer();

		if (!$container->hasParameter('contao.backend'))
		{
			return;
		}

		$backendConfig = $container->getParameter('contao.backend');

		if (!empty($backendConfig['attributes']) && \is_array($backendConfig['attributes']))
		{
			$this->attributes = ' ' . implode(' ', array_map(
				static function ($v, $k) { return sprintf('%s="%s"', $k, $v); },
				$backendConfig['attributes'],
				array_keys($backendConfig['attributes'])
			));
		}

		// TODO move this to the Encore TagRenderer
		if (!empty($backendConfig['custom_css']) && \is_array($backendConfig['custom_css']))
		{
			if (!\is_array($GLOBALS['TL_CSS']))
			{
				$GLOBALS['TL_CSS'] = array();
			}

			$GLOBALS['TL_CSS'] = array_merge($GLOBALS['TL_CSS'], $backendConfig['custom_css']);
		}

		// TODO move this to the Encore TagRenderer
		if (!empty($backendConfig['custom_js']) && \is_array($backendConfig['custom_js']))
		{
			if (!\is_array($GLOBALS['TL_JAVASCRIPT']))
			{
				$GLOBALS['TL_JAVASCRIPT'] = array();
			}

			$GLOBALS['TL_JAVASCRIPT'] = array_merge($GLOBALS['TL_JAVASCRIPT'], $backendConfig['custom_js']);
		}

		$this->badgeTitle = $backendConfig['badge_title'];
	}
}

class_alias(BackendTemplate::class, 'BackendTemplate');
