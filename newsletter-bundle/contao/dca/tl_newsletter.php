<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\Config;
use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\DC_Table;
use Contao\Environment;
use Contao\NewsletterChannelModel;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_newsletter'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_newsletter_channel',
		'enableVersioning'            => true,
		'markAsCopy'                  => 'subject',
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid' => 'index',
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
			'fields'                  => array('sent', 'date'),
			'headerFields'            => array('title', 'jumpTo', 'tstamp', 'sender'),
			'panelLayout'             => 'filter;sort,search,limit',
			'defaultSearchField'      => 'subject',
			'child_record_callback'   => array('tl_newsletter', 'listNewsletters'),
			'renderAsGrid'            => true,
			'limitHeight'             => 160
		),
		'operations' => array
		(
			'edit',
			'copy',
			'cut',
			'delete',
			'show',
			'send' => array
			(
				'href'                => 'key=send',
				'icon'                => 'bundles/contaonewsletter/send.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('addFile'),
		'default'                     => '{title_legend},subject,alias;{html_legend},preheader,content;{text_legend:hide},text;{attachment_legend},addFile;{template_legend:hide},template;{sender_legend:hide},sender,senderName,mailerTransport;{expert_legend:hide},sendText,externalImages'
	),

	// Sub-palettes
	'subpalettes' => array
	(
		'addFile'                     => 'files'
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
			'foreignKey'              => 'tl_newsletter_channel.title',
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'subject' => array
		(
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'alias' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_newsletter', 'generateAlias')
			),
			'sql'                     => "varchar(255) BINARY NOT NULL default ''"
		),
		'preheader' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'maxlength'=>255),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'content' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyNews', 'helpwizard'=>true),
			'explanation'             => 'insertTags',
			'load_callback' => array
			(
				array('tl_newsletter', 'convertAbsoluteLinks')
			),
			'save_callback' => array
			(
				array('tl_newsletter', 'convertRelativeLinks')
			),
			'sql'                     => "mediumtext NULL"
		),
		'text' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('decodeEntities'=>true, 'class'=>'noresize'),
			'sql'                     => "mediumtext NULL"
		),
		'addFile' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'files' => array
		(
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'filesOnly'=>true, 'mandatory'=>true),
			'sql'                     => "blob NULL"
		),
		'template' => array
		(
			'inputType'               => 'select',
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'options_callback' => static function () {
				return Controller::getTemplateGroup('mail_');
			},
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'sendText' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'externalImages' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'mailerTransport' => array
		(
			'inputType'               => 'select',
			'eval'                    => array('tl_class'=>'w33', 'includeBlankOption'=>true),
			'options_callback'        => array('contao.mailer.available_transports', 'getTransportOptions'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'sender' => array
		(
			'search'                  => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'email', 'maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w33'),
			'load_callback' => array
			(
				array('tl_newsletter', 'addSenderPlaceholder')
			),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'senderName' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'maxlength'=>128, 'tl_class'=>'w33'),
			'load_callback' => array
			(
				array('tl_newsletter', 'addSenderNamePlaceholder')
			),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'sent' => array
		(
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_ASC,
			'eval'                    => array('doNotCopy'=>true, 'isBoolean'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'date' => array
		(
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_MONTH_DESC,
			'eval'                    => array('rgxp'=>'datim'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_newsletter extends Backend
{
	/**
	 * List records
	 *
	 * @param array $arrRow
	 *
	 * @return string
	 */
	public function listNewsletters($arrRow)
	{
		return '
<div class="cte_type ' . (($arrRow['sent'] && $arrRow['date']) ? 'published' : 'unpublished') . '"><strong>' . $arrRow['subject'] . '</strong> - ' . (($arrRow['sent'] && $arrRow['date']) ? sprintf($GLOBALS['TL_LANG']['tl_newsletter']['sentOn'], Date::parse(Config::get('datimFormat'), $arrRow['date'])) : $GLOBALS['TL_LANG']['tl_newsletter']['notSent']) . '</div>
<div class="cte_preview">' . (!$arrRow['sendText'] ? '
' . StringUtil::insertTagToSrc($arrRow['content']) . '<hr>' : '') . '
<pre style="white-space:pre-wrap">' . $arrRow['text'] . '</pre>
</div>' . "\n";
	}

	/**
	 * Convert absolute URLs from TinyMCE to relative URLs
	 *
	 * @param string $strContent
	 *
	 * @return string
	 */
	public function convertAbsoluteLinks($strContent)
	{
		return str_replace('src="' . Environment::get('base'), 'src="', $strContent);
	}

	/**
	 * Convert relative URLs from TinyMCE to absolute URLs
	 *
	 * @param string $strContent
	 *
	 * @return string
	 */
	public function convertRelativeLinks($strContent)
	{
		return $this->convertRelativeUrls($strContent);
	}

	/**
	 * Auto-generate the newsletter alias if it has not been set yet
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
				->prepare("SELECT id FROM tl_newsletter WHERE alias=? AND id!=?")
				->execute($alias, $dc->id);

			return $result->numRows > 0;
		};

		// Generate alias if there is none
		if (!$varValue)
		{
			$varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->subject, NewsletterChannelModel::findById($dc->activeRecord->pid)->jumpTo, $aliasExists);
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
	 * Add the sender address as placeholder
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 */
	public function addSenderPlaceholder($varValue, DataContainer $dc)
	{
		if ($dc->activeRecord?->pid)
		{
			$objChannel = Database::getInstance()
				->prepare("SELECT sender FROM tl_newsletter_channel WHERE id=?")
				->execute($dc->activeRecord->pid);

			$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['placeholder'] = $objChannel->sender;
		}

		return $varValue;
	}

	/**
	 * Add the sender name as placeholder
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 */
	public function addSenderNamePlaceholder($varValue, DataContainer $dc)
	{
		if ($dc->activeRecord?->pid)
		{
			$objChannel = Database::getInstance()
				->prepare("SELECT senderName FROM tl_newsletter_channel WHERE id=?")
				->execute($dc->activeRecord->pid);

			$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['placeholder'] = $objChannel->senderName;
		}

		return $varValue;
	}
}
