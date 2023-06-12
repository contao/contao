<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\ArticleModel;
use Contao\Backend;
use Contao\BackendUser;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Versions;

$this->loadDataContainer('tl_page');

$GLOBALS['TL_DCA']['tl_article'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_page',
		'ctable'                      => array('tl_content'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'markAsCopy'                  => 'title',
		'onload_callback' => array
		(
			array('tl_article', 'checkPermission'),
			array('tl_article', 'addCustomLayoutSectionReferences'),
			array('tl_page', 'addBreadcrumb')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'alias' => 'index',
				'pid,published,inColumn,start,stop' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_TREE_EXTENDED,
			'panelLayout'             => 'filter;search',
			'defaultSearchField'      => 'title'
		),
		'label' => array
		(
			'fields'                  => array('title', 'inColumn'),
			'format'                  => '%s <span class="label-info">[%s]</span>',
			'label_callback'          => array
			(
				array('tl_article', 'addIcon')
			)
		),
		'global_operations' => array
		(
			'toggleNodes' => array
			(
				'href'                => 'ptg=all',
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
				'href'                => 'act=edit',
				'icon'                => 'edit.svg',
				'button_callback'     => array('tl_article', 'editHeader')
			),
			'children' => array
			(
				'href'                => 'table=tl_content',
				'icon'                => 'children.svg',
				'button_callback'     => array('tl_article', 'editArticle')
			),
			'copy' => array
			(
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"',
				'button_callback'     => array('tl_article', 'copyArticle')
			),
			'cut' => array
			(
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"',
				'button_callback'     => array('tl_article', 'cutArticle')
			),
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
				'button_callback'     => array('tl_article', 'deleteArticle')
			),
			'toggle' => array
			(
				'href'                => 'act=toggle&amp;field=published',
				'icon'                => 'visible.svg',
				'button_callback'     => array('tl_article', 'toggleIcon'),
				'showInHeader'        => true
			),
			'show'
		)
	),

	// Select
	'select' => array
	(
		'buttons_callback' => array
		(
			array('tl_article', 'addAliasButton')
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('protected'),
		'default'                     => '{title_legend},title,alias,author;{layout_legend},inColumn;{teaser_legend:hide},teaserCssID,showTeaser,teaser;{syndication_legend},printable;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{publish_legend},published,start,stop'
	),

	// Sub-palettes
	'subpalettes' => array
	(
		'protected'                   => 'groups'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'label'                   => array('ID'),
			'search'                  => true,
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'foreignKey'              => 'tl_page.title',
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
		'title' => array
		(
			'inputType'               => 'text',
			'search'                  => true,
			'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'alias' => array
		(
			'inputType'               => 'text',
			'search'                  => true,
			'eval'                    => array('rgxp'=>'alias', 'doNotCopy'=>true, 'maxlength'=>255, 'tl_class'=>'w50 clr'),
			'save_callback' => array
			(
				array('tl_article', 'generateAlias')
			),
			'sql'                     => "varchar(255) BINARY NOT NULL default ''"
		),
		'author' => array
		(
			'default'                 => static fn () => BackendUser::getInstance()->id,
			'search'                  => true,
			'filter'                  => true,
			'inputType'               => 'select',
			'foreignKey'              => 'tl_user.name',
			'eval'                    => array('doNotCopy'=>true, 'mandatory'=>true, 'chosen'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'inColumn' => array
		(
			'filter'                  => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_article', 'getActiveLayoutSections'),
			'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['COLS'],
			'sql'                     => "varchar(32) NOT NULL default 'main'"
		),
		'showTeaser' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'teaserCssID' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'teaser' => array
		(
			'inputType'               => 'textarea',
			'search'                  => true,
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'printable' => array
		(
			'inputType'               => 'checkbox',
			'options'                 => array('print', 'facebook', 'twitter'),
			'eval'                    => array('multiple'=>true),
			'reference'               => &$GLOBALS['TL_LANG']['tl_article'],
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'customTpl' => array
		(
			'inputType'               => 'select',
			'options_callback' => static function () {
				return Controller::getTemplateGroup('mod_article_', array(), 'mod_article');
			},
			'eval'                    => array('chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'protected' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'groups' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
		'cssID' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'published' => array
		(
			'toggle'                  => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'start' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'stop' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_article extends Backend
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
	 * Check permissions to edit table tl_page
	 *
	 * @throws AccessDeniedException
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();
		$session = $objSession->all();

		// Set the default page user and group
		$GLOBALS['TL_DCA']['tl_page']['fields']['cuser']['default'] = (int) Config::get('defaultUser') ?: $this->User->id;
		$GLOBALS['TL_DCA']['tl_page']['fields']['cgroup']['default'] = (int) Config::get('defaultGroup') ?: (int) $this->User->groups[0];

		// Restrict the page tree
		if (empty($this->User->pagemounts) || !is_array($this->User->pagemounts))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->pagemounts;
		}

		$GLOBALS['TL_DCA']['tl_page']['list']['sorting']['root'] = $root;
		$security = System::getContainer()->get('security.helper');

		// Set allowed page IDs (edit multiple)
		if (is_array($session['CURRENT']['IDS'] ?? null))
		{
			$edit_all = array();
			$delete_all = array();

			foreach ($session['CURRENT']['IDS'] as $id)
			{
				$objArticle = $this->Database->prepare("SELECT p.pid, p.includeChmod, p.chmod, p.cuser, p.cgroup FROM tl_article a, tl_page p WHERE a.id=? AND a.pid=p.id")
											 ->limit(1)
											 ->execute($id);

				if ($objArticle->numRows < 1)
				{
					continue;
				}

				$row = $objArticle->row();

				if ($security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, $row))
				{
					$edit_all[] = $id;
				}

				if ($security->isGranted(ContaoCorePermissions::USER_CAN_DELETE_ARTICLES, $row))
				{
					$delete_all[] = $id;
				}
			}

			$session['CURRENT']['IDS'] = (Input::get('act') == 'deleteAll') ? $delete_all : $edit_all;
		}

		// Set allowed clipboard IDs
		if (!empty($session['CLIPBOARD']['tl_article']['id']) && is_array($session['CLIPBOARD']['tl_article']['id']))
		{
			$clipboard = array();

			foreach ($session['CLIPBOARD']['tl_article']['id'] as $id)
			{
				$objArticle = $this->Database->prepare("SELECT p.pid, p.includeChmod, p.chmod, p.cuser, p.cgroup FROM tl_article a, tl_page p WHERE a.id=? AND a.pid=p.id")
											 ->limit(1)
											 ->execute($id);

				if ($objArticle->numRows < 1)
				{
					continue;
				}

				if ($security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, $objArticle->row()))
				{
					$clipboard[] = $id;
				}
			}

			$session['CLIPBOARD']['tl_article']['id'] = $clipboard;
		}

		$permission = null;

		// Overwrite the session
		$objSession->replace($session);

		// Check current action
		if (Input::get('act') && Input::get('act') != 'paste')
		{
			// Set ID of the article's page
			$objPage = $this->Database->prepare("SELECT pid FROM tl_article WHERE id=?")
									  ->limit(1)
									  ->execute(Input::get('id'));

			$ids = $objPage->numRows ? array($objPage->pid) : array();

			// Set permission
			switch (Input::get('act'))
			{
				case 'edit':
				case 'toggle':
					$permission = ContaoCorePermissions::USER_CAN_EDIT_ARTICLES;
					break;

				// Do not insert articles into a website root page
				case 'create':
				case 'copy':
				case 'copyAll':
				case 'cut':
				case 'cutAll':
					$permission = ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY;

					// Insert into a page
					if (Input::get('mode') == 2)
					{
						$objParent = $this->Database->prepare("SELECT id, type FROM tl_page WHERE id=?")
													->limit(1)
													->execute(Input::get('pid'));

						$ids[] = Input::get('pid');
					}

					// Insert after an article
					else
					{
						$objParent = $this->Database->prepare("SELECT id, type FROM tl_page WHERE id=(SELECT pid FROM tl_article WHERE id=?)")
													->limit(1)
													->execute(Input::get('pid'));

						$ids[] = $objParent->id;
					}

					if ($objParent->numRows && $objParent->type == 'root')
					{
						throw new AccessDeniedException('Attempt to insert an article into website root page ID ' . Input::get('pid') . '.');
					}
					break;

				case 'delete':
					$permission = ContaoCorePermissions::USER_CAN_DELETE_ARTICLES;
					break;
			}

			// Check user permissions
			$pagemounts = array();

			// Get all allowed pages for the current user
			foreach ($this->User->pagemounts as $root)
			{
				$pagemounts[] = array($root);
				$pagemounts[] = $this->Database->getChildRecords($root, 'tl_page');
			}

			if (!empty($pagemounts))
			{
				$pagemounts = array_merge(...$pagemounts);
			}

			$pagemounts = array_unique($pagemounts);

			// Check each page
			foreach ($ids as $id)
			{
				if (!in_array($id, $pagemounts))
				{
					throw new AccessDeniedException('Page ID ' . $id . ' is not mounted.');
				}

				if (Input::get('act') == 'show')
				{
					continue;
				}

				// Check whether the current user has permission for the current page
				if ($permission === null || !$security->isGranted($permission, $id))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' ' . (Input::get('id') ? 'article ID ' . Input::get('id') : ' articles') . ' on page ID ' . $id . ' or to paste it/them into page ID ' . $id . '.');
				}
			}
		}
	}

	/**
	 * Add an image to each page in the tree
	 *
	 * @param array  $row
	 * @param string $label
	 *
	 * @return string
	 */
	public function addIcon($row, $label)
	{
		$sub = 0;
		$unpublished = ($row['start'] && $row['start'] > time()) || ($row['stop'] && $row['stop'] <= time());

		if ($unpublished || !$row['published'])
		{
			++$sub;
		}

		if ($row['protected'])
		{
			$sub += 2;
		}

		$image = 'articles.svg';

		if ($sub > 0)
		{
			$image = 'articles_' . $sub . '.svg';
		}

		$attributes = sprintf(
			'data-icon="%s" data-icon-disabled="%s"',
			$row['protected'] ? 'articles_2.svg' : 'articles.svg',
			$row['protected'] ? 'articles_3.svg' : 'articles_1.svg',
		);

		$href = System::getContainer()->get('router')->generate('contao_backend_preview', array('page'=>$row['pid'], 'article'=>($row['alias'] ?: $row['id'])));

		return '<a href="' . StringUtil::specialcharsUrl($href) . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['view']) . '" target="_blank">' . Image::getHtml($image, '', $attributes) . '</a> ' . $label;
	}

	/**
	 * Auto-generate an article alias if it has not been set yet
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function generateAlias($varValue, DataContainer $dc)
	{
		$aliasExists = function (string $alias) use ($dc): bool {
			if (in_array($alias, array('top', 'wrapper', 'header', 'container', 'main', 'left', 'right', 'footer'), true))
			{
				return true;
			}

			return $this->Database->prepare("SELECT id FROM tl_article WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
		};

		// Generate an alias if there is none
		if (!$varValue)
		{
			$varValue = System::getContainer()->get('contao.slug')->generate((string) $dc->activeRecord->title, (int) $dc->activeRecord->pid, $aliasExists);
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
	 * Return all active layout sections as array
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getActiveLayoutSections(DataContainer $dc)
	{
		// Show only active sections
		if ($dc->activeRecord->pid ?? null)
		{
			$arrSections = array();
			$objPage = PageModel::findWithDetails($dc->activeRecord->pid);

			// Get the layout sections
			if ($objPage->layout)
			{
				$objLayout = LayoutModel::findByPk($objPage->layout);

				if ($objLayout === null)
				{
					return array();
				}

				$arrModules = StringUtil::deserialize($objLayout->modules);

				if (empty($arrModules) || !is_array($arrModules))
				{
					return array();
				}

				// Find all sections with an article module (see #6094)
				foreach ($arrModules as $arrModule)
				{
					if ($arrModule['mod'] == 0 && $arrModule['enable'])
					{
						$arrSections[] = $arrModule['col'];
					}
				}
			}
		}

		// Show all sections (e.g. "override all" mode)
		else
		{
			$arrSections = array('header', 'left', 'right', 'main', 'footer');
			$objLayout = $this->Database->query("SELECT sections FROM tl_layout WHERE sections!=''");

			while ($objLayout->next())
			{
				$arrCustom = StringUtil::deserialize($objLayout->sections);

				// Add the custom layout sections
				if (!empty($arrCustom) && is_array($arrCustom))
				{
					foreach ($arrCustom as $v)
					{
						if (!empty($v['id']))
						{
							$arrSections[] = $v['id'];
						}
					}
				}
			}
		}

		return Backend::convertLayoutSectionIdsToAssociativeArray($arrSections);
	}

	/**
	 * Return the edit article button
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
	public function editArticle($row, $href, $label, $title, $icon, $attributes)
	{
		$objPage = PageModel::findById($row['pid']);

		return System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, $objPage->row()) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the edit header button
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
	public function editHeader($row, $href, $label, $title, $icon, $attributes)
	{
		$security = System::getContainer()->get('security.helper');

		if (!$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, 'tl_article'))
		{
			return Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
		}

		$objPage = PageModel::findById($row['pid']);

		return $security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, $objPage->row()) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the copy article button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 * @param string $table
	 *
	 * @return string
	 */
	public function copyArticle($row, $href, $label, $title, $icon, $attributes, $table)
	{
		if ($GLOBALS['TL_DCA'][$table]['config']['closed'] ?? null)
		{
			return '';
		}

		$objPage = PageModel::findById($row['pid']);

		return System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, $objPage->row()) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the cut article button
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
	public function cutArticle($row, $href, $label, $title, $icon, $attributes)
	{
		$objPage = PageModel::findById($row['pid']);

		return System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, $objPage->row()) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the delete article button
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
	public function deleteArticle($row, $href, $label, $title, $icon, $attributes)
	{
		$objPage = PageModel::findById($row['pid']);

		return System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_DELETE_ARTICLES, $objPage->row()) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Automatically generate the folder URL aliases
	 *
	 * @param array $arrButtons
	 *
	 * @return array
	 */
	public function addAliasButton($arrButtons)
	{
		if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_article::alias'))
		{
			return $arrButtons;
		}

		// Generate the aliases
		if (Input::post('alias') !== null && Input::post('FORM_SUBMIT') == 'tl_select')
		{
			$objSession = System::getContainer()->get('request_stack')->getSession();
			$session = $objSession->all();
			$ids = $session['CURRENT']['IDS'] ?? array();

			foreach ($ids as $id)
			{
				$objArticle = ArticleModel::findByPk($id);

				if ($objArticle === null)
				{
					continue;
				}

				$strAlias = System::getContainer()->get('contao.slug')->generate($objArticle->title, $objArticle->pid);

				// The alias has not changed
				if ($strAlias == $objArticle->alias)
				{
					continue;
				}

				// Initialize the version manager
				$objVersions = new Versions('tl_article', $id);
				$objVersions->initialize();

				// Store the new alias
				$this->Database->prepare("UPDATE tl_article SET alias=? WHERE id=?")
							   ->execute($strAlias, $id);

				// Create a new version
				$objVersions->create();
			}

			$this->redirect($this->getReferer());
		}

		// Add the button
		$arrButtons['alias'] = '<button type="submit" name="alias" id="alias" class="tl_submit" accesskey="a">' . $GLOBALS['TL_LANG']['MSC']['aliasSelected'] . '</button> ';

		return $arrButtons;
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
		if (!$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_article::published'))
		{
			return '';
		}

		$href .= '&amp;id=' . $row['id'];

		if (!$row['published'])
		{
			$icon = 'invisible.svg';
		}

		$objPage = PageModel::findById($row['pid']);

		if (!$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, $objPage->row()))
		{
			if ($row['published'])
			{
				$icon = preg_replace('/\.svg$/i', '_.svg', $icon); // see #8126
			}

			return Image::getHtml($icon) . ' ';
		}

		return '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.getScrollOffset();return AjaxRequest.toggleField(this,true)">' . Image::getHtml($icon, $label, 'data-icon="visible.svg" data-icon-disabled="invisible.svg" data-state="' . ($row['published'] ? 1 : 0) . '"') . '</a> ';
	}
}
