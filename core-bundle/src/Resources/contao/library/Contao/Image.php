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

		if (pathinfo($src, PATHINFO_EXTENSION) == 'svg')
		{
			return 'system/themes/' . $theme . '/icons/' . $src;
		}

		$filename = pathinfo($src, PATHINFO_FILENAME);

		// Prefer SVG icons
		if (file_exists($projectDir . '/system/themes/' . $theme . '/icons/' . $filename . '.svg'))
		{
			return 'system/themes/' . $theme . '/icons/' . $filename . '.svg';
		}

		return 'system/themes/' . $theme . '/images/' . $src;
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

		$context = (strncmp($src, 'assets/', 7) === 0) ? 'assets_context' : 'files_context';

		return '<img src="' . Controller::addStaticUrlTo(System::urlEncode($src), $container->get('contao.assets.' . $context)) . '" width="' . $objFile->width . '" height="' . $objFile->height . '" alt="' . StringUtil::specialchars($alt) . '"' . ($attributes ? ' ' . $attributes : '') . '>';
	}
}
