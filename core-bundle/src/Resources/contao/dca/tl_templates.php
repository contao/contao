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
use Contao\DataContainer;
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
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Webmozart\PathUtil\Path;

System::loadLanguageFile('tl_files');

$GLOBALS['TL_DCA']['tl_templates'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => 'Folder',
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
class tl_templates extends Backend
{
	/**
	 * Adjust some global settings in the template editor
	 */
	public function adjustSettings()
	{
		Config::set('uploadPath', 'templates');
		Config::set('editableFiles', 'html5,twig');
	}

	/**
	 * Add the breadcrumb menu
	 *
	 * @throws RuntimeException
	 */
	public function addBreadcrumb()
	{
		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = System::getContainer()->get('session')->getBag('contao_backend');

		// Set a new node
		if (isset($_GET['fn']))
		{
			// Check the path (thanks to Arnaud Buchoux)
			if (Validator::isInsecurePath(Input::get('fn', true)))
			{
				throw new RuntimeException('Insecure path ' . Input::get('fn', true));
			}

			$objSessionBag->set('tl_templates_node', Input::get('fn', true));
			$this->redirect(preg_replace('/[?&]fn=[^&]*/', '', Environment::get('request')));
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
		$GLOBALS['TL_DCA']['tl_templates']['list']['sorting']['breadcrumb'] .= '

<nav aria-label="' . $GLOBALS['TL_LANG']['MSC']['breadcrumbMenu'] . '">
  <ul id="tl_breadcrumb">
    <li>' . implode(' › </li><li>', $arrLinks) . '</li>
  </ul>
</nav>';
	}

	/**
	 * Compares the current to the original template
	 *
	 * @param DataContainer $dc
	 *
	 * @throws InternalServerErrorException
	 */
	public function compareTemplate(DataContainer $dc)
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
		$objTemplate->base = Environment::get('base');
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->title = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['showDifferences']);
		$objTemplate->charset = Config::get('characterSet');

		Config::set('debugMode', false);

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
		return substr($row['id'], -6) == '.html5' && is_file(System::getContainer()->getParameter('kernel.project_dir') . '/' . rawurldecode($row['id'])) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", rawurldecode($row['id']))) . '\',\'url\':this.href});return false"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
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
		return in_array(Path::getExtension($row['id'], true), array('html5', 'twig')) && is_file(System::getContainer()->getParameter('kernel.project_dir') . '/' . rawurldecode($row['id'])) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Add the file location instead of the help text (see #6503)
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 */
	public function addFileLocation(DataContainer $dc)
	{
		// Unset the default help text
		unset($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][1]);

		return '<p class="tl_help tl_tip">' . sprintf($GLOBALS['TL_LANG']['tl_files']['fileLocation'], $dc->id) . '</p>';
	}
}
