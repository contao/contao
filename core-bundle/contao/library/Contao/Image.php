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
		'admin_',
		'admin_two_factor_',
		'article_',
		'children_',
		'copy_',
		'copychilds_',
		'cut_',
		'delete_',
		'diffTemplate_',
		'diff_',
		'edit_',
		'editor_',
		'featured_',
		'group_',
		'header_',
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

		if (strpos($src, '/') !== false)
		{
			return $src;
		}

		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		if (strncmp($src, 'icon', 4) === 0)
		{
			if (pathinfo($src, PATHINFO_EXTENSION) == 'svg')
			{
				return 'assets/contao/images/' . $src;
			}

			$filename = pathinfo($src, PATHINFO_FILENAME);

			// Prefer SVG icons
			if (file_exists($projectDir . '/assets/contao/images/' . $filename . '.svg'))
			{
				return 'assets/contao/images/' . $filename . '.svg';
			}

			return 'assets/contao/images/' . $src;
		}

		$theme = Backend::getTheme();
		$filename = pathinfo($src, PATHINFO_FILENAME);

		if (\in_array($filename, self::$deprecated))
		{
			trigger_deprecation('contao/core-bundle', '5.2', 'Using the "%s" icon has been deprecated and will no longer work in Contao 6. Use the "%s--disabled" icon instead.', $filename, substr($filename, 0, -1));
		}

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
		$src = static::getPath($src);

		if (!$src)
		{
			return '';
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
				return '';
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

			$darkAttributes = new HtmlAttributes($attributes);
			$darkAttributes->mergeWith(array('class' => 'color-scheme--dark', 'loading' => 'lazy'));

			$lightAttributes = new HtmlAttributes($attributes);
			$lightAttributes->mergeWith(array('class' => 'color-scheme--light', 'loading' => 'lazy'));

			return '<img src="' . self::getUrl($darkSrc) . '" width="' . $objFile->width . '" height="' . $objFile->height . '" alt="' . StringUtil::specialchars($alt) . '"' . $darkAttributes . '><img src="' . self::getUrl($src) . '" width="' . $objFile->width . '" height="' . $objFile->height . '" alt="' . StringUtil::specialchars($alt) . '"' . $lightAttributes . '>';
		}

		return '<img src="' . self::getUrl($src) . '" width="' . $objFile->width . '" height="' . $objFile->height . '" alt="' . StringUtil::specialchars($alt) . '"' . ($attributes ? ' ' . $attributes : '') . '>';
	}
}
