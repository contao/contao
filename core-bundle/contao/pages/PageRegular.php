<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\NoLayoutSpecifiedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Util\LocaleUtil;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provide methods to handle a regular front end page.
 */
class PageRegular extends Frontend
{
	/**
	 * @var Template
	 */
	public $Template;

	/**
	 * @var ResponseContext
	 */
	protected $responseContext;

	/**
	 * Return a response object
	 *
	 * @param PageModel $objPage
	 * @param boolean   $blnCheckRequest
	 *
	 * @return Response
	 */
	public function getResponse($objPage, $blnCheckRequest=false)
	{
		$this->prepare($objPage);

		$response = $this->Template->getResponse($blnCheckRequest);

		return $response;
	}

	/**
	 * Generate a regular page
	 *
	 * @param PageModel $objPage
	 */
	private function prepare($objPage)
	{
		// Deprecated since Contao 4.0, to be removed in Contao 6.0
		$GLOBALS['TL_LANGUAGE'] = LocaleUtil::formatAsLanguageTag($objPage->language);

		$locale = LocaleUtil::formatAsLocale($objPage->language);

		$container = System::getContainer();
		$container->get('translator')->setLocale($locale);

		$request = $container->get('request_stack')->getCurrentRequest();
		$request->setLocale($locale);

		$this->responseContext = $container->get('contao.routing.response_context_factory')->createContaoWebpageResponseContext($objPage);
		$blnShowUnpublished = $container->get('contao.security.token_checker')->isPreviewMode();

		System::loadLanguageFile('default');

		// Get the page layout
		$objLayout = $this->getPageLayout($objPage);

		/** @var ThemeModel $objTheme */
		$objTheme = $objLayout->getRelated('pid');

		// Set the default image densities
		$container->get('contao.image.picture_factory')->setDefaultDensities($objLayout->defaultImageDensities);
		$container->get('contao.image.preview_factory')->setDefaultDensities($objLayout->defaultImageDensities);

		// Store the layout ID
		$objPage->layoutId = $objLayout->id;

		// Set the layout template and template group
		$objPage->template = $objLayout->template ?: 'fe_page';
		$objPage->templateGroup = $objTheme->templates ?? null;

		// Minify the markup
		$objPage->minifyMarkup = $objLayout->minifyMarkup;

		// Initialize the template
		$this->createTemplate($objPage, $objLayout);

		// Initialize modules and sections
		$arrCustomSections = array();
		$arrSections = array('header', 'left', 'right', 'main', 'footer');
		$arrModules = StringUtil::deserialize($objLayout->modules);
		$arrModuleIds = array();

		// Filter the disabled modules
		foreach ($arrModules as $module)
		{
			if ($module['enable'] ?? null)
			{
				$arrModuleIds[] = (int) $module['mod'];
			}
		}

		// Get all modules in a single DB query
		$objModules = ModuleModel::findMultipleByIds($arrModuleIds);

		if ($objModules !== null || \in_array(0, $arrModuleIds, true))
		{
			$arrMapper = array();

			// Create a mapper array in case a module is included more than once (see #4849)
			if ($objModules !== null)
			{
				while ($objModules->next())
				{
					$arrMapper[$objModules->id] = $objModules->current();
				}
			}

			foreach ($arrModules as $arrModule)
			{
				// Disabled module
				if (!$blnShowUnpublished && !($arrModule['enable'] ?? null))
				{
					continue;
				}

				// Replace the module ID with the module model
				if ($arrModule['mod'] > 0 && isset($arrMapper[$arrModule['mod']]))
				{
					$arrModule['mod'] = $arrMapper[$arrModule['mod']];
				}

				// Generate the modules
				if (\in_array($arrModule['col'], $arrSections))
				{
					// Filter active sections (see #3273)
					if ($objLayout->rows != '2rwh' && $objLayout->rows != '3rw' && $arrModule['col'] == 'header')
					{
						continue;
					}

					if ($objLayout->cols != '2cll' && $objLayout->cols != '3cl' && $arrModule['col'] == 'left')
					{
						continue;
					}

					if ($objLayout->cols != '2clr' && $objLayout->cols != '3cl' && $arrModule['col'] == 'right')
					{
						continue;
					}

					if ($objLayout->rows != '2rwf' && $objLayout->rows != '3rw' && $arrModule['col'] == 'footer')
					{
						continue;
					}

					$this->Template->{$arrModule['col']} .= $this->getFrontendModule($arrModule['mod'], $arrModule['col']);
				}
				else
				{
					if (!isset($arrCustomSections[$arrModule['col']]))
					{
						$arrCustomSections[$arrModule['col']] = '';
					}

					$arrCustomSections[$arrModule['col']] .= $this->getFrontendModule($arrModule['mod'], $arrModule['col']);
				}
			}
		}

		$this->Template->sections = $arrCustomSections;

		// Mark RTL languages (see #7171, #3360)
		if ((\ResourceBundle::create($locale, 'ICUDATA', true)['layout']['characters'] ?? null) == 'right-to-left')
		{
			$this->Template->isRTL = true;
		}

		// HOOK: modify the page or layout object
		if (isset($GLOBALS['TL_HOOKS']['generatePage']) && \is_array($GLOBALS['TL_HOOKS']['generatePage']))
		{
			foreach ($GLOBALS['TL_HOOKS']['generatePage'] as $callback)
			{
				System::importStatic($callback[0])->{$callback[1]}($objPage, $objLayout, $this);
			}
		}

		$headBag = $this->responseContext->get(HtmlHeadBag::class);

		// Set the page title and description AFTER the modules have been generated
		$this->Template->mainTitle = $objPage->rootPageTitle;
		$this->Template->pageTitle = htmlspecialchars($headBag->getTitle());

		// Remove shy-entities (see #2709)
		$this->Template->mainTitle = str_replace('[-]', '', $this->Template->mainTitle);
		$this->Template->pageTitle = str_replace('[-]', '', $this->Template->pageTitle);

		// Meta robots tag
		$this->Template->robots = htmlspecialchars($headBag->getMetaRobots());

		// Canonical
		if ($objPage->enableCanonical)
		{
			$this->Template->canonical = htmlspecialchars($headBag->getCanonicalUriForRequest($request));
		}

		// Fall back to the default title tag
		if (!$objLayout->titleTag)
		{
			$objLayout->titleTag = '{{page::pageTitle}} - {{page::rootPageTitle}}';
		}

		// Assign the title and description
		$this->Template->title = strip_tags(System::getContainer()->get('contao.insert_tag.parser')->replaceInline($objLayout->titleTag));
		$this->Template->description = htmlspecialchars($headBag->getMetaDescription());

		// Body onload and body classes
		$this->Template->onload = trim($objLayout->onload);
		$this->Template->class = trim($objLayout->cssClass . ' ' . $objPage->cssClass);

		// Execute AFTER the modules have been generated and create footer scripts first
		$this->createFooterScripts($objPage, $objLayout);
		$this->createHeaderScripts($objPage, $objLayout);
	}

	/**
	 * Get a page layout and return it as database result object
	 *
	 * @param PageModel $objPage
	 *
	 * @return LayoutModel
	 */
	protected function getPageLayout($objPage)
	{
		$objLayout = LayoutModel::findByPk($objPage->layout);

		// Die if there is no layout
		if (null === $objLayout)
		{
			System::getContainer()->get('monolog.logger.contao.error')->error('Could not find layout ID "' . $objPage->layout . '"');

			throw new NoLayoutSpecifiedException('No layout specified');
		}

		$objPage->hasJQuery = $objLayout->addJQuery;
		$objPage->hasMooTools = $objLayout->addMooTools;

		// HOOK: modify the page or layout object (see #4736)
		if (isset($GLOBALS['TL_HOOKS']['getPageLayout']) && \is_array($GLOBALS['TL_HOOKS']['getPageLayout']))
		{
			foreach ($GLOBALS['TL_HOOKS']['getPageLayout'] as $callback)
			{
				System::importStatic($callback[0])->{$callback[1]}($objPage, $objLayout, $this);
			}
		}

		return $objLayout;
	}

	/**
	 * Create a new template
	 *
	 * @param PageModel   $objPage
	 * @param LayoutModel $objLayout
	 */
	protected function createTemplate($objPage, $objLayout)
	{
		$this->Template = new FrontendTemplate($objPage->template);
		$this->Template->viewport = '';
		$this->Template->framework = '';

		$arrFramework = StringUtil::deserialize($objLayout->framework);

		// Generate the CSS framework
		if (\is_array($arrFramework) && \in_array('layout.css', $arrFramework))
		{
			$strFramework = '';

			if (\in_array('responsive.css', $arrFramework))
			{
				$this->Template->viewport = '<meta name="viewport" content="width=device-width,initial-scale=1.0">' . "\n";
			}

			// Wrapper
			if ($objLayout->static)
			{
				$arrSize = StringUtil::deserialize($objLayout->width);

				if (isset($arrSize['value']) && $arrSize['value'] && $arrSize['value'] >= 0)
				{
					$arrMargin = array('left'=>'0 auto 0 0', 'center'=>'0 auto', 'right'=>'0 0 0 auto');
					$strFramework .= sprintf('#wrapper{width:%s;margin:%s}', $arrSize['value'] . $arrSize['unit'], $arrMargin[$objLayout->align]);
				}
			}

			// Header
			if ($objLayout->rows == '2rwh' || $objLayout->rows == '3rw')
			{
				$arrSize = StringUtil::deserialize($objLayout->headerHeight);

				if (isset($arrSize['value']) && $arrSize['value'] && $arrSize['value'] >= 0)
				{
					$strFramework .= sprintf('#header{height:%s}', $arrSize['value'] . $arrSize['unit']);
				}
			}

			$strContainer = '';

			// Left column
			if ($objLayout->cols == '2cll' || $objLayout->cols == '3cl')
			{
				$arrSize = StringUtil::deserialize($objLayout->widthLeft);

				if (isset($arrSize['value']) && $arrSize['value'] && $arrSize['value'] >= 0)
				{
					$strFramework .= sprintf('#left{width:%s;right:%s}', $arrSize['value'] . $arrSize['unit'], $arrSize['value'] . $arrSize['unit']);
					$strContainer .= sprintf('padding-left:%s;', $arrSize['value'] . $arrSize['unit']);
				}
			}

			// Right column
			if ($objLayout->cols == '2clr' || $objLayout->cols == '3cl')
			{
				$arrSize = StringUtil::deserialize($objLayout->widthRight);

				if (isset($arrSize['value']) && $arrSize['value'] && $arrSize['value'] >= 0)
				{
					$strFramework .= sprintf('#right{width:%s}', $arrSize['value'] . $arrSize['unit']);
					$strContainer .= sprintf('padding-right:%s;', $arrSize['value'] . $arrSize['unit']);
				}
			}

			// Main column
			if ($strContainer)
			{
				$strFramework .= sprintf('#container{%s}', substr($strContainer, 0, -1));
			}

			// Footer
			if ($objLayout->rows == '2rwf' || $objLayout->rows == '3rw')
			{
				$arrSize = StringUtil::deserialize($objLayout->footerHeight);

				if (isset($arrSize['value']) && $arrSize['value'] && $arrSize['value'] >= 0)
				{
					$strFramework .= sprintf('#footer{height:%s}', $arrSize['value'] . $arrSize['unit']);
				}
			}

			// Add the layout specific CSS
			if ($strFramework)
			{
				$this->Template->framework = Template::generateInlineStyle($strFramework) . "\n";
			}
		}

		// Overwrite the viewport tag (see #6251)
		if ($objLayout->viewport)
		{
			$this->Template->viewport = '<meta name="viewport" content="' . $objLayout->viewport . '">' . "\n";
		}

		$this->Template->mooScripts = '';

		// Make sure TL_JAVASCRIPT exists (see #4890)
		if (isset($GLOBALS['TL_JAVASCRIPT']) && \is_array($GLOBALS['TL_JAVASCRIPT']))
		{
			$arrAppendJs = $GLOBALS['TL_JAVASCRIPT'];
			$GLOBALS['TL_JAVASCRIPT'] = array();
		}
		else
		{
			$arrAppendJs = array();
			$GLOBALS['TL_JAVASCRIPT'] = array();
		}

		// jQuery scripts
		if ($objLayout->addJQuery)
		{
			$GLOBALS['TL_JAVASCRIPT'][] = 'assets/jquery/js/jquery.min.js|static';
		}

		// MooTools scripts
		if ($objLayout->addMooTools)
		{
			$GLOBALS['TL_JAVASCRIPT'][] = 'assets/mootools/js/mootools.min.js|static';
		}

		// Check whether TL_APPEND_JS exists (see #4890)
		if (!empty($arrAppendJs))
		{
			$GLOBALS['TL_JAVASCRIPT'] = array_merge($GLOBALS['TL_JAVASCRIPT'], $arrAppendJs);
		}

		// Initialize the sections
		$this->Template->header = '';
		$this->Template->left = '';
		$this->Template->main = '';
		$this->Template->right = '';
		$this->Template->footer = '';

		// Initialize the custom layout sections
		$this->Template->sections = array();
		$this->Template->positions = array();

		if ($objLayout->sections)
		{
			$arrPositions = array();
			$arrSections = StringUtil::deserialize($objLayout->sections);

			if (!empty($arrSections) && \is_array($arrSections))
			{
				foreach ($arrSections as $v)
				{
					$arrPositions[$v['position']][$v['id']] = $v;
				}
			}

			$this->Template->positions = $arrPositions;
		}

		// Add the check_cookies image and the request token script if needed
		if ($objPage->alwaysLoadFromCache)
		{
			$GLOBALS['TL_BODY'][] = sprintf('<img src="%s" width="1" height="1" class="invisible" alt aria-hidden="true" onload="this.parentNode.removeChild(this)">', System::getContainer()->get('router')->generate('contao_frontend_check_cookies'));
			$GLOBALS['TL_BODY'][] = sprintf('<script src="%s" async></script>', System::getContainer()->get('router')->generate('contao_frontend_request_token_script'));
		}

		// Default settings
		$this->Template->layout = $objLayout;
		$this->Template->language = $GLOBALS['TL_LANGUAGE'];
		$this->Template->charset = System::getContainer()->getParameter('kernel.charset');
		$this->Template->base = Environment::get('base');
		$this->Template->isRTL = false;
	}

	/**
	 * Create all header scripts
	 *
	 * @param PageModel   $objPage
	 * @param LayoutModel $objLayout
	 */
	protected function createHeaderScripts($objPage, $objLayout)
	{
		$strStyleSheets = '';
		$strCcStyleSheets = '';
		$arrFramework = StringUtil::deserialize($objLayout->framework);

		// Add the Contao CSS framework style sheets
		if (\is_array($arrFramework))
		{
			foreach ($arrFramework as $strFile)
			{
				if ($strFile != 'tinymce.css')
				{
					$GLOBALS['TL_FRAMEWORK_CSS'][] = 'assets/contao/css/' . basename($strFile, '.css') . '.min.css';
				}
			}
		}

		// Make sure TL_USER_CSS is set
		if (!isset($GLOBALS['TL_USER_CSS']) || !\is_array($GLOBALS['TL_USER_CSS']))
		{
			$GLOBALS['TL_USER_CSS'] = array();
		}

		$arrExternal = StringUtil::deserialize($objLayout->external);

		// External style sheets
		if (!empty($arrExternal) && \is_array($arrExternal))
		{
			// Get the file entries from the database
			$objFiles = FilesModel::findMultipleByUuids($arrExternal);
			$projectDir = System::getContainer()->getParameter('kernel.project_dir');

			if ($objFiles !== null)
			{
				while ($objFiles->next())
				{
					if (file_exists($projectDir . '/' . $objFiles->path))
					{
						$GLOBALS['TL_USER_CSS'][] = $objFiles->path . '|static';
					}
				}
			}
		}

		$nonce = ContaoFramework::getNonce();

		// Add a placeholder for dynamic style sheets (see #4203)
		$strStyleSheets .= "[[TL_CSS_$nonce]]";

		// Always add conditional style sheets at the end
		$strStyleSheets .= $strCcStyleSheets;

		// Add a placeholder for dynamic <head> tags (see #4203)
		$strHeadTags = "[[TL_HEAD_$nonce]]";

		// Add the analytics scripts
		if ($objLayout->analytics)
		{
			$arrAnalytics = StringUtil::deserialize($objLayout->analytics, true);

			foreach ($arrAnalytics as $strTemplate)
			{
				if ($strTemplate)
				{
					$objTemplate = new FrontendTemplate($strTemplate);
					$strHeadTags .= $objTemplate->parse();
				}
			}
		}

		// Add the user <head> tags
		if ($strHead = trim($objLayout->head ?? ''))
		{
			$strHeadTags .= $strHead . "\n";
		}

		$this->Template->stylesheets = $strStyleSheets;
		$this->Template->head = $strHeadTags;
	}

	/**
	 * Create all footer scripts
	 *
	 * @param PageModel   $objPage
	 * @param LayoutModel $objLayout
	 */
	protected function createFooterScripts($objPage, $objLayout)
	{
		$strScripts = '';
		$nonce = ContaoFramework::getNonce();

		// jQuery
		if ($objLayout->addJQuery)
		{
			$arrJquery = StringUtil::deserialize($objLayout->jquery, true);

			foreach ($arrJquery as $strTemplate)
			{
				if ($strTemplate)
				{
					$objTemplate = new FrontendTemplate($strTemplate);
					$strScripts .= $objTemplate->parse();
				}
			}

			// Add a placeholder for dynamic scripts (see #4203)
			$strScripts .= "[[TL_JQUERY_$nonce]]";
		}

		// MooTools
		if ($objLayout->addMooTools)
		{
			$arrMootools = StringUtil::deserialize($objLayout->mootools, true);

			foreach ($arrMootools as $strTemplate)
			{
				if ($strTemplate)
				{
					$objTemplate = new FrontendTemplate($strTemplate);
					$strScripts .= $objTemplate->parse();
				}
			}

			// Add a placeholder for dynamic scripts (see #4203)
			$strScripts .= "[[TL_MOOTOOLS_$nonce]]";
		}

		// Add the framework-agnostic JavaScripts
		if ($objLayout->scripts)
		{
			$arrScripts = StringUtil::deserialize($objLayout->scripts, true);

			foreach ($arrScripts as $strTemplate)
			{
				if ($strTemplate)
				{
					$objTemplate = new FrontendTemplate($strTemplate);
					$strScripts .= $objTemplate->parse();
				}
			}
		}

		// Add a placeholder for dynamic scripts (see #4203, #5583)
		$strScripts .= "[[TL_BODY_$nonce]]";

		// Add the external JavaScripts
		$arrExternalJs = StringUtil::deserialize($objLayout->externalJs);

		// Get the file entries from the database
		$objFiles = FilesModel::findMultipleByUuids($arrExternalJs);
		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		if ($objFiles !== null)
		{
			while ($objFiles->next())
			{
				if (file_exists($projectDir . '/' . $objFiles->path))
				{
					$strScripts .= Template::generateScriptTag($objFiles->path, false, null);
				}
			}
		}

		// Add the custom JavaScript
		if ($objLayout->script)
		{
			$customScript = trim($objLayout->script);

			if ($nonce = $this->Template->nonce('script-src'))
			{
				$customScript = str_replace('<script', '<script nonce="' . $nonce . '"', $customScript);
			}

			$strScripts .= "\n" . $customScript . "\n";
		}

		$this->Template->mootools = $strScripts;

		$this->Template->jsonLdScripts = function () {
			if (!$this->responseContext->isInitialized(JsonLdManager::class))
			{
				return '';
			}

			/** @var JsonLdManager $jsonLdManager */
			$jsonLdManager = $this->responseContext->get(JsonLdManager::class);

			return $jsonLdManager->collectFinalScriptFromGraphs();
		};
	}
}
