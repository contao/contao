<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\EventListener\Widget\CustomRgxpListener;
use Contao\CoreBundle\EventListener\Widget\HttpUrlListener;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\FormHidden;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;

$GLOBALS['TL_DCA']['tl_form_field'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'enableVersioning'            => true,
		'ptable'                      => 'tl_form',
		'markAsCopy'                  => 'label',
		'onload_callback' => array
		(
			array('tl_form_field', 'checkPermission'),
			array('tl_form_field', 'filterFormFields')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid,invisible' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_PARENT,
			'fields'                  => array('sorting'),
			'panelLayout'             => 'filter;search,limit',
			'defaultSearchField'      => 'label',
			'headerFields'            => array('title', 'tstamp', 'formID', 'storeValues', 'sendViaEmail', 'recipient', 'subject'),
			'child_record_callback'   => array('tl_form_field', 'listFormFields')
		),
		'global_operations' => array
		(
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
				'href'                => 'act=edit',
				'icon'                => 'edit.svg',
				'button_callback'     => array('tl_form_field', 'disableButton')
			),
			'copy' => array
			(
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"',
				'button_callback'     => array('tl_form_field', 'disableButton')
			),
			'cut',
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
				'button_callback'     => array('tl_form_field', 'disableButton')
			),
			'toggle' => array
			(
				'href'                => 'act=toggle&amp;field=invisible',
				'icon'                => 'visible.svg',
				'reverse'             => true,
				'button_callback'     => array('tl_form_field', 'toggleIcon')
			),
			'show'
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('type', 'multiple', 'storeFile', 'imageSubmit', 'rgxp'),
		'default'                     => '{type_legend},type',
		'explanation'                 => '{type_legend},type;{text_legend},text;{expert_legend:hide},class;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'fieldsetStart'               => '{type_legend},type;{fconfig_legend},label;{expert_legend:hide},class;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'fieldsetStop'                => '{type_legend},type;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'html'                        => '{type_legend},type;{text_legend},html;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'text'                        => '{type_legend},type,name,label;{fconfig_legend},mandatory,rgxp,placeholder;{expert_legend:hide},class,value,minlength,maxlength,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'textdigit'                   => '{type_legend},type,name,label;{fconfig_legend},mandatory,rgxp,placeholder;{expert_legend:hide},class,value,minval,maxval,step,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'textcustom'                  => '{type_legend},type,name,label;{fconfig_legend},mandatory,rgxp,placeholder,customRgxp,errorMsg;{expert_legend:hide},class,value,minlength,maxlength,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'password'                    => '{type_legend},type,name,label;{fconfig_legend},mandatory,rgxp,placeholder;{expert_legend:hide},class,value,minlength,maxlength,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'passwordcustom'              => '{type_legend},type,name,label;{fconfig_legend},mandatory,rgxp,placeholder,customRgxp,errorMsg;{expert_legend:hide},class,value,minlength,maxlength,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'textarea'                    => '{type_legend},type,name,label;{fconfig_legend},mandatory,rgxp,placeholder;{size_legend},size;{expert_legend:hide},class,value,minlength,maxlength,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'textareacustom'              => '{type_legend},type,name,label;{fconfig_legend},mandatory,rgxp,placeholder,customRgxp,errorMsg;{size_legend},size;{expert_legend:hide},class,value,minlength,maxlength,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'select'                      => '{type_legend},type,name,label;{fconfig_legend},mandatory,multiple;{options_legend},options;{expert_legend:hide},class,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'radio'                       => '{type_legend},type,name,label;{fconfig_legend},mandatory;{options_legend},options;{expert_legend:hide},class;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'checkbox'                    => '{type_legend},type,name,label;{fconfig_legend},mandatory;{options_legend},options;{expert_legend:hide},class;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'upload'                      => '{type_legend},type,name,label;{fconfig_legend},mandatory,extensions,maxlength,maxImageWidth,maxImageHeight;{store_legend:hide},storeFile;{expert_legend:hide},class,accesskey,fSize;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'range'                       => '{type_legend},type,name,label;{fconfig_legend},mandatory;{expert_legend:hide},class,value,minval,maxval,step,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'hidden'                      => '{type_legend},type,name,value;{fconfig_legend},mandatory,rgxp;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'hiddencustom'                => '{type_legend},type,name,value;{fconfig_legend},mandatory,rgxp,customRgxp;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'captcha'                     => '{type_legend},type,label;{fconfig_legend},placeholder;{expert_legend:hide},class,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'submit'                      => '{type_legend},type,slabel;{image_legend:hide},imageSubmit;{expert_legend:hide},class,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'multiple'                    => 'mSize',
		'storeFile'                   => 'uploadFolder,useHomeDir,doNotOverwrite',
		'imageSubmit'                 => 'singleSRC'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'foreignKey'              => 'tl_form.title',
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'sorting' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'type' => array
		(
			'filter'                  => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_form_field', 'getFields'),
			'eval'                    => array('helpwizard'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['FFL'],
			'sql'                     => array('name'=>'type', 'type'=>'string', 'length'=>64, 'default'=>'text')
		),
		'label' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'name' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'fieldname', 'spaceToUnderscore'=>true, 'maxlength'=>64, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'text' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'helpwizard'=>true),
			'explanation'             => 'insertTags',
			'sql'                     => "text NULL"
		),
		'html' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>true, 'allowHtml'=>true, 'class'=>'monospace', 'rte'=>'ace|html'),
			'sql'                     => "text NULL"
		),
		'options' => array
		(
			'inputType'               => 'optionWizard',
			'eval'                    => array('mandatory'=>true, 'allowHtml'=>true),
			'xlabel' => array
			(
				array('tl_form_field', 'optionImportWizard')
			),
			'sql'                     => "blob NULL"
		),
		'mandatory' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'rgxp' => array
		(
			'inputType'               => 'select',
			'options'                 => array('digit', 'alpha', 'alnum', 'extnd', 'date', 'time', 'datim', 'phone', 'email', 'url', HttpUrlListener::RGXP_NAME, CustomRgxpListener::RGXP_NAME),
			'reference'               => &$GLOBALS['TL_LANG']['tl_form_field'],
			'eval'                    => array('helpwizard'=>true, 'includeBlankOption'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'placeholder' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'customRgxp' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50', 'mandatory'=>true),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'errorMsg' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'minlength' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'maxlength' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'maxImageWidth' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'maxImageHeight' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'minval' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'tl_class'=>'w50'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'maxval' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'tl_class'=>'w50'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'step' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'tl_class'=>'w50'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'size' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true, 'size'=>2, 'rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default 'a:2:{i:0;i:4;i:1;i:40;}'"
		),
		'multiple' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'clr'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'mSize' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'extensions' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'extnd', 'maxlength'=>255, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_form_field', 'checkExtensions')
			),
			'sql'                     => "varchar(255) NOT NULL default 'jpg,jpeg,gif,png,pdf,doc,docx,xls,xlsx,ppt,pptx'"
		),
		'storeFile' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'uploadFolder' => array
		(
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "binary(16) NULL"
		),
		'useHomeDir' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'doNotOverwrite' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'class' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'value' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'accesskey' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alnum', 'maxlength'=>1, 'tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'fSize' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'customTpl' => array
		(
			'inputType'               => 'select',
			'eval'                    => array('chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'slabel' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'imageSubmit' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'singleSRC' => array
		(
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'filesOnly'=>true, 'mandatory'=>true, 'tl_class'=>'clr'),
			'sql'                     => "binary(16) NULL"
		),
		'invisible' => array
		(
			'reverseToggle'           => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => array('type' => 'boolean', 'default' => false)
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_form_field extends Backend
{
	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import(BackendUser::class, 'User');
	}

	/**
	 * Check permissions to edit table tl_form_field
	 *
	 * @param DataContainer $dc
	 *
	 * @throws AccessDeniedException
	 */
	public function checkPermission(DataContainer $dc)
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();

		// Set root IDs
		if (empty($this->User->forms) || !is_array($this->User->forms))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->forms;
		}

		$id = strlen(Input::get('id')) ? Input::get('id') : $dc->currentPid;

		// Check current action
		switch (Input::get('act'))
		{
			case 'paste':
			case 'select':
				// Check currentId here (see #247)
				if (!in_array($dc->currentPid, $root))
				{
					throw new AccessDeniedException('Not enough permissions to access form ID ' . $id . '.');
				}
				break;

			case 'create':
			case 'cut':
			case 'copy':
				$pid = Input::get('pid');

				// Get form ID
				if (Input::get('mode') == 1)
				{
					$objField = $this->Database->prepare("SELECT pid FROM tl_form_field WHERE id=?")
											   ->limit(1)
											   ->execute(Input::get('pid'));

					if ($objField->numRows < 1)
					{
						throw new AccessDeniedException('Invalid form field ID ' . Input::get('pid') . '.');
					}

					$pid = $objField->pid;
				}

				if (!in_array($pid, $root))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' form field ID ' . $id . ' to form ID ' . $pid . '.');
				}

				if (Input::get('act') == 'create')
				{
					break;
				}
				// no break

			case 'edit':
			case 'show':
			case 'delete':
			case 'toggle':
				$objField = $this->Database->prepare("SELECT pid FROM tl_form_field WHERE id=?")
										   ->limit(1)
										   ->execute($id);

				if ($objField->numRows < 1)
				{
					throw new AccessDeniedException('Invalid form field ID ' . $id . '.');
				}

				if (!in_array($objField->pid, $root))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' form field ID ' . $id . ' of form ID ' . $objField->pid . '.');
				}
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'cutAll':
			case 'copyAll':
				if (!in_array($id, $root))
				{
					throw new AccessDeniedException('Not enough permissions to access form ID ' . $id . '.');
				}

				$objForm = $this->Database->prepare("SELECT id FROM tl_form_field WHERE pid=?")
										  ->execute($id);

				$session = $objSession->all();
				$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objForm->fetchEach('id'));
				$objSession->replace($session);
				break;

			default:
				if (Input::get('act'))
				{
					throw new AccessDeniedException('Invalid command "' . Input::get('act') . '".');
				}

				if (!in_array($id, $root))
				{
					throw new AccessDeniedException('Not enough permissions to access form ID ' . $id . '.');
				}
				break;
		}
	}

	/**
	 * Filter the form fields
	 */
	public function filterFormFields()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		if (empty($this->User->fields))
		{
			$GLOBALS['TL_DCA']['tl_form_field']['config']['closed'] = true;
			$GLOBALS['TL_DCA']['tl_form_field']['config']['notEditable'] = true;
		}
		elseif (!in_array($GLOBALS['TL_DCA']['tl_form_field']['fields']['type']['sql']['default'] ?? null, $this->User->fields))
		{
			$GLOBALS['TL_DCA']['tl_form_field']['fields']['type']['default'] = $this->User->fields[0];
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();

		// Prevent editing form fields with not allowed types
		if (Input::get('act') == 'edit' || Input::get('act') == 'toggle' || Input::get('act') == 'delete' || (Input::get('act') == 'paste' && Input::get('mode') == 'copy'))
		{
			$objField = $this->Database->prepare("SELECT type FROM tl_form_field WHERE id=?")
									   ->execute(Input::get('id'));

			if ($objField->numRows && !in_array($objField->type, $this->User->fields))
			{
				throw new AccessDeniedException('Not enough permissions to modify form fields of type "' . $objField->type . '".');
			}
		}

		// Prevent editing content elements with not allowed types
		if (Input::get('act') == 'editAll' || Input::get('act') == 'overrideAll' || Input::get('act') == 'deleteAll')
		{
			$session = $objSession->all();

			if (!empty($session['CURRENT']['IDS']) && is_array($session['CURRENT']['IDS']))
			{
				if (empty($this->User->fields))
				{
					$session['CURRENT']['IDS'] = array();
				}
				else
				{
					$objFields = $this->Database->prepare("SELECT id FROM tl_form_field WHERE id IN(" . implode(',', array_map('\intval', $session['CURRENT']['IDS'])) . ") AND type IN(" . implode(',', array_fill(0, count($this->User->fields), '?')) . ")")
												->execute(...$this->User->fields);

					$session['CURRENT']['IDS'] = $objFields->fetchEach('id');
				}

				$objSession->replace($session);
			}
		}

		// Prevent copying content elements with not allowed types
		if (Input::get('act') == 'copyAll')
		{
			$session = $objSession->all();

			if (!empty($session['CLIPBOARD']['tl_form_field']['id']) && is_array($session['CLIPBOARD']['tl_form_field']['id']))
			{
				if (empty($this->User->fields))
				{
					$session['CLIPBOARD']['tl_form_field']['id'] = array();
				}
				else
				{
					$objFields = $this->Database->prepare("SELECT id, type FROM tl_form_field WHERE id IN(" . implode(',', array_map('\intval', $session['CLIPBOARD']['tl_form_field']['id'])) . ") AND type IN(" . implode(',', array_fill(0, count($this->User->fields), '?')) . ")")
												->execute(...$this->User->fields);

					$session['CLIPBOARD']['tl_form_field']['id'] = $objFields->fetchEach('id');
				}

				$objSession->replace($session);
			}
		}
	}

	/**
	 * Add the type of input field
	 *
	 * @param array $arrRow
	 *
	 * @return string
	 */
	public function listFormFields($arrRow)
	{
		$arrRow['required'] = $arrRow['mandatory'];
		$strClass = $GLOBALS['TL_FFL'][$arrRow['type']] ?? null;

		if (!class_exists($strClass))
		{
			return '';
		}

		/** @var Widget $objWidget */
		$objWidget = new $strClass($arrRow);
		$key = $arrRow['invisible'] ? 'unpublished' : 'published';

		$strType = '
<div class="cte_type ' . $key . '">' . $GLOBALS['TL_LANG']['FFL'][$arrRow['type']][0] . ($objWidget->submitInput() && $arrRow['name'] ? ' (' . $arrRow['name'] . ')' : '') . '</div>
<div class="cte_preview limit_height' . (!Config::get('doNotCollapse') ? ' h32' : '') . '">';

		$strWidget = $objWidget->parse();
		$strWidget = preg_replace('/ name="[^"]+"/i', '', $strWidget);
		$strWidget = str_replace(array(' type="submit"', ' autofocus', ' required'), array(' type="button"', '', ''), $strWidget);

		if ($objWidget instanceof FormHidden)
		{
			return $strType . "\n" . $objWidget->value . "\n</div>\n";
		}

		return $strType . StringUtil::insertTagToSrc($strWidget) . '
</div>' . "\n";
	}

	/**
	 * Add a link to the option items import wizard
	 *
	 * @return string
	 */
	public function optionImportWizard()
	{
		return ' <a href="' . $this->addToUrl('key=option') . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['ow_import'][1]) . '" onclick="Backend.getScrollOffset()">' . Image::getHtml('tablewizard.svg', $GLOBALS['TL_LANG']['MSC']['ow_import'][0]) . '</a>';
	}

	/**
	 * Check the configured extensions against the upload types
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return string
	 */
	public function checkExtensions($varValue, DataContainer $dc)
	{
		// Convert the extensions to lowercase
		$varValue = strtolower($varValue);
		$arrExtensions = StringUtil::trimsplit(',', $varValue);
		$arrUploadTypes = StringUtil::trimsplit(',', strtolower(Config::get('uploadTypes')));
		$arrNotAllowed = array_diff($arrExtensions, $arrUploadTypes);

		if (0 !== count($arrNotAllowed))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['forbiddenExtensions'], implode(', ', $arrNotAllowed)));
		}

		return $varValue;
	}

	/**
	 * Return a list of form fields
	 *
	 * @return array
	 */
	public function getFields()
	{
		$fields = array();
		$security = System::getContainer()->get('security.helper');

		foreach ($GLOBALS['TL_FFL'] as $k=>$v)
		{
			if ($security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_FIELD_TYPE, $k))
			{
				$fields[] = $k;
			}
		}

		return $fields;
	}

	/**
	 * Disable the button if the element type is not allowed
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
	public function disableButton($row, $href, $label, $title, $icon, $attributes)
	{
		return System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_FIELD_TYPE, $row['type']) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the "toggle visibility" button
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
	public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
	{
		$security = System::getContainer()->get('security.helper');

		// Check permissions AFTER checking the tid, so hacking attempts are logged
		if (!$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_form_field::invisible'))
		{
			return '';
		}

		// Disable the button if the element type is not allowed
		if (!$security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_FIELD_TYPE, $row['type']))
		{
			return Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
		}

		$href .= '&amp;id=' . $row['id'];

		if ($row['invisible'])
		{
			$icon = 'invisible.svg';
		}

		return '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.getScrollOffset();return AjaxRequest.toggleField(this,true)">' . Image::getHtml($icon, $label, 'data-icon="' . Image::getUrl('visible.svg') . '" data-icon-disabled="' . Image::getUrl('invisible.svg') . '" data-state="' . ($row['invisible'] ? 0 : 1) . '"') . '</a> ';
	}
}
