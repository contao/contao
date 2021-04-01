<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Controller\InsertTagsController;
use Contao\CoreBundle\Image\Studio\FigureRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

/**
 * A static class to replace insert tags
 *
 * Usage:
 *
 *     $it = new InsertTags();
 *     echo $it->replace($text);
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InsertTags extends Controller
{
	/**
	 * @var array
	 */
	protected static $arrItCache = array();

	/**
	 * Make the constructor public
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Recursively replace insert tags with their values
	 *
	 * @param string  $strBuffer The text with the tags to be replaced
	 * @param boolean $blnCache  If false, non-cacheable tags will be replaced
	 *
	 * @return string The text with the replaced tags
	 */
	public function replace($strBuffer, $blnCache=true)
	{
		$strBuffer = $this->doReplace($strBuffer, $blnCache);

		// Run the replacement recursively (see #8172)
		while (strpos($strBuffer, '{{') !== false && ($strTmp = $this->doReplace($strBuffer, $blnCache)) != $strBuffer)
		{
			$strBuffer = $strTmp;
		}

		return $strBuffer;
	}

	/**
	 * Reset the insert tag cache
	 */
	public static function reset()
	{
		static::$arrItCache = array();
	}

	/**
	 * Replace insert tags with their values
	 *
	 * @param string  $strBuffer The text with the tags to be replaced
	 * @param boolean $blnCache  If false, non-cacheable tags will be replaced
	 *
	 * @return string The text with the replaced tags
	 */
	protected function doReplace($strBuffer, $blnCache)
	{
		/** @var PageModel $objPage */
		global $objPage;

		// Preserve insert tags
		if (Config::get('disableInsertTags'))
		{
			return StringUtil::restoreBasicEntities($strBuffer);
		}

		// The first letter must not be a reserved character of Twig, Mustache or similar template engines (see #805)
		$tags = preg_split('~{{([a-zA-Z0-9\x80-\xFF][^{}]*)}}~', $strBuffer, -1, PREG_SPLIT_DELIM_CAPTURE);

		if (\count($tags) < 2)
		{
			return StringUtil::restoreBasicEntities($strBuffer);
		}

		$strBuffer = '';
		$container = System::getContainer();
		$blnFeUserLoggedIn = $container->get('contao.security.token_checker')->hasFrontendUser();

		// Create one cache per cache setting (see #7700)
		$arrCache = &static::$arrItCache[$blnCache];

		for ($_rit=0, $_cnt=\count($tags); $_rit<$_cnt; $_rit+=2)
		{
			$strBuffer .= $tags[$_rit];

			// Skip empty tags
			if (empty($tags[$_rit+1]))
			{
				continue;
			}

			$strTag = $tags[$_rit+1];
			$flags = explode('|', $strTag);
			$tag = array_shift($flags);
			$elements = explode('::', $tag);

			// Load the value from cache
			if (isset($arrCache[$strTag]) && $elements[0] != 'page' && !\in_array('refresh', $flags))
			{
				$strBuffer .= $arrCache[$strTag];
				continue;
			}

			// Skip certain elements if the output will be cached
			if ($blnCache)
			{
				if ($elements[0] == 'date' || $elements[0] == 'ua' || $elements[0] == 'post' || ($elements[1] ?? null) == 'back' || ($elements[1] ?? null) == 'referer' || \in_array('uncached', $flags) || strncmp($elements[0], 'cache_', 6) === 0)
				{
					/** @var FragmentHandler $fragmentHandler */
					$fragmentHandler = $container->get('fragment.handler');

					$attributes = array('insertTag' => '{{' . $strTag . '}}');

					/** @var Request|null $request */
					$request = $container->get('request_stack')->getCurrentRequest();

					if (null !== $request && ($scope = $request->attributes->get('_scope')))
					{
						$attributes['_scope'] = $scope;
					}

					$strBuffer .= $fragmentHandler->render(
						new ControllerReference(
							InsertTagsController::class . '::renderAction',
							$attributes,
							array('clientCache' => (int) $objPage->clientCache, 'pageId' => $objPage->id, 'request' => Environment::get('request'))
						),
						'esi',
						array('ignore_errors'=>false) // see #48
					);

					continue;
				}
			}

			$arrCache[$strTag] = '';

			// Replace the tag
			switch (strtolower($elements[0]))
			{
				// Date
				case 'date':
					$arrCache[$strTag] = Date::parse($elements[1] ?: Config::get('dateFormat'));
					break;

				// Accessibility tags
				case 'lang':
					if (empty($elements[1]))
					{
						$arrCache[$strTag] = '</span>';
					}
					else
					{
						$arrCache[$strTag] = $arrCache[$strTag] = '<span lang="' . StringUtil::specialchars($elements[1]) . '">';
					}
					break;

				// Line break
				case 'br':
					$arrCache[$strTag] = '<br>';
					break;

				// E-mail addresses
				case 'email':
				case 'email_open':
				case 'email_url':
					if (empty($elements[1]))
					{
						$arrCache[$strTag] = '';
						break;
					}

					$strEmail = StringUtil::encodeEmail($elements[1]);

					// Replace the tag
					switch (strtolower($elements[0]))
					{
						case 'email':
							$arrCache[$strTag] = '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;' . $strEmail . '" class="email">' . preg_replace('/\?.*$/', '', $strEmail) . '</a>';
							break;

						case 'email_open':
							$arrCache[$strTag] = '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;' . $strEmail . '" title="' . $strEmail . '" class="email">';
							break;

						case 'email_url':
							$arrCache[$strTag] = $strEmail;
							break;
					}
					break;

				// Label tags
				case 'label':
					$keys = explode(':', $elements[1]);

					if (\count($keys) < 2)
					{
						$arrCache[$strTag] = '';
						break;
					}

					$file = $keys[0];

					// Map the key (see #7217)
					switch ($file)
					{
						case 'CNT':
							$file = 'countries';
							break;

						case 'LNG':
							$file = 'languages';
							break;

						case 'MOD':
						case 'FMD':
							$file = 'modules';
							break;

						case 'FFL':
							$file = 'tl_form_field';
							break;

						case 'CACHE':
							$file = 'tl_page';
							break;

						case 'XPL':
							$file = 'explain';
							break;

						case 'XPT':
							$file = 'exception';
							break;

						case 'MSC':
						case 'ERR':
						case 'CTE':
						case 'PTY':
						case 'FOP':
						case 'CHMOD':
						case 'DAYS':
						case 'MONTHS':
						case 'UNITS':
						case 'CONFIRM':
						case 'DP':
						case 'COLS':
						case 'SECTIONS':
						case 'DCA':
						case 'CRAWL':
							$file = 'default';
							break;
					}

					System::loadLanguageFile($file);

					if (\count($keys) == 2)
					{
						$arrCache[$strTag] = $GLOBALS['TL_LANG'][$keys[0]][$keys[1]];
					}
					else
					{
						$arrCache[$strTag] = $GLOBALS['TL_LANG'][$keys[0]][$keys[1]][$keys[2]];
					}
					break;

				// Front end user
				case 'user':
					if ($blnFeUserLoggedIn)
					{
						$this->import(FrontendUser::class, 'User');
						$value = $this->User->{$elements[1]};

						if (!$value)
						{
							$arrCache[$strTag] = $value;
							break;
						}

						$this->loadDataContainer('tl_member');

						if (($GLOBALS['TL_DCA']['tl_member']['fields'][$elements[1]]['inputType'] ?? null) == 'password')
						{
							$arrCache[$strTag] = '';
							break;
						}

						$value = StringUtil::deserialize($value);

						// Decrypt the value
						if ($GLOBALS['TL_DCA']['tl_member']['fields'][$elements[1]]['eval']['encrypt'] ?? null)
						{
							$value = Encryption::decrypt($value);
						}

						$rgxp = $GLOBALS['TL_DCA']['tl_member']['fields'][$elements[1]]['eval']['rgxp'] ?? null;
						$opts = $GLOBALS['TL_DCA']['tl_member']['fields'][$elements[1]]['options'] ?? null;
						$rfrc = $GLOBALS['TL_DCA']['tl_member']['fields'][$elements[1]]['reference'] ?? null;

						if ($rgxp == 'date')
						{
							$arrCache[$strTag] = Date::parse(Config::get('dateFormat'), $value);
						}
						elseif ($rgxp == 'time')
						{
							$arrCache[$strTag] = Date::parse(Config::get('timeFormat'), $value);
						}
						elseif ($rgxp == 'datim')
						{
							$arrCache[$strTag] = Date::parse(Config::get('datimFormat'), $value);
						}
						elseif (\is_array($value))
						{
							$arrCache[$strTag] = implode(', ', $value);
						}
						elseif (\is_array($opts) && ArrayUtil::isAssoc($opts))
						{
							$arrCache[$strTag] = $opts[$value] ?? $value;
						}
						elseif (\is_array($rfrc))
						{
							$arrCache[$strTag] = isset($rfrc[$value]) ? ((\is_array($rfrc[$value])) ? $rfrc[$value][0] : $rfrc[$value]) : $value;
						}
						else
						{
							$arrCache[$strTag] = $value;
						}

						// Convert special characters (see #1890)
						$arrCache[$strTag] = StringUtil::specialchars($arrCache[$strTag]);
					}
					break;

				// Link
				case 'link':
				case 'link_open':
				case 'link_url':
				case 'link_title':
				case 'link_target':
				case 'link_name':
					$strTarget = null;
					$strClass = '';

					// Back link
					if ($elements[1] == 'back')
					{
						$strUrl = 'javascript:history.go(-1)';
						$strTitle = $GLOBALS['TL_LANG']['MSC']['goBack'];

						// No language files if the page is cached
						if (!$strTitle)
						{
							$strTitle = 'Go back';
						}

						$strName = $strTitle;
					}

					// External links
					elseif (strncmp($elements[1], 'http://', 7) === 0 || strncmp($elements[1], 'https://', 8) === 0)
					{
						$strUrl = $elements[1];
						$strTitle = $elements[1];
						$strName = str_replace(array('http://', 'https://'), '', $elements[1]);
					}

					// Regular link
					else
					{
						// User login page
						if ($elements[1] == 'login')
						{
							if (!$blnFeUserLoggedIn)
							{
								break;
							}

							$this->import(FrontendUser::class, 'User');
							$elements[1] = $this->User->loginPage;
						}

						$objNextPage = PageModel::findByIdOrAlias($elements[1]);

						if ($objNextPage === null)
						{
							// Prevent broken markup with link_open and link_close (see #92)
							if (strtolower($elements[0]) == 'link_open')
							{
								$arrCache[$strTag] = '<a>';
							}

							break;
						}

						// Page type specific settings (thanks to Andreas Schempp)
						switch ($objNextPage->type)
						{
							case 'redirect':
								$strUrl = $objNextPage->url;

								if (strncasecmp($strUrl, 'mailto:', 7) === 0)
								{
									$strUrl = StringUtil::encodeEmail($strUrl);
								}
								break;

							case 'forward':
								if ($objNextPage->jumpTo)
								{
									$objNext = PageModel::findPublishedById($objNextPage->jumpTo);
								}
								else
								{
									$objNext = PageModel::findFirstPublishedRegularByPid($objNextPage->id);
								}

								if ($objNext instanceof PageModel)
								{
									$strUrl = \in_array('absolute', $flags, true) ? $objNext->getAbsoluteUrl() : $objNext->getFrontendUrl();
									break;
								}
								// no break

							default:
								$strUrl = \in_array('absolute', $flags, true) ? $objNextPage->getAbsoluteUrl() : $objNextPage->getFrontendUrl();
								break;
						}

						$strName = $objNextPage->title;
						$strTarget = $objNextPage->target ? ' target="_blank" rel="noreferrer noopener"' : '';
						$strClass = $objNextPage->cssClass ? sprintf(' class="%s"', $objNextPage->cssClass) : '';
						$strTitle = $objNextPage->pageTitle ?: $objNextPage->title;
					}

					// Replace the tag
					switch (strtolower($elements[0]))
					{
						case 'link':
							$arrCache[$strTag] = sprintf('<a href="%s" title="%s"%s%s>%s</a>', $strUrl ?: './', StringUtil::specialchars($strTitle), $strClass, $strTarget, $strName);
							break;

						case 'link_open':
							$arrCache[$strTag] = sprintf('<a href="%s" title="%s"%s%s>', $strUrl ?: './', StringUtil::specialchars($strTitle), $strClass, $strTarget);
							break;

						case 'link_url':
							$arrCache[$strTag] = $strUrl ?: './';
							break;

						case 'link_title':
							$arrCache[$strTag] = StringUtil::specialchars($strTitle);
							break;

						case 'link_target':
							$arrCache[$strTag] = $strTarget;
							break;

						case 'link_name':
							$arrCache[$strTag] = $strName;
							break;
					}
					break;

				// Closing link tag
				case 'link_close':
				case 'email_close':
					$arrCache[$strTag] = '</a>';
					break;

				// Insert article
				case 'insert_article':
					if (($strOutput = $this->getArticle($elements[1], false, true)) !== false)
					{
						$arrCache[$strTag] = ltrim($strOutput);
					}
					else
					{
						$arrCache[$strTag] = '<p class="error">' . sprintf($GLOBALS['TL_LANG']['MSC']['invalidPage'], $elements[1]) . '</p>';
					}
					break;

				// Insert content element
				case 'insert_content':
					$arrCache[$strTag] = $this->getContentElement($elements[1]);
					break;

				// Insert module
				case 'insert_module':
					$arrCache[$strTag] = $this->getFrontendModule($elements[1]);
					break;

				// Insert form
				case 'insert_form':
					$arrCache[$strTag] = $this->getForm($elements[1]);
					break;

				// Article
				case 'article':
				case 'article_open':
				case 'article_url':
				case 'article_title':
					if (!(($objArticle = ArticleModel::findByIdOrAlias($elements[1])) instanceof ArticleModel) || !(($objPid = $objArticle->getRelated('pid')) instanceof PageModel))
					{
						break;
					}

					/** @var PageModel $objPid */
					$params = '/articles/' . ($objArticle->alias ?: $objArticle->id);
					$strUrl = \in_array('absolute', $flags, true) ? $objPid->getAbsoluteUrl($params) : $objPid->getFrontendUrl($params);

					// Replace the tag
					switch (strtolower($elements[0]))
					{
						case 'article':
							$arrCache[$strTag] = sprintf('<a href="%s" title="%s">%s</a>', $strUrl, StringUtil::specialchars($objArticle->title), $objArticle->title);
							break;

						case 'article_open':
							$arrCache[$strTag] = sprintf('<a href="%s" title="%s">', $strUrl, StringUtil::specialchars($objArticle->title));
							break;

						case 'article_url':
							$arrCache[$strTag] = $strUrl;
							break;

						case 'article_title':
							$arrCache[$strTag] = StringUtil::specialchars($objArticle->title);
							break;
					}
					break;

				// Article teaser
				case 'article_teaser':
					$objTeaser = ArticleModel::findByIdOrAlias($elements[1]);

					if ($objTeaser !== null)
					{
						$arrCache[$strTag] = StringUtil::toHtml5($objTeaser->teaser);
					}
					break;

				// Last update
				case 'last_update':
					$strQuery = "SELECT MAX(tstamp) AS tc";
					$bundles = $container->getParameter('kernel.bundles');

					if (isset($bundles['ContaoNewsBundle']))
					{
						$strQuery .= ", (SELECT MAX(tstamp) FROM tl_news) AS tn";
					}

					if (isset($bundles['ContaoCalendarBundle']))
					{
						$strQuery .= ", (SELECT MAX(tstamp) FROM tl_calendar_events) AS te";
					}

					$strQuery .= " FROM tl_content";
					$objUpdate = Database::getInstance()->query($strQuery);

					if ($objUpdate->numRows)
					{
						$arrCache[$strTag] = Date::parse($elements[1] ?: Config::get('datimFormat'), max($objUpdate->tc, $objUpdate->tn, $objUpdate->te));
					}
					break;

				// Version
				case 'version':
					$arrCache[$strTag] = VERSION . '.' . BUILD;
					break;

				// Request token
				case 'request_token':
					$arrCache[$strTag] = REQUEST_TOKEN;
					break;

				// POST data
				case 'post':
					$arrCache[$strTag] = Input::post($elements[1]);
					break;

				// Conditional tags (if)
				case 'iflng':
					if ($elements[1])
					{
						$langs = StringUtil::trimsplit(',', $elements[1]);

						// Check if there are wildcards (see #8313)
						foreach ($langs as $k=>$v)
						{
							if (substr($v, -1) == '*')
							{
								$langs[$k] = substr($v, 0, -1);

								if (\strlen($objPage->language) > 2 && 0 === strncmp($objPage->language, $langs[$k], 2))
								{
									$langs[] = $objPage->language;
								}
							}
						}

						if (!\in_array($objPage->language, $langs))
						{
							for (; $_rit<$_cnt; $_rit+=2)
							{
								if ($tags[$_rit+1] == 'iflng' || $tags[$_rit+1] == 'iflng::' . $objPage->language)
								{
									break;
								}
							}
						}
					}
					unset($arrCache[$strTag]);
					break;

				// Conditional tags (if not)
				case 'ifnlng':
					if ($elements[1])
					{
						$langs = StringUtil::trimsplit(',', $elements[1]);

						// Check if there are wildcards (see #8313)
						foreach ($langs as $k=>$v)
						{
							if (substr($v, -1) == '*')
							{
								$langs[$k] = substr($v, 0, -1);

								if (\strlen($objPage->language) > 2 && 0 === strncmp($objPage->language, $langs[$k], 2))
								{
									$langs[] = $objPage->language;
								}
							}
						}

						if (\in_array($objPage->language, $langs))
						{
							for (; $_rit<$_cnt; $_rit+=2)
							{
								if ($tags[$_rit+1] == 'ifnlng')
								{
									break;
								}
							}
						}
					}
					unset($arrCache[$strTag]);
					break;

				// Environment
				case 'env':
					switch ($elements[1])
					{
						case 'host':
							$arrCache[$strTag] = Idna::decode(Environment::get('host'));
							break;

						case 'http_host':
							$arrCache[$strTag] = Idna::decode(Environment::get('httpHost'));
							break;

						case 'url':
							$arrCache[$strTag] = Idna::decode(Environment::get('url'));
							break;

						case 'path':
							$arrCache[$strTag] = Idna::decode(Environment::get('base'));
							break;

						case 'request':
							$arrCache[$strTag] = Environment::get('indexFreeRequest');
							break;

						case 'ip':
							$arrCache[$strTag] = Environment::get('ip');
							break;

						case 'referer':
							$arrCache[$strTag] = $this->getReferer(true);
							break;

						case 'files_url':
							$arrCache[$strTag] = $container->get('contao.assets.files_context')->getStaticUrl();
							break;

						case 'assets_url':
						case 'plugins_url':
						case 'script_url':
							$arrCache[$strTag] = $container->get('contao.assets.assets_context')->getStaticUrl();
							break;

						case 'base_url':
							$arrCache[$strTag] = $container->get('request_stack')->getCurrentRequest()->getBaseUrl();
							break;
					}
					break;

				// Page
				case 'page':
					if (!$objPage->pageTitle && $elements[1] == 'pageTitle')
					{
						$elements[1] = 'title';
					}
					elseif (!$objPage->parentPageTitle && $elements[1] == 'parentPageTitle')
					{
						$elements[1] = 'parentTitle';
					}
					elseif (!$objPage->mainPageTitle && $elements[1] == 'mainPageTitle')
					{
						$elements[1] = 'mainTitle';
					}

					// Do not use StringUtil::specialchars() here (see #4687)
					$arrCache[$strTag] = $objPage->{$elements[1]};
					break;

				// User agent
				case 'ua':
					$ua = Environment::get('agent');

					if (!empty($elements[1]))
					{
						$arrCache[$strTag] = $ua->{$elements[1]};
					}
					else
					{
						$arrCache[$strTag] = '';
					}
					break;

				// Abbreviations
				case 'abbr':
				case 'acronym':
					if (!empty($elements[1]))
					{
						$arrCache[$strTag] = '<abbr title="' . StringUtil::specialchars($elements[1]) . '">';
					}
					else
					{
						$arrCache[$strTag] = '</abbr>';
					}
					break;

				// Images
				case 'figure':
					// Expected format: {{figure::<from>[?<key>=<value>,[&<key>=<value>]*]}}
					list($from, $configuration) = $this->parseUrlWithQueryString($elements[1] ?? '');

					if (null === $from || 2 !== \count($elements))
					{
						$arrCache[$strTag] = '';
						break;
					}

					$size = $configuration['size'] ?? null;
					$template = $configuration['template'] ?? '@ContaoCore/Image/Studio/figure.html.twig';

					unset($configuration['size'], $configuration['template']);

					// Render the figure
					$figureRenderer = $container->get(FigureRenderer::class);

					try
					{
						$arrCache[$strTag] = $figureRenderer->render($from, $size, $configuration, $template) ?? '';
					}
					catch (\Throwable $e)
					{
						$arrCache[$strTag] = '';
					}
					break;

				case 'image':
				case 'picture':
					$width = null;
					$height = null;
					$alt = '';
					$class = '';
					$rel = '';
					$strFile = $elements[1];
					$mode = '';
					$size = null;
					$strTemplate = 'picture_default';

					// Take arguments
					if (strpos($elements[1], '?') !== false)
					{
						$arrChunks = explode('?', urldecode($elements[1]), 2);
						$strSource = StringUtil::decodeEntities($arrChunks[1]);
						$strSource = str_replace('[&]', '&', $strSource);
						$arrParams = explode('&', $strSource);

						foreach ($arrParams as $strParam)
						{
							list($key, $value) = explode('=', $strParam);

							switch ($key)
							{
								case 'width':
									$width = $value;
									break;

								case 'height':
									$height = $value;
									break;

								case 'alt':
									$alt = $value;
									break;

								case 'class':
									$class = $value;
									break;

								case 'rel':
									$rel = $value;
									break;

								case 'mode':
									$mode = $value;
									break;

								case 'size':
									$size = is_numeric($value) ? (int) $value : $value;
									break;

								case 'template':
									$strTemplate = preg_replace('/[^a-z0-9_]/i', '', $value);
									break;
							}
						}

						$strFile = $arrChunks[0];
					}

					if (Validator::isUuid($strFile))
					{
						// Handle UUIDs
						$objFile = FilesModel::findByUuid($strFile);

						if ($objFile === null)
						{
							$arrCache[$strTag] = '';
							break;
						}

						$strFile = $objFile->path;
					}
					elseif (is_numeric($strFile))
					{
						// Handle numeric IDs (see #4805)
						$objFile = FilesModel::findByPk($strFile);

						if ($objFile === null)
						{
							$arrCache[$strTag] = '';
							break;
						}

						$strFile = $objFile->path;
					}
					elseif (Validator::isInsecurePath($strFile))
					{
						throw new \RuntimeException('Invalid path ' . $strFile);
					}

					$maxImageWidth = Config::get('maxImageWidth');

					// Check the maximum image width
					if ($maxImageWidth > 0 && $width > $maxImageWidth)
					{
						trigger_deprecation('contao/core-bundle', '4.0', 'Using a maximum front end width has been deprecated and will no longer work in Contao 5.0. Remove the "maxImageWidth" configuration and use responsive images instead.');

						$width = $maxImageWidth;
						$height = null;
					}

					// Use the alternative text from the image metadata if none is given
					if (!$alt && ($objFile = FilesModel::findByPath($strFile)))
					{
						$arrMeta = Frontend::getMetaData($objFile->meta, $objPage->language);

						if (isset($arrMeta['alt']))
						{
							$alt = $arrMeta['alt'];
						}
					}

					// Generate the thumbnail image
					try
					{
						// Image
						if (strtolower($elements[0]) == 'image')
						{
							$dimensions = '';
							$src = $container->get('contao.image.image_factory')->create($container->getParameter('kernel.project_dir') . '/' . rawurldecode($strFile), array($width, $height, $mode))->getUrl($container->getParameter('kernel.project_dir'));
							$objFile = new File(rawurldecode($src));

							// Add the image dimensions
							if (($imgSize = $objFile->imageSize) !== false)
							{
								$dimensions = ' width="' . StringUtil::specialchars($imgSize[0]) . '" height="' . StringUtil::specialchars($imgSize[1]) . '"';
							}

							$arrCache[$strTag] = '<img src="' . Controller::addFilesUrlTo($src) . '" ' . $dimensions . ' alt="' . StringUtil::specialchars($alt) . '"' . ($class ? ' class="' . StringUtil::specialchars($class) . '"' : '') . '>';
						}

						// Picture
						else
						{
							$staticUrl = $container->get('contao.assets.files_context')->getStaticUrl();
							$picture = $container->get('contao.image.picture_factory')->create($container->getParameter('kernel.project_dir') . '/' . $strFile, $size);

							$picture = array
							(
								'img' => $picture->getImg($container->getParameter('kernel.project_dir'), $staticUrl),
								'sources' => $picture->getSources($container->getParameter('kernel.project_dir'), $staticUrl)
							);

							$picture['alt'] = $alt;
							$picture['class'] = $class;
							$pictureTemplate = new FrontendTemplate($strTemplate);
							$pictureTemplate->setData($picture);
							$arrCache[$strTag] = $pictureTemplate->parse();
						}

						// Add a lightbox link
						if ($rel)
						{
							$arrCache[$strTag] = '<a href="' . Controller::addFilesUrlTo($strFile) . '"' . ($alt ? ' title="' . StringUtil::specialchars($alt) . '"' : '') . ' data-lightbox="' . StringUtil::specialchars($rel) . '">' . $arrCache[$strTag] . '</a>';
						}
					}
					catch (\Exception $e)
					{
						$arrCache[$strTag] = '';
					}
					break;

				// Files (UUID or template path)
				case 'file':
					if (Validator::isUuid($elements[1]))
					{
						$objFile = FilesModel::findByUuid($elements[1]);

						if ($objFile !== null)
						{
							$arrCache[$strTag] = System::urlEncode($objFile->path);
							break;
						}
					}

					$arrGet = $_GET;
					Input::resetCache();
					$strFile = $elements[1];

					// Take arguments and add them to the $_GET array
					if (strpos($elements[1], '?') !== false)
					{
						$arrChunks = explode('?', urldecode($elements[1]));
						$strSource = StringUtil::decodeEntities($arrChunks[1]);
						$strSource = str_replace('[&]', '&', $strSource);
						$arrParams = explode('&', $strSource);

						foreach ($arrParams as $strParam)
						{
							$arrParam = explode('=', $strParam);
							$_GET[$arrParam[0]] = $arrParam[1];
						}

						$strFile = $arrChunks[0];
					}

					// Check the path
					if (Validator::isInsecurePath($strFile))
					{
						throw new \RuntimeException('Invalid path ' . $strFile);
					}

					// Include .php, .tpl, .xhtml and .html5 files
					if (preg_match('/\.(php|tpl|xhtml|html5)$/', $strFile) && file_exists($container->getParameter('kernel.project_dir') . '/templates/' . $strFile))
					{
						ob_start();

						try
						{
							include $container->getParameter('kernel.project_dir') . '/templates/' . $strFile;
							$arrCache[$strTag] = ob_get_contents();
						}
						finally
						{
							ob_end_clean();
						}
					}

					$_GET = $arrGet;
					Input::resetCache();
					break;

				// HOOK: pass unknown tags to callback functions
				default:
					if (isset($GLOBALS['TL_HOOKS']['replaceInsertTags']) && \is_array($GLOBALS['TL_HOOKS']['replaceInsertTags']))
					{
						foreach ($GLOBALS['TL_HOOKS']['replaceInsertTags'] as $callback)
						{
							$this->import($callback[0]);
							$varValue = $this->{$callback[0]}->{$callback[1]}($tag, $blnCache, $arrCache[$strTag], $flags, $tags, $arrCache, $_rit, $_cnt); // see #6672

							// Replace the tag and stop the loop
							if ($varValue !== false)
							{
								$arrCache[$strTag] = $varValue;
								break 2;
							}
						}
					}

					$this->log('Unknown insert tag {{' . $strTag . '}} on page ' . Environment::get('uri'), __METHOD__, TL_ERROR);
					break;
			}

			// Handle the flags
			if (!empty($flags))
			{
				foreach ($flags as $flag)
				{
					switch ($flag)
					{
						case 'addslashes':
						case 'standardize':
						case 'ampersand':
						case 'specialchars':
						case 'strtolower':
						case 'utf8_strtolower':
						case 'strtoupper':
						case 'utf8_strtoupper':
						case 'ucfirst':
						case 'lcfirst':
						case 'ucwords':
						case 'trim':
						case 'rtrim':
						case 'ltrim':
						case 'utf8_romanize':
						case 'urlencode':
						case 'rawurlencode':
							$arrCache[$strTag] = $flag($arrCache[$strTag]);
							break;

						case 'nl2br_pre':
							trigger_deprecation('contao/core-bundle', '4.0', 'Using nl2br_pre() has been deprecated and will no longer work in Contao 5.0.');
							// no break

						case 'nl2br':
							$arrCache[$strTag] = preg_replace('/\r?\n/', '<br>', $arrCache[$strTag]);
							break;

						case 'encodeEmail':
							$arrCache[$strTag] = StringUtil::$flag($arrCache[$strTag]);
							break;

						case 'number_format':
							$arrCache[$strTag] = System::getFormattedNumber($arrCache[$strTag], 0);
							break;

						case 'currency_format':
							$arrCache[$strTag] = System::getFormattedNumber($arrCache[$strTag]);
							break;

						case 'readable_size':
							$arrCache[$strTag] = System::getReadableSize($arrCache[$strTag]);
							break;

						case 'flatten':
							if (!\is_array($arrCache[$strTag]))
							{
								break;
							}

							$it = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($arrCache[$strTag]));
							$result = array();

							foreach ($it as $leafValue)
							{
								$keys = array();

								foreach (range(0, $it->getDepth()) as $depth)
								{
									$keys[] = $it->getSubIterator($depth)->key();
								}

								$result[] = implode('.', $keys) . ': ' . $leafValue;
							}

							$arrCache[$strTag] = implode(', ', $result);
							break;

						case 'absolute':
						case 'refresh':
						case 'uncached':
							// ignore
							break;

						// HOOK: pass unknown flags to callback functions
						default:
							if (isset($GLOBALS['TL_HOOKS']['insertTagFlags']) && \is_array($GLOBALS['TL_HOOKS']['insertTagFlags']))
							{
								foreach ($GLOBALS['TL_HOOKS']['insertTagFlags'] as $callback)
								{
									$this->import($callback[0]);
									$varValue = $this->{$callback[0]}->{$callback[1]}($flag, $tag, $arrCache[$strTag], $flags, $blnCache, $tags, $arrCache, $_rit, $_cnt); // see #5806

									// Replace the tag and stop the loop
									if ($varValue !== false)
									{
										$arrCache[$strTag] = $varValue;
										break 2;
									}
								}
							}

							$this->log('Unknown insert tag flag "' . $flag . '" in {{' . $strTag . '}} on page ' . Environment::get('uri'), __METHOD__, TL_ERROR);
							break;
					}
				}
			}

			$strBuffer .= $arrCache[$strTag];
		}

		return StringUtil::restoreBasicEntities($strBuffer);
	}

	/**
	 * @return array<string|null, array>
	 */
	private function parseUrlWithQueryString(string $url): array
	{
		// Restore [&]
		$url = str_replace('[&]', '&', $url);

		$base = parse_url($url, PHP_URL_PATH) ?: null;
		$query = parse_url($url, PHP_URL_QUERY) ?: '';

		parse_str($query, $attributes);

		// Cast and encode values
		array_walk_recursive($attributes, static function (&$value)
		{
			if (is_numeric($value))
			{
				$value = (int) $value;

				return;
			}

			$value = StringUtil::specialchars($value);
		});

		return array($base, $attributes);
	}
}

class_alias(InsertTags::class, 'InsertTags');
