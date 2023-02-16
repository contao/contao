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

		$strBuffer = self::encodeHtmlAttributes($strBuffer);

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

			if (!isset($tags[$_rit+1]))
			{
				continue;
			}

			$strTag = $tags[$_rit+1];

			// Skip empty tags
			if (!$strTag)
			{
				continue;
			}

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
					$flags[] = 'attr';
					$arrCache[$strTag] = Date::parse($elements[1] ?: $objPage->dateFormat);
					break;

				// Accessibility tags
				case 'lang':
					if (!$elements[1])
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
					if (!$elements[1])
					{
						$arrCache[$strTag] = '';
						break;
					}

					$strEmail = StringUtil::specialcharsUrl(StringUtil::encodeEmail($elements[1]));

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
					$flags[] = 'attr';
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

					try
					{
						System::loadLanguageFile($file);
					}
					catch (\InvalidArgumentException $exception)
					{
						$this->log('Invalid label insert tag {{' . $strTag . '}} on page ' . Environment::get('uri') . ': ' . $exception->getMessage(), __METHOD__, TL_ERROR);
					}

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
						$flags[] = 'attr';
						$this->import(FrontendUser::class, 'User');
						$value = $this->User->{$elements[1]};

						if (!$value)
						{
							$arrCache[$strTag] = $value;
							break;
						}

						$this->loadDataContainer('tl_member');

						if ($GLOBALS['TL_DCA']['tl_member']['fields'][$elements[1]]['inputType'] == 'password')
						{
							$arrCache[$strTag] = '';
							break;
						}

						$value = StringUtil::deserialize($value);

						// Decrypt the value
						if ($GLOBALS['TL_DCA']['tl_member']['fields'][$elements[1]]['eval']['encrypt'])
						{
							$value = Encryption::decrypt($value);
						}

						$rgxp = $GLOBALS['TL_DCA']['tl_member']['fields'][$elements[1]]['eval']['rgxp'];
						$opts = $GLOBALS['TL_DCA']['tl_member']['fields'][$elements[1]]['options'];
						$rfrc = $GLOBALS['TL_DCA']['tl_member']['fields'][$elements[1]]['reference'];

						if ($rgxp == 'date')
						{
							$arrCache[$strTag] = Date::parse($objPage->dateFormat, $value);
						}
						elseif ($rgxp == 'time')
						{
							$arrCache[$strTag] = Date::parse($objPage->timeFormat, $value);
						}
						elseif ($rgxp == 'datim')
						{
							$arrCache[$strTag] = Date::parse($objPage->datimFormat, $value);
						}
						elseif (\is_array($value))
						{
							$arrCache[$strTag] = implode(', ', $value);
						}
						elseif (\is_array($opts) && array_is_assoc($opts))
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
						@trigger_error('Using the link::back insert tag has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

						$strUrl = 'javascript:history.go(-1)';
						$strTitle = $GLOBALS['TL_LANG']['MSC']['goBack'] ?? null;

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
						$strUrl = StringUtil::specialcharsUrl($elements[1]);
						$strTitle = $elements[1];
						$strName = str_replace(array('http://', 'https://'), '', $strUrl);
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

						$strUrl = '';

						// Do not generate URL for insert tags that don't need it
						if (\in_array(strtolower($elements[0]), array('link', 'link_open', 'link_url'), true))
						{
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
						}

						$strName = $objNextPage->title;
						$strTarget = ($objNextPage->target && 'redirect' === $objNextPage->type) ? ' target="_blank" rel="noreferrer noopener"' : '';
						$strClass = $objNextPage->cssClass ? sprintf(' class="%s"', $objNextPage->cssClass) : '';
						$strTitle = $objNextPage->pageTitle ?: $objNextPage->title;
					}

					// Replace the tag
					switch (strtolower($elements[0]))
					{
						case 'link':
							$arrCache[$strTag] = sprintf('<a href="%s" title="%s"%s%s>%s</a>', $strUrl, StringUtil::specialcharsAttribute($strTitle), $strClass, $strTarget, $strName);
							break;

						case 'link_open':
							$arrCache[$strTag] = sprintf('<a href="%s" title="%s"%s%s>', $strUrl, StringUtil::specialcharsAttribute($strTitle), $strClass, $strTarget);
							break;

						case 'link_url':
							$arrCache[$strTag] = $strUrl;
							break;

						case 'link_title':
							$arrCache[$strTag] = StringUtil::specialcharsAttribute($strTitle);
							break;

						case 'link_target':
							@trigger_error('Using the link_target insert tag has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);
							$arrCache[$strTag] = $strTarget ? ' target=_blank rel=noreferrer&#32;noopener' : $strTarget;
							break;

						case 'link_name':
							$arrCache[$strTag] = StringUtil::specialcharsAttribute($strName);
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
							$arrCache[$strTag] = sprintf('<a href="%s" title="%s">%s</a>', $strUrl, StringUtil::specialcharsAttribute($objArticle->title), $objArticle->title);
							break;

						case 'article_open':
							$arrCache[$strTag] = sprintf('<a href="%s" title="%s">', $strUrl, StringUtil::specialcharsAttribute($objArticle->title));
							break;

						case 'article_url':
							$arrCache[$strTag] = $strUrl;
							break;

						case 'article_title':
							$arrCache[$strTag] = StringUtil::specialcharsAttribute($objArticle->title);
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
					$flags[] = 'attr';

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
						$arrCache[$strTag] = Date::parse($elements[1] ?: $objPage->datimFormat, max($objUpdate->tc, $objUpdate->tn, $objUpdate->te));
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
					$flags[] = 'attr';
					$arrCache[$strTag] = Input::post($elements[1]);
					break;

				// Conditional tags (if, if not)
				case 'iflng':
				case 'ifnlng':
					if (!empty($elements[1]) && $this->languageMatches($elements[1]) === (strtolower($elements[0]) === 'ifnlng'))
					{
						// Skip everything until the next tag
						for (; $_rit<$_cnt; $_rit+=2)
						{
							// Case insensitive match for iflng/ifnlng optionally followed by "::" or "|"
							if (1 === preg_match('/^' . preg_quote($elements[0], '/') . '(?:$|::|\|)/i', $tags[$_rit+3] ?? ''))
							{
								$tags[$_rit+2] = '';
								break;
							}
						}
					}

					// Does not output anything and the cache must not be used
					unset($arrCache[$strTag]);
					continue 2;

				// Environment
				case 'env':
					$flags[] = 'urlattr';

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
					if (!\in_array($elements[1], array('title', 'parentTitle', 'mainTitle', 'rootTitle', 'pageTitle', 'parentPageTitle', 'mainPageTitle', 'rootPageTitle'), true))
					{
						$flags[] = 'attr';
					}

					$arrCache[$strTag] = $objPage->{$elements[1]};
					break;

				// User agent
				case 'ua':
					$flags[] = 'attr';
					$ua = Environment::get('agent');

					if ($elements[1])
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
					if ($elements[1])
					{
						$arrCache[$strTag] = '<abbr title="' . StringUtil::specialchars($elements[1]) . '">';
					}
					else
					{
						$arrCache[$strTag] = '</abbr>';
					}
					break;

				// Images
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
									$width = (int) $value;
									break;

								case 'height':
									$height = (int) $value;
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
						@trigger_error('Using a maximum front end width has been deprecated and will no longer work in Contao 5.0. Remove the "maxImageWidth" configuration and use responsive images instead.', E_USER_DEPRECATED);

						$width = $maxImageWidth;
						$height = null;
					}

					// Use the alternative text from the image meta data if none is given
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
							if (isset($objFile->imageSize[0], $objFile->imageSize[1]))
							{
								$dimensions = ' width="' . $objFile->imageSize[0] . '" height="' . $objFile->imageSize[1] . '"';
							}

							$arrCache[$strTag] = '<img src="' . StringUtil::specialcharsUrl(Controller::addFilesUrlTo($src)) . '" ' . $dimensions . ' alt="' . StringUtil::specialcharsAttribute($alt) . '"' . ($class ? ' class="' . StringUtil::specialcharsAttribute($class) . '"' : '') . '>';
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

							$picture['alt'] = StringUtil::specialcharsAttribute($alt);
							$picture['class'] = StringUtil::specialcharsAttribute($class);
							$pictureTemplate = new FrontendTemplate($strTemplate);
							$pictureTemplate->setData($picture);
							$arrCache[$strTag] = $pictureTemplate->parse();
						}

						// Add a lightbox link
						if ($rel)
						{
							$arrCache[$strTag] = '<a href="' . StringUtil::specialcharsUrl(Controller::addFilesUrlTo($strFile)) . '"' . ($alt ? ' title="' . StringUtil::specialcharsAttribute($alt) . '"' : '') . ' data-lightbox="' . StringUtil::specialcharsAttribute($rel) . '">' . $arrCache[$strTag] . '</a>';
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
						case 'nl2br':
						case 'nl2br_pre':
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

						case 'attr':
							$arrCache[$strTag] = StringUtil::specialcharsAttribute($arrCache[$strTag]);
							break;

						case 'urlattr':
							$arrCache[$strTag] = StringUtil::specialcharsUrl($arrCache[$strTag]);
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

			$strBuffer .= $arrCache[$strTag] ?? '';
		}

		return StringUtil::restoreBasicEntities($strBuffer);
	}

	/**
	 * Add the specialchars flag to all insert tags used in HTML attributes
	 *
	 * @param string $html
	 *
	 * @return string The html with the encoded insert tags
	 */
	private function encodeHtmlAttributes($html)
	{
		if (strpos($html, '{{') === false && strpos($html, '}}') === false)
		{
			return $html;
		}

		// Regular expression to match tags according to https://html.spec.whatwg.org/#tag-open-state
		$tagRegEx = '('
			. '<'                         // Tag start
			. '/?+'                       // Optional slash for closing element
			. '([a-z][^\s/>]*+)'          // Tag name
			. '(?:'                       // Attribute
				. '[\s/]*+'               // Optional white space including slash
				. '[^>\s/][^>\s/=]*+'     // Attribute name
				. '[\s]*+'                // Optional white space
				. '(?:='                  // Assignment
					. '[\s]*+'            // Optional white space
					. '(?:'               // Value
						. '"[^"]*"'       // Double quoted value
						. '|\'[^\']*\''   // Or single quoted value
						. '|[^>][^\s>]*+' // Or unquoted value
					. ')?+'               // Value is optional
				. ')?+'                   // Assignment is optional
			. ')*+'                       // Attributes may occur zero or more times
			. '[\s/]*+'                   // Optional white space including slash
			. '>?+'                       // Tag end (optional if EOF)
			. '|<!--'                     // Or comment
			. '|<!'                       // Or bogus ! comment
			. '|<\?'                      // Or bogus ? comment
			. '|</(?![a-z])'              // Or bogus / comment
		. ')iS';

		$htmlResult = '';
		$offset = 0;

		while (preg_match($tagRegEx, $html, $matches, PREG_OFFSET_CAPTURE, $offset))
		{
			$htmlResult .= substr($html, $offset, $matches[0][1] - $offset);

			// Skip comments
			if (\in_array($matches[0][0], array('<!--', '<!', '</', '<?'), true))
			{
				$commentCloseString = $matches[0][0] === '<!--' ? '-->' : '>';
				$commentClosePos = strpos($html, $commentCloseString, $offset);
				$offset = $commentClosePos ? $commentClosePos + \strlen($commentCloseString) : \strlen($html);

				// Encode insert tags in comments
				$htmlResult .= str_replace(array('{{', '}}'), array('[{]', '[}]'), substr($html, $matches[0][1], $offset - $matches[0][1]));
				continue;
			}

			$tag = $matches[0][0];

			if (strpos($tag, '{{') !== false || strpos($tag, '}}') !== false)
			{
				// Encode insert tags
				$tagPrefix = substr($tag, 0, $matches[1][1] - $matches[0][1] + \strlen($matches[1][0]));
				$tag = $tagPrefix . $this->fixUnclosedTagsAndUrlAttributes(substr($tag, \strlen($tagPrefix)));
				$tag = preg_replace('/(?:\|attr)?}}/', '|attr}}', $tag);
				$tag = str_replace('|urlattr|attr}}', '|urlattr}}', $tag);
			}

			$offset = $matches[0][1] + \strlen($matches[0][0]);
			$htmlResult .= $tag;

			// Skip RCDATA and RAWTEXT elements https://html.spec.whatwg.org/#rcdata-state
			if (
				\in_array(strtolower($matches[1][0]), array('script', 'title', 'textarea', 'style', 'xmp', 'iframe', 'noembed', 'noframes', 'noscript'), true)
				&& preg_match('(</' . preg_quote($matches[1][0]) . '[\s/>])i', $html, $endTagMatches, PREG_OFFSET_CAPTURE, $offset)
			) {
				$offset = $endTagMatches[0][1] + \strlen($endTagMatches[0][0]);
				$htmlResult .= substr($html, $matches[0][1] + \strlen($matches[0][0]), $offset - $matches[0][1] - \strlen($matches[0][0]));
			}
		}

		$htmlResult .= substr($html, $offset);

		return $htmlResult;
	}

	/**
	 * Detect strip and encode unclosed insert tags and add the urlattr flag to
	 * all insert tags used in URL attributes
	 *
	 * @param string $attributes
	 *
	 * @return string The attributes html with the encoded insert tags
	 */
	private function fixUnclosedTagsAndUrlAttributes($attributes)
	{
		$attrRegEx = '('
			. '[\s/]*+'               // Optional white space including slash
			. '([^>\s/][^>\s/=]*+)'   // Attribute name
			. '[\s]*+'                // Optional white space
			. '(?:='                  // Assignment
				. '[\s]*+'            // Optional white space
				. '(?:'               // Value
					. '"[^"]*"'       // Double quoted value
					. '|\'[^\']*\''   // Or single quoted value
					. '|[^>][^\s>]*+' // Or unquoted value
				. ')?+'               // Value is optional
			. ')?+'                   // Assignment is optional
		. ')iS';

		$attributesResult = '';
		$offset = 0;

		while (preg_match($attrRegEx, $attributes, $matches, PREG_OFFSET_CAPTURE, $offset))
		{
			$attributesResult .= substr($attributes, $offset, $matches[0][1] - $offset);
			$offset = $matches[0][1] + \strlen($matches[0][0]);

			// Strip unclosed iflng tags
			$intLastIflng = strripos($matches[0][0], '{{iflng');

			if (
				$intLastIflng !== strripos($matches[0][0], '{{iflng}}')
				&& $intLastIflng !== strripos($matches[0][0], '{{iflng|urlattr}}')
				&& $intLastIflng !== strripos($matches[0][0], '{{iflng|attr}}')
			) {
				$matches[0][0] = StringUtil::stripInsertTags($matches[0][0]);
			}

			// Strip unclosed ifnlng tags
			$intLastIfnlng = strripos($matches[0][0], '{{ifnlng');

			if (
				$intLastIfnlng !== strripos($matches[0][0], '{{ifnlng}}')
				&& $intLastIfnlng !== strripos($matches[0][0], '{{ifnlng|urlattr}}')
				&& $intLastIfnlng !== strripos($matches[0][0], '{{ifnlng|attr}}')
			) {
				$matches[0][0] = StringUtil::stripInsertTags($matches[0][0]);
			}

			// Strip unclosed insert tags
			$intLastOpen = strrpos($matches[0][0], '{{');
			$intLastClose = strrpos($matches[0][0], '}}');

			if ($intLastOpen !== false && ($intLastClose === false || $intLastClose < $intLastOpen))
			{
				$matches[0][0] = StringUtil::stripInsertTags($matches[0][0]);
				$matches[0][0] = str_replace(array('{{', '}}'), array('[{]', '[}]'), $matches[0][0]);
			}
			elseif ($intLastOpen === false && $intLastClose !== false)
			{
				// Improve compatibility with JSON in attributes
				$matches[0][0] = str_replace('}}', '&#125;&#125;', $matches[0][0]);
			}

			// Add the urlattr insert tags flag in URL attributes
			if (\in_array(strtolower($matches[1][0]), array('src', 'srcset', 'href', 'action', 'formaction', 'codebase', 'cite', 'background', 'longdesc', 'profile', 'usemap', 'classid', 'data', 'icon', 'manifest', 'poster', 'archive'), true))
			{
				$matches[0][0] = preg_replace('/(?:\|(?:url)?attr)?}}/', '|urlattr}}', $matches[0][0]);

				// Backwards compatibility
				if (trim($matches[0][0]) === 'href="{{link_url::back|urlattr}}"')
				{
					$matches[0][0] = str_replace('{{link_url::back|urlattr}}', '{{link_url::back}}', $matches[0][0]);
				}
			}

			$attributesResult .= $matches[0][0];
		}

		$attributesResult .= substr($attributes, $offset);

		return $attributesResult;
	}

	/**
	 * Check if the language matches
	 *
	 * @param string $language
	 *
	 * @return boolean
	 */
	private function languageMatches($language)
	{
		global $objPage;

		foreach (StringUtil::trimsplit(',', $language) as $lang)
		{
			if ($objPage->language === $lang)
			{
				return true;
			}

			if (substr($lang, -1) === '*' && 0 === strncmp($objPage->language, $lang, \strlen($lang) - 1))
			{
				return true;
			}
		}

		return false;
	}
}

class_alias(InsertTags::class, 'InsertTags');
