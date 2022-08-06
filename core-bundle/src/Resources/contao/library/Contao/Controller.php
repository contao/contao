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
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Database\Result;
use Contao\Image\PictureConfiguration;
use Contao\Model\Collection;
use Imagine\Image\BoxInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
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
	 * @var Template
	 *
	 * @todo: Add in Contao 5.0
	 */
	//protected $Template;

	/**
	 * @var array
	 */
	protected static $arrQueryCache = array();

	/**
	 * @var array
	 */
	private static $arrOldBePathCache = array();

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

		// Check for a theme folder
		if (\defined('TL_MODE') && TL_MODE == 'FE')
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
		$arrMapper['form'][] = 'textfield'; // TODO: remove in Contao 5.0

		// Add templates that are not directly associated with a module
		$arrMapper['mod'][] = 'article';
		$arrMapper['mod'][] = 'message';
		$arrMapper['mod'][] = 'password'; // TODO: remove in Contao 5.0
		$arrMapper['mod'][] = 'comment_form'; // TODO: remove in Contao 5.0
		$arrMapper['mod'][] = 'newsletter'; // TODO: remove in Contao 5.0

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

		// Backwards compatibility (see #725)
		if (substr($strGlobPrefix, -1) == '_')
		{
			$strGlobPrefix = substr($strGlobPrefix, 0, -1) . '[_-]';
		}

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
					trigger_deprecation('contao/core-bundle', '4.9', 'Using hyphens in the template name "' . $strTemplate . '.html5" has been deprecated and will no longer work in Contao 5.0. Use snake_case instead.');
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

		// Print the article as PDF
		if (isset($_GET['pdf']) && Input::get('pdf') == $objRow->id)
		{
			// Deprecated since Contao 4.0, to be removed in Contao 5.0
			if ($objRow->printable == 1)
			{
				trigger_deprecation('contao/core-bundle', '4.0', 'Setting tl_article.printable to "1" has been deprecated and will no longer work in Contao 5.0.');

				$objArticle = new ModuleArticle($objRow);
				$objArticle->generatePdf();
			}
			elseif ($objRow->printable)
			{
				$options = StringUtil::deserialize($objRow->printable);

				if (\is_array($options) && \in_array('pdf', $options))
				{
					$objArticle = new ModuleArticle($objRow);
					$objArticle->generatePdf();
				}
			}
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
	 * Return the languages for the TinyMCE spellchecker
	 *
	 * @return string The TinyMCE spellchecker language string
	 *
	 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0.
	 */
	protected function getSpellcheckerString()
	{
		trigger_deprecation('contao/core-bundle', '4.13', 'Using "%s()" has been deprecated and will no longer work in Contao 5.0.', __METHOD__);

		System::loadLanguageFile('languages');

		$return = array();
		$langs = Folder::scan(__DIR__ . '/../../languages');
		array_unshift($langs, $GLOBALS['TL_LANGUAGE']);

		foreach ($langs as $lang)
		{
			$lang = substr($lang, 0, 2);

			if (isset($GLOBALS['TL_LANG']['LNG'][$lang]))
			{
				$return[$lang] = $GLOBALS['TL_LANG']['LNG'][$lang] . '=' . $lang;
			}
		}

		return '+' . implode(',', array_unique($return));
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

		// Only apply the restrictions in the front end
		if (TL_MODE == 'FE')
		{
			$security = System::getContainer()->get('security.helper');

			if ($objElement->protected)
			{
				$groups = StringUtil::deserialize($objElement->groups, true);
				$blnReturn = $security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $groups);
			}
			elseif ($objElement->guests)
			{
				trigger_deprecation('contao/core-bundle', '4.12', 'Using the "show to guests only" feature has been deprecated an will no longer work in Contao 5.0. Use the "protect page" function instead.');
				$blnReturn = !$security->isGranted('ROLE_MEMBER'); // backwards compatibility
			}
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
	 * Replace insert tags with their values
	 *
	 * @param string  $strBuffer The text with the tags to be replaced
	 * @param boolean $blnCache  If false, non-cacheable tags will be replaced
	 *
	 * @return string The text with the replaced tags
	 *
	 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0.
	 *             Use the InsertTagParser service instead.
	 */
	public static function replaceInsertTags($strBuffer, $blnCache=true)
	{
		trigger_deprecation('contao/core-bundle', '4.13', 'Using "%s()" has been deprecated and will no longer work in Contao 5.0. Use the InsertTagParser service instead.', __METHOD__);

		$parser = System::getContainer()->get('contao.insert_tag.parser');

		if ($blnCache)
		{
			return $parser->replace((string) $strBuffer);
		}

		return $parser->replaceInline((string) $strBuffer);
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

			foreach (array_unique($GLOBALS['TL_JAVASCRIPT']) as $javascript)
			{
				$options = StringUtil::resolveFlaggedUrl($javascript);

				if ($options->static)
				{
					$options->async ? $objCombinerAsync->add($javascript, $options->mtime) : $objCombiner->add($javascript, $options->mtime);
				}
				else
				{
					$strScripts .= Template::generateScriptTag(static::addAssetsUrlTo($javascript), $options->async, $options->mtime);
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
	 * Compile the margin format definition based on an array of values
	 *
	 * @param array  $arrValues An array of four values and a unit
	 * @param string $strType   Either "margin" or "padding"
	 *
	 * @return string The CSS markup
	 *
	 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0.
	 */
	public static function generateMargin($arrValues, $strType='margin')
	{
		trigger_deprecation('contao/core-bundle', '4.13', 'Using Contao\Controller::generateMargin is deprecated since Contao 4.13 and will be removed in Contao 5.');

		// Initialize an empty array (see #5217)
		if (!\is_array($arrValues))
		{
			$arrValues = array('top'=>'', 'right'=>'', 'bottom'=>'', 'left'=>'', 'unit'=>'');
		}

		$top = $arrValues['top'];
		$right = $arrValues['right'];
		$bottom = $arrValues['bottom'];
		$left = $arrValues['left'];

		// Try to shorten the definition
		if ($top && $right  && $bottom  && $left)
		{
			if ($top == $right && $top == $bottom && $top == $left)
			{
				return $strType . ':' . $top . $arrValues['unit'] . ';';
			}

			if ($top == $bottom && $right == $left)
			{
				return $strType . ':' . $top . $arrValues['unit'] . ' ' . $left . $arrValues['unit'] . ';';
			}

			if ($top != $bottom && $right == $left)
			{
				return $strType . ':' . $top . $arrValues['unit'] . ' ' . $right . $arrValues['unit'] . ' ' . $bottom . $arrValues['unit'] . ';';
			}

			return $strType . ':' . $top . $arrValues['unit'] . ' ' . $right . $arrValues['unit'] . ' ' . $bottom . $arrValues['unit'] . ' ' . $left . $arrValues['unit'] . ';';
		}

		$return = array();
		$arrDir = compact('top', 'right', 'bottom', 'left');

		foreach ($arrDir as $k=>$v)
		{
			if ($v)
			{
				$return[] = $strType . '-' . $k . ':' . $v . $arrValues['unit'] . ';';
			}
		}

		return implode('', $return);
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

		return TL_SCRIPT . $uri;
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
		$strLocation = static::replaceOldBePaths($strLocation);

		// Make the location an absolute URL
		if (!preg_match('@^https?://@i', $strLocation))
		{
			$strLocation = Environment::get('base') . ltrim($strLocation, '/');
		}

		// Ajax request
		if (Environment::get('isAjaxRequest'))
		{
			throw new AjaxRedirectResponseException($strLocation);
		}

		throw new RedirectResponseException($strLocation, $intStatus);
	}

	/**
	 * Replace the old back end paths
	 *
	 * @param string $strContext The context
	 *
	 * @return string The modified context
	 */
	protected static function replaceOldBePaths($strContext)
	{
		$arrCache = &self::$arrOldBePathCache;

		$arrMapper = array
		(
			'contao/confirm.php'   => 'contao_backend_confirm',
			'contao/file.php'      => 'contao_backend_file',
			'contao/help.php'      => 'contao_backend_help',
			'contao/index.php'     => 'contao_backend_login',
			'contao/main.php'      => 'contao_backend',
			'contao/page.php'      => 'contao_backend_page',
			'contao/password.php'  => 'contao_backend_password',
			'contao/popup.php'     => 'contao_backend_popup',
			'contao/preview.php'   => 'contao_backend_preview',
		);

		$replace = static function ($matches) use ($arrMapper, &$arrCache)
		{
			$key = $matches[0];

			if (!isset($arrCache[$key]))
			{
				trigger_deprecation('contao/core-bundle', '4.0', 'Using old backend paths has been deprecated in Contao 4.0 and will be removed in Contao 5. Use the backend routes instead.');

				$router = System::getContainer()->get('router');
				$arrCache[$key] = substr($router->generate($arrMapper[$key]), \strlen(Environment::get('path')) + 1);
			}

			return $arrCache[$key];
		};

		$regex = '(' . implode('|', array_map('preg_quote', array_keys($arrMapper))) . ')';

		return preg_replace_callback($regex, $replace, $strContext);
	}

	/**
	 * Generate a front end URL
	 *
	 * @param array   $arrRow       An array of page parameters
	 * @param string  $strParams    An optional string of URL parameters
	 * @param string  $strForceLang Force a certain language
	 * @param boolean $blnFixDomain Check the domain of the target page and append it if necessary
	 *
	 * @return string A URL that can be used in the front end
	 *
	 * @deprecated Deprecated since Contao 4.2, to be removed in Contao 5.0.
	 *             Use PageModel::getFrontendUrl() instead.
	 */
	public static function generateFrontendUrl(array $arrRow, $strParams=null, $strForceLang=null, $blnFixDomain=false)
	{
		trigger_deprecation('contao/core-bundle', '4.2', 'Using "Contao\Controller::generateFrontendUrl()" has been deprecated and will no longer work in Contao 5.0. Use PageModel::getFrontendUrl() instead.');

		$page = new PageModel();
		$page->preventSaving(false);
		$page->setRow($arrRow);

		if (!isset($arrRow['rootId']))
		{
			$page->loadDetails();

			foreach (array('domain', 'rootLanguage', 'rootUseSSL') as $key)
			{
				if (isset($arrRow[$key]))
				{
					$page->$key = $arrRow[$key];
				}
				else
				{
					$arrRow[$key] = $page->$key;
				}
			}
		}

		// Set the language
		if ($strForceLang !== null)
		{
			$strForceLang = LocaleUtil::formatAsLocale($strForceLang);

			$page->language = $strForceLang;
			$page->rootLanguage = $strForceLang;

			if (System::getContainer()->getParameter('contao.legacy_routing'))
			{
				$page->urlPrefix = System::getContainer()->getParameter('contao.prepend_locale') ? $strForceLang : '';
			}
		}

		// Add the domain if it differs from the current one (see #3765 and #6927)
		if ($blnFixDomain)
		{
			$page->domain = $arrRow['domain'];
			$page->rootUseSSL = (bool) $arrRow['rootUseSSL'];
		}

		$objRouter = System::getContainer()->get('router');
		$strUrl = $objRouter->generate(RouteObjectInterface::OBJECT_BASED_ROUTE_NAME, array(RouteObjectInterface::CONTENT_OBJECT => $page, 'parameters' => $strParams));

		// Remove path from absolute URLs
		if (0 === strncmp($strUrl, '/', 1) && 0 !== strncmp($strUrl, '//', 2))
		{
			$strUrl = substr($strUrl, \strlen(Environment::get('path')) + 1);
		}

		// Decode sprintf placeholders
		if (strpos($strParams, '%') !== false)
		{
			$arrMatches = array();
			preg_match_all('/%([sducoxXbgGeEfF])/', $strParams, $arrMatches);

			foreach (array_unique($arrMatches[1]) as $v)
			{
				$strUrl = str_replace('%25' . $v, '%' . $v, $strUrl);
			}
		}

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['generateFrontendUrl']) && \is_array($GLOBALS['TL_HOOKS']['generateFrontendUrl']))
		{
			foreach ($GLOBALS['TL_HOOKS']['generateFrontendUrl'] as $callback)
			{
				$strUrl = static::importStatic($callback[0])->{$callback[1]}($arrRow, $strParams, $strUrl);
			}
		}

		return $strUrl;
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
	 * @param string  $strTable   The table name
	 * @param boolean $blnNoCache If true, the cache will be bypassed
	 */
	public static function loadDataContainer($strTable, $blnNoCache=false)
	{
		if (\func_num_args() > 1)
		{
			trigger_deprecation('contao/core-bundle', '4.13', 'Calling "%s" with the $blnNoCache parameter has been deprecated and will no longer work in Contao 5.0.', __METHOD__);
		}

		$loader = new DcaLoader($strTable);
		$loader->load(...($blnNoCache ? array(true) : array()));
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
		self::$arrOldBePathCache = array();
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
	 * Add an image to a template
	 *
	 * @param object          $template                The template object to add the image to
	 * @param array           $rowData                 The element or module as array
	 * @param integer|null    $maxWidth                An optional maximum width of the image
	 * @param string|null     $lightboxGroupIdentifier An optional lightbox group identifier
	 * @param FilesModel|null $filesModel              An optional files model
	 *
	 * @deprecated Deprecated since Contao 4.11, to be removed in Contao 5.0;
	 *             use the Contao\CoreBundle\Image\Studio\FigureBuilder instead.
	 */
	public static function addImageToTemplate($template, array $rowData, $maxWidth = null, $lightboxGroupIdentifier = null, FilesModel $filesModel = null): void
	{
		trigger_deprecation('contao/core-bundle', '4.11', 'Using Controller::addImageToTemplate() is deprecated and will no longer work in Contao 5.0. Use the "Contao\CoreBundle\Image\Studio\FigureBuilder" class instead.');

		// Helper: Create metadata from the specified row data
		$createMetadataOverwriteFromRowData = static function (bool $interpretAsContentModel) use ($rowData)
		{
			if ($interpretAsContentModel)
			{
				// This will be null if "overwriteMeta" is not set
				return (new ContentModel())->setRow($rowData)->getOverwriteMetadata();
			}

			// Manually create metadata that always contains certain properties (BC)
			return new Metadata(array(
				Metadata::VALUE_ALT => $rowData['alt'] ?? '',
				Metadata::VALUE_TITLE => $rowData['imageTitle'] ?? '',
				Metadata::VALUE_URL => System::getContainer()->get('contao.insert_tag.parser')->replaceInline($rowData['imageUrl'] ?? ''),
				'linkTitle' => (string) ($rowData['linkTitle'] ?? ''),
			));
		};

		// Helper: Create fallback template data with (mostly) empty fields (used if resource acquisition fails)
		$createFallBackTemplateData = static function () use ($filesModel, $rowData)
		{
			$templateData = array(
				'width' => null,
				'height' => null,
				'picture' => array(
					'img' => array(
						'src' => '',
						'srcset' => '',
					),
					'sources' => array(),
					'alt' => '',
					'title' => '',
				),
				'singleSRC' => $rowData['singleSRC'],
				'src' => '',
				'linkTitle' => '',
				'margin' => '',
				'addImage' => true,
				'addBefore' => true,
				'fullsize' => false,
			);

			if (null !== $filesModel)
			{
				// Set empty metadata
				$templateData = array_replace_recursive(
					$templateData,
					array(
						'alt' => '',
						'caption' => '',
						'imageTitle' => '',
						'imageUrl' => '',
					)
				);
			}

			return $templateData;
		};

		// Helper: Get size and margins and handle legacy $maxWidth option
		$getSizeAndMargin = static function () use ($rowData, $maxWidth)
		{
			$size = $rowData['size'] ?? null;
			$margin = StringUtil::deserialize($rowData['imagemargin'] ?? null);
			$maxWidth = (int) ($maxWidth ?? Config::get('maxImageWidth'));

			if (0 === $maxWidth)
			{
				return array($size, $margin);
			}

			trigger_deprecation('contao/core-bundle', '4.10', 'Using a maximum front end width has been deprecated and will no longer work in Contao 5.0. Remove the "maxImageWidth" configuration and use responsive images instead.');

			// Adjust margins if needed
			if ('px' === ($margin['unit'] ?? null))
			{
				$horizontalMargin = (int) ($margin['left'] ?? 0) + (int) ($margin['right'] ?? 0);

				if ($maxWidth - $horizontalMargin < 1)
				{
					$margin['left'] = '';
					$margin['right'] = '';
				}
				else
				{
					$maxWidth -= $horizontalMargin;
				}
			}

			// Normalize size
			if ($size instanceof PictureConfiguration)
			{
				return array($size, $margin);
			}

			$size = StringUtil::deserialize($size);

			if (is_numeric($size))
			{
				$size = array(0, 0, (int) $size);
			}
			else
			{
				$size = (\is_array($size) ? $size : array()) + array(0, 0, 'crop');
				$size[0] = (int) $size[0];
				$size[1] = (int) $size[1];
			}

			// Adjust image size configuration if it exceeds the max width
			if ($size[0] > 0 && $size[1] > 0)
			{
				list($width, $height) = $size;
			}
			else
			{
				$container = System::getContainer();

				/** @var BoxInterface $originalSize */
				$originalSize = $container
					->get('contao.image.factory')
					->create($container->getParameter('kernel.project_dir') . '/' . $rowData['singleSRC'])
					->getDimensions()
					->getSize();

				$width = $originalSize->getWidth();
				$height = $originalSize->getHeight();
			}

			if ($width <= $maxWidth)
			{
				return array($size, $margin);
			}

			$size[0] = $maxWidth;
			$size[1] = (int) floor($maxWidth * ($height / $width));

			return array($size, $margin);
		};

		$figureBuilder = System::getContainer()->get('contao.image.studio')->createFigureBuilder();

		// Set image resource
		if (null !== $filesModel)
		{
			// Make sure model points to the same resource (BC)
			$filesModel = clone $filesModel;
			$filesModel->path = $rowData['singleSRC'];

			// Use source + metadata from files model (if not overwritten)
			$figureBuilder
				->fromFilesModel($filesModel)
				->setMetadata($createMetadataOverwriteFromRowData(true));

			$includeFullMetadata = true;
		}
		else
		{
			// Always ignore file metadata when building from path (BC)
			$figureBuilder
				->fromPath($rowData['singleSRC'], false)
				->setMetadata($createMetadataOverwriteFromRowData(false));

			$includeFullMetadata = false;
		}

		// Set size and lightbox configuration
		list($size, $margin) = $getSizeAndMargin();

		$lightboxSize = StringUtil::deserialize($rowData['lightboxSize'] ?? null) ?: null;

		$figure = $figureBuilder
			->setSize($size)
			->setLightboxGroupIdentifier($lightboxGroupIdentifier)
			->setLightboxSize($lightboxSize)
			->enableLightbox((bool) ($rowData['fullsize'] ?? false))
			->buildIfResourceExists();

		if (null === $figure)
		{
			System::getContainer()->get('monolog.logger.contao.error')->error('Image "' . $rowData['singleSRC'] . '" could not be processed: ' . $figureBuilder->getLastException()->getMessage());

			// Fall back to apply a sparse data set instead of failing (BC)
			foreach ($createFallBackTemplateData() as $key => $value)
			{
				$template->$key = $value;
			}

			return;
		}

		// Build result and apply it to the template
		$figure->applyLegacyTemplateData($template, $margin, $rowData['floating'] ?? null, $includeFullMetadata);

		// Fall back to manually specified link title or empty string if not set (backwards compatibility)
		$template->linkTitle ??= StringUtil::specialchars($rowData['title'] ?? '');
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
				$strHref = Environment::get('request');

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
					'path'      => $objFile->dirname,
					'enclosure' => $objFiles->path // backwards compatibility
				);
			}
		}

		// Order the enclosures
		if (!empty($arrItem['orderEnclosure']))
		{
			trigger_deprecation('contao/core-bundle', '4.10', 'Using "orderEnclosure" has been deprecated and will no longer work in Contao 5.0. Use a file tree with "isSortable" instead.');

			$arrEnclosures = ArrayUtil::sortByOrderField($arrEnclosures, $arrItem['orderEnclosure']);
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

		if (\func_num_args() > 0)
		{
			trigger_deprecation('contao/core-bundle', '4.9', 'Using "Contao\Controller::setStaticUrls()" has been deprecated and will no longer work in Contao 5.0. Use the asset contexts instead.');

			if (!isset($GLOBALS['objPage']))
			{
				$GLOBALS['objPage'] = func_get_arg(0);
			}
		}

		\define('TL_ASSETS_URL', System::getContainer()->get('contao.assets.assets_context')->getStaticUrl());
		\define('TL_FILES_URL', System::getContainer()->get('contao.assets.files_context')->getStaticUrl());

		// Deprecated since Contao 4.0, to be removed in Contao 5.0
		\define('TL_SCRIPT_URL', TL_ASSETS_URL);
		\define('TL_PLUGINS_URL', TL_ASSETS_URL);
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
	 * Return the current theme as string
	 *
	 * @return string The name of the theme
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Backend::getTheme() instead.
	 */
	public static function getTheme()
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::getTheme()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Backend::getTheme()" instead.');

		return Backend::getTheme();
	}

	/**
	 * Return the back end themes as array
	 *
	 * @return array An array of available back end themes
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Backend::getThemes() instead.
	 */
	public static function getBackendThemes()
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::getBackendThemes()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Backend::getThemes()" instead.');

		return Backend::getThemes();
	}

	/**
	 * Get the details of a page including inherited parameters
	 *
	 * @param mixed $intId A page ID or a Model object
	 *
	 * @return PageModel The page model or null
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use PageModel::findWithDetails() or PageModel->loadDetails() instead.
	 */
	public static function getPageDetails($intId)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::getPageDetails()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\PageModel::findWithDetails()" or "Contao\PageModel->loadDetails()" instead.');

		if ($intId instanceof PageModel)
		{
			return $intId->loadDetails();
		}

		if ($intId instanceof Collection)
		{
			/** @var PageModel $objPage */
			$objPage = $intId->current();

			return $objPage->loadDetails();
		}

		if (\is_object($intId))
		{
			$strKey = __METHOD__ . '-' . $intId->id;

			// Try to load from cache
			if (Cache::has($strKey))
			{
				return Cache::get($strKey);
			}

			// Create a model from the database result
			$objPage = new PageModel();
			$objPage->setRow($intId->row());
			$objPage->loadDetails();

			Cache::set($strKey, $objPage);

			return $objPage;
		}

		// Invalid ID
		if ($intId < 1 || !\strlen($intId))
		{
			return null;
		}

		$strKey = __METHOD__ . '-' . $intId;

		// Try to load from cache
		if (Cache::has($strKey))
		{
			return Cache::get($strKey);
		}

		$objPage = PageModel::findWithDetails($intId);

		Cache::set($strKey, $objPage);

		return $objPage;
	}

	/**
	 * Remove old XML files from the share directory
	 *
	 * @param boolean $blnReturn If true, only return the finds and don't delete
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Automator::purgeXmlFiles() instead.
	 */
	protected function removeOldFeeds($blnReturn=false)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::removeOldFeeds()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Automator::purgeXmlFiles()" instead.');

		$this->import(Automator::class, 'Automator');
		$this->Automator->purgeXmlFiles($blnReturn);
	}

	/**
	 * Return true if a class exists (tries to autoload the class)
	 *
	 * @param string $strClass The class name
	 *
	 * @return boolean True if the class exists
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use the PHP function class_exists() instead.
	 */
	protected function classFileExists($strClass)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::classFileExists()" has been deprecated and will no longer work in Contao 5.0. Use the PHP function "class_exists()" instead.');

		return class_exists($strClass);
	}

	/**
	 * Restore basic entities
	 *
	 * @param string $strBuffer The string with the tags to be replaced
	 *
	 * @return string The string with the original entities
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use StringUtil::restoreBasicEntities() instead.
	 */
	public static function restoreBasicEntities($strBuffer)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::restoreBasicEntities()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\StringUtil::restoreBasicEntities()" instead.');

		return StringUtil::restoreBasicEntities($strBuffer);
	}

	/**
	 * Resize an image and crop it if necessary
	 *
	 * @param string  $image  The image path
	 * @param integer $width  The target width
	 * @param integer $height The target height
	 * @param string  $mode   An optional resize mode
	 *
	 * @return boolean True if the image has been resized correctly
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Image::resize() instead.
	 */
	protected function resizeImage($image, $width, $height, $mode='')
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::resizeImage()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Image::resize()" instead.');

		return Image::resize($image, $width, $height, $mode);
	}

	/**
	 * Resize an image and crop it if necessary
	 *
	 * @param string  $image  The image path
	 * @param integer $width  The target width
	 * @param integer $height The target height
	 * @param string  $mode   An optional resize mode
	 * @param string  $target An optional target to be replaced
	 * @param boolean $force  Override existing target images
	 *
	 * @return string|null The image path or null
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Image::get() instead.
	 */
	protected function getImage($image, $width, $height, $mode='', $target=null, $force=false)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::getImage()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Image::get()" instead.');

		return Image::get($image, $width, $height, $mode, $target, $force);
	}

	/**
	 * Generate an image tag and return it as string
	 *
	 * @param string $src        The image path
	 * @param string $alt        An optional alt attribute
	 * @param string $attributes A string of other attributes
	 *
	 * @return string The image HTML tag
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Image::getHtml() instead.
	 */
	public static function generateImage($src, $alt='', $attributes='')
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::generateImage()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Image::getHtml()" instead.');

		return Image::getHtml($src, $alt, $attributes);
	}

	/**
	 * Return the date picker string (see #3218)
	 *
	 * @return boolean
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Specify "datepicker"=>true in your DCA file instead.
	 */
	protected function getDatePickerString()
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::getDatePickerString()" has been deprecated and will no longer work in Contao 5.0. Specify "\'datepicker\' => true" in your DCA file instead.');

		return true;
	}

	/**
	 * Return the installed back end languages as array
	 *
	 * @return array An array of available back end languages
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use the Contao\CoreBundle\Intl\Locales service instead.
	 */
	protected function getBackendLanguages()
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::getBackendLanguages()" has been deprecated and will no longer work in Contao 5.0. Use the Contao\CoreBundle\Intl\Locales service instead.');

		return $this->getLanguages(true);
	}

	/**
	 * Parse simple tokens that can be used to personalize newsletters
	 *
	 * @param string $strBuffer The text with the tokens to be replaced
	 * @param array  $arrData   The replacement data as array
	 *
	 * @return string The text with the replaced tokens
	 *
	 * @deprecated Deprecated since Contao 4.10, to be removed in Contao 5.0;
	 *             Use the contao.string.simple_token_parser service instead.
	 */
	protected function parseSimpleTokens($strBuffer, $arrData)
	{
		trigger_deprecation('contao/core-bundle', '4.10', 'Using "Contao\Controller::parseSimpleTokens()" has been deprecated and will no longer work in Contao 5.0. Use the "contao.string.simple_token_parser" service instead.');

		return System::getContainer()->get('contao.string.simple_token_parser')->parse($strBuffer, $arrData);
	}

	/**
	 * Convert a DCA file configuration to be used with widgets
	 *
	 * @param array  $arrData  The field configuration array
	 * @param string $strName  The field name in the form
	 * @param mixed  $varValue The field value
	 * @param string $strField The field name in the database
	 * @param string $strTable The table name
	 *
	 * @return array An array that can be passed to a widget
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Widget::getAttributesFromDca() instead.
	 */
	protected function prepareForWidget($arrData, $strName, $varValue=null, $strField='', $strTable='')
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::prepareForWidget()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Widget::getAttributesFromDca()" instead.');

		return Widget::getAttributesFromDca($arrData, $strName, $varValue, $strField, $strTable);
	}

	/**
	 * Return the IDs of all child records of a particular record (see #2475)
	 *
	 * @param mixed   $arrParentIds An array of parent IDs
	 * @param string  $strTable     The table name
	 * @param boolean $blnSorting   True if the table has a sorting field
	 * @param array   $arrReturn    The array to be returned
	 * @param string  $strWhere     Additional WHERE condition
	 *
	 * @return array An array of child record IDs
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Database::getChildRecords() instead.
	 */
	protected function getChildRecords($arrParentIds, $strTable, $blnSorting=false, $arrReturn=array(), $strWhere='')
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::getChildRecords()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Database::getChildRecords()" instead.');

		return $this->Database->getChildRecords($arrParentIds, $strTable, $blnSorting, $arrReturn, $strWhere);
	}

	/**
	 * Return the IDs of all parent records of a particular record
	 *
	 * @param integer $intId    The ID of the record
	 * @param string  $strTable The table name
	 *
	 * @return array An array of parent record IDs
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Database::getParentRecords() instead.
	 */
	protected function getParentRecords($intId, $strTable)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::getParentRecords()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Database::getParentRecords()" instead.');

		return $this->Database->getParentRecords($intId, $strTable);
	}

	/**
	 * Print an article as PDF and stream it to the browser
	 *
	 * @param ModuleModel $objArticle An article object
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use ModuleArticle->generatePdf() instead.
	 */
	protected function printArticleAsPdf($objArticle)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::printArticleAsPdf()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\ModuleArticle->generatePdf()" instead.');

		$objArticle = new ModuleArticle($objArticle);
		$objArticle->generatePdf();
	}

	/**
	 * Return all page sections as array
	 *
	 * @return array An array of active page sections
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             See https://github.com/contao/core/issues/4693.
	 */
	public static function getPageSections()
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::getPageSections()" has been deprecated and will no longer work in Contao 5.0.');

		return array('header', 'left', 'right', 'main', 'footer');
	}

	/**
	 * Return a "selected" attribute if the option is selected
	 *
	 * @param string $strOption The option to check
	 * @param mixed  $varValues One or more values to check against
	 *
	 * @return string The attribute or an empty string
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Widget::optionSelected() instead.
	 */
	public static function optionSelected($strOption, $varValues)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::optionSelected()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Widget::optionSelected()" instead.');

		return Widget::optionSelected($strOption, $varValues);
	}

	/**
	 * Return a "checked" attribute if the option is checked
	 *
	 * @param string $strOption The option to check
	 * @param mixed  $varValues One or more values to check against
	 *
	 * @return string The attribute or an empty string
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Widget::optionChecked() instead.
	 */
	public static function optionChecked($strOption, $varValues)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::optionChecked()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Widget::optionChecked()" instead.');

		return Widget::optionChecked($strOption, $varValues);
	}

	/**
	 * Find a content element in the TL_CTE array and return the class name
	 *
	 * @param string $strName The content element name
	 *
	 * @return string The class name
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use ContentElement::findClass() instead.
	 */
	public static function findContentElement($strName)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::findContentElement()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\ContentElement::findClass()" instead.');

		return ContentElement::findClass($strName);
	}

	/**
	 * Find a front end module in the FE_MOD array and return the class name
	 *
	 * @param string $strName The front end module name
	 *
	 * @return string The class name
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Module::findClass() instead.
	 */
	public static function findFrontendModule($strName)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using Contao\Controller::findFrontendModule() has been deprecated and will no longer work in Contao 5.0. Use Contao\Module::findClass() instead.');

		return Module::findClass($strName);
	}

	/**
	 * Create an initial version of a record
	 *
	 * @param string  $strTable The table name
	 * @param integer $intId    The ID of the element to be versioned
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Versions->initialize() instead.
	 */
	protected function createInitialVersion($strTable, $intId)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::createInitialVersion()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Versions->initialize()" instead.');

		$objVersions = new Versions($strTable, $intId);
		$objVersions->initialize();
	}

	/**
	 * Create a new version of a record
	 *
	 * @param string  $strTable The table name
	 * @param integer $intId    The ID of the element to be versioned
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Versions->create() instead.
	 */
	protected function createNewVersion($strTable, $intId)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Controller::createNewVersion()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Versions->create()" instead.');

		$objVersions = new Versions($strTable, $intId);
		$objVersions->create();
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

class_alias(Controller::class, 'Controller');
