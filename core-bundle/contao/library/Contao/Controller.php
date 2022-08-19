<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\AjaxRedirectResponseException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\Database\Result;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Glob;

/**
 * Abstract parent class for Controllers
 *
 * Some of the methods have been made static in Contao 3 and can be used in
 * non-object context as well.
 *
 * Usage:
 *
 *     echo Controller::getTheme();
 *
 * Inside a controller:
 *
 *     public function generate()
 *     {
 *         return $this->getArticle(2);
 *     }
 */
abstract class Controller extends System
{
	/**
	 * @var array
	 */
	protected static $arrQueryCache = array();

	/**
	 * Find a particular template file and return its path
	 *
	 * @param string $strTemplate The name of the template
	 *
	 * @return string The path to the template file
	 *
	 * @throws \RuntimeException If the template group folder is insecure
	 */
	public static function getTemplate($strTemplate)
	{
		$strTemplate = basename($strTemplate);
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		// Check for a theme folder
		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($request))
		{
			/** @var PageModel|null $objPage */
			global $objPage;

			if ($objPage->templateGroup ?? null)
			{
				if (Validator::isInsecurePath($objPage->templateGroup))
				{
					throw new \RuntimeException('Invalid path ' . $objPage->templateGroup);
				}

				return TemplateLoader::getPath($strTemplate, 'html5', $objPage->templateGroup);
			}
		}

		return TemplateLoader::getPath($strTemplate, 'html5');
	}

	/**
	 * Return all template files of a particular group as array
	 *
	 * @param string $strPrefix           The template name prefix (e.g. "ce_")
	 * @param array  $arrAdditionalMapper An additional mapper array
	 * @param string $strDefaultTemplate  An optional default template
	 *
	 * @return array An array of template names
	 */
	public static function getTemplateGroup($strPrefix, array $arrAdditionalMapper=array(), $strDefaultTemplate='')
	{
		$arrTemplates = array();
		$arrBundleTemplates = array();

		$arrMapper = array_merge
		(
			$arrAdditionalMapper,
			array
			(
				'ce' => array_keys(array_merge(...array_values($GLOBALS['TL_CTE']))),
				'form' => array_keys($GLOBALS['TL_FFL']),
				'mod' => array_keys(array_merge(...array_values($GLOBALS['FE_MOD']))),
			)
		);

		// Add templates that are not directly associated with a form field
		$arrMapper['form'][] = 'row';
		$arrMapper['form'][] = 'row_double';
		$arrMapper['form'][] = 'xml';
		$arrMapper['form'][] = 'wrapper';
		$arrMapper['form'][] = 'message';

		// Add templates that are not directly associated with a module
		$arrMapper['mod'][] = 'article';
		$arrMapper['mod'][] = 'message';

		/** @var TemplateHierarchyInterface $templateHierarchy */
		$templateHierarchy = System::getContainer()->get('contao.twig.filesystem_loader');
		$identifierPattern = sprintf('/^%s%s/', preg_quote($strPrefix, '/'), substr($strPrefix, -1) !== '_' ? '($|_)' : '');

		$prefixedFiles = array_merge(
			array_filter(
				array_keys($templateHierarchy->getInheritanceChains()),
				static fn (string $identifier): bool => 1 === preg_match($identifierPattern, $identifier),
			),
			// Merge with the templates from the TemplateLoader for backwards
			// compatibility in case someone has added templates manually
			TemplateLoader::getPrefixedFiles($strPrefix),
		);

		foreach ($prefixedFiles as $strTemplate)
		{
			if ($strTemplate != $strPrefix)
			{
				list($k, $strKey) = explode('_', $strTemplate, 2);

				if (isset($arrMapper[$k]) && \in_array($strKey, $arrMapper[$k]))
				{
					$arrBundleTemplates[] = $strTemplate;
					continue;
				}
			}

			$arrTemplates[$strTemplate][] = 'root';
		}

		$strGlobPrefix = $strPrefix;

		$projectDir = System::getContainer()->getParameter('kernel.project_dir');
		$arrCustomized = self::braceGlob($projectDir . '/templates/' . $strGlobPrefix . '*.html5');

		// Add the customized templates
		if (!empty($arrCustomized) && \is_array($arrCustomized))
		{
			$blnIsGroupPrefix = preg_match('/^[a-z]+_$/', $strPrefix);

			foreach ($arrCustomized as $strFile)
			{
				$strTemplate = basename($strFile, strrchr($strFile, '.'));

				if (strpos($strTemplate, '-') !== false)
				{
					throw new \RuntimeException(sprintf('Using hyphens in the template name "%s" is not allowed, use snake_case instead.', $strTemplate));
				}

				// Ignore bundle templates, e.g. mod_article and mod_article_list
				if (\in_array($strTemplate, $arrBundleTemplates))
				{
					continue;
				}

				// Also ignore custom templates belonging to a different bundle template,
				// e.g. mod_article and mod_article_list_custom
				if (!$blnIsGroupPrefix)
				{
					foreach ($arrBundleTemplates as $strKey)
					{
						if (strpos($strTemplate, $strKey . '_') === 0)
						{
							continue 2;
						}
					}
				}

				$arrTemplates[$strTemplate][] = $GLOBALS['TL_LANG']['MSC']['global'] ?? 'global';
			}
		}

		$arrDefaultPlaces = array();

		if ($strDefaultTemplate)
		{
			$arrDefaultPlaces[] = $GLOBALS['TL_LANG']['MSC']['default'];

			if (file_exists($projectDir . '/templates/' . $strDefaultTemplate . '.html5'))
			{
				$arrDefaultPlaces[] = $GLOBALS['TL_LANG']['MSC']['global'];
			}
		}

		// Do not look for back end templates in theme folders (see #5379)
		if ($strPrefix != 'be_' && $strPrefix != 'mail_')
		{
			// Try to select the themes (see #5210)
			try
			{
				$objTheme = ThemeModel::findAll(array('order'=>'name'));
			}
			catch (\Throwable $e)
			{
				$objTheme = null;
			}

			// Add the theme templates
			if ($objTheme !== null)
			{
				while ($objTheme->next())
				{
					if (!$objTheme->templates)
					{
						continue;
					}

					if ($strDefaultTemplate && file_exists($projectDir . '/' . $objTheme->templates . '/' . $strDefaultTemplate . '.html5'))
					{
						$arrDefaultPlaces[] = $objTheme->name;
					}

					$arrThemeTemplates = self::braceGlob($projectDir . '/' . $objTheme->templates . '/' . $strGlobPrefix . '*.html5');

					if (!empty($arrThemeTemplates) && \is_array($arrThemeTemplates))
					{
						foreach ($arrThemeTemplates as $strFile)
						{
							$strTemplate = basename($strFile, strrchr($strFile, '.'));
							$arrTemplates[$strTemplate][] = $objTheme->name;
						}
					}
				}
			}
		}

		// Show the template sources (see #6875)
		foreach ($arrTemplates as $k=>$v)
		{
			$v = array_filter($v, static function ($a)
			{
				return $a != 'root';
			});

			if (empty($v))
			{
				$arrTemplates[$k] = $k;
			}
			else
			{
				$arrTemplates[$k] = $k . ' (' . implode(', ', $v) . ')';
			}
		}

		// Sort the template names
		ksort($arrTemplates);

		if ($strDefaultTemplate)
		{
			if (!empty($arrDefaultPlaces))
			{
				$strDefaultTemplate .= ' (' . implode(', ', $arrDefaultPlaces) . ')';
			}

			$arrTemplates = array('' => $strDefaultTemplate) + $arrTemplates;
		}

		return $arrTemplates;
	}

	/**
	 * Generate a front end module and return it as string
	 *
	 * @param mixed  $intId     A module ID or a Model object
	 * @param string $strColumn The name of the column
	 *
	 * @return string The module HTML markup
	 */
	public static function getFrontendModule($intId, $strColumn='main')
	{
		if (!\is_object($intId) && !\strlen($intId))
		{
			return '';
		}

		/** @var PageModel $objPage */
		global $objPage;

		// Articles
		if (!\is_object($intId) && $intId == 0)
		{
			// Show a particular article only
			if ($objPage->type == 'regular' && Input::get('articles'))
			{
				list($strSection, $strArticle) = explode(':', Input::get('articles')) + array(null, null);

				if ($strArticle === null)
				{
					$strArticle = $strSection;
					$strSection = 'main';
				}

				if ($strSection == $strColumn)
				{
					$objArticle = ArticleModel::findPublishedByIdOrAliasAndPid($strArticle, $objPage->id);

					// Send a 404 header if there is no published article
					if (null === $objArticle)
					{
						throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
					}

					// Send a 403 header if the article cannot be accessed
					if (!static::isVisibleElement($objArticle))
					{
						throw new AccessDeniedException('Access denied: ' . Environment::get('uri'));
					}

					return static::getArticle($objArticle);
				}
			}

			// HOOK: add custom logic
			if (isset($GLOBALS['TL_HOOKS']['getArticles']) && \is_array($GLOBALS['TL_HOOKS']['getArticles']))
			{
				foreach ($GLOBALS['TL_HOOKS']['getArticles'] as $callback)
				{
					$return = static::importStatic($callback[0])->{$callback[1]}($objPage->id, $strColumn);

					if (\is_string($return))
					{
						return $return;
					}
				}
			}

			// Show all articles (no else block here, see #4740)
			$objArticles = ArticleModel::findPublishedByPidAndColumn($objPage->id, $strColumn);

			if ($objArticles === null)
			{
				return '';
			}

			$return = '';
			$blnMultiMode = ($objArticles->count() > 1);

			while ($objArticles->next())
			{
				$return .= static::getArticle($objArticles->current(), $blnMultiMode, false, $strColumn);
			}

			return $return;
		}

		// Other modules
		if (\is_object($intId))
		{
			$objRow = $intId;
		}
		else
		{
			$objRow = ModuleModel::findByPk($intId);

			if ($objRow === null)
			{
				return '';
			}
		}

		// Check the visibility (see #6311)
		if (!static::isVisibleElement($objRow))
		{
			return '';
		}

		$strClass = Module::findClass($objRow->type);

		// Return if the class does not exist
		if (!class_exists($strClass))
		{
			System::getContainer()->get('monolog.logger.contao.error')->error('Module class "' . $strClass . '" (module "' . $objRow->type . '") does not exist');

			return '';
		}

		$strStopWatchId = 'contao.frontend_module.' . $objRow->type . ' (ID ' . $objRow->id . ')';

		if (System::getContainer()->getParameter('kernel.debug'))
		{
			$objStopwatch = System::getContainer()->get('debug.stopwatch');
			$objStopwatch->start($strStopWatchId, 'contao.layout');
		}

		$objRow->typePrefix = 'mod_';

		/** @var Module $objModule */
		$objModule = new $strClass($objRow, $strColumn);
		$strBuffer = $objModule->generate();

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['getFrontendModule']) && \is_array($GLOBALS['TL_HOOKS']['getFrontendModule']))
		{
			foreach ($GLOBALS['TL_HOOKS']['getFrontendModule'] as $callback)
			{
				$strBuffer = static::importStatic($callback[0])->{$callback[1]}($objRow, $strBuffer, $objModule);
			}
		}

		// Disable indexing if protected
		if ($objModule->protected && !preg_match('/^\s*<!-- indexer::stop/', $strBuffer))
		{
			$strBuffer = "\n<!-- indexer::stop -->" . $strBuffer . "<!-- indexer::continue -->\n";
		}

		if (isset($objStopwatch) && $objStopwatch->isStarted($strStopWatchId))
		{
			$objStopwatch->stop($strStopWatchId);
		}

		return $strBuffer;
	}

	/**
	 * Generate an article and return it as string
	 *
	 * @param mixed   $varId          The article ID or a Model object
	 * @param boolean $blnMultiMode   If true, only teasers will be shown
	 * @param boolean $blnIsInsertTag If true, there will be no page relation
	 * @param string  $strColumn      The name of the column
	 *
	 * @return string|boolean The article HTML markup or false
	 */
	public static function getArticle($varId, $blnMultiMode=false, $blnIsInsertTag=false, $strColumn='main')
	{
		/** @var PageModel $objPage */
		global $objPage;

		if (\is_object($varId))
		{
			$objRow = $varId;
		}
		else
		{
			if (!$varId)
			{
				return '';
			}

			$objRow = ArticleModel::findByIdOrAliasAndPid($varId, (!$blnIsInsertTag ? $objPage->id : null));

			if ($objRow === null)
			{
				return false;
			}
		}

		// Check the visibility (see #6311)
		if (!static::isVisibleElement($objRow))
		{
			return '';
		}

		$objRow->headline = $objRow->title;
		$objRow->multiMode = $blnMultiMode;

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['getArticle']) && \is_array($GLOBALS['TL_HOOKS']['getArticle']))
		{
			foreach ($GLOBALS['TL_HOOKS']['getArticle'] as $callback)
			{
				static::importStatic($callback[0])->{$callback[1]}($objRow);
			}
		}

		$strStopWatchId = 'contao.article (ID ' . $objRow->id . ')';

		if (System::getContainer()->getParameter('kernel.debug'))
		{
			$objStopwatch = System::getContainer()->get('debug.stopwatch');
			$objStopwatch->start($strStopWatchId, 'contao.layout');
		}

		$objArticle = new ModuleArticle($objRow, $strColumn);
		$strBuffer = $objArticle->generate($blnIsInsertTag);

		// Disable indexing if protected
		if ($objArticle->protected && !preg_match('/^\s*<!-- indexer::stop/', $strBuffer))
		{
			$strBuffer = "\n<!-- indexer::stop -->" . $strBuffer . "<!-- indexer::continue -->\n";
		}

		if (isset($objStopwatch) && $objStopwatch->isStarted($strStopWatchId))
		{
			$objStopwatch->stop($strStopWatchId);
		}

		return $strBuffer;
	}

	/**
	 * Generate a content element and return it as string
	 *
	 * @param mixed  $intId     A content element ID or a Model object
	 * @param string $strColumn The column the element is in
	 *
	 * @return string The content element HTML markup
	 */
	public static function getContentElement($intId, $strColumn='main')
	{
		if (\is_object($intId))
		{
			$objRow = $intId;
		}
		else
		{
			if ($intId < 1 || !\strlen($intId))
			{
				return '';
			}

			$objRow = ContentModel::findByPk($intId);

			if ($objRow === null)
			{
				return '';
			}
		}

		// Check the visibility (see #6311)
		if (!static::isVisibleElement($objRow))
		{
			return '';
		}

		$strClass = ContentElement::findClass($objRow->type);

		// Return if the class does not exist
		if (!class_exists($strClass))
		{
			System::getContainer()->get('monolog.logger.contao.error')->error('Content element class "' . $strClass . '" (content element "' . $objRow->type . '") does not exist');

			return '';
		}

		$objRow->typePrefix = 'ce_';
		$strStopWatchId = 'contao.content_element.' . $objRow->type . ' (ID ' . $objRow->id . ')';

		if ($objRow->type != 'module' && System::getContainer()->getParameter('kernel.debug'))
		{
			$objStopwatch = System::getContainer()->get('debug.stopwatch');
			$objStopwatch->start($strStopWatchId, 'contao.layout');
		}

		/** @var ContentElement $objElement */
		$objElement = new $strClass($objRow, $strColumn);
		$strBuffer = $objElement->generate();

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['getContentElement']) && \is_array($GLOBALS['TL_HOOKS']['getContentElement']))
		{
			foreach ($GLOBALS['TL_HOOKS']['getContentElement'] as $callback)
			{
				$strBuffer = static::importStatic($callback[0])->{$callback[1]}($objRow, $strBuffer, $objElement);
			}
		}

		// Disable indexing if protected
		if ($objElement->protected && !preg_match('/^\s*<!-- indexer::stop/', $strBuffer))
		{
			$strBuffer = "\n<!-- indexer::stop -->" . $strBuffer . "<!-- indexer::continue -->\n";
		}

		if (isset($objStopwatch) && $objStopwatch->isStarted($strStopWatchId))
		{
			$objStopwatch->stop($strStopWatchId);
		}

		return $strBuffer;
	}

	/**
	 * Generate a form and return it as string
	 *
	 * @param mixed   $varId     A form ID or a Model object
	 * @param string  $strColumn The column the form is in
	 * @param boolean $blnModule Render the form as module
	 *
	 * @return string The form HTML markup
	 */
	public static function getForm($varId, $strColumn='main', $blnModule=false)
	{
		if (\is_object($varId))
		{
			$objRow = $varId;
		}
		else
		{
			if (!$varId)
			{
				return '';
			}

			$objRow = FormModel::findByIdOrAlias($varId);

			if ($objRow === null)
			{
				return '';
			}
		}

		$strClass = $blnModule ? Module::findClass('form') : ContentElement::findClass('form');

		if (!class_exists($strClass))
		{
			System::getContainer()->get('monolog.logger.contao.error')->error('Form class "' . $strClass . '" does not exist');

			return '';
		}

		$objRow->typePrefix = $blnModule ? 'mod_' : 'ce_';
		$objRow->form = $objRow->id;

		/** @var Form $objElement */
		$objElement = new $strClass($objRow, $strColumn);
		$strBuffer = $objElement->generate();

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['getForm']) && \is_array($GLOBALS['TL_HOOKS']['getForm']))
		{
			foreach ($GLOBALS['TL_HOOKS']['getForm'] as $callback)
			{
				$strBuffer = static::importStatic($callback[0])->{$callback[1]}($objRow, $strBuffer, $objElement);
			}
		}

		return $strBuffer;
	}

	/**
	 * Calculate the page status icon name based on the page parameters
	 *
	 * @param PageModel|Result|\stdClass $objPage The page object
	 *
	 * @return string The status icon name
	 */
	public static function getPageStatusIcon($objPage)
	{
		$sub = 0;
		$type = \in_array($objPage->type, array('regular', 'root', 'forward', 'redirect', 'error_401', 'error_403', 'error_404', 'error_503'), true) ? $objPage->type : 'regular';
		$image = $type . '.svg';

		// Page not published or not active
		if (!$objPage->published || ($objPage->start && $objPage->start > time()) || ($objPage->stop && $objPage->stop <= time()))
		{
			++$sub;
		}

		// Page hidden from menu
		if ($objPage->hide && !\in_array($type, array('root', 'error_401', 'error_403', 'error_404', 'error_503')))
		{
			$sub += 2;
		}

		// Page protected
		if ($objPage->protected && !\in_array($type, array('root', 'error_401', 'error_403', 'error_404', 'error_503')))
		{
			$sub += 4;
		}

		// Change icon if root page is published and in maintenance mode
		if ($sub == 0 && $objPage->type == 'root' && $objPage->maintenanceMode)
		{
			$sub = 2;
		}

		// Get the image name
		if ($sub > 0)
		{
			$image = $type . '_' . $sub . '.svg';
		}

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['getPageStatusIcon']) && \is_array($GLOBALS['TL_HOOKS']['getPageStatusIcon']))
		{
			foreach ($GLOBALS['TL_HOOKS']['getPageStatusIcon'] as $callback)
			{
				$image = static::importStatic($callback[0])->{$callback[1]}($objPage, $image);
			}
		}

		return $image;
	}

	/**
	 * Check whether an element is visible in the front end
	 *
	 * @param Model|ContentModel|ModuleModel $objElement The element model
	 *
	 * @return boolean True if the element is visible
	 */
	public static function isVisibleElement(Model $objElement)
	{
		$blnReturn = true;
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		// Only apply the restrictions in the front end
		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($request) && $objElement->protected)
		{
			$groups = StringUtil::deserialize($objElement->groups, true);
			$security = System::getContainer()->get('security.helper');
			$blnReturn = $security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $groups);
		}

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['isVisibleElement']) && \is_array($GLOBALS['TL_HOOKS']['isVisibleElement']))
		{
			foreach ($GLOBALS['TL_HOOKS']['isVisibleElement'] as $callback)
			{
				$blnReturn = static::importStatic($callback[0])->{$callback[1]}($objElement, $blnReturn);
			}
		}

		return $blnReturn;
	}

	/**
	 * Replace the dynamic script tags (see #4203)
	 *
	 * @param string $strBuffer The string with the tags to be replaced
	 *
	 * @return string The string with the replaced tags
	 */
	public static function replaceDynamicScriptTags($strBuffer)
	{
		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['replaceDynamicScriptTags']) && \is_array($GLOBALS['TL_HOOKS']['replaceDynamicScriptTags']))
		{
			foreach ($GLOBALS['TL_HOOKS']['replaceDynamicScriptTags'] as $callback)
			{
				$strBuffer = static::importStatic($callback[0])->{$callback[1]}($strBuffer);
			}
		}

		$arrReplace = array();
		$strScripts = '';

		// Add the internal jQuery scripts
		if (!empty($GLOBALS['TL_JQUERY']) && \is_array($GLOBALS['TL_JQUERY']))
		{
			$strScripts .= implode('', array_unique($GLOBALS['TL_JQUERY']));
		}

		$nonce = ContaoFramework::getNonce();

		$arrReplace["[[TL_JQUERY_$nonce]]"] = $strScripts;
		$strScripts = '';

		// Add the internal MooTools scripts
		if (!empty($GLOBALS['TL_MOOTOOLS']) && \is_array($GLOBALS['TL_MOOTOOLS']))
		{
			$strScripts .= implode('', array_unique($GLOBALS['TL_MOOTOOLS']));
		}

		$arrReplace["[[TL_MOOTOOLS_$nonce]]"] = $strScripts;
		$strScripts = '';

		// Add the internal <body> tags
		if (!empty($GLOBALS['TL_BODY']) && \is_array($GLOBALS['TL_BODY']))
		{
			$strScripts .= implode('', array_unique($GLOBALS['TL_BODY']));
		}

		/** @var PageModel|null $objPage */
		global $objPage;

		$objLayout = ($objPage !== null) ? LayoutModel::findByPk($objPage->layoutId) : null;
		$blnCombineScripts = $objLayout !== null && $objLayout->combineScripts;

		$arrReplace["[[TL_BODY_$nonce]]"] = $strScripts;
		$strScripts = '';

		$objCombiner = new Combiner();

		// Add the CSS framework style sheets
		if (!empty($GLOBALS['TL_FRAMEWORK_CSS']) && \is_array($GLOBALS['TL_FRAMEWORK_CSS']))
		{
			foreach (array_unique($GLOBALS['TL_FRAMEWORK_CSS']) as $stylesheet)
			{
				$objCombiner->add($stylesheet);
			}
		}

		// Add the internal style sheets
		if (!empty($GLOBALS['TL_CSS']) && \is_array($GLOBALS['TL_CSS']))
		{
			foreach (array_unique($GLOBALS['TL_CSS']) as $stylesheet)
			{
				$options = StringUtil::resolveFlaggedUrl($stylesheet);

				if ($options->static)
				{
					$objCombiner->add($stylesheet, $options->mtime, $options->media);
				}
				else
				{
					$strScripts .= Template::generateStyleTag(static::addAssetsUrlTo($stylesheet), $options->media, $options->mtime);
				}
			}
		}

		// Add the user style sheets
		if (!empty($GLOBALS['TL_USER_CSS']) && \is_array($GLOBALS['TL_USER_CSS']))
		{
			foreach (array_unique($GLOBALS['TL_USER_CSS']) as $stylesheet)
			{
				$options = StringUtil::resolveFlaggedUrl($stylesheet);

				if ($options->static)
				{
					$objCombiner->add($stylesheet, $options->mtime, $options->media);
				}
				else
				{
					$strScripts .= Template::generateStyleTag(static::addAssetsUrlTo($stylesheet), $options->media, $options->mtime);
				}
			}
		}

		// Create the aggregated style sheet
		if ($objCombiner->hasEntries())
		{
			if ($blnCombineScripts)
			{
				$strScripts .= Template::generateStyleTag($objCombiner->getCombinedFile(), 'all');
			}
			else
			{
				foreach ($objCombiner->getFileUrls() as $strUrl)
				{
					$options = StringUtil::resolveFlaggedUrl($strUrl);
					$strScripts .= Template::generateStyleTag($strUrl, $options->media, $options->mtime);
				}
			}
		}

		$arrReplace["[[TL_CSS_$nonce]]"] = $strScripts;
		$strScripts = '';

		// Add the internal scripts
		if (!empty($GLOBALS['TL_JAVASCRIPT']) && \is_array($GLOBALS['TL_JAVASCRIPT']))
		{
			$objCombiner = new Combiner();
			$objCombinerAsync = new Combiner();
			$objCombinerDefer = new Combiner();

			foreach (array_unique($GLOBALS['TL_JAVASCRIPT']) as $javascript)
			{
				$options = StringUtil::resolveFlaggedUrl($javascript);

				if ($options->static)
				{
					if ($options->async)
					{
						$objCombinerAsync->add($javascript, $options->mtime);
					}
					elseif ($options->defer)
					{
						$objCombinerDefer->add($javascript, $options->mtime);
					}
					else
					{
						$objCombiner->add($javascript, $options->mtime);
					}
				}
				else
				{
					$strScripts .= Template::generateScriptTag(static::addAssetsUrlTo($javascript), $options->async, $options->mtime, null, null, null, $options->defer);
				}
			}

			// Create the aggregated script and add it before the non-static scripts (see #4890)
			if ($objCombiner->hasEntries())
			{
				if ($blnCombineScripts)
				{
					$strScripts = Template::generateScriptTag($objCombiner->getCombinedFile()) . $strScripts;
				}
				else
				{
					$arrReversed = array_reverse($objCombiner->getFileUrls());

					foreach ($arrReversed as $strUrl)
					{
						$options = StringUtil::resolveFlaggedUrl($strUrl);
						$strScripts = Template::generateScriptTag($strUrl, false, $options->mtime) . $strScripts;
					}
				}
			}

			if ($objCombinerAsync->hasEntries())
			{
				if ($blnCombineScripts)
				{
					$strScripts = Template::generateScriptTag($objCombinerAsync->getCombinedFile(), true) . $strScripts;
				}
				else
				{
					$arrReversed = array_reverse($objCombinerAsync->getFileUrls());

					foreach ($arrReversed as $strUrl)
					{
						$options = StringUtil::resolveFlaggedUrl($strUrl);
						$strScripts = Template::generateScriptTag($strUrl, true, $options->mtime) . $strScripts;
					}
				}
			}

			if ($objCombinerDefer->hasEntries())
			{
				if ($blnCombineScripts)
				{
					$strScripts = Template::generateScriptTag($objCombinerDefer->getCombinedFile(), true) . $strScripts;
				}
				else
				{
					$arrReversed = array_reverse($objCombinerDefer->getFileUrls());

					foreach ($arrReversed as $strUrl)
					{
						$options = StringUtil::resolveFlaggedUrl($strUrl);
						$strScripts = Template::generateScriptTag($strUrl, false, $options->mtime, null, null, null, true) . $strScripts;
					}
				}
			}
		}

		// Add the internal <head> tags
		if (!empty($GLOBALS['TL_HEAD']) && \is_array($GLOBALS['TL_HEAD']))
		{
			foreach (array_unique($GLOBALS['TL_HEAD']) as $head)
			{
				$strScripts .= $head;
			}
		}

		$arrReplace["[[TL_HEAD_$nonce]]"] = $strScripts;

		return str_replace(array_keys($arrReplace), $arrReplace, $strBuffer);
	}

	/**
	 * Add a request string to the current URL
	 *
	 * @param string  $strRequest The string to be added
	 * @param boolean $blnAddRef  Add the referer ID
	 * @param array   $arrUnset   An optional array of keys to unset
	 *
	 * @return string The new URL
	 */
	public static function addToUrl($strRequest, $blnAddRef=true, $arrUnset=array())
	{
		$pairs = array();
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request->server->has('QUERY_STRING'))
		{
			$cacheKey = md5($request->server->get('QUERY_STRING'));

			if (!isset(static::$arrQueryCache[$cacheKey]))
			{
				parse_str($request->server->get('QUERY_STRING'), $pairs);
				ksort($pairs);

				static::$arrQueryCache[$cacheKey] = $pairs;
			}

			$pairs = static::$arrQueryCache[$cacheKey];
		}

		// Remove the request token and referer ID
		unset($pairs['rt'], $pairs['ref'], $pairs['revise']);

		foreach ($arrUnset as $key)
		{
			unset($pairs[$key]);
		}

		// Merge the request string to be added
		if ($strRequest)
		{
			parse_str(str_replace('&amp;', '&', $strRequest), $newPairs);
			$pairs = array_merge($pairs, $newPairs);
		}

		// Add the referer ID
		if ($request->query->has('ref') || ($strRequest && $blnAddRef))
		{
			$pairs['ref'] = $request->attributes->get('_contao_referer_id');
		}

		$uri = '';

		if (!empty($pairs))
		{
			$uri = '?' . http_build_query($pairs, '', '&amp;', PHP_QUERY_RFC3986);
		}

		return $request->getBaseUrl() . $request->getPathInfo() . $uri;
	}

	/**
	 * Reload the current page
	 */
	public static function reload()
	{
		static::redirect(Environment::get('uri'));
	}

	/**
	 * Redirect to another page
	 *
	 * @param string  $strLocation The target URL
	 * @param integer $intStatus   The HTTP status code (defaults to 303)
	 */
	public static function redirect($strLocation, $intStatus=303)
	{
		$strLocation = str_replace('&amp;', '&', $strLocation);

		// Make the location an absolute URL
		if (!preg_match('@^https?://@i', $strLocation))
		{
			if ($strLocation[0] == '/')
			{
				$strLocation = Environment::get('url') . $strLocation;
			}
			else
			{
				$strLocation = Environment::get('base') . $strLocation;
			}
		}

		// Ajax request
		if (Environment::get('isAjaxRequest'))
		{
			throw new AjaxRedirectResponseException($strLocation);
		}

		throw new RedirectResponseException($strLocation, $intStatus);
	}

	/**
	 * Convert relative URLs in href and src attributes to absolute URLs
	 *
	 * @param string  $strContent  The text with the URLs to be converted
	 * @param string  $strBase     An optional base URL
	 * @param boolean $blnHrefOnly If true, only href attributes will be converted
	 *
	 * @return string The text with the replaced URLs
	 */
	public static function convertRelativeUrls($strContent, $strBase='', $blnHrefOnly=false)
	{
		if (!$strBase)
		{
			$strBase = Environment::get('base');
		}

		$search = $blnHrefOnly ? 'href' : 'href|src';
		$arrUrls = preg_split('/((' . $search . ')="([^"]+)")/i', $strContent, -1, PREG_SPLIT_DELIM_CAPTURE);
		$strContent = '';

		for ($i=0, $c=\count($arrUrls); $i<$c; $i+=4)
		{
			$strContent .= $arrUrls[$i];

			if (!isset($arrUrls[$i+2]))
			{
				continue;
			}

			$strAttribute = $arrUrls[$i+2];
			$strUrl = $arrUrls[$i+3];

			if (!preg_match('@^(?:[a-z0-9]+:|#)@i', $strUrl))
			{
				$strUrl = $strBase . (($strUrl != '/') ? $strUrl : '');
			}

			$strContent .= $strAttribute . '="' . $strUrl . '"';
		}

		return $strContent;
	}

	/**
	 * Send a file to the browser so the "save as …" dialogue opens
	 *
	 * @param string  $strFile The file path
	 * @param boolean $inline  Show the file in the browser instead of opening the download dialog
	 *
	 * @throws AccessDeniedException
	 */
	public static function sendFileToBrowser($strFile, $inline=false)
	{
		// Make sure there are no attempts to hack the file system
		if (preg_match('@^\.+@', $strFile) || preg_match('@\.+/@', $strFile) || preg_match('@(://)+@', $strFile))
		{
			throw new PageNotFoundException('Invalid file name');
		}

		// Limit downloads to the files directory
		if (!preg_match('@^' . preg_quote(System::getContainer()->getParameter('contao.upload_path'), '@') . '@i', $strFile))
		{
			throw new PageNotFoundException('Invalid path');
		}

		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		// Check whether the file exists
		if (!file_exists($projectDir . '/' . $strFile))
		{
			throw new PageNotFoundException('File not found');
		}

		$objFile = new File($strFile);
		$arrAllowedTypes = StringUtil::trimsplit(',', strtolower(Config::get('allowedDownload')));

		// Check whether the file type is allowed to be downloaded
		if (!\in_array($objFile->extension, $arrAllowedTypes))
		{
			throw new AccessDeniedException(sprintf('File type "%s" is not allowed', $objFile->extension));
		}

		// HOOK: post download callback
		if (isset($GLOBALS['TL_HOOKS']['postDownload']) && \is_array($GLOBALS['TL_HOOKS']['postDownload']))
		{
			foreach ($GLOBALS['TL_HOOKS']['postDownload'] as $callback)
			{
				static::importStatic($callback[0])->{$callback[1]}($strFile);
			}
		}

		// Send the file (will stop the script execution)
		$objFile->sendToBrowser('', $inline);
	}

	/**
	 * Load a set of DCA files
	 *
	 * @param string $strTable The table name
	 */
	public static function loadDataContainer($strTable)
	{
		$loader = new DcaLoader($strTable);
		$loader->load();
	}

	/**
	 * Do not name this "reset" because it might result in conflicts with child classes
	 * @see https://github.com/contao/contao/issues/4257
	 *
	 * @internal
	 */
	public static function resetControllerCache()
	{
		self::$arrQueryCache = array();
	}

	/**
	 * Redirect to a front end page
	 *
	 * @param integer $intPage    The page ID
	 * @param string  $strArticle An optional article alias
	 * @param boolean $blnReturn  If true, return the URL and don't redirect
	 *
	 * @return string The URL of the target page
	 */
	protected function redirectToFrontendPage($intPage, $strArticle=null, $blnReturn=false)
	{
		if (($intPage = (int) $intPage) <= 0)
		{
			return '';
		}

		$objPage = PageModel::findWithDetails($intPage);

		if ($objPage === null)
		{
			return '';
		}

		$strParams = null;

		// Add the /article/ fragment (see #673)
		if ($strArticle !== null && ($objArticle = ArticleModel::findByAlias($strArticle)) !== null)
		{
			$strParams = '/articles/' . (($objArticle->inColumn != 'main') ? $objArticle->inColumn . ':' : '') . $strArticle;
		}

		$strUrl = $objPage->getPreviewUrl($strParams);

		if (!$blnReturn)
		{
			$this->redirect($strUrl);
		}

		return $strUrl;
	}

	/**
	 * Get the parent records of an entry and return them as string which can
	 * be used in a log message
	 *
	 * @param string  $strTable The table name
	 * @param integer $intId    The record ID
	 *
	 * @return string A string that can be used in a log message
	 */
	protected function getParentEntries($strTable, $intId)
	{
		// No parent table
		if (empty($GLOBALS['TL_DCA'][$strTable]['config']['ptable']))
		{
			return '';
		}

		$arrParent = array();

		do
		{
			// Get the pid
			$objParent = $this->Database->prepare("SELECT pid FROM " . $strTable . " WHERE id=?")
										->limit(1)
										->execute($intId);

			if ($objParent->numRows < 1)
			{
				break;
			}

			// Store the parent table information
			$strTable = $GLOBALS['TL_DCA'][$strTable]['config']['ptable'];
			$intId = $objParent->pid;

			// Add the log entry
			$arrParent[] = $strTable . '.id=' . $intId;

			// Load the data container of the parent table
			$this->loadDataContainer($strTable);
		}
		while ($intId && !empty($GLOBALS['TL_DCA'][$strTable]['config']['ptable']));

		if (empty($arrParent))
		{
			return '';
		}

		return ' (parent records: ' . implode(', ', $arrParent) . ')';
	}

	/**
	 * Take an array of file paths and eliminate the nested ones
	 *
	 * @param array $arrPaths The array of file paths
	 *
	 * @return array The file paths array without the nested paths
	 */
	protected function eliminateNestedPaths($arrPaths)
	{
		$arrPaths = array_filter($arrPaths);

		if (empty($arrPaths) || !\is_array($arrPaths))
		{
			return array();
		}

		$nested = array();

		foreach ($arrPaths as $path)
		{
			$nested[] = preg_grep('/^' . preg_quote($path, '/') . '\/.+/', $arrPaths);
		}

		if (!empty($nested))
		{
			$nested = array_merge(...$nested);
		}

		return array_values(array_diff($arrPaths, $nested));
	}

	/**
	 * Take an array of pages and eliminate the nested ones
	 *
	 * @param array   $arrPages   The array of page IDs
	 * @param string  $strTable   The table name
	 * @param boolean $blnSorting True if the table has a sorting field
	 *
	 * @return array The page IDs array without the nested IDs
	 */
	protected function eliminateNestedPages($arrPages, $strTable=null, $blnSorting=false)
	{
		if (empty($arrPages) || !\is_array($arrPages))
		{
			return array();
		}

		if (!$strTable)
		{
			$strTable = 'tl_page';
		}

		// Thanks to Andreas Schempp (see #2475 and #3423)
		$arrPages = array_intersect($arrPages, $this->Database->getChildRecords(0, $strTable, $blnSorting));
		$arrPages = array_values(array_diff($arrPages, $this->Database->getChildRecords($arrPages, $strTable, $blnSorting)));

		return $arrPages;
	}

	/**
	 * Add enclosures to a template
	 *
	 * @param object $objTemplate The template object to add the enclosures to
	 * @param array  $arrItem     The element or module as array
	 * @param string $strKey      The name of the enclosures field in $arrItem
	 */
	public static function addEnclosuresToTemplate($objTemplate, $arrItem, $strKey='enclosure')
	{
		$arrEnclosures = StringUtil::deserialize($arrItem[$strKey]);

		if (empty($arrEnclosures) || !\is_array($arrEnclosures))
		{
			return;
		}

		$objFiles = FilesModel::findMultipleByUuids($arrEnclosures);

		if ($objFiles === null)
		{
			return;
		}

		$file = Input::get('file', true);

		// Send the file to the browser and do not send a 404 header (see #5178)
		if ($file)
		{
			while ($objFiles->next())
			{
				if ($file == $objFiles->path)
				{
					static::sendFileToBrowser($file);
				}
			}

			$objFiles->reset();
		}

		/** @var PageModel $objPage */
		global $objPage;

		$arrEnclosures = array();
		$allowedDownload = StringUtil::trimsplit(',', strtolower(Config::get('allowedDownload')));
		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		// Add download links
		while ($objFiles->next())
		{
			if ($objFiles->type == 'file')
			{
				if (!\in_array($objFiles->extension, $allowedDownload) || !is_file($projectDir . '/' . $objFiles->path))
				{
					continue;
				}

				$objFile = new File($objFiles->path);
				$strHref = Environment::get('requestUri');

				// Remove an existing file parameter (see #5683)
				if (preg_match('/(&(amp;)?|\?)file=/', $strHref))
				{
					$strHref = preg_replace('/(&(amp;)?|\?)file=[^&]+/', '', $strHref);
				}

				$strHref .= ((strpos($strHref, '?') !== false) ? '&amp;' : '?') . 'file=' . System::urlEncode($objFiles->path);

				$arrMeta = Frontend::getMetaData($objFiles->meta, $objPage->language);

				if (empty($arrMeta) && $objPage->rootFallbackLanguage !== null)
				{
					$arrMeta = Frontend::getMetaData($objFiles->meta, $objPage->rootFallbackLanguage);
				}

				// Use the file name as title if none is given
				if (empty($arrMeta['title']))
				{
					$arrMeta['title'] = StringUtil::specialchars($objFile->basename);
				}

				$arrEnclosures[] = array
				(
					'id'        => $objFiles->id,
					'uuid'      => $objFiles->uuid,
					'name'      => $objFile->basename,
					'title'     => StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['download'], $objFile->basename)),
					'link'      => $arrMeta['title'],
					'caption'   => $arrMeta['caption'] ?? null,
					'href'      => $strHref,
					'filesize'  => static::getReadableSize($objFile->filesize),
					'icon'      => Image::getPath($objFile->icon),
					'mime'      => $objFile->mime,
					'meta'      => $arrMeta,
					'extension' => $objFile->extension,
					'path'      => $objFile->dirname
				);
			}
		}

		$objTemplate->enclosure = $arrEnclosures;
	}

	/**
	 * Set the static URL constants
	 */
	public static function setStaticUrls()
	{
		if (\defined('TL_FILES_URL'))
		{
			return;
		}

		\define('TL_ASSETS_URL', System::getContainer()->get('contao.assets.assets_context')->getStaticUrl());
		\define('TL_FILES_URL', System::getContainer()->get('contao.assets.files_context')->getStaticUrl());
	}

	/**
	 * Add a static URL to a script
	 *
	 * @param string             $script  The script path
	 * @param ContaoContext|null $context
	 *
	 * @return string The script path with the static URL
	 */
	public static function addStaticUrlTo($script, ContaoContext $context = null)
	{
		// Absolute URLs
		if (preg_match('@^https?://@', $script))
		{
			return $script;
		}

		if ($context === null)
		{
			$context = System::getContainer()->get('contao.assets.assets_context');
		}

		if ($strStaticUrl = $context->getStaticUrl())
		{
			return $strStaticUrl . $script;
		}

		return $script;
	}

	/**
	 * Add the assets URL to a script
	 *
	 * @param string $script The script path
	 *
	 * @return string The script path with the assets URL
	 */
	public static function addAssetsUrlTo($script)
	{
		return static::addStaticUrlTo($script, System::getContainer()->get('contao.assets.assets_context'));
	}

	/**
	 * Add the files URL to a script
	 *
	 * @param string $script The script path
	 *
	 * @return string The script path with the files URL
	 */
	public static function addFilesUrlTo($script)
	{
		return static::addStaticUrlTo($script, System::getContainer()->get('contao.assets.files_context'));
	}

	/**
	 * Return the files matching a GLOB pattern
	 *
	 * @param string $pattern
	 *
	 * @return array|false
	 */
	protected static function braceGlob($pattern)
	{
		// Use glob() if possible
		if (false === strpos($pattern, '/**/') && (\defined('GLOB_BRACE') || false === strpos($pattern, '{')))
		{
			return glob($pattern, \defined('GLOB_BRACE') ? GLOB_BRACE : 0);
		}

		$finder = new Finder();
		$regex = Glob::toRegex($pattern);

		// All files in the given template folder
		$filesIterator = $finder
			->files()
			->followLinks()
			->sortByName()
			->in(\dirname($pattern))
		;

		// Match the actual regex and filter the files
		$filesIterator = $filesIterator->filter(static function (\SplFileInfo $info) use ($regex)
		{
			$path = $info->getPathname();

			return preg_match($regex, $path) && $info->isFile();
		});

		$files = iterator_to_array($filesIterator);

		return array_keys($files);
	}
}
