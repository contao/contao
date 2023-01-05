<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\Image\DeferredImageInterface;
use Symfony\Component\Filesystem\Path;

class Image
{
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
		if (strncmp($src, $webDir . '/', \strlen($webDir) + 1) === 0)
		{
			$src = substr($src, \strlen($webDir) + 1);
		}

		// Check for a dark theme icon and return a picture element if there is one
		if ($objFile->mime == 'image/svg+xml' && str_contains($src, 'system/themes/'))
		{
			$darkSrc = 'system/themes/' . Backend::getTheme() . '/icons-dark/' . $objFile->filename . '.svg';

			if (file_exists(Path::join($projectDir, $darkSrc)))
			{
				return '<img class="color-scheme--dark" style="display:none" src="' . self::getUrl($darkSrc) . '" width="' . $objFile->width . '" height="' . $objFile->height . '" alt="' . StringUtil::specialchars($alt) . '"' . ($attributes ? ' ' . $attributes : '') . '><img class="color-scheme--light" src="' . self::getUrl($src) . '" width="' . $objFile->width . '" height="' . $objFile->height . '" alt="' . StringUtil::specialchars($alt) . '"' . ($attributes ? ' ' . $attributes : '') . '>';
			}
		}

		return '<img src="' . self::getUrl($src) . '" width="' . $objFile->width . '" height="' . $objFile->height . '" alt="' . StringUtil::specialchars($alt) . '"' . ($attributes ? ' ' . $attributes : '') . '>';
	}
}
