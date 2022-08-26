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
 * @internal
 */
trait BackendTemplateTrait
{
	/**
	 * Return the locale string
	 *
	 * @return string
	 */
	protected function getLocaleString()
	{
		$container = System::getContainer();

		return
			'var Contao={'
				. 'theme:"' . Backend::getTheme() . '",'
				. 'lang:{'
					. 'close:"' . $GLOBALS['TL_LANG']['MSC']['close'] . '",'
					. 'cancel:"' . $GLOBALS['TL_LANG']['MSC']['cancelBT'] . '",'
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
	 */
	protected function getDateString()
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
}
