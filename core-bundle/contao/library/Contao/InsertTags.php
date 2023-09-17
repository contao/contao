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
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTagFlag;
use Contao\CoreBundle\InsertTag\ChunkedText;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

/**
 * A static class to replace insert tags
 *
 * Usage:
 *
 *     $it = new InsertTags();
 *     echo $it->replace($text);
 */
class InsertTags extends Controller
{
	private const MAX_NESTING_LEVEL = 64;

	/**
	 * @var int
	 */
	private static $intRecursionCount = 0;

	/**
	 * @var array
	 */
	protected static $arrItCache = array();

	/**
	 * @var ?string
	 */
	protected static $strAllowedTagsRegex;

	private InsertTagParser $parser;

	/**
	 * Make the constructor public
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Reset the insert tag cache
	 */
	public static function reset()
	{
		static::$arrItCache = array();
		static::$strAllowedTagsRegex = null;
	}

	/**
	 * @internal
	 */
	public function replaceInternal(string $strBuffer, bool $blnCache, InsertTagParser $parser): ChunkedText
	{
		$this->parser = $parser;

		if (self::$intRecursionCount > self::MAX_NESTING_LEVEL)
		{
			throw new \RuntimeException(sprintf('Maximum insert tag nesting level of %s reached', self::MAX_NESTING_LEVEL));
		}

		++self::$intRecursionCount;

		try
		{
			return $this->executeReplace($strBuffer, $blnCache);
		}
		finally
		{
			--self::$intRecursionCount;
		}
	}

	/**
	 * @internal
	 */
	private function executeReplace(string $strBuffer, bool $blnCache)
	{
		/** @var PageModel $objPage */
		$objPage = $GLOBALS['objPage'] ?? null;

		$container = System::getContainer();

		// Preserve insert tags
		if (!$container->getParameter('contao.insert_tags.allowed_tags'))
		{
			return new ChunkedText(array($strBuffer));
		}

		$strBuffer = $this->encodeHtmlAttributes($strBuffer);

		$strRegExpStart = '{{'           // Starts with two opening curly braces
			. '('                        // Match the contents of the tag
				. '[a-zA-Z0-9\x80-\xFF]' // The first letter must not be a reserved character of Twig, Mustache or similar template engines (see #805)
				. '(?>[^{}]|'            // Match any character not curly brace or a nested insert tag
		;

		$strRegExpEnd = ')*+)}}';        // Ends with two closing curly braces

		$tags = preg_split(
			'(' . $strRegExpStart . str_repeat('{{(?:' . substr($strRegExpStart, 3), 9) . str_repeat($strRegExpEnd, 10) . ')',
			$strBuffer,
			-1,
			PREG_SPLIT_DELIM_CAPTURE
		);

		if ($tags === false)
		{
			throw new \RuntimeException(sprintf('PCRE: %s', preg_last_error_msg()), preg_last_error());
		}

		if (\count($tags) < 2)
		{
			return new ChunkedText(array($strBuffer));
		}

		$arrBuffer = array();
		$blnFeUserLoggedIn = $container->get('contao.security.token_checker')->hasFrontendUser();
		$request = $container->get('request_stack')->getCurrentRequest();

		if (static::$strAllowedTagsRegex === null)
		{
			static::$strAllowedTagsRegex = '(' . implode('|', array_map(
				static function ($allowedTag) {
					return '^' . implode('.+', array_map('preg_quote', explode('*', $allowedTag))) . '$';
				},
				$container->getParameter('contao.insert_tags.allowed_tags')
			)) . ')';
		}

		// Create one cache per cache setting (see #7700)
		$arrCache = &static::$arrItCache[$blnCache];

		for ($_rit=0, $_cnt=\count($tags); $_rit<$_cnt; $_rit+=2)
		{
			$arrBuffer[$_rit] = $tags[$_rit];

			// Skip empty tags
			if (!isset($tags[$_rit+1]))
			{
				break;
			}

			if (!$blnCache || !str_starts_with(strtolower($tags[$_rit+1]), 'fragment::'))
			{
				// Regular insert tags nested inside legacy insert tags need to be replaced by the new parser
				if ($blnCache)
				{
					$tags[$_rit+1] = $this->parser->replace($tags[$_rit+1]);
				}
				else
				{
					$tags[$_rit+1] = $this->parser->replaceInline($tags[$_rit+1]);
				}
			}

			$strTag = $tags[$_rit+1];
			$flags = explode('|', $strTag);
			$tag = array_shift($flags);
			$elements = explode('::', $tag);

			if (preg_match(static::$strAllowedTagsRegex, $elements[0]) !== 1)
			{
				$arrBuffer[$_rit] .= '{{' . $strTag . '}}';
				$arrBuffer[$_rit+1] = '';
				continue;
			}

			// Skip certain elements if the output will be cached
			if ($blnCache)
			{
				if (($elements[1] ?? null) == 'referer' || strncmp($elements[0], 'cache_', 6) === 0)
				{
					trigger_deprecation('contao/core-bundle', '5.0', 'Insert tag naming conventions {{cache_*}} and {{*::referer}} for fragments have been deprecated and will no longer work in Contao 6.0. Use #[AsInsertTag(asFragment: true)] instead.', $elements[0], strtolower($elements[0]));

					/** @var FragmentHandler $fragmentHandler */
					$fragmentHandler = $container->get('fragment.handler');

					$attributes = array('insertTag' => '{{' . $strTag . '}}');

					if (null !== $request && ($scope = $request->attributes->get('_scope')))
					{
						$attributes['_scope'] = $scope;
					}

					$arrBuffer[$_rit+1] = $fragmentHandler->render(
						new ControllerReference(
							InsertTagsController::class . '::renderAction',
							$attributes,
							array('clientCache' => $objPage->clientCache ?? 0, 'pageId' => $objPage->id ?? null, 'request' => Environment::get('requestUri'))
						),
						'esi',
						array('ignore_errors'=>false) // see #48
					);

					continue;
				}
			}

			$arrCache[$strTag] = '';

			if (strtolower($elements[0]) !== $elements[0])
			{
				trigger_deprecation('contao/core-bundle', '5.0', 'Insert tags with uppercase letters ("%s") have been deprecated and will no longer work in Contao 6.0. Use "%s" instead.', $elements[0], strtolower($elements[0]));
			}

			// Replace the tag
			switch (strtolower($elements[0]))
			{
				// HOOK: pass unknown tags to callback functions
				default:
					if (!$this->parser->hasInsertTag(strtolower($elements[0])))
					{
						if (isset($GLOBALS['TL_HOOKS']['replaceInsertTags']) && \is_array($GLOBALS['TL_HOOKS']['replaceInsertTags']))
						{
							trigger_deprecation('contao/core-bundle', '5.2', 'Using the "replaceInsertTags" hook has been deprecated and will no longer work in Contao 6.0. Use the "%s" attribute instead.', AsInsertTag::class);

							foreach ($GLOBALS['TL_HOOKS']['replaceInsertTags'] as $callback)
							{
								$varValue = System::importStatic($callback[0])->{$callback[1]}($tag, $blnCache, '', $flags, $tags, array(), $_rit, $_cnt); // see #6672

								// Replace the tag and stop the loop
								if ($varValue !== false)
								{
									$arrCache[$strTag] = $varValue;
									break 2;
								}
							}
						}

						$container->get('monolog.logger.contao.error')->error('Unknown insert tag {{' . $strTag . '}} on page ' . Environment::get('uri'));
					}

					// Do not use the cache
					unset($arrCache[$strTag]);

					// Output the insert tag as plain string
					$arrBuffer[$_rit] .= '{{' . $strTag . '}}';
					$arrBuffer[$_rit+1] = '';
					continue 2;
			}

			// Handle the flags
			if (!empty($flags))
			{
				foreach ($flags as $flag)
				{
					switch ($flag)
					{
						case 'flatten':
							trigger_deprecation('contao/core-bundle', '5.0', 'The insert tag flag "|flatten" has been deprecated and will no longer work in Contao 6.0. Use a proper insert tag instead.');

							if (\is_array($arrCache[$strTag]))
							{
								$arrCache[$strTag] = ArrayUtil::flattenToString($arrCache[$strTag]);
							}

							break;

						case 'refresh':
							trigger_deprecation('contao/core-bundle', '5.0', 'The insert tag flag "|refresh" has been deprecated and has no effect anymore.');

							// ignore
							break;

						default:
							if (false !== $varValue = $this->parser->renderFlagForLegacyResult($flag, $arrCache[$strTag]))
							{
								$arrCache[$strTag] = $varValue;
								break;
							}

							// HOOK: pass unknown flags to callback functions
							if (isset($GLOBALS['TL_HOOKS']['insertTagFlags']) && \is_array($GLOBALS['TL_HOOKS']['insertTagFlags']))
							{
								trigger_deprecation('contao/core-bundle', '5.2', 'Using the "insertTagFlags" hook has been deprecated and will no longer work in Contao 6.0. Use the "%s" attribute instead.', AsInsertTagFlag::class);

								foreach ($GLOBALS['TL_HOOKS']['insertTagFlags'] as $callback)
								{
									$varValue = System::importStatic($callback[0])->{$callback[1]}($flag, $tag, $arrCache[$strTag], $flags, $blnCache, $tags, array(), $_rit, $_cnt); // see #5806

									// Replace the tag and stop the loop
									if ($varValue !== false)
									{
										$arrCache[$strTag] = $varValue;
										break 2;
									}
								}
							}

							$container->get('monolog.logger.contao.error')->error('Unknown insert tag flag "' . $flag . '" in {{' . $strTag . '}} on page ' . Environment::get('uri'));
							break;
					}
				}
			}

			if (isset($arrCache[$strTag]))
			{
				// Regular insert tags nested inside legacy insert tags need to be replaced by the new parser
				if ($blnCache)
				{
					$arrCache[$strTag] = $this->parser->replace($arrCache[$strTag]);
				}
				else
				{
					$arrCache[$strTag] = $this->parser->replaceInline($arrCache[$strTag]);
				}
			}

			$arrBuffer[$_rit+1] = (string) ($arrCache[$strTag] ?? '');
		}

		return new ChunkedText($arrBuffer);
	}

	/**
	 * Add the specialchars flag to all insert tags used in HTML attributes
	 *
	 * @internal
	 *
	 * @param string $html
	 *
	 * @return string The html with the encoded insert tags
	 */
	public function encodeHtmlAttributes($html)
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
				&& preg_match('(</' . preg_quote($matches[1][0], null) . '[\s/>])i', $html, $endTagMatches, PREG_OFFSET_CAPTURE, $offset)
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
			}

			$attributesResult .= $matches[0][0];
		}

		$attributesResult .= substr($attributes, $offset);

		return $attributesResult;
	}
}
