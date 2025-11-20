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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\FormHidden;
use Contao\Image;
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
			array('tl_form_field', 'filterFormFields')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid,invisible' => 'index',
				'tstamp' => 'index'
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
			'renderAsGrid'            => true,
			'limitHeight'             => 104
		),
		'label' => array
		(
			'fields'                  => array('type', 'name'),
			'format'                  => '%s (%s)',
			'label_callback'          => array('tl_form_field', 'listFormFields'),
		),
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
		'text'                        => '{type_legend},type,name,label;{fconfig_legend},mandatory,placeholder,help;{rgxp_legend},rgxp;{expert_legend:hide},class,value,minlength,maxlength,autocomplete,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'textdigit'                   => '{type_legend},type,name,label;{fconfig_legend},mandatory,placeholder,help;{rgxp_legend},rgxp;{expert_legend:hide},class,value,minval,maxval,step,autocomplete,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'textcustom'                  => '{type_legend},type,name,label;{fconfig_legend},mandatory,placeholder,help;{rgxp_legend},rgxp,customRgxp,errorMsg;{expert_legend:hide},class,value,minlength,maxlength,autocomplete,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'password'                    => '{type_legend},type,name,label;{fconfig_legend},mandatory,placeholder,help;{rgxp_legend},rgxp;{expert_legend:hide},class,value,minlength,maxlength,autocomplete,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'passwordcustom'              => '{type_legend},type,name,label;{fconfig_legend},mandatory,placeholder,help;{rgxp_legend},rgxp,customRgxp,errorMsg;{expert_legend:hide},class,value,minlength,maxlength,autocomplete,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'textarea'                    => '{type_legend},type,name,label;{fconfig_legend},mandatory,placeholder,help;{rgxp_legend},rgxp;{size_legend},size;{expert_legend:hide},class,value,minlength,maxlength,autocomplete,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'textareacustom'              => '{type_legend},type,name,label;{fconfig_legend},mandatory,placeholder,help;{rgxp_legend},rgxp,customRgxp,errorMsg;{size_legend},size;{expert_legend:hide},class,value,minlength,maxlength,autocomplete,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'select'                      => '{type_legend},type,name,label;{fconfig_legend},mandatory,multiple,help;{options_legend},options;{expert_legend:hide},class,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'radio'                       => '{type_legend},type,name,label;{fconfig_legend},mandatory,help;{options_legend},options;{expert_legend:hide},class;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'checkbox'                    => '{type_legend},type,name,label;{fconfig_legend},mandatory,help;{options_legend},options;{expert_legend:hide},class;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'upload'                      => '{type_legend},type,name,label;{fconfig_legend},mandatory,extensions,maxlength,multipleFiles,maxImageWidth,maxImageHeight,help;{store_legend:hide},storeFile;{expert_legend:hide},class,accesskey,fSize;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'range'                       => '{type_legend},type,name,label;{fconfig_legend},mandatory,help;{expert_legend:hide},class,value,minval,maxval,step,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'hidden'                      => '{type_legend},type,name,value;{fconfig_legend},mandatory,rgxp;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'hiddencustom'                => '{type_legend},type,name,value;{fconfig_legend},mandatory,rgxp,customRgxp;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'captcha'                     => '{type_legend},type,label;{fconfig_legend},placeholder;{expert_legend:hide},class,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'altcha'                      => '{type_legend},type,name,label;{fconfig_legend},altchaAuto,altchaHideLogo,altchaHideFooter;{expert_legend:hide},class;{template_legend:hide},customTpl;{invisible_legend:hide},invisible',
		'submit'                      => '{type_legend},type,slabel;{image_legend:hide},imageSubmit;{expert_legend:hide},class,accesskey;{template_legend:hide},customTpl;{invisible_legend:hide},invisible'
	),

	// Sub-palettes
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
			'eval'                    => array('helpwizard'=>true, 'chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50'),
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
			'eval'                    => array('rte'=>'tinyMCE', 'basicEntities'=>true, 'helpwizard'=>true),
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
		'help' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'customRgxp' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50 clr', 'mandatory'=>true),
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
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w25'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'maxlength' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w25'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'maxImageWidth' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w25'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'maxImageHeight' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w25'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'minval' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'tl_class'=>'w25'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'maxval' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'tl_class'=>'w25'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'step' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'tl_class'=>'w25'),
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
		'multipleFiles' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w25'),
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
			'eval'                    => array('rgxp'=>'alnum', 'maxlength'=>1, 'tl_class'=>'w25'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'fSize' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w25'),
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
		'altchaAuto' => array
		(
			'inputType'               => 'select',
			'options'                 => array('onfocus', 'onsubmit'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(12) NOT NULL default ''"
		),
		'altchaHideLogo' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w25'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'altchaHideFooter' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w25'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'autocomplete' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('helpwizard'=>true, 'maxlength'=>255, 'tl_class'=>'w25'),
			'explanation'             => 'autocomplete',
			'sql'                     => "varchar(255) NOT NULL default ''"
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
	 * Filter the form fields
	 */
	public function filterFormFields()
	{
		$user = BackendUser::getInstance();

		if ($user->isAdmin)
		{
			return;
		}

		if (empty($user->fields))
		{
			$GLOBALS['TL_DCA']['tl_form_field']['config']['closed'] = true;
			$GLOBALS['TL_DCA']['tl_form_field']['config']['notEditable'] = true;
		}
		elseif (!in_array($GLOBALS['TL_DCA']['tl_form_field']['fields']['type']['sql']['default'] ?? null, $user->fields))
		{
			$GLOBALS['TL_DCA']['tl_form_field']['fields']['type']['default'] = $user->fields[0];
		}
	}

	/**
	 * Add the type of input field
	 *
	 * @param array $arrRow
	 *
	 * @return array
	 */
	public function listFormFields($arrRow)
	{
		$arrRow['required'] = $arrRow['mandatory'];

		/** @var class-string<Widget> $strClass */
		$strClass = $GLOBALS['TL_FFL'][$arrRow['type']] ?? null;

		if (class_exists($strClass))
		{
			$objWidget = new $strClass($arrRow);
		}
		else
		{
			$objWidget = null;
		}

		$label = array(
			($GLOBALS['TL_LANG']['FFL'][$arrRow['type']][0] ?? $arrRow['type']) . ($objWidget?->submitInput() && $arrRow['name'] ? ' (' . $arrRow['name'] . ')' : ''),
			'',
			$arrRow['invisible'] ? 'unpublished' : 'published',
		);

		if ($objWidget)
		{
			$strWidget = $objWidget->parse();
			$strWidget = preg_replace('/ name="[^"]+"/i', '', $strWidget);
			$strWidget = str_replace(array(' type="submit"', ' autofocus', ' required'), array(' type="button"', '', ''), $strWidget);

			if ($objWidget instanceof FormHidden)
			{
				$label[1] = $objWidget->value;
			}
			else
			{
				$label[1] = StringUtil::insertTagToSrc($strWidget);
			}
		}

		return $label;
	}

	/**
	 * Add a link to the option items import wizard
	 *
	 * @return string
	 */
	public function optionImportWizard()
	{
		return ' <a href="' . $this->addToUrl('key=option') . '" data-action="contao--scroll-offset#store">' . Image::getHtml('tablewizard.svg', $GLOBALS['TL_LANG']['MSC']['ow_import'][1]) . '</a>';
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
}
