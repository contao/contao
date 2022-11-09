<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

Contao\System::loadLanguageFile('tl_files');

$GLOBALS['TL_DCA']['tl_templates'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => Contao\DC_Folder::class,
		'closed'                      => true,
		'onload_callback' => array
		(
			array('tl_templates', 'adjustSettings'),
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
			'toggleNodes' => array
			(
				'href'                => 'tg=all',
				'class'               => 'header_toggle',
				'showOnSelect'        => true
			),
			'all' => array
			(
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_files']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_files']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_files']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_files']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirmFile'] . '\'))return false;Backend.getScrollOffset()"'
			),
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_files']['name'],
			'inputType'               => 'text',
			'wizard' => array
			(
				array('tl_templates', 'addFileLocation')
			),
			'eval'                    => array('mandatory'=>true, 'maxlength'=>64, 'spaceToUnderscore'=>true, 'tl_class'=>'w50', 'addWizardClass'=>false)
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class tl_templates extends Contao\Backend
{
	/**
	 * Adjust some global settings in the template editor
	 */
	public function adjustSettings()
	{
		Contao\Config::set('uploadPath', 'templates');
		Contao\Config::set('editableFiles', 'html5');
	}

	/**
	 * Add the breadcrumb menu
	 *
	 * @throws RuntimeException
	 */
	public function addBreadcrumb()
	{
		/** @var Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface $objSessionBag */
		$objSessionBag = Contao\System::getContainer()->get('session')->getBag('contao_backend');

		// Set a new node
		if (isset($_GET['fn']))
		{
			// Check the path (thanks to Arnaud Buchoux)
			if (Contao\Validator::isInsecurePath(Contao\Input::get('fn', true)))
			{
				throw new RuntimeException('Insecure path ' . Contao\Input::get('fn', true));
			}

			$objSessionBag->set('tl_templates_node', Contao\Input::get('fn', true));
			$this->redirect(preg_replace('/[?&]fn=[^&]*/', '', Contao\Environment::get('request')));
		}

		$strNode = $objSessionBag->get('tl_templates_node');

		if (!$strNode)
		{
			return;
		}

		// Check the path (thanks to Arnaud Buchoux)
		if (Contao\Validator::isInsecurePath($strNode))
		{
			throw new RuntimeException('Insecure path ' . $strNode);
		}

		$projectDir = Contao\System::getContainer()->getParameter('kernel.project_dir');

		// Currently selected folder does not exist
		if (!is_dir($projectDir . '/' . $strNode))
		{
			$objSessionBag->set('tl_templates_node', '');

			return;
		}

		$strPath = 'templates';
		$arrNodes = explode('/', preg_replace('/^templates\//', '', $strNode));
		$arrLinks = array();

		// Add root link
		$arrLinks[] = Contao\Image::getHtml('filemounts.svg') . ' <a href="' . $this->addToUrl('fn=') . '" title="' . Contao\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectAllNodes']) . '">' . $GLOBALS['TL_LANG']['MSC']['filterAll'] . '</a>';

		// Generate breadcrumb trail
		foreach ($arrNodes as $strFolder)
		{
			$strPath .= '/' . $strFolder;

			// No link for the active folder
			if ($strFolder == basename($strNode))
			{
				$arrLinks[] = Contao\Image::getHtml('folderC.svg') . ' ' . $strFolder;
			}
			else
			{
				$arrLinks[] = Contao\Image::getHtml('folderC.svg') . ' <a href="' . $this->addToUrl('fn=' . $strPath) . '" title="' . Contao\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']) . '">' . $strFolder . '</a>';
			}
		}

		// Limit tree
		$GLOBALS['TL_DCA']['tl_templates']['list']['sorting']['root'] = array($strNode);

		// Insert breadcrumb menu
		$GLOBALS['TL_DCA']['tl_templates']['list']['sorting']['breadcrumb'] .= '

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

		/** @var SplFileInfo[] $files */
		$files = Contao\System::getContainer()->get('contao.resource_finder')->findIn('templates')->files()->name('/\.html5$/');
		$projectDir = Contao\System::getContainer()->getParameter('kernel.project_dir');

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

		// Copy an existing template
		if (Contao\Input::post('FORM_SUBMIT') == 'tl_create_template')
		{
			$strOriginal = Contao\Input::post('original', true);
			$strTarget = Contao\Input::post('target', true);

			if (Contao\Validator::isInsecurePath($strTarget))
			{
				throw new RuntimeException('Invalid path ' . $strTarget);
			}

			// Validate the target path
			if (strncmp($strTarget, 'templates', 9) !== 0 || !is_dir($projectDir . '/' . $strTarget))
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
						$this->redirect($this->getReferer());
					}
				}
			}
		}

		$strAllTemplates = '';

		// Group the templates by module
		foreach ($arrAllTemplates as $k=>$v)
		{
			$strAllTemplates .= '<optgroup label="' . $k . '">';

			foreach ($v as $kk=>$vv)
			{
				$strAllTemplates .= sprintf('<option value="%s"%s>%s</option>', $kk, ((Contao\Input::post('original') == $kk) ? ' selected="selected"' : ''), $vv);
			}

			$strAllTemplates .= '</optgroup>';
		}

		// Show form
		return ($strError ? '
<div class="tl_message">
<p class="tl_error">' . Contao\StringUtil::specialchars($strError) . '</p>
</div>' : '') . '

<div id="tl_buttons">
<a href="' . $this->getReferer(true) . '" class="header_back" title="' . Contao\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']) . '" accesskey="b" onclick="Backend.getScrollOffset()">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a>
</div>

<form id="tl_create_template" class="tl_form tl_edit_form" method="post">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="tl_create_template">
<input type="hidden" name="REQUEST_TOKEN" value="' . REQUEST_TOKEN . '">
<div class="tl_tbox cf">
<div class="w50 widget">
  <h3><label for="ctrl_original">' . $GLOBALS['TL_LANG']['tl_templates']['original'][0] . '</label></h3>
  <select name="original" id="ctrl_original" class="tl_select tl_chosen" onfocus="Backend.getScrollOffset()">' . $strAllTemplates . '</select>' . (($GLOBALS['TL_LANG']['tl_templates']['original'][1] && Contao\Config::get('showHelp')) ? '
  <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['tl_templates']['original'][1] . '</p>' : '') . '
</div>
<div class="w50 widget">
  <h3><label for="ctrl_target">' . $GLOBALS['TL_LANG']['tl_templates']['target'][0] . '</label></h3>
  <select name="target" id="ctrl_target" class="tl_select" onfocus="Backend.getScrollOffset()"><option value="templates">templates</option>' . $this->getTargetFolders('templates') . '</select>' . (($GLOBALS['TL_LANG']['tl_templates']['target'][1] && Contao\Config::get('showHelp')) ? '
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
	 * @param Contao\DataContainer $dc
	 *
	 * @throws Contao\CoreBundle\Exception\InternalServerErrorException
	 */
	public function compareTemplate(Contao\DataContainer $dc)
	{
		$objCurrentFile = new Contao\File($dc->id);
		$strName = $objCurrentFile->filename;
		$strExtension = $objCurrentFile->extension;
		$arrTemplates = Contao\TemplateLoader::getFiles();
		$blnOverridesAnotherTpl = isset($arrTemplates[$strName]);

		$strPrefix = '';

		if (($pos = strpos($strName, '_')) !== false)
		{
			$strPrefix = substr($strName, 0, $pos + 1);
		}

		$strBuffer = '';
		$strCompareName = null;
		$strComparePath = null;

		// By default it's the original template to compare against
		if ($blnOverridesAnotherTpl)
		{
			$strCompareName = $strName;
			$strComparePath = $arrTemplates[$strCompareName] . '/' . $strCompareName . '.' . $strExtension;

			if ($strComparePath !== null)
			{
				$strBuffer .= '<p class="tl_info" style="margin-bottom:1em">' . sprintf($GLOBALS['TL_LANG']['tl_templates']['overridesAnotherTpl'], $strComparePath) . '</p>';
			}
		}
		else
		{
			// Try to find the base template by strippig suffixes
			while (strpos($strName, '_') !== false)
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
		if (Contao\Input::post('from') && isset($arrTemplates[Contao\Input::post('from')]))
		{
			$strCompareName = Contao\Input::post('from');
			$strComparePath = $arrTemplates[$strCompareName] . '/' . $strCompareName . '.' . $strExtension;
		}

		if ($strComparePath !== null)
		{
			$objCompareFile = new Contao\File($strComparePath);

			// Abort if one file is missing
			if (!$objCurrentFile->exists() || !$objCompareFile->exists())
			{
				throw new Contao\CoreBundle\Exception\InternalServerErrorException('The source or target file does not exist.');
			}

			$objDiff = new Diff($objCompareFile->getContentAsArray(), $objCurrentFile->getContentAsArray());
			$strDiff = $objDiff->render(new Contao\DiffRenderer(array('field'=>$dc->id)));

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
		if ($strPrefix && count(Contao\TemplateLoader::getPrefixedFiles($strPrefix)) < 1)
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

		$objTemplate = new Contao\BackendTemplate('be_diff');
		$objTemplate->staticTo = $dc->id;
		$objTemplate->versions = $arrComparable;
		$objTemplate->from = $strCompareName;
		$objTemplate->showLabel = Contao\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['showDifferences']);
		$objTemplate->content = $strBuffer;
		$objTemplate->theme = Contao\Backend::getTheme();
		$objTemplate->base = Contao\Environment::get('base');
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->title = Contao\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['showDifferences']);
		$objTemplate->charset = Contao\Config::get('characterSet');

		Contao\Config::set('debugMode', false);

		throw new Contao\CoreBundle\Exception\ResponseException($objTemplate->getResponse());
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
		return substr($row['id'], -6) == '.html5' && is_file(Contao\System::getContainer()->getParameter('kernel.project_dir') . '/' . rawurldecode($row['id'])) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . Contao\StringUtil::specialchars($title) . '" onclick="Backend.openModalIframe({\'title\':\'' . Contao\StringUtil::specialchars(str_replace("'", "\\'", rawurldecode($row['id']))) . '\',\'url\':this.href});return false"' . $attributes . '>' . Contao\Image::getHtml($icon, $label) . '</a> ' : Contao\Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
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
		return '<button type="button" title="' . Contao\StringUtil::specialchars($title) . '" ' . $attributes . '>' . Contao\Image::getHtml($icon, $label) . '</button> ';
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
		$strPath = Contao\System::getContainer()->getParameter('kernel.project_dir') . '/' . $strFolder;

		foreach (scan($strPath) as $strFile)
		{
			if (!is_dir($strPath . '/' . $strFile) || strncmp($strFile, '.', 1) === 0)
			{
				continue;
			}

			$strRelPath = $strFolder . '/' . $strFile;
			$strFolders .= sprintf('<option value="%s"%s>%s%s</option>', $strRelPath, ((Contao\Input::post('target') == $strRelPath) ? ' selected="selected"' : ''), str_repeat(' &nbsp; ', $intLevel), basename($strRelPath));
			$strFolders .= $this->getTargetFolders($strRelPath, ($intLevel + 1));
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
		return substr($row['id'], -6) == '.html5' && is_file(Contao\System::getContainer()->getParameter('kernel.project_dir') . '/' . rawurldecode($row['id'])) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . Contao\StringUtil::specialchars($title) . '"' . $attributes . '>' . Contao\Image::getHtml($icon, $label) . '</a> ' : Contao\Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Add the file location instead of the help text (see #6503)
	 *
	 * @param Contao\DataContainer $dc
	 *
	 * @return string
	 */
	public function addFileLocation(Contao\DataContainer $dc)
	{
		// Unset the default help text
		unset($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][1]);

		return '<p class="tl_help tl_tip">' . sprintf($GLOBALS['TL_LANG']['tl_files']['fileLocation'], $dc->id) . '</p>';
	}
}
