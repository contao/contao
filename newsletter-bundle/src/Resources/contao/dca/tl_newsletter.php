<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_newsletter'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_newsletter_channel',
		'enableVersioning'            => true,
		'onload_callback' => array
		(
			array('tl_newsletter', 'checkPermission')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('sent', 'date DESC', 'tstamp DESC'),
			'headerFields'            => array('title', 'jumpTo', 'tstamp', 'sender'),
			'panelLayout'             => 'filter;sort,search,limit',
			'child_record_callback'   => array('tl_newsletter', 'listNewsletters')
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_newsletter']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_newsletter']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_newsletter']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_newsletter']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_newsletter']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			),
			'send' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_newsletter']['send'],
				'href'                => 'key=send',
				'icon'                => 'bundles/contaonewsletter/send.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('addFile'),
		'default'                     => '{title_legend},subject,alias;{html_legend},content;{text_legend:hide},text;{attachment_legend},addFile;{template_legend:hide},template;{sender_legend:hide},sender,senderName;{expert_legend:hide},sendText,externalImages'
	),

	// Subpalettes
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
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'belongsTo', 'load'=>'eager')
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'subject' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter']['subject'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'alias' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter']['alias'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_newsletter', 'generateAlias')
			),
			'sql'                     => "varchar(128) COLLATE utf8_bin NOT NULL default ''"
		),
		'content' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter']['content'],
			'exclude'                 => true,
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter']['text'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('decodeEntities'=>true, 'class'=>'noresize'),
			'sql'                     => "mediumtext NULL"
		),
		'addFile' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter']['addFile'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'files' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter']['files'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'filesOnly'=>true, 'mandatory'=>true),
			'sql'                     => "blob NULL"
		),
		'template' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter']['template'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'options_callback'        => function ()
			{
				return Controller::getTemplateGroup('mail_');
			},
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'sendText' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter']['sendText'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'externalImages' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter']['externalImages'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'sender' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter']['sender'],
			'exclude'                 => true,
			'search'                  => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'email', 'maxlength'=>128, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'senderName' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter']['senderName'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 11,
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'sent' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter']['sent'],
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 11,
			'eval'                    => array('doNotCopy'=>true, 'isBoolean'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'date' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter']['mailingDate'],
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 8,
			'eval'                    => array('rgxp'=>'datim'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class tl_newsletter extends Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	/**
	 * Check permissions to edit table tl_newsletter
	 *
	 * @throws Contao\CoreBundle\Exception\AccessDeniedException
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (empty($this->User->newsletters) || !\is_array($this->User->newsletters))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->newsletters;
		}

		$id = \strlen(Input::get('id')) ? Input::get('id') : CURRENT_ID;

		// Check current action
		switch (Input::get('act'))
		{
			case 'paste':
			case 'select':
				// Allow
				break;

			case 'create':
				if (!\strlen(Input::get('pid')) || !\in_array(Input::get('pid'), $root))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to create newsletters in channel ID ' . Input::get('pid') . '.');
				}
				break;

			case 'cut':
			case 'copy':
				if (!\in_array(Input::get('pid'), $root))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' newsletter ID ' . $id . ' to channel ID ' . Input::get('pid') . '.');
				}
				// NO BREAK STATEMENT HERE

			case 'edit':
			case 'show':
			case 'delete':
				$objChannel = $this->Database->prepare("SELECT pid FROM tl_newsletter WHERE id=?")
											 ->limit(1)
											 ->execute($id);

				if ($objChannel->numRows < 1)
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Invalid newsletter ID ' . $id . '.');
				}

				if (!\in_array($objChannel->pid, $root))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' newsletter ID ' . $id . ' of newsletter channel ID ' . $objChannel->pid . '.');
				}
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'cutAll':
			case 'copyAll':
				if (!\in_array($id, $root))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to access newsletter channel ID ' . $id . '.');
				}

				$objChannel = $this->Database->prepare("SELECT id FROM tl_newsletter WHERE pid=?")
											 ->execute($id);

				if ($objChannel->numRows < 1)
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Invalid newsletter channel ID  ' . $id . '.');
				}

				/** @var Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
				$objSession = System::getContainer()->get('session');

				$session = $objSession->all();
				$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objChannel->fetchEach('id'));
				$objSession->replace($session);
				break;

			default:
				if (\strlen(Input::get('act')))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Invalid command "' . Input::get('act') . '".');
				}

				if (Input::get('key') == 'send')
				{
					$objChannel = $this->Database->prepare("SELECT pid FROM tl_newsletter WHERE id=?")
												 ->limit(1)
												 ->execute($id);

					if ($objChannel->numRows < 1)
					{
						throw new Contao\CoreBundle\Exception\AccessDeniedException('Invalid newsletter ID ' . $id . '.');
					}

					if (!\in_array($objChannel->pid, $root))
					{
						throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to send newsletter ID ' . $id . ' of newsletter channel ID ' . $objChannel->pid . '.');
					}
				}
				elseif (!\in_array($id, $root))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to access newsletter channel ID ' . $id . '.');
				}
				break;
		}
	}

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
<div class="limit_height' . (!Config::get('doNotCollapse') ? ' h85' : '') . '">' . (!$arrRow['sendText'] ? '
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
		return str_replace('src="' .Environment::get('base'), 'src="', $strContent);
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
		$autoAlias = false;

		// Generate alias if there is none
		if ($varValue == '')
		{
			$autoAlias = true;
			$varValue = StringUtil::generateAlias($dc->activeRecord->subject);
		}

		$objAlias = $this->Database->prepare("SELECT id FROM tl_newsletter WHERE alias=? AND id!=?")
								   ->execute($varValue, $dc->id);

		// Check whether the newsletter alias exists
		if ($objAlias->numRows)
		{
			if (!$autoAlias)
			{
				throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
			}

			$varValue .= '-' . $dc->id;
		}

		return $varValue;
	}
}
