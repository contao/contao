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
use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_form'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'ctable'                      => array('tl_form_field'),
		'markAsCopy'                  => 'title',
		'onload_callback' => array
		(
			array('tl_form', 'adjustDca')
		),
		'oncreate_callback' => array
		(
			array('tl_form', 'adjustPermissions')
		),
		'oncopy_callback' => array
		(
			array('tl_form', 'adjustPermissions')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index',
				'alias' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTED,
			'fields'                  => array('title'),
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'panelLayout'             => 'filter;search,limit',
			'defaultSearchField'      => 'title'
		),
		'label' => array
		(
			'fields'                  => array('title', 'formID'),
			'format'                  => '%s <span class="label-info">[%s]</span>'
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('sendViaEmail', 'storeValues'),
		'default'                     => '{title_legend},title,alias,jumpTo;{config_legend},ajax,allowTags;{confirm_legend},confirmation;{email_legend},sendViaEmail;{store_legend:hide},storeValues;{template_legend:hide},customTpl;{expert_legend:hide},method,novalidate,attributes,formID'
	),

	// Sub-palettes
	'subpalettes' => array
	(
		'sendViaEmail'                => 'mailerTransport,recipient,subject,format,skipEmpty',
		'storeValues'                 => 'targetTable'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'title' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'alias' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alias', 'doNotCopy'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_form', 'generateAlias')
			),
			'sql'                     => "varchar(255) BINARY NOT NULL default ''"
		),
		'jumpTo' => array
		(
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'confirmation' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'helpwizard'=>true),
			'explanation'             => 'insertTags',
			'sql'                     => "text NULL"
		),
		'sendViaEmail' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'mailerTransport' => array
		(
			'inputType'               => 'select',
			'eval'                    => array('tl_class'=>'w50', 'includeBlankOption'=>true),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'recipient' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>1022, 'rgxp'=>'emails', 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(1022) NOT NULL default ''"
		),
		'subject' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'format' => array
		(
			'inputType'               => 'select',
			'options'                 => array('raw', 'xml', 'csv', 'csv_excel', 'email'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_form'],
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(12) NOT NULL default 'raw'"
		),
		'skipEmpty' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'storeValues' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'targetTable' => array
		(
			'search'                  => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_form', 'getAllTables'),
			'eval'                    => array('chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'customTpl' => array
		(
			'inputType'               => 'select',
			'options_callback' => static function () {
				return Controller::getTemplateGroup('form_wrapper_', array(), 'form_wrapper');
			},
			'eval'                    => array('chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'method' => array
		(
			'filter'                  => true,
			'inputType'               => 'select',
			'options'                 => array('POST', 'GET'),
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(12) NOT NULL default 'POST'"
		),
		'novalidate' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'attributes' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'formID' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('nospace'=>true, 'doNotCopy'=>true, 'maxlength'=>64, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'ajax' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'allowTags' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_form extends Backend
{
	/**
	 *  Set the root IDs.
	 */
	public function adjustDca()
	{
		$user = BackendUser::getInstance();

		if ($user->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (empty($user->forms) || !is_array($user->forms))
		{
			$root = array(0);
		}
		else
		{
			$root = $user->forms;
		}

		$GLOBALS['TL_DCA']['tl_form']['list']['sorting']['root'] = $root;
	}

	/**
	 * Add the new form to the permissions
	 *
	 * @param string|int $insertId
	 */
	public function adjustPermissions($insertId)
	{
		// The oncreate_callback passes $insertId as second argument
		if (func_num_args() == 4)
		{
			$insertId = func_get_arg(1);
		}

		$user = BackendUser::getInstance();

		if ($user->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (empty($user->forms) || !is_array($user->forms))
		{
			$root = array(0);
		}
		else
		{
			$root = $user->forms;
		}

		// The form is enabled already
		if (in_array($insertId, $root))
		{
			return;
		}

		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
		$arrNew = $objSessionBag->get('new_records');

		if (is_array($arrNew['tl_form']) && in_array($insertId, $arrNew['tl_form']))
		{
			$db = Database::getInstance();

			// Add the permissions on group level
			if ($user->inherit != 'custom')
			{
				$objGroup = $db->execute("SELECT id, forms, formp FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $user->groups)) . ")");

				while ($objGroup->next())
				{
					$arrFormp = StringUtil::deserialize($objGroup->formp);

					if (is_array($arrFormp) && in_array('create', $arrFormp))
					{
						$arrForms = StringUtil::deserialize($objGroup->forms, true);
						$arrForms[] = $insertId;

						$db->prepare("UPDATE tl_user_group SET forms=? WHERE id=?")->execute(serialize($arrForms), $objGroup->id);
					}
				}
			}

			// Add the permissions on user level
			if ($user->inherit != 'group')
			{
				$objUser = $db
					->prepare("SELECT forms, formp FROM tl_user WHERE id=?")
					->limit(1)
					->execute($user->id);

				$arrFormp = StringUtil::deserialize($objUser->formp);

				if (is_array($arrFormp) && in_array('create', $arrFormp))
				{
					$arrForms = StringUtil::deserialize($objUser->forms, true);
					$arrForms[] = $insertId;

					$db->prepare("UPDATE tl_user SET forms=? WHERE id=?")->execute(serialize($arrForms), $user->id);
				}
			}

			// Add the new element to the user object
			$root[] = $insertId;
			$user->forms = $root;
		}
	}

	/**
	 * Auto-generate a form alias if it has not been set yet
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 */
	public function generateAlias($varValue, DataContainer $dc)
	{
		$aliasExists = static function (string $alias) use ($dc): bool {
			$result = Database::getInstance()
				->prepare("SELECT id FROM tl_form WHERE alias=? AND id!=?")
				->execute($alias, $dc->id);

			return $result->numRows > 0;
		};

		// Generate an alias if there is none
		if (!$varValue)
		{
			$varValue = System::getContainer()->get('contao.slug')->generate((string) $dc->activeRecord->title, (int) (Input::post('jumpTo') ?: $dc->activeRecord->jumpTo), $aliasExists);
		}
		elseif (preg_match('/^[1-9]\d*$/', $varValue))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
		}
		elseif ($aliasExists($varValue))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
		}

		return $varValue;
	}

	/**
	 * Get all tables and return them as array
	 *
	 * @return array
	 */
	public function getAllTables()
	{
		// Return allowlisted tables if defined
		if (!empty($GLOBALS['TL_DCA']['tl_form']['fields']['targetTable']['options']))
		{
			return $GLOBALS['TL_DCA']['tl_form']['fields']['targetTable']['options'];
		}

		$GLOBALS['TL_DCA']['tl_form']['fields']['targetTable']['label'][1] = '<span class="tl_red">' . sprintf($GLOBALS['TL_LANG']['tl_form']['targetTableMissingAllowlist'], "\$GLOBALS['TL_DCA']['tl_form']['fields']['targetTable']['options']") . '</span>';

		if (!BackendUser::getInstance()->isAdmin)
		{
			return array();
		}

		$arrTables = Database::getInstance()->listTables();
		$arrViews = System::getContainer()->get('database_connection')->createSchemaManager()->listViews();

		if (!empty($arrViews))
		{
			$arrTables = array_merge($arrTables, array_keys($arrViews));
			natsort($arrTables);
		}

		return array_values($arrTables);
	}
}
