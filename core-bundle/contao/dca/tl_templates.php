<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Config;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Twig\Inspector\InspectionException;
use Contao\DataContainer;
use Contao\DC_Folder;
use Contao\DiffRenderer;
use Contao\Environment;
use Contao\File;
use Contao\Folder;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\TemplateLoader;
use Contao\Validator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Twig\Error\LoaderError;

System::loadLanguageFile('tl_files');

$GLOBALS['TL_DCA']['tl_templates'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Folder::class,
		'uploadPath'                  => 'templates',
		'editableFileTypes'           => 'html5,twig',
		'closed'                      => true,
		'onload_callback' => array
		(
			array('tl_templates', 'addBreadcrumb'),
		)
	),

	// List
	'list' => array
	(
		'global_operations' => array
		(
			'new' => array
			(
				'href'                => 'act=paste&amp;mode=create',
				'class'               => 'header_new_folder'
			),
			'new_tpl' => array
			(
				'href'                => 'key=new_tpl',
				'class'               => 'header_new'
			),
			'toggleNodes',
			'all'
		),
		'operations' => array
		(
			'edit',
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_files']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg',
				'attributes'          => 'data-action="contao--scroll-offset#store"',
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_files']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg',
				'attributes'          => 'data-action="contao--scroll-offset#store"',
			),
			'delete',
			'source' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_files']['source'],
				'href'                => 'act=source',
				'icon'                => 'editor.svg',
				'button_callback'     => array('tl_templates', 'editSource')
			),
			'compare' => array
			(
				'href'                => 'key=compare',
				'icon'                => 'diffTemplate.svg',
				'button_callback'     => array('tl_templates', 'compareButton')
			),
			'drag' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_files']['cut'],
				'icon'                => 'drag.svg',
				'attributes'          => 'class="drag-handle" aria-hidden="true"',
				'button_callback'     => array('tl_templates', 'dragFile')
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => 'name'
	),

	// Fields
	'fields' => array
	(
		'name' => array
		(
			'exclude'                 => false,
			'label'                   => &$GLOBALS['TL_LANG']['tl_files']['name'],
			'inputType'               => 'text',
			'load_callback' => array
			(
				array('tl_templates', 'addFileLocation')
			),
			'eval'                    => array('mandatory'=>true, 'maxlength'=>64, 'spaceToUnderscore'=>true, 'tl_class'=>'w50')
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_templates extends Backend
{
	/**
	 * Add the breadcrumb menu
	 *
	 * @throws RuntimeException
	 */
	public function addBreadcrumb()
	{
		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');

		// Set a new node
		if (Input::get('fn') !== null)
		{
			// Check the path (thanks to Arnaud Buchoux)
			if (Validator::isInsecurePath(Input::get('fn', true)))
			{
				throw new RuntimeException('Insecure path ' . Input::get('fn', true));
			}

			$objSessionBag->set('tl_templates_node', Input::get('fn', true));
			$this->redirect(preg_replace('/[?&]fn=[^&]*/', '', Environment::get('requestUri')));
		}

		$strNode = $objSessionBag->get('tl_templates_node');

		if (!$strNode)
		{
			return;
		}

		// Check the path (thanks to Arnaud Buchoux)
		if (Validator::isInsecurePath($strNode))
		{
			throw new RuntimeException('Insecure path ' . $strNode);
		}

		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		// The currently selected folder does not exist
		if (!is_dir($projectDir . '/' . $strNode))
		{
			$objSessionBag->set('tl_templates_node', '');

			return;
		}

		$strPath = 'templates';
		$arrNodes = explode('/', preg_replace('/^templates\//', '', $strNode));
		$arrLinks = array();

		// Add root link
		$arrLinks[] = Image::getHtml('filemounts.svg') . ' <a href="' . $this->addToUrl('fn=') . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectAllNodes']) . '">' . $GLOBALS['TL_LANG']['MSC']['filterAll'] . '</a>';

		// Generate breadcrumb trail
		foreach ($arrNodes as $strFolder)
		{
			$strPath .= '/' . $strFolder;

			// No link for the active folder
			if ($strFolder == basename($strNode))
			{
				$arrLinks[] = Image::getHtml('folderC.svg') . ' ' . $strFolder;
			}
			else
			{
				$arrLinks[] = Image::getHtml('folderC.svg') . ' <a href="' . $this->addToUrl('fn=' . $strPath) . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']) . '">' . $strFolder . '</a>';
			}
		}

		// Limit tree
		$GLOBALS['TL_DCA']['tl_templates']['list']['sorting']['root'] = array($strNode);

		// Insert breadcrumb menu
		$GLOBALS['TL_DCA']['tl_templates']['list']['sorting']['breadcrumb'] = '

<nav aria-label="' . $GLOBALS['TL_LANG']['MSC']['breadcrumbMenu'] . '">
  <ul id="tl_breadcrumb">
    <li>' . implode(' › </li><li>', $arrLinks) . '</li>
  </ul>
</nav>';
	}

	/**
	 * Create a new template
	 *
	 * @return string
	 */
	public function addNewTemplate()
	{
		$arrAllTemplates = array();

		// Add modern templates
		$container = System::getContainer();
		$chains = $container->get('contao.twig.filesystem_loader')->getInheritanceChains();

		foreach ($chains as $identifier => $chain)
		{
			if (!str_contains($identifier, '/'))
			{
				continue;
			}

			$parts = explode('/', $identifier);
			$rootCategory = array_shift($parts);

			$arrAllTemplates[$rootCategory]["@Contao/$identifier.html.twig"] = sprintf('%s [%s.html.twig]', implode('/', $parts), $identifier);

			ksort($arrAllTemplates[$rootCategory]);
		}

		$files = $container->get('contao.resource_finder')->findIn('templates')->files()->name('/\.html5$/');
		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		// Add legacy templates
		foreach ($files as $file)
		{
			// Do not use "StringUtil::stripRootDir()" here, because for
			// symlinked bundles, the path will be outside the project dir.
			$strRelpath = Path::makeRelative($file->getPathname(), $projectDir);

			$modulePatterns = array(
				"vendor/([^/]+/[^/]+)",
				"\\.\\..*?([^/]+/[^/]+)/(?:src/Resources/contao/templates|contao/templates)",
				"system/modules/([^/]+)"
			);

			preg_match('@^(?|' . implode('|', $modulePatterns) . ')/.*$@', $strRelpath, $matches);

			// Use the matched "module" group and fall back to the full
			// directory path (e.g. "contao/templates" in the app).
			$strModule = $matches[1] ?? dirname($strRelpath);

			$arrAllTemplates[$strModule][$strRelpath] = basename($strRelpath);
		}

		$strError = '';

		// Handle creating a new template
		if (Input::post('FORM_SUBMIT') == 'tl_create_template')
		{
			$createModernTemplate = static function (string $template, string $target) use ($container, &$strError): void {
				$filesystem = new Filesystem();
				$targetFile = Path::join($container->getParameter('kernel.project_dir'), $target, substr($template, 8));

				if ($filesystem->exists($targetFile))
				{
					$strError = sprintf($GLOBALS['TL_LANG']['tl_templates']['exists'], $targetFile);

					return;
				}

				try
				{
					$info = $container->get('contao.twig.inspector')->inspectTemplate($template);
				}
				catch (InspectionException $e)
				{
					if ($e->getPrevious() instanceof LoaderError)
					{
						throw new RuntimeException('Invalid template ' . $template);
					}

					$strError = sprintf($GLOBALS['TL_LANG']['tl_templates']['hasErrors'], $template, $e->getPrevious()->getMessage());

					return;
				}

				$content = $container->get('twig')->render(
					'@Contao/backend/template_skeleton.html.twig',
					array(
						'type' => str_starts_with($template, '@Contao/component') ? 'use' : 'extends',
						'template' => $info,
					)
				);

				$filesystem->dumpFile($targetFile, $content);
			};

			$createLegacyTemplate = static function (string $strOriginal, $strTarget) use (&$strError, $arrAllTemplates): void {
				$projectDir = System::getContainer()->getParameter('kernel.project_dir');

				// Validate the target path
				if (!str_starts_with($strTarget, 'templates') || !is_dir($projectDir . '/' . $strTarget))
				{
					$strError = sprintf($GLOBALS['TL_LANG']['tl_templates']['invalid'], $strTarget);
				}
				else
				{
					$blnFound = false;

					// Validate the source path
					foreach ($arrAllTemplates as $arrTemplates)
					{
						if (isset($arrTemplates[$strOriginal]))
						{
							$blnFound = true;
							break;
						}
					}

					if (!$blnFound)
					{
						$strError = sprintf($GLOBALS['TL_LANG']['tl_templates']['invalid'], $strOriginal);
					}
					else
					{
						$strTarget .= '/' . basename($strOriginal);

						// Check whether the target file exists
						if (file_exists($projectDir . '/' . $strTarget))
						{
							$strError = sprintf($GLOBALS['TL_LANG']['tl_templates']['exists'], $strTarget);
						}
						else
						{
							(new Filesystem())->copy(
								Path::makeAbsolute($strOriginal, $projectDir),
								Path::makeAbsolute($strTarget, $projectDir)
							);
						}
					}
				}
			};

			$strTarget = Input::post('target', true);

			if (Validator::isInsecurePath($strTarget))
			{
				throw new RuntimeException('Invalid path ' . $strTarget);
			}

			$strOriginal = Input::post('original', true);

			if (str_starts_with($strOriginal, '@'))
			{
				$createModernTemplate($strOriginal, $strTarget);
			}
			else
			{
				$createLegacyTemplate($strOriginal, $strTarget);
			}

			if (!$strError)
			{
				$this->redirect($this->getReferer());
			}
		}

		$strAllTemplates = '';

		// Group the templates by module
		foreach ($arrAllTemplates as $k=>$v)
		{
			$strAllTemplates .= '<optgroup label="' . $k . '">';

			foreach ($v as $kk=>$vv)
			{
				$strAllTemplates .= sprintf('<option value="%s"%s>%s</option>', $kk, (Input::post('original') == $kk) ? ' selected="selected"' : '', $vv);
			}

			$strAllTemplates .= '</optgroup>';
		}

		// Show form
		return ($strError ? '
<div class="tl_message">
<p class="tl_error">' . StringUtil::specialchars($strError) . '</p>
</div>' : '') . '

<div id="tl_buttons">
<a href="' . $this->getReferer(true) . '" class="header_back" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']) . '" accesskey="b" data-action="contao--scroll-offset#store">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a>
</div>

<form id="tl_create_template" class="tl_form tl_edit_form" method="post">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="tl_create_template">
<input type="hidden" name="REQUEST_TOKEN" value="' . htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5) . '">
<div class="tl_tbox cf">
<div class="w50 widget">
  <h3><label for="ctrl_original">' . $GLOBALS['TL_LANG']['tl_templates']['original'][0] . '</label></h3>
  <select name="original" id="ctrl_original" class="tl_select" data-action="focus->contao--scroll-offset#store" data-controller="contao--chosen">' . $strAllTemplates . '</select>' . (($GLOBALS['TL_LANG']['tl_templates']['original'][1] && Config::get('showHelp')) ? '
  <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['tl_templates']['original'][1] . '</p>' : '') . '
</div>
<div class="w50 widget">
  <h3><label for="ctrl_target">' . $GLOBALS['TL_LANG']['tl_templates']['target'][0] . '</label></h3>
  <select name="target" id="ctrl_target" class="tl_select" data-action="focus->contao--scroll-offset#store"><option value="templates">templates</option>' . $this->getTargetFolders('templates') . '</select>' . (($GLOBALS['TL_LANG']['tl_templates']['target'][1] && Config::get('showHelp')) ? '
  <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['tl_templates']['target'][1] . '</p>' : '') . '
</div>
</div>
</div>

<div class="tl_formbody_submit">
<div class="tl_submit_container">
  <button type="submit" name="create" id="create" class="tl_submit" accesskey="s">' . $GLOBALS['TL_LANG']['tl_templates']['newTpl'] . '</button>
</div>
</div>
</form>';
	}

	/**
	 * Compares the current to the original template
	 *
	 * @param DataContainer $dc
	 *
	 * @throws InternalServerErrorException
	 */
	public function compareTemplate(DataContainer $dc): never
	{
		$objCurrentFile = new File($dc->id);
		$strName = $objCurrentFile->filename;
		$strExtension = $objCurrentFile->extension;
		$arrTemplates = TemplateLoader::getFiles();
		$blnOverridesAnotherTpl = isset($arrTemplates[$strName]);

		$strPrefix = '';

		if (($pos = strpos($strName, '_')) !== false)
		{
			$strPrefix = substr($strName, 0, $pos + 1);
		}

		$strBuffer = '';
		$strCompareName = null;
		$strComparePath = null;

		// By default, it's the original template to compare against
		if ($blnOverridesAnotherTpl)
		{
			$strCompareName = $strName;
			$strComparePath = $arrTemplates[$strCompareName] . '/' . $strCompareName . '.' . $strExtension;

			$strBuffer .= '<p class="tl_info" style="margin-bottom:1em">' . sprintf($GLOBALS['TL_LANG']['tl_templates']['overridesAnotherTpl'], $strComparePath) . '</p>';
		}
		else
		{
			// Try to find the base template by stripping suffixes
			while (str_contains($strName, '_'))
			{
				$strName = substr($strName, 0, strrpos($strName, '_'));

				if (isset($arrTemplates[$strName]))
				{
					$strCompareName = $strName;
					$strComparePath = $arrTemplates[$strCompareName] . '/' . $strCompareName . '.' . $strExtension;
					break;
				}
			}
		}

		// User selected template to compare against
		if (Input::post('from') && isset($arrTemplates[Input::post('from')]))
		{
			$strCompareName = Input::post('from');
			$strComparePath = $arrTemplates[$strCompareName] . '/' . $strCompareName . '.' . $strExtension;
		}

		if ($strComparePath !== null)
		{
			$objCompareFile = new File($strComparePath);

			// Abort if one file is missing
			if (!$objCurrentFile->exists() || !$objCompareFile->exists())
			{
				throw new InternalServerErrorException('The source or target file does not exist.');
			}

			$objDiff = new Diff($objCompareFile->getContentAsArray(), $objCurrentFile->getContentAsArray());
			$strDiff = $objDiff->render(new DiffRenderer(array('field'=>$dc->id)));

			// Identical versions
			if (!$strDiff)
			{
				$strBuffer .= '<p>' . $GLOBALS['TL_LANG']['MSC']['identicalVersions'] . '</p>';
			}
			else
			{
				$strBuffer .= $strDiff;
			}
		}
		else
		{
			$strBuffer .= '<p class="tl_info">' . $GLOBALS['TL_LANG']['tl_templates']['pleaseSelect'] . '</p>';
		}

		// Unset a custom prefix to show all templates in the drop-down menu (see #784)
		if ($strPrefix && count(TemplateLoader::getPrefixedFiles($strPrefix)) < 1)
		{
			$strPrefix = '';
		}

		// Templates to compare against
		$arrComparable = array();
		$intPrefixLength = strlen($strPrefix);

		foreach ($arrTemplates as $k => $v)
		{
			if (!$intPrefixLength || 0 === strncmp($k, $strPrefix, $intPrefixLength))
			{
				$arrComparable[$k] = array
				(
					'version' => $k,
					'info'    => $k . '.' . $strExtension
				);
			}
		}

		ksort($arrComparable);

		$objTemplate = new BackendTemplate('be_diff');
		$objTemplate->staticTo = $dc->id;
		$objTemplate->versions = $arrComparable;
		$objTemplate->from = $strCompareName;
		$objTemplate->showLabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['showDifferences']);
		$objTemplate->content = $strBuffer;
		$objTemplate->theme = Backend::getTheme();
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->title = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['showDifferences']);
		$objTemplate->charset = System::getContainer()->getParameter('kernel.charset');

		throw new ResponseException($objTemplate->getResponse());
	}

	/**
	 * Return the "compare template" button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function compareButton($row, $href, $label, $title, $icon, $attributes)
	{
		return str_ends_with($row['id'], '.html5') && is_file(System::getContainer()->getParameter('kernel.project_dir') . '/' . rawurldecode($row['id'])) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", rawurldecode($row['id']))) . '\',\'url\':this.href});return false"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(str_replace('.svg', '--disabled.svg', $icon)) . ' ';
	}

	/**
	 * Return the drag file button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function dragFile($row, $href, $label, $title, $icon, $attributes)
	{
		return '<button type="button" title="' . StringUtil::specialchars($title) . '" ' . $attributes . '>' . Image::getHtml($icon, $label) . '</button> ';
	}

	/**
	 * Recursively scan the templates directory and return all folders as array
	 *
	 * @param string  $strFolder
	 * @param integer $intLevel
	 *
	 * @return string
	 */
	protected function getTargetFolders($strFolder, $intLevel=1)
	{
		$strFolders = '';
		$strPath = System::getContainer()->getParameter('kernel.project_dir') . '/' . $strFolder;

		foreach (Folder::scan($strPath) as $strFile)
		{
			if (!is_dir($strPath . '/' . $strFile) || str_starts_with($strFile, '.'))
			{
				continue;
			}

			$strRelPath = $strFolder . '/' . $strFile;
			$strFolders .= sprintf('<option value="%s"%s>%s%s</option>', $strRelPath, (Input::post('target') == $strRelPath) ? ' selected="selected"' : '', str_repeat(' &nbsp; ', $intLevel), basename($strRelPath));
			$strFolders .= $this->getTargetFolders($strRelPath, $intLevel + 1);
		}

		return $strFolders;
	}

	/**
	 * Return the edit file source button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function editSource($row, $href, $label, $title, $icon, $attributes)
	{
		/** @var DC_Folder $dc */
		$dc = func_num_args() <= 12 ? null : func_get_arg(12);
		$arrEditableFileTypes = $dc->editableFileTypes ?? StringUtil::trimsplit(',', strtolower($GLOBALS['TL_DCA']['tl_templates']['config']['editableFileTypes'] ?? System::getContainer()->getParameter('contao.editable_files')));

		return in_array(Path::getExtension($row['id'], true), $arrEditableFileTypes) && is_file(System::getContainer()->getParameter('kernel.project_dir') . '/' . rawurldecode($row['id'])) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(str_replace('.svg', '--disabled.svg', $icon)) . ' ';
	}

	/**
	 * Add the file location instead of the help text (see #6503)
	 *
	 * @param mixed         $value
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 */
	public function addFileLocation($value, DataContainer $dc)
	{
		$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][1] = sprintf($GLOBALS['TL_LANG']['tl_files']['fileLocation'], $dc->id);

		return $value;
	}
}
