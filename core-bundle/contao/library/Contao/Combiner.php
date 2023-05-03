<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;

/**
 * Combines .css or .js files into one single file
 *
 * Usage:
 *
 *     $combiner = new Combiner();
 *
 *     $combiner->add('css/style.css');
 *     $combiner->add('css/fonts.scss');
 *     $combiner->add('css/print.less');
 *
 *     echo $combiner->getCombinedFile();
 */
class Combiner extends System
{
	/**
	 * The .css file extension
	 */
	const CSS = '.css';

	/**
	 * The .js file extension
	 */
	const JS = '.js';

	/**
	 * The .scss file extension
	 */
	const SCSS = '.scss';

	/**
	 * The .less file extension
	 */
	const LESS = '.less';

	/**
	 * Unique file key
	 * @var string
	 */
	protected $strKey = '';

	/**
	 * Operation mode
	 * @var string
	 */
	protected $strMode;

	/**
	 * Files
	 * @var array
	 */
	protected $arrFiles = array();

	/**
	 * Root dir
	 * @var string
	 */
	protected $strRootDir;

	/**
	 * Web dir relative to $this->strRootDir
	 * @var string
	 */
	protected $strWebDir;

	/**
	 * Public constructor required
	 */
	public function __construct()
	{
		$container = System::getContainer();

		$this->strRootDir = $container->getParameter('kernel.project_dir');
		$this->strWebDir = StringUtil::stripRootDir($container->getParameter('contao.web_dir'));
	}

	/**
	 * Add a file to the combined file
	 *
	 * @param string $strFile    The file to be added
	 * @param string $strVersion An optional version number
	 * @param string $strMedia   The media type of the file (.css only)
	 *
	 * @throws \InvalidArgumentException If $strFile is invalid
	 * @throws \LogicException           If different file types are mixed
	 */
	public function add($strFile, $strVersion=null, $strMedia='all')
	{
		$strType = strrchr($strFile, '.');

		// Check the file type
		if ($strType != self::CSS && $strType != self::JS && $strType != self::SCSS && $strType != self::LESS)
		{
			throw new \InvalidArgumentException("Invalid file $strFile");
		}

		$strMode = ($strType == self::JS) ? self::JS : self::CSS;

		// Set the operation mode
		if ($this->strMode === null)
		{
			$this->strMode = $strMode;
		}
		elseif ($this->strMode != $strMode)
		{
			throw new \LogicException('You cannot mix different file types. Create another Combiner object instead.');
		}

		// Check the source file
		if (!file_exists($this->strRootDir . '/' . $strFile))
		{
			// Handle public bundle resources in the contao.web_dir folder
			if (file_exists($this->strRootDir . '/' . $this->strWebDir . '/' . $strFile))
			{
				$strFile = $this->strWebDir . '/' . $strFile;
			}
			else
			{
				return;
			}
		}

		// Prevent duplicates
		if (isset($this->arrFiles[$strFile]))
		{
			return;
		}

		// Default version
		if ($strVersion === null)
		{
			$strVersion = filemtime($this->strRootDir . '/' . $strFile);
		}

		// Store the file
		$arrFile = array
		(
			'name' => $strFile,
			'version' => $strVersion,
			'media' => $strMedia,
			'extension' => $strType
		);

		$this->arrFiles[$strFile] = $arrFile;
		$this->strKey .= '-f' . $strFile . '-v' . $strVersion . '-m' . $strMedia;
	}

	/**
	 * Add multiple files from an array
	 *
	 * @param array  $arrFiles   An array of files to be added
	 * @param string $strVersion An optional version number
	 * @param string $strMedia   The media type of the file (.css only)
	 */
	public function addMultiple(array $arrFiles, $strVersion=null, $strMedia='screen')
	{
		foreach ($arrFiles as $strFile)
		{
			$this->add($strFile, $strVersion, $strMedia);
		}
	}

	/**
	 * Check whether files have been added
	 *
	 * @return boolean True if there are files
	 */
	public function hasEntries()
	{
		return !empty($this->arrFiles);
	}

	/**
	 * Generates the files and returns the URLs.
	 *
	 * @param string $strUrl An optional URL to prepend
	 *
	 * @return array The file URLs
	 */
	public function getFileUrls($strUrl=null)
	{
		if ($strUrl === null)
		{
			$strUrl = System::getContainer()->get('contao.assets.assets_context')->getStaticUrl();
		}

		$return = array();
		$strTarget = substr($this->strMode, 1);
		$blnDebug = System::getContainer()->getParameter('kernel.debug');

		foreach ($this->arrFiles as $arrFile)
		{
			// Compile SCSS/LESS files into temporary files
			if ($arrFile['extension'] == self::SCSS || $arrFile['extension'] == self::LESS)
			{
				$strPath = 'assets/' . $strTarget . '/' . str_replace('/', '_', $arrFile['name']) . $this->strMode;

				if ($blnDebug || !file_exists($this->strRootDir . '/' . $strPath))
				{
					$objFile = new File($strPath);
					$objFile->write($this->handleScssLess(file_get_contents($this->strRootDir . '/' . $arrFile['name']), $arrFile));
					$objFile->close();
				}

				$return[] = $strUrl . $strPath . '|' . $arrFile['version'];
			}
			else
			{
				$name = $arrFile['name'];

				// Strip the contao.web_dir directory prefix (see #328)
				if (strncmp($name, $this->strWebDir . '/', \strlen($this->strWebDir) + 1) === 0)
				{
					$name = substr($name, \strlen($this->strWebDir) + 1);
				}

				// Add the media query (see #7070)
				if ($this->strMode == self::CSS && $arrFile['media'] && $arrFile['media'] != 'all' && !$this->hasMediaTag($arrFile['name']))
				{
					$name .= '|' . $arrFile['media'];
				}

				$return[] = $strUrl . $name . '|' . $arrFile['version'];
			}
		}

		return $return;
	}

	/**
	 * Generate the combined file and return its path
	 *
	 * @param string $strUrl An optional URL to prepend
	 *
	 * @return string The path to the combined file
	 */
	public function getCombinedFile($strUrl=null)
	{
		if (System::getContainer()->getParameter('kernel.debug'))
		{
			return $this->getDebugMarkup($strUrl);
		}

		return $this->getCombinedFileUrl($strUrl);
	}

	/**
	 * Generates the debug markup.
	 *
	 * @param string $strUrl An optional URL to prepend
	 *
	 * @return string The debug markup
	 */
	protected function getDebugMarkup($strUrl)
	{
		$return = $this->getFileUrls($strUrl);

		foreach ($return as $k=>$v)
		{
			$options = StringUtil::resolveFlaggedUrl($v);
			$return[$k] = $v;

			if ($options->mtime)
			{
				$return[$k] .= '?v=' . substr(md5($options->mtime), 0, 8);
			}

			if ($options->media)
			{
				$return[$k] .= '" media="' . $options->media;
			}
		}

		if ($this->strMode == self::JS)
		{
			return implode('"></script><script src="', $return);
		}

		return implode('"><link rel="stylesheet" href="', $return);
	}

	/**
	 * Generate the combined file and return its path
	 *
	 * @param string $strUrl An optional URL to prepend
	 *
	 * @return string The path to the combined file
	 */
	protected function getCombinedFileUrl($strUrl=null)
	{
		if ($strUrl === null)
		{
			$strUrl = System::getContainer()->get('contao.assets.assets_context')->getStaticUrl();
		}

		$arrPrefix = array();
		$strTarget = substr($this->strMode, 1);

		foreach ($this->arrFiles as $arrFile)
		{
			$arrPrefix[] = basename($arrFile['name']);
		}

		$strKey = StringUtil::substr(implode(',', $arrPrefix), 64, '...') . '-' . substr(md5($this->strKey), 0, 8);

		// Load the existing file
		if (file_exists($this->strRootDir . '/assets/' . $strTarget . '/' . $strKey . $this->strMode))
		{
			return $strUrl . 'assets/' . $strTarget . '/' . $strKey . $this->strMode;
		}

		// Create the file
		$objFile = new File('assets/' . $strTarget . '/' . $strKey . $this->strMode);
		$objFile->truncate();

		foreach ($this->arrFiles as $arrFile)
		{
			$content = file_get_contents($this->strRootDir . '/' . $arrFile['name']);

			// Remove UTF-8 BOM
			if (strncmp($content, "\xEF\xBB\xBF", 3) === 0)
			{
				$content = substr($content, 3);
			}

			// HOOK: modify the file content
			if (isset($GLOBALS['TL_HOOKS']['getCombinedFile']) && \is_array($GLOBALS['TL_HOOKS']['getCombinedFile']))
			{
				foreach ($GLOBALS['TL_HOOKS']['getCombinedFile'] as $callback)
				{
					$this->import($callback[0]);
					$content = $this->{$callback[0]}->{$callback[1]}($content, $strKey, $this->strMode, $arrFile);
				}
			}

			if ($arrFile['extension'] == self::CSS)
			{
				$content = $this->handleCss($content, $arrFile);
			}
			elseif ($arrFile['extension'] == self::SCSS || $arrFile['extension'] == self::LESS)
			{
				$content = $this->handleScssLess($content, $arrFile);
			}

			$objFile->append($content);
		}

		unset($content);
		$objFile->close();

		return $strUrl . 'assets/' . $strTarget . '/' . $strKey . $this->strMode;
	}

	/**
	 * Handle CSS files
	 *
	 * @param string $content The file content
	 * @param array  $arrFile The file array
	 *
	 * @return string The modified file content
	 */
	protected function handleCss($content, $arrFile)
	{
		$content = $this->fixPaths($content, $arrFile);

		// Add the media type if there is no @media command in the code
		if ($arrFile['media'] && $arrFile['media'] != 'all' && strpos($content, '@media') === false)
		{
			$content = '@media ' . $arrFile['media'] . "{\n" . $content . "\n}";
		}

		return $content;
	}

	/**
	 * Handle SCSS/LESS files
	 *
	 * @param string $content The file content
	 * @param array  $arrFile The file array
	 *
	 * @return string The modified file content
	 */
	protected function handleScssLess($content, $arrFile)
	{
		$blnDebug = System::getContainer()->getParameter('kernel.debug');

		if ($arrFile['extension'] == self::SCSS)
		{
			$objCompiler = new Compiler();
			$objCompiler->setImportPaths($this->strRootDir . '/' . \dirname($arrFile['name']));
			$objCompiler->setOutputStyle($blnDebug ? OutputStyle::EXPANDED : OutputStyle::COMPRESSED);

			if ($blnDebug)
			{
				$objCompiler->setSourceMap(Compiler::SOURCE_MAP_INLINE);
			}

			return $this->fixPaths($objCompiler->compileString($content, $this->strRootDir . '/' . $arrFile['name'])->getCss(), $arrFile);
		}

		$strPath = \dirname($arrFile['name']);

		$arrOptions = array
		(
			'strictMath' => true,
			'compress' => !$blnDebug,
			'import_dirs' => array($this->strRootDir . '/' . $strPath => $strPath)
		);

		$objParser = new \Less_Parser();
		$objParser->SetOptions($arrOptions);
		$objParser->parse($content);

		return $this->fixPaths($objParser->getCss(), $arrFile);
	}

	/**
	 * Fix the paths
	 *
	 * @param string $content The file content
	 * @param array  $arrFile The file array
	 *
	 * @return string The modified file content
	 */
	protected function fixPaths($content, $arrFile)
	{
		$strName = $arrFile['name'];

		// Strip the contao.web_dir directory prefix
		if (strpos($strName, $this->strWebDir . '/') === 0)
		{
			$strName = substr($strName, \strlen($this->strWebDir) + 1);
		}

		$strDirname = \dirname($strName);
		$strGlue = ($strDirname != '.') ? $strDirname . '/' : '';

		return preg_replace_callback(
			'/url\(("[^"\n]+"|\'[^\'\n]+\'|[^"\'\s()]+)\)/',
			static function ($matches) use ($strDirname, $strGlue) {
				$strData = $matches[1];

				if ($strData[0] == '"' || $strData[0] == "'")
				{
					$strData = substr($strData, 1, -1);
				}

				// Skip absolute links and embedded images (see #5082)
				if ($strData[0] == '/' || $strData[0] == '#' || strncmp($strData, 'data:', 5) === 0 || strncmp($strData, 'http://', 7) === 0 || strncmp($strData, 'https://', 8) === 0 || strncmp($strData, 'assets/css3pie/', 15) === 0)
				{
					return $matches[0];
				}

				// Make the paths relative to the root (see #4161)
				if (strncmp($strData, '../', 3) !== 0)
				{
					$strData = '../../' . $strGlue . $strData;
				}
				else
				{
					$dir = $strDirname;

					// Remove relative paths
					while (strncmp($strData, '../', 3) === 0)
					{
						$dir = \dirname($dir);
						$strData = substr($strData, 3);
					}

					$glue = ($dir != '.') ? $dir . '/' : '';
					$strData = '../../' . $glue . $strData;
				}

				$strQuote = '';

				if ($matches[1][0] == "'" || $matches[1][0] == '"')
				{
					$strQuote = $matches[1][0];
				}

				if (preg_match('/[(),\s"\']/', $strData))
				{
					if ($matches[1][0] == "'")
					{
						$strData = str_replace("'", "\\'", $strData);
					}
					else
					{
						$strQuote = '"';
						$strData = str_replace('"', '\"', $strData);
					}
				}

				return 'url(' . $strQuote . $strData . $strQuote . ')';
			},
			$content
		);
	}

	/**
	 * Check if the file has a @media tag
	 *
	 * @param string $strFile
	 *
	 * @return boolean True if the file has a @media tag
	 */
	protected function hasMediaTag($strFile)
	{
		$return = false;
		$fh = fopen($this->strRootDir . '/' . $strFile, 'r');

		while (($line = fgets($fh)) !== false)
		{
			if (strpos($line, '@media') !== false)
			{
				$return = true;
				break;
			}
		}

		fclose($fh);

		return $return;
	}
}
