<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\String\HtmlAttributes;
use Contao\Image\DeferredImageInterface;
use Symfony\Component\Filesystem\Path;

class Image
{
	private static array $deprecated = array
	(
		'alias',
		'copychilds',
		'copychilds_',
		'filemanager',
		'folMinus',
		'folPlus',
		'header',
		'header_',
		'important',
		'manager',
		'pickfile',
		'settings',
		'unpublished',
	);

	private static array $disabled = array
	(
		'admin_',
		'admin_two_factor_',
		'article_',
		'children_',
		'copy_',
		'cut_',
		'delete_',
		'diff_',
		'diffTemplate_',
		'edit_',
		'editor_',
		'featured_',
		'group_',
		'layout_',
		'member_',
		'member_two_factor_',
		'mgroup_',
		'modules_',
		'parent_',
		'pasteafter_',
		'pasteinto_',
		'share_',
		'sizes_',
		'su_',
		'theme_export_',
		'user_',
		'user_two_factor_',
		'visible_',
	);

	private static array $htmlTemplateCache = array();

	/**
	 * Get the relative path to an image
	 *
	 * @param string $src The image name or path
	 *
	 * @return string The relative path
	 */
	public static function getPath($src)
	{
		if (!$src)
		{
			return '';
		}

		$src = rawurldecode($src);

		if (str_contains($src, '/'))
		{
			return $src;
		}

		if (str_starts_with($src, 'icon'))
		{
			return 'assets/contao/images/' . $src;
		}

		$theme = Backend::getTheme();
		$filename = pathinfo($src, PATHINFO_FILENAME);

		if (\in_array($filename, self::$deprecated))
		{
			trigger_deprecation('contao/core-bundle', '5.2', 'Using the "%s" icon has been deprecated and will no longer work in Contao 6.');
		}
		elseif (\in_array($filename, self::$disabled))
		{
			trigger_deprecation('contao/core-bundle', '5.2', 'Using the "%s" icon has been deprecated and will no longer work in Contao 6. Use the "%s--disabled" icon instead.', $filename, substr($filename, 0, -1));
		}

		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		// Prefer SVG icons
		if (file_exists($projectDir . '/system/themes/' . $theme . '/icons/' . $filename . '.svg'))
		{
			return 'system/themes/' . $theme . '/icons/' . $filename . '.svg';
		}

		return 'system/themes/' . $theme . '/images/' . $src;
	}

	/**
	 * Get the URL to an image
	 *
	 * @param string $src The image name or path
	 *
	 * @return string The path-absolute or absolute URL depending on the assets context
	 */
	public static function getUrl($src)
	{
		$src = System::urlEncode(self::getPath($src));

		if (!$src)
		{
			return '';
		}

		$context = str_starts_with($src, 'assets/') ? 'assets_context' : 'files_context';

		return Controller::addStaticUrlTo($src, System::getContainer()->get('contao.assets.' . $context));
	}

	/**
	 * Generate an image tag and return it as string
	 *
	 * @param string $src        The image path
	 * @param string $alt        An optional alt attribute
	 * @param string $attributes A string of other attributes
	 *
	 * @return string The image HTML tag
	 */
	public static function getHtml($src, $alt='', $attributes='')
	{
		$template = self::getHtmlTemplate($src);

		$search = array('{alt}', '{attributes}');
		$replace = array(StringUtil::specialchars($alt), $attributes ? ' ' . $attributes : '');

		if (str_contains($template, '{darkAttributes}'))
		{
			$darkAttributes = new HtmlAttributes($attributes);

			foreach (array('data-icon', 'data-icon-disabled') as $icon)
			{
				if (isset($darkAttributes[$icon]))
				{
					$pathinfo = pathinfo($darkAttributes[$icon]);
					$darkAttributes[$icon] = $pathinfo['filename'] . '--dark.' . $pathinfo['extension'];
				}
			}

			$search[] = '{darkAttributes}';
			$replace[] = $darkAttributes->mergeWith(array('class' => 'color-scheme--dark', 'loading' => 'lazy'))->toString();
		}

		if (str_contains($template, '{lightAttributes}'))
		{
			$search[] = '{lightAttributes}';
			$replace[] = (new HtmlAttributes($attributes))->mergeWith(array('class' => 'color-scheme--light', 'loading' => 'lazy'))->toString();
		}

		return str_replace($search, $replace, $template);
	}

	private static function getHtmlTemplate($src): string
	{
		if (!$src)
		{
			return '';
		}

		$cacheKey = $src;

		if (isset(self::$htmlTemplateCache[$cacheKey]))
		{
			return self::$htmlTemplateCache[$cacheKey];
		}

		$src = static::getPath($src);

		if (!$src)
		{
			return self::$htmlTemplateCache[$cacheKey] = '';
		}

		$container = System::getContainer();
		$projectDir = $container->getParameter('kernel.project_dir');
		$webDir = StringUtil::stripRootDir($container->getParameter('contao.web_dir'));

		if (!is_file($projectDir . '/' . $src))
		{
			try
			{
				$deferredImage = $container->get('contao.image.factory')->create($projectDir . '/' . $src);
			}
			catch (\Exception $e)
			{
				$deferredImage = null;
			}

			// Handle public bundle resources
			if (file_exists($projectDir . '/' . $webDir . '/' . $src))
			{
				$src = $webDir . '/' . $src;
			}
			elseif (!$deferredImage instanceof DeferredImageInterface)
			{
				return self::$htmlTemplateCache[$cacheKey] = '';
			}
		}

		$objFile = new File($src);

		// Strip the contao.web_dir directory prefix (see #337)
		if (str_starts_with($src, $webDir . '/'))
		{
			$src = substr($src, \strlen($webDir) + 1);
		}

		$darkSrc = \dirname($objFile->path) . '/' . $objFile->filename . '--dark.' . $objFile->extension;

		// Check for a dark theme icon and return a picture element if there is one
		if (file_exists(Path::join($projectDir, $darkSrc)))
		{
			// Strip the contao.web_dir directory prefix (see #337)
			if (str_starts_with($darkSrc, $webDir . '/'))
			{
				$darkSrc = substr($darkSrc, \strlen($webDir) + 1);
			}

			return self::$htmlTemplateCache[$cacheKey] = '<img src="' . self::getUrl($darkSrc) . '" width="' . $objFile->width . '" height="' . $objFile->height . '" alt="{alt}"{darkAttributes}><img src="' . self::getUrl($src) . '" width="' . $objFile->width . '" height="' . $objFile->height . '" alt="{alt}"{lightAttributes}>';
		}

		return self::$htmlTemplateCache[$cacheKey] = '<img src="' . self::getUrl($src) . '" width="' . $objFile->width . '" height="' . $objFile->height . '" alt="{alt}"{attributes}>';
	}
}
