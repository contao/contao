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
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use Contao\MemberGroupModel;
use Contao\Message;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_content'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'enableVersioning'            => true,
		'dynamicPtable'               => true,
		'markAsCopy'                  => 'headline',
		'onload_callback'             => array
		(
			array('tl_content', 'adjustDcaByType'),
			array('tl_content', 'showJsLibraryHint'),
			array('tl_content', 'filterContentElements'),
			array('tl_content', 'preserveReferenced')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid,ptable,invisible,start,stop' => 'index',
				'type' => 'index',
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
			'headerFields'            => array('title', 'headline', 'author', 'tstamp', 'start', 'stop'),
			'child_record_callback'   => array('tl_content', 'addCteType')
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
				'button_callback'     => array('tl_content', 'disableButton')
			),
			'copy' => array
			(
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"',
				'button_callback'     => array('tl_content', 'disableButton')
			),
			'cut' => array
			(
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
				'button_callback'     => array('tl_content', 'deleteElement')
			),
			'toggle' => array
			(
				'href'                => 'act=toggle&amp;field=invisible',
				'icon'                => 'visible.svg',
				'button_callback'     => array('tl_content', 'toggleIcon')
			),
			'show' => array
			(
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		),
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('type', 'addImage', 'sortable', 'useImage', 'overwriteMeta', 'overwriteLink', 'protected', 'splashImage', 'markdownSource', 'showPreview'),
		'default'                     => '{type_legend},type',
		'headline'                    => '{type_legend},type,headline;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'text'                        => '{type_legend},type,headline;{text_legend},text;{image_legend},addImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'html'                        => '{type_legend},type;{text_legend},html;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests;{invisible_legend:hide},invisible,start,stop',
		'list'                        => '{type_legend},type,headline;{list_legend},listtype,listitems;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'table'                       => '{type_legend},type,headline;{table_legend},tableitems;{tconfig_legend},summary,thead,tfoot,tleft;{sortable_legend:hide},sortable;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'accordionStart'              => '{type_legend},type;{moo_legend},mooHeadline,mooStyle,mooClasses;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'accordionStop'               => '{type_legend},type;{moo_legend},mooClasses;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests;{invisible_legend:hide},invisible,start,stop',
		'accordionSingle'             => '{type_legend},type;{moo_legend},mooHeadline,mooStyle,mooClasses;{text_legend},text;{image_legend},addImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'sliderStart'                 => '{type_legend},type,headline;{slider_legend},sliderDelay,sliderSpeed,sliderStartSlide,sliderContinuous;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'sliderStop'                  => '{type_legend},type;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests;{invisible_legend:hide},invisible,start,stop',
		'code'                        => '{type_legend},type,headline;{text_legend},highlight,code;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'markdown'                    => '{type_legend},type,headline;{text_legend},markdownSource;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'template'                    => '{type_legend},type,headline;{template_legend},data,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'hyperlink'                   => '{type_legend},type,headline;{link_legend},url,target,linkTitle,embed,titleText,rel;{imglink_legend:hide},useImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'toplink'                     => '{type_legend},type;{link_legend},linkTitle;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'image'                       => '{type_legend},type,headline;{source_legend},singleSRC,size,imagemargin,fullsize,overwriteMeta;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'gallery'                     => '{type_legend},type,headline;{source_legend},multiSRC,sortBy,metaIgnore;{image_legend},size,imagemargin,perRow,fullsize,perPage,numberOfItems;{template_legend:hide},galleryTpl,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,useHomeDir;{invisible_legend:hide},invisible,start,stop',
		'player'                      => '{type_legend},type,headline;{source_legend},playerSRC;{player_legend},playerSize,playerOptions,playerStart,playerStop,playerCaption,playerPreload;{poster_legend:hide},posterSRC;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'youtube'                     => '{type_legend},type,headline;{source_legend},youtube;{player_legend},playerSize,youtubeOptions,playerStart,playerStop,playerCaption,playerAspect;{splash_legend},splashImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'vimeo'                       => '{type_legend},type,headline;{source_legend},vimeo;{player_legend},playerSize,vimeoOptions,playerStart,playerColor,playerCaption,playerAspect;{splash_legend},splashImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'download'                    => '{type_legend},type,headline;{source_legend},singleSRC;{download_legend},inline,overwriteLink;{preview_legend},showPreview;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'downloads'                   => '{type_legend},type,headline;{source_legend},multiSRC;{download_legend},inline,sortBy,metaIgnore;{preview_legend},showPreview;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,useHomeDir;{invisible_legend:hide},invisible,start,stop',
		'alias'                       => '{type_legend},type;{include_legend},cteAlias;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'article'                     => '{type_legend},type;{include_legend},articleAlias;{protected_legend:hide},protected;{invisible_legend:hide},invisible,start,stop',
		'teaser'                      => '{type_legend},type;{include_legend},article;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'form'                        => '{type_legend},type,headline;{include_legend},form;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop',
		'module'                      => '{type_legend},type;{include_legend},module;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'addImage'                    => 'singleSRC,size,floating,imagemargin,fullsize,overwriteMeta',
		'sortable'                    => 'sortIndex,sortOrder',
		'useImage'                    => 'singleSRC,size,overwriteMeta',
		'overwriteMeta'               => 'alt,imageTitle,imageUrl,caption',
		'overwriteLink'               => 'linkTitle,titleText',
		'protected'                   => 'groups',
		'splashImage'                 => 'singleSRC,size',
		'markdownSource_sourceText'   => 'code',
		'markdownSource_sourceFile'   => 'singleSRC',
		'showPreview'                 => 'size,fullsize,numberOfItems',
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment",
			'search'                  => true
		),
		'pid' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'ptable' => array
		(
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
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
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_content', 'getContentElements'),
			'reference'               => &$GLOBALS['TL_LANG']['CTE'],
			'eval'                    => array('helpwizard'=>true, 'chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50'),
			'sql'                     => array('name'=>'type', 'type'=>'string', 'length'=>64, 'default'=>'text', 'customSchemaOptions'=>array('collation'=>'ascii_bin'))
		),
		'headline' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'inputUnit',
			'options'                 => array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'),
			'eval'                    => array('maxlength'=>200, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(255) NOT NULL default 'a:2:{s:5:\"value\";s:0:\"\";s:4:\"unit\";s:2:\"h2\";}'"
		),
		'text' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>true, 'rte'=>'tinyMCE', 'helpwizard'=>true),
			'explanation'             => 'insertTags',
			'sql'                     => "mediumtext NULL"
		),
		'addImage' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'showPreview' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true, 'tl_class' => 'clr'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'inline' => array(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'overwriteMeta' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'singleSRC' => array
		(
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio', 'mandatory'=>true, 'tl_class'=>'clr'),
			'load_callback' => array
			(
				array('tl_content', 'setSingleSrcFlags')
			),
			'sql'                     => "binary(16) NULL"
		),
		'alt' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'imageTitle' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'size' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
			'exclude'                 => true,
			'inputType'               => 'imageSize',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) COLLATE ascii_bin NOT NULL default ''"
		),
		'imagemargin' => array
		(
			'exclude'                 => true,
			'inputType'               => 'trbl',
			'options'                 => array('px', '%', 'em', 'rem'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) COLLATE ascii_bin NOT NULL default ''"
		),
		'imageUrl' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => "text NULL"
		),
		'fullsize' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'caption' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'allowHtml'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'floating' => array
		(
			'exclude'                 => true,
			'inputType'               => 'radioTable',
			'options'                 => array('above', 'left', 'right', 'below'),
			'eval'                    => array('cols'=>4, 'tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default 'above'"
		),
		'html' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('allowHtml'=>true, 'class'=>'monospace', 'rte'=>'ace|html', 'helpwizard'=>true),
			'explanation'             => 'insertTags',
			'sql'                     => "mediumtext NULL"
		),
		'listtype' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('ordered', 'unordered'),
			'eval'                    => array('tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content'],
			'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default ''"
		),
		'listitems' => array
		(
			'exclude'                 => true,
			'inputType'               => 'listWizard',
			'eval'                    => array('multiple'=>true, 'allowHtml'=>true, 'tl_class'=>'clr'),
			'xlabel' => array
			(
				array('tl_content', 'listImportWizard')
			),
			'sql'                     => "blob NULL"
		),
		'tableitems' => array
		(
			'exclude'                 => true,
			'inputType'               => 'tableWizard',
			'eval'                    => array('multiple'=>true, 'allowHtml'=>true, 'doNotSaveEmpty'=>true, 'style'=>'width:142px;height:66px'),
			'xlabel' => array
			(
				array('tl_content', 'tableImportWizard')
			),
			'sql'                     => "mediumblob NULL"
		),
		'summary' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'thead' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'tfoot' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'tleft' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'sortable' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'sortIndex' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'sortOrder' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('ascending', 'descending'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default 'ascending'"
		),
		'mooHeadline' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'allowHtml'=>true, 'tl_class'=>'long'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'mooStyle' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'mooClasses' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'rgxp'=>'alnum', 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'highlight' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('Apache', 'Bash', 'C#', 'C++', 'CSS', 'Diff', 'HTML', 'HTTP', 'Ini', 'JSON', 'Java', 'JavaScript', 'Markdown', 'Nginx', 'Perl', 'PHP', 'PowerShell', 'Python', 'Ruby', 'SCSS', 'SQL', 'YAML', 'XML'),
			'eval'                    => array('includeBlankOption'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default ''"
		),
		'markdownSource' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('sourceText', 'sourceFile'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content']['markdownSource'],
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(12) COLLATE ascii_bin NOT NULL default 'sourceText'"
		),
		'code' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>true, 'preserveTags'=>true, 'decodeEntities'=>true, 'class'=>'monospace', 'rte'=>'ace', 'helpwizard'=>true, 'tl_class'=>'clr'),
			'explanation'             => 'insertTags',
			'load_callback' => array
			(
				array('tl_content', 'setRteSyntax')
			),
			'sql'                     => "text NULL"
		),
		'url' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['url'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => "text NULL"
		),
		'target' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['target'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'overwriteLink' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['overwriteMeta'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'titleText' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'linkTitle' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'embed' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'rel' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>64, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'useImage' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'multiSRC' => array
		(
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'orderField'=>'orderSRC', 'files'=>true),
			'sql'                     => "blob NULL",
			'load_callback' => array
			(
				array('tl_content', 'setMultiSrcFlags')
			)
		),
		'orderSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['sortOrder'],
			'sql'                     => "blob NULL"
		),
		'useHomeDir' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'perRow' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12),
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 4"
		),
		'perPage' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'numberOfItems' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['numberOfItems'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'sortBy' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('custom', 'name_asc', 'name_desc', 'date_asc', 'date_desc', 'random'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content'],
			'eval'                    => array('tl_class'=>'w50 clr'),
			'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default ''"
		),
		'metaIgnore' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'galleryTpl' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback' => static function ()
			{
				return Controller::getTemplateGroup('gallery_');
			},
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
		),
		'customTpl' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'eval'                    => array('chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
		),
		'playerSRC' => array
		(
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'files'=>true, 'mandatory'=>true),
			'sql'                     => "blob NULL"
		),
		'youtube' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_content', 'extractYouTubeId')
			),
			'sql'                     => "varchar(16) COLLATE ascii_bin NOT NULL default ''"
		),
		'vimeo' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_content', 'extractVimeoId')
			),
			'sql'                     => "varchar(16) COLLATE ascii_bin NOT NULL default ''"
		),
		'posterSRC' => array
		(
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio'),
			'sql'                     => "binary(16) NULL"
		),
		'playerSize' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
		),
		'playerOptions' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'options'                 => array('player_autoplay', 'player_nocontrols', 'player_loop', 'player_playsinline', 'player_muted'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content'],
			'eval'                    => array('multiple'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'playerStart' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'playerStop' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'playerCaption' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'playerAspect' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('16:9', '16:10', '21:9', '4:3', '3:2'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content']['player_aspect'],
			'eval'                    => array('includeBlankOption' => true, 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(8) COLLATE ascii_bin NOT NULL default ''"
		),
		'splashImage' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'playerPreload' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('auto', 'metadata', 'none'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content']['player_preload'],
			'eval'                    => array('includeBlankOption' => true, 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(8) COLLATE ascii_bin NOT NULL default ''"
		),
		'playerColor' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>6, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(6) COLLATE ascii_bin NOT NULL default ''"
		),
		'youtubeOptions' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['playerOptions'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'options'                 => array('youtube_autoplay', 'youtube_controls', 'youtube_cc_load_policy', 'youtube_fs', 'youtube_hl', 'youtube_iv_load_policy', 'youtube_modestbranding', 'youtube_rel', 'youtube_nocookie', 'youtube_loop'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content'],
			'eval'                    => array('multiple'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'vimeoOptions' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['playerOptions'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'options'                 => array('vimeo_autoplay', 'vimeo_loop', 'vimeo_portrait', 'vimeo_title', 'vimeo_byline', 'vimeo_dnt'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content'],
			'eval'                    => array('multiple'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'sliderDelay' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'sliderSpeed' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 300"
		),
		'sliderStartSlide' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'sliderContinuous' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'data' => array
		(
			'exclude'                 => true,
			'inputType'               => 'keyValueWizard',
			'sql'                     => "text NULL"
		),
		'cteAlias' => array
		(
			'exclude'                 => true,
			'inputType'               => 'picker',
			'eval'                    => array('mandatory'=>true, 'tl_class'=>'clr'),
			'save_callback' => array
			(
				array('tl_content', 'saveAlias'),
			),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy', 'table'=>'tl_content')
		),
		'articleAlias' => array
		(
			'exclude'                 => true,
			'inputType'               => 'picker',
			'foreignKey'              => 'tl_article.title',
			'eval'                    => array('mandatory'=>true, 'tl_class'=>'clr'),
			'save_callback' => array
			(
				array('tl_content', 'saveArticleAlias'),
			),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'article' => array
		(
			'exclude'                 => true,
			'inputType'               => 'picker',
			'foreignKey'              => 'tl_article.title',
			'eval'                    => array('mandatory'=>true, 'tl_class'=>'clr'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'form' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_content', 'getForms'),
			'eval'                    => array('mandatory'=>true, 'chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50 wizard'),
			'wizard' => array
			(
				array('tl_content', 'editForm')
			),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'module' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_content', 'getModules'),
			'eval'                    => array('mandatory'=>true, 'chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50 wizard'),
			'wizard' => array
			(
				array('tl_content', 'editModule')
			),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'protected' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'groups' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
		'guests' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'cssID' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'invisible' => array
		(
			'exclude'                 => true,
			'toggle'                  => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'start' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) COLLATE ascii_bin NOT NULL default ''"
		),
		'stop' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) COLLATE ascii_bin NOT NULL default ''"
		)
	)
);

// Dynamically add the permission check
if (Input::get('do') == 'article')
{
	array_unshift($GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'], array('tl_content', 'checkPermission'));
}

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 */
class tl_content extends Backend
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
	 * Check permissions to edit table tl_content
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		// Get the pagemounts
		$pagemounts = array();

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

		// Check the current action
		switch (Input::get('act'))
		{
			case '': // empty
			case 'paste':
			case 'create':
			case 'select':
				// Check access to the article
				$this->checkAccessToElement(CURRENT_ID, $pagemounts, true);
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'cutAll':
			case 'copyAll':
				// Check access to the parent element if a content element is moved
				if (in_array(Input::get('act'), array('cutAll', 'copyAll')))
				{
					$this->checkAccessToElement(Input::get('pid'), $pagemounts, (Input::get('mode') == 2));
				}

				$objCes = $this->Database->prepare("SELECT id FROM tl_content WHERE (ptable='tl_article' OR ptable='') AND pid=?")
										 ->execute(CURRENT_ID);

				$objSession = System::getContainer()->get('session');

				$session = $objSession->all();
				$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objCes->fetchEach('id'));
				$objSession->replace($session);
				break;

			case 'cut':
			case 'copy':
				// Check access to the parent element if a content element is moved
				$this->checkAccessToElement(Input::get('pid'), $pagemounts, (Input::get('mode') == 2));
				// no break

			default:
				// Check access to the content element
				$this->checkAccessToElement(Input::get('id'), $pagemounts);
				break;
		}
	}

	/**
	 * Check access to a particular content element
	 *
	 * @param integer $id
	 * @param array   $pagemounts
	 * @param boolean $blnIsPid
	 *
	 * @throws AccessDeniedException
	 */
	protected function checkAccessToElement($id, $pagemounts, $blnIsPid=false)
	{
		if ($blnIsPid)
		{
			$objPage = $this->Database->prepare("SELECT p.id, p.pid, p.includeChmod, p.chmod, p.cuser, p.cgroup, a.id AS aid FROM tl_article a, tl_page p WHERE a.id=? AND a.pid=p.id")
									  ->limit(1)
									  ->execute($id);
		}
		else
		{
			$objPage = $this->Database->prepare("SELECT p.id, p.pid, p.includeChmod, p.chmod, p.cuser, p.cgroup, a.id AS aid FROM tl_content c, tl_article a, tl_page p WHERE c.id=? AND c.pid=a.id AND a.pid=p.id")
									  ->limit(1)
									  ->execute($id);
		}

		// Invalid ID
		if ($objPage->numRows < 1)
		{
			throw new AccessDeniedException('Invalid content element ID ' . $id . '.');
		}

		// The page is not mounted
		if (!in_array($objPage->id, $pagemounts))
		{
			throw new AccessDeniedException('Not enough permissions to modify article ID ' . $objPage->aid . ' on page ID ' . $objPage->id . '.');
		}

		// Not enough permissions to modify the article
		if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, $objPage->row()))
		{
			throw new AccessDeniedException('Not enough permissions to modify article ID ' . $objPage->aid . '.');
		}
	}

	/**
	 * Return all content elements as array
	 *
	 * @return array
	 */
	public function getContentElements()
	{
		$security = System::getContainer()->get('security.helper');
		$groups = array();

		foreach ($GLOBALS['TL_CTE'] as $k=>$v)
		{
			foreach (array_keys($v) as $kk)
			{
				if ($security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_ELEMENT_TYPE, $kk))
				{
					$groups[$k][] = $kk;
				}
			}
		}

		return $groups;
	}

	/**
	 * Return the group of a content element
	 *
	 * @param string $element
	 *
	 * @return string
	 */
	public function getContentElementGroup($element)
	{
		foreach ($GLOBALS['TL_CTE'] as $k=>$v)
		{
			if (array_key_exists($element, $v))
			{
				return $k;
			}
		}

		return null;
	}

	/**
	 * Adjust the DCA by type
	 *
	 * @param object $dc
	 */
	public function adjustDcaByType($dc)
	{
		$objCte = ContentModel::findByPk($dc->id);

		if ($objCte === null)
		{
			return;
		}

		switch ($objCte->type)
		{
			case 'hyperlink':
				unset($GLOBALS['TL_DCA']['tl_content']['fields']['imageUrl']);
				break;

			case 'image':
				$GLOBALS['TL_DCA']['tl_content']['fields']['imagemargin']['eval']['tl_class'] .= ' clr';
				break;

			case 'download':
			case 'downloads':
				$GLOBALS['TL_DCA']['tl_content']['fields']['size']['eval']['mandatory'] = true;
				break;
		}
	}

	/**
	 * Prevent deleting referenced elements (see #4898)
	 */
	public function preserveReferenced()
	{
		if (Input::get('act') == 'delete')
		{
			$objCes = $this->Database->prepare("SELECT COUNT(*) AS cnt FROM tl_content WHERE type='alias' AND cteAlias=? AND (ptable='tl_article' OR ptable='')")
									 ->execute(Input::get('id'));

			if ($objCes->cnt > 0)
			{
				throw new InternalServerErrorException('Content element ID ' . Input::get('id') . ' is used in an alias element and can therefore not be deleted.');
			}
		}

		if (Input::get('act') == 'deleteAll')
		{
			$objCes = $this->Database->prepare("SELECT cteAlias FROM tl_content WHERE type='alias' AND (ptable='tl_article' OR ptable='')")
									 ->execute();

			$objSession = System::getContainer()->get('session');
			$session = $objSession->all();
			$session['CURRENT']['IDS'] = array_diff($session['CURRENT']['IDS'], $objCes->fetchEach('cteAlias'));
			$objSession->replace($session);
		}
	}

	/**
	 * Filter the content elements
	 */
	public function filterContentElements()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		if (empty($this->User->elements))
		{
			$GLOBALS['TL_DCA']['tl_content']['config']['closed'] = true;
			$GLOBALS['TL_DCA']['tl_content']['config']['notEditable'] = true;
		}
		elseif (!in_array($GLOBALS['TL_DCA']['tl_content']['fields']['type']['sql']['default'] ?? null, $this->User->elements))
		{
			$GLOBALS['TL_DCA']['tl_content']['fields']['type']['default'] = $this->User->elements[0];
		}

		$objSession = System::getContainer()->get('session');

		// Prevent editing content elements with not allowed types
		if (Input::get('act') == 'edit' || Input::get('act') == 'toggle' || Input::get('act') == 'delete' || (Input::get('act') == 'paste' && Input::get('mode') == 'copy'))
		{
			$objCes = $this->Database->prepare("SELECT type FROM tl_content WHERE id=?")
									 ->execute(Input::get('id'));

			if ($objCes->numRows && !in_array($objCes->type, $this->User->elements))
			{
				throw new AccessDeniedException('Not enough permissions to modify content elements of type "' . $objCes->type . '".');
			}
		}

		// Prevent editing content elements with not allowed types
		if (Input::get('act') == 'editAll' || Input::get('act') == 'overrideAll' || Input::get('act') == 'deleteAll')
		{
			$session = $objSession->all();

			if (!empty($session['CURRENT']['IDS']) && is_array($session['CURRENT']['IDS']))
			{
				if (empty($this->User->elements))
				{
					$session['CURRENT']['IDS'] = array();
				}
				else
				{
					$objCes = $this->Database->prepare("SELECT id FROM tl_content WHERE id IN(" . implode(',', array_map('\intval', $session['CURRENT']['IDS'])) . ") AND type IN(" . implode(',', array_fill(0, count($this->User->elements), '?')) . ")")
											 ->execute(...$this->User->elements);

					$session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $objCes->fetchEach('id'));
				}

				$objSession->replace($session);
			}
		}

		// Prevent copying content elements with not allowed types
		if (Input::get('act') == 'copyAll')
		{
			$session = $objSession->all();

			if (!empty($session['CLIPBOARD']['tl_content']['id']) && is_array($session['CLIPBOARD']['tl_content']['id']))
			{
				if (empty($this->User->elements))
				{
					$session['CLIPBOARD']['tl_content']['id'] = array();
				}
				else
				{
					$objCes = $this->Database->prepare("SELECT id, type FROM tl_content WHERE id IN(" . implode(',', array_map('\intval', $session['CLIPBOARD']['tl_content']['id'])) . ") AND type IN(" . implode(',', array_fill(0, count($this->User->elements), '?')) . ")")
											 ->execute(...$this->User->elements);

					$session['CLIPBOARD']['tl_content']['id'] = array_intersect($session['CLIPBOARD']['tl_content']['id'], $objCes->fetchEach('id'));
				}

				$objSession->replace($session);
			}
		}
	}

	/**
	 * Show a hint if a JavaScript library needs to be included in the page layout
	 *
	 * @param object $dc
	 */
	public function showJsLibraryHint($dc)
	{
		if ($_POST || Input::get('act') != 'edit')
		{
			return;
		}

		$security = System::getContainer()->get('security.helper');

		// Return if the user cannot access the layout module (see #6190)
		if (!$security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'themes') || !$security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_LAYOUTS))
		{
			return;
		}

		$objCte = ContentModel::findByPk($dc->id);

		if ($objCte === null)
		{
			return;
		}

		switch ($objCte->type)
		{
			case 'gallery':
				Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_content']['includeTemplates'], 'moo_mediabox', 'j_colorbox'));
				break;

			case 'sliderStart':
			case 'sliderStop':
				Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_content']['includeTemplate'], 'js_slider'));
				break;

			case 'accordionSingle':
			case 'accordionStart':
			case 'accordionStop':
				Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_content']['includeTemplates'], 'moo_accordion', 'j_accordion'));
				break;

			case 'table':
				if ($objCte->sortable)
				{
					Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_content']['includeTemplates'], 'moo_tablesort', 'j_tablesort'));
				}
				break;
		}
	}

	/**
	 * Add the type of content element
	 *
	 * @param array $arrRow
	 *
	 * @return string
	 */
	public function addCteType($arrRow)
	{
		$key = $arrRow['invisible'] ? 'unpublished' : 'published';
		$type = $GLOBALS['TL_LANG']['CTE'][$arrRow['type']][0] ?? $arrRow['type'];
		$class = 'limit_height';

		// Remove the class if it is a wrapper element
		if (in_array($arrRow['type'], $GLOBALS['TL_WRAPPERS']['start']) || in_array($arrRow['type'], $GLOBALS['TL_WRAPPERS']['separator']) || in_array($arrRow['type'], $GLOBALS['TL_WRAPPERS']['stop']))
		{
			$class = '';

			if (($group = $this->getContentElementGroup($arrRow['type'])) !== null)
			{
				$type = ($GLOBALS['TL_LANG']['CTE'][$group] ?? $group) . ' (' . $type . ')';
			}
		}

		// Add the group name if it is a single element (see #5814)
		elseif (in_array($arrRow['type'], $GLOBALS['TL_WRAPPERS']['single']))
		{
			if (($group = $this->getContentElementGroup($arrRow['type'])) !== null)
			{
				$type = ($GLOBALS['TL_LANG']['CTE'][$group] ?? $group) . ' (' . $type . ')';
			}
		}

		// Add the ID of the aliased element
		if ($arrRow['type'] == 'alias')
		{
			$type .= ' ID ' . $arrRow['cteAlias'];
		}

		// Add the protection status
		if ($arrRow['protected'] ?? null)
		{
			$groupIds = StringUtil::deserialize($arrRow['groups'], true);
			$groupNames = array();

			if (!empty($groupIds))
			{
				if (in_array(-1, array_map('intval', $groupIds), true))
				{
					$groupNames[] = $GLOBALS['TL_LANG']['MSC']['guests'];
				}

				if (null !== ($groups = MemberGroupModel::findMultipleByIds($groupIds)))
				{
					$groupNames += $groups->fetchEach('name');
				}
			}

			$type .= ' (' . $GLOBALS['TL_LANG']['MSC']['protected'] . ($groupNames ? ': ' . implode(', ', $groupNames) : '') . ')';
		}

		// Add the headline level (see #5858)
		if ($arrRow['type'] == 'headline' && is_array($headline = StringUtil::deserialize($arrRow['headline'])))
		{
			$type .= ' (' . $headline['unit'] . ')';
		}

		// Limit the element's height
		if (!Config::get('doNotCollapse'))
		{
			$class .= ' h40';
		}

		$objModel = new ContentModel();
		$objModel->setRow($arrRow);

		return '
<div class="cte_type ' . $key . '">' . $type . '</div>
<div class="' . trim($class) . '">
' . StringUtil::insertTagToSrc($this->getContentElement($objModel)) . '
</div>' . "\n";
	}

	/**
	 * Return the edit article alias wizard
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 *
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0
	 */
	public function editArticleAlias(DataContainer $dc)
	{
		trigger_deprecation('contao/core-bundle', '4.9', 'Using "tl_content::editArticleAlias()" has been deprecated and will no longer work in Contao 5.0.');

		if ($dc->value < 1)
		{
			return '';
		}

		$title = sprintf($GLOBALS['TL_LANG']['tl_content']['editalias'], $dc->value);
		$href = System::getContainer()->get('router')->generate('contao_backend', array('do'=>'article', 'table'=>'tl_content', 'id'=>$dc->value, 'popup'=>'1', 'nb'=>'1', 'rt'=>REQUEST_TOKEN));

		return ' <a href="' . StringUtil::specialcharsUrl($href) . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", $title)) . '\',\'url\':this.href});return false">' . Image::getHtml('alias.svg', $title) . '</a>';
	}

	/**
	 * Get all articles and return them as array (article alias)
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 *
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0
	 */
	public function getArticleAlias(DataContainer $dc)
	{
		trigger_deprecation('contao/core-bundle', '4.9', 'Using "tl_content::getArticleAlias()" has been deprecated and will no longer work in Contao 5.0.');

		$arrPids = array();
		$arrAlias = array();

		if (!$this->User->isAdmin)
		{
			foreach ($this->User->pagemounts as $id)
			{
				$arrPids[] = array($id);
				$arrPids[] = $this->Database->getChildRecords($id, 'tl_page');
			}

			if (!empty($arrPids))
			{
				$arrPids = array_merge(...$arrPids);
			}
			else
			{
				return $arrAlias;
			}

			$objAlias = $this->Database->prepare("SELECT a.id, a.pid, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid WHERE a.pid IN(" . implode(',', array_map('\intval', array_unique($arrPids))) . ") AND a.id!=(SELECT pid FROM tl_content WHERE id=?) ORDER BY parent, a.sorting")
									   ->execute($dc->id);
		}
		else
		{
			$objAlias = $this->Database->prepare("SELECT a.id, a.pid, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid WHERE a.id!=(SELECT pid FROM tl_content WHERE id=?) ORDER BY parent, a.sorting")
									   ->execute($dc->id);
		}

		if ($objAlias->numRows)
		{
			System::loadLanguageFile('tl_article');

			while ($objAlias->next())
			{
				$key = $objAlias->parent . ' (ID ' . $objAlias->pid . ')';
				$arrAlias[$key][$objAlias->id] = $objAlias->title . ' (' . ($GLOBALS['TL_LANG']['COLS'][$objAlias->inColumn] ?: $objAlias->inColumn) . ', ID ' . $objAlias->id . ')';
			}
		}

		return $arrAlias;
	}

	/**
	 * Throw an exception if the current article is selected (circular reference)
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 */
	public function saveArticleAlias($varValue, DataContainer $dc)
	{
		if ($dc->activeRecord && $dc->activeRecord->pid == $varValue)
		{
			throw new RuntimeException($GLOBALS['TL_LANG']['ERR']['circularPicker']);
		}

		return $varValue;
	}

	/**
	 * Return the edit alias wizard
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 *
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0
	 */
	public function editAlias(DataContainer $dc)
	{
		trigger_deprecation('contao/core-bundle', '4.9', 'Using "tl_content::editAlias()" has been deprecated and will no longer work in Contao 5.0.');

		if ($dc->value < 1)
		{
			return '';
		}

		$title = sprintf($GLOBALS['TL_LANG']['tl_content']['editalias'], $dc->value);
		$href = System::getContainer()->get('router')->generate('contao_backend', array('do'=>'article', 'table'=>'tl_content', 'act'=>'edit', 'id'=>$dc->value, 'popup'=>'1', 'nb'=>'1', 'rt'=>REQUEST_TOKEN));

		return ' <a href="' . StringUtil::specialcharsUrl($href) . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", $title)) . '\',\'url\':this.href});return false">' . Image::getHtml('alias.svg', $title) . '</a>';
	}

	/**
	 * Get all content elements and return them as array (content element alias)
	 *
	 * @return array
	 *
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0
	 */
	public function getAlias()
	{
		trigger_deprecation('contao/core-bundle', '4.9', 'Using "tl_content::getAlias()" has been deprecated and will no longer work in Contao 5.0.');

		$arrPids = array();
		$arrAlias = array();

		if (!$this->User->isAdmin)
		{
			foreach ($this->User->pagemounts as $id)
			{
				$arrPids[] = array($id);
				$arrPids[] = $this->Database->getChildRecords($id, 'tl_page');
			}

			if (!empty($arrPids))
			{
				$arrPids = array_merge(...$arrPids);
			}
			else
			{
				return $arrAlias;
			}

			$objAlias = $this->Database->prepare("SELECT c.id, c.pid, c.type, (CASE c.type WHEN 'module' THEN m.name WHEN 'form' THEN f.title WHEN 'table' THEN c.summary ELSE c.headline END) AS headline, c.text, a.title FROM tl_content c LEFT JOIN tl_article a ON a.id=c.pid LEFT JOIN tl_module m ON m.id=c.module LEFT JOIN tl_form f on f.id=c.form WHERE a.pid IN(" . implode(',', array_map('\intval', array_unique($arrPids))) . ") AND (c.ptable='tl_article' OR c.ptable='') AND c.id!=? ORDER BY a.title, c.sorting")
									   ->execute(Input::get('id'));
		}
		else
		{
			$objAlias = $this->Database->prepare("SELECT c.id, c.pid, c.type, (CASE c.type WHEN 'module' THEN m.name WHEN 'form' THEN f.title WHEN 'table' THEN c.summary ELSE c.headline END) AS headline, c.text, a.title FROM tl_content c LEFT JOIN tl_article a ON a.id=c.pid LEFT JOIN tl_module m ON m.id=c.module LEFT JOIN tl_form f on f.id=c.form WHERE (c.ptable='tl_article' OR c.ptable='') AND c.id!=? ORDER BY a.title, c.sorting")
									   ->execute(Input::get('id'));
		}

		while ($objAlias->next())
		{
			$arrHeadline = StringUtil::deserialize($objAlias->headline, true);

			if (isset($arrHeadline['value']))
			{
				$headline = StringUtil::substr($arrHeadline['value'], 32);
			}
			else
			{
				$headline = StringUtil::substr(preg_replace('/[\n\r\t]+/', ' ', $arrHeadline[0]), 32);
			}

			$text = StringUtil::substr(strip_tags(preg_replace('/[\n\r\t]+/', ' ', $objAlias->text)), 32);
			$strText = ($GLOBALS['TL_LANG']['CTE'][$objAlias->type][0] ?? $objAlias->type) . ' (';

			if ($headline)
			{
				$strText .= $headline . ', ';
			}
			elseif ($text)
			{
				$strText .= $text . ', ';
			}

			$key = $objAlias->title . ' (ID ' . $objAlias->pid . ')';
			$arrAlias[$key][$objAlias->id] = $strText . 'ID ' . $objAlias->id . ')';
		}

		return $arrAlias;
	}

	/**
	 * Throw an exception if the current content element is selected (circular reference)
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 */
	public function saveAlias($varValue, DataContainer $dc)
	{
		if ($dc->activeRecord && $dc->activeRecord->id == $varValue)
		{
			throw new RuntimeException($GLOBALS['TL_LANG']['ERR']['circularPicker']);
		}

		return $varValue;
	}

	/**
	 * Return the edit form wizard
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 */
	public function editForm(DataContainer $dc)
	{
		if ($dc->value < 1)
		{
			return '';
		}

		$title = sprintf($GLOBALS['TL_LANG']['tl_content']['editalias'], $dc->value);
		$href = System::getContainer()->get('router')->generate('contao_backend', array('do'=>'form', 'table'=>'tl_form_field', 'id'=>$dc->value, 'popup'=>'1', 'nb'=>'1', 'rt'=>REQUEST_TOKEN));

		return ' <a href="' . StringUtil::specialcharsUrl($href) . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", $title)) . '\',\'url\':this.href});return false">' . Image::getHtml('alias.svg', $title) . '</a>';
	}

	/**
	 * Get all forms and return them as array
	 *
	 * @return array
	 */
	public function getForms()
	{
		if (!$this->User->isAdmin && !is_array($this->User->forms))
		{
			return array();
		}

		$arrForms = array();
		$objForms = $this->Database->execute("SELECT id, title FROM tl_form ORDER BY title");
		$security = System::getContainer()->get('security.helper');

		while ($objForms->next())
		{
			if ($security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_FORM, $objForms->id))
			{
				$arrForms[$objForms->id] = $objForms->title . ' (ID ' . $objForms->id . ')';
			}
		}

		return $arrForms;
	}

	/**
	 * Return the edit module wizard
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 */
	public function editModule(DataContainer $dc)
	{
		if ($dc->value < 1)
		{
			return '';
		}

		$title = sprintf($GLOBALS['TL_LANG']['tl_content']['editalias'], $dc->value);
		$href = System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$dc->value, 'popup'=>'1', 'nb'=>'1', 'rt'=>REQUEST_TOKEN));

		return ' <a href="' . StringUtil::specialcharsUrl($href) . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", $title)) . '\',\'url\':this.href});return false">' . Image::getHtml('alias.svg', $title) . '</a>';
	}

	/**
	 * Get all modules and return them as array
	 *
	 * @return array
	 */
	public function getModules()
	{
		$arrModules = array();
		$objModules = $this->Database->execute("SELECT m.id, m.name, t.name AS theme FROM tl_module m LEFT JOIN tl_theme t ON m.pid=t.id ORDER BY t.name, m.name");

		while ($objModules->next())
		{
			$arrModules[$objModules->theme][$objModules->id] = $objModules->name . ' (ID ' . $objModules->id . ')';
		}

		return $arrModules;
	}

	/**
	 * Return the edit article teaser wizard
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 *
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0
	 */
	public function editArticle(DataContainer $dc)
	{
		trigger_deprecation('contao/core-bundle', '4.9', 'Using "tl_content::editArticle()" has been deprecated and will no longer work in Contao 5.0.');

		if ($dc->value < 1)
		{
			return '';
		}

		$title = sprintf($GLOBALS['TL_LANG']['tl_content']['editarticle'], $dc->value);
		$href = System::getContainer()->get('router')->generate('contao_backend', array('do'=>'article', 'table'=>'tl_content', 'id'=>$dc->value, 'popup'=>'1', 'nb'=>'1', 'rt'=>REQUEST_TOKEN));

		return ' <a href="' . StringUtil::specialcharsUrl($href) . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", $title)) . '\',\'url\':this.href});return false">' . Image::getHtml('alias.svg', $title) . '</a>';
	}

	/**
	 * Get all articles and return them as array (article teaser)
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 *
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0
	 */
	public function getArticles(DataContainer $dc)
	{
		trigger_deprecation('contao/core-bundle', '4.9', 'Using "tl_content::getArticles()" has been deprecated and will no longer work in Contao 5.0.');

		$arrPids = array();
		$arrArticle = array();
		$arrRoot = array();
		$intPid = $dc->activeRecord->pid ?? null;

		if (Input::get('act') == 'overrideAll')
		{
			$intPid = Input::get('id');
		}

		// Limit pages to the website root
		$objArticle = $this->Database->prepare("SELECT pid FROM tl_article WHERE id=?")
									 ->limit(1)
									 ->execute($intPid);

		if ($objArticle->numRows)
		{
			$objPage = PageModel::findWithDetails($objArticle->pid);
			$arrRoot = $this->Database->getChildRecords($objPage->rootId, 'tl_page');
			array_unshift($arrRoot, $objPage->rootId);
		}

		unset($objArticle);

		// Limit pages to the user's pagemounts
		if ($this->User->isAdmin)
		{
			$objArticle = $this->Database->execute("SELECT a.id, a.pid, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid" . (!empty($arrRoot) ? " WHERE a.pid IN(" . implode(',', array_map('\intval', array_unique($arrRoot))) . ")" : "") . " ORDER BY parent, a.sorting");
		}
		else
		{
			foreach ($this->User->pagemounts as $id)
			{
				if (!in_array($id, $arrRoot))
				{
					continue;
				}

				$arrPids[] = array($id);
				$arrPids[] = $this->Database->getChildRecords($id, 'tl_page');
			}

			if (!empty($arrPids))
			{
				$arrPids = array_merge(...$arrPids);
			}
			else
			{
				return $arrArticle;
			}

			$objArticle = $this->Database->execute("SELECT a.id, a.pid, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid WHERE a.pid IN(" . implode(',', array_map('\intval', array_unique($arrPids))) . ") ORDER BY parent, a.sorting");
		}

		// Edit the result
		if ($objArticle->numRows)
		{
			System::loadLanguageFile('tl_article');

			while ($objArticle->next())
			{
				$key = $objArticle->parent . ' (ID ' . $objArticle->pid . ')';
				$arrArticle[$key][$objArticle->id] = $objArticle->title . ' (' . ($GLOBALS['TL_LANG']['COLS'][$objArticle->inColumn] ?: $objArticle->inColumn) . ', ID ' . $objArticle->id . ')';
			}
		}

		return $arrArticle;
	}

	/**
	 * Dynamically set the ace syntax
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return string
	 */
	public function setRteSyntax($varValue, DataContainer $dc)
	{
		switch ($dc->activeRecord->highlight)
		{
			case 'C':
			case 'CSharp':
				$syntax = 'c_cpp';
				break;

			case 'CSS':
			case 'Diff':
			case 'Groovy':
			case 'HTML':
			case 'Java':
			case 'JavaScript':
			case 'Perl':
			case 'PHP':
			case 'PowerShell':
			case 'Python':
			case 'Ruby':
			case 'Scala':
			case 'SQL':
			case 'Text':
			case 'YAML':
				$syntax = strtolower($dc->activeRecord->highlight);
				break;

			case 'VB':
				$syntax = 'vbscript';
				break;

			case 'XML':
			case 'XHTML':
				$syntax = 'xml';
				break;

			default:
				$syntax = 'text';
				break;
		}

		if ($dc->activeRecord->type == 'markdown')
		{
			$syntax = 'markdown';
		}

		$GLOBALS['TL_DCA']['tl_content']['fields']['code']['eval']['rte'] = 'ace|' . $syntax;

		return $varValue;
	}

	/**
	 * Add a link to the list items import wizard
	 *
	 * @return string
	 */
	public function listImportWizard()
	{
		return ' <a href="' . $this->addToUrl('key=list') . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['lw_import'][1]) . '" onclick="Backend.getScrollOffset()">' . Image::getHtml('tablewizard.svg', $GLOBALS['TL_LANG']['MSC']['tw_import'][0]) . '</a>';
	}

	/**
	 * Add a link to the table items import wizard
	 *
	 * @return string
	 */
	public function tableImportWizard()
	{
		return ' <a href="' . $this->addToUrl('key=table') . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['tw_import'][1]) . '" onclick="Backend.getScrollOffset()">' . Image::getHtml('tablewizard.svg', $GLOBALS['TL_LANG']['MSC']['tw_import'][0]) . '</a> ' . Image::getHtml('demagnify.svg', '', 'title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['tw_shrink']) . '" style="cursor:pointer" onclick="Backend.tableWizardResize(0.9)"') . Image::getHtml('magnify.svg', '', 'title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['tw_expand']) . '" style="cursor:pointer" onclick="Backend.tableWizardResize(1.1)"');
	}

	/**
	 * Return the link picker wizard
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 *
	 * @deprecated Deprecated since Contao 4.4, to be removed in Contao 5.
	 *             Set the "dcaPicker" eval attribute instead.
	 */
	public function pagePicker(DataContainer $dc)
	{
		trigger_deprecation('contao/core-bundle', '4.4', 'Using "tl_content::pagePicker()" has been deprecated and will no longer work in Contao 5.0. Set the "dcaPicker" eval attribute instead.');

		return Backend::getDcaPickerWizard(true, $dc->table, $dc->field, $dc->inputName);
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
		return System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_ELEMENT_TYPE, $row['type']) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the delete content element button
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
	public function deleteElement($row, $href, $label, $title, $icon, $attributes)
	{
		// Disable the button if the element type is not allowed
		if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_ELEMENT_TYPE, $row['type']))
		{
			return Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
		}

		$objElement = $this->Database->prepare("SELECT id FROM tl_content WHERE type='alias' AND cteAlias=?")
									 ->limit(1)
									 ->execute($row['id']);

		return $objElement->numRows ? Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ' : '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ';
	}

	/**
	 * Dynamically add flags to the "singleSRC" field
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 */
	public function setSingleSrcFlags($varValue, DataContainer $dc)
	{
		if ($dc->activeRecord)
		{
			switch ($dc->activeRecord->type)
			{
				case 'text':
				case 'hyperlink':
				case 'image':
				case 'accordionSingle':
				case 'youtube':
				case 'vimeo':
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['extensions'] = '%contao.image.valid_extensions%';
					break;

				case 'download':
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['extensions'] = Config::get('allowedDownload');
					break;

				case 'markdown':
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['extensions'] = 'md';
					break;
			}
		}

		return $varValue;
	}

	/**
	 * Dynamically add flags to the "multiSRC" field
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 */
	public function setMultiSrcFlags($varValue, DataContainer $dc)
	{
		if ($dc->activeRecord)
		{
			switch ($dc->activeRecord->type)
			{
				case 'gallery':
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['isGallery'] = true;
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['extensions'] = '%contao.image.valid_extensions%';
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['mandatory'] = true;
					break;

				case 'downloads':
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['isDownloads'] = true;
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['extensions'] = Config::get('allowedDownload');
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['mandatory'] = true;
					break;
			}
		}

		return $varValue;
	}

	/**
	 * Extract the YouTube ID from a URL
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 */
	public function extractYouTubeId($varValue, DataContainer $dc)
	{
		if ($dc->activeRecord->youtube != $varValue)
		{
			$matches = array();

			if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $varValue, $matches))
			{
				$varValue = $matches[1];
			}
		}

		return $varValue;
	}

	/**
	 * Extract the Vimeo ID from a URL
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 */
	public function extractVimeoId($varValue, DataContainer $dc)
	{
		if ($dc->activeRecord->vimeo != $varValue)
		{
			$matches = array();

			if (preg_match('%vimeo\.com/(?:channels/(?:\w+/)?|groups/(?:[^/]+)/videos/|album/(?:\d+)/video/)?(\d+)(?:$|/|\?)%i', $varValue, $matches))
			{
				$varValue = $matches[1];
			}
		}

		return $varValue;
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

		if (!$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_content::invisible'))
		{
			return '';
		}

		// Disable the button if the element type is not allowed
		if (!$security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_ELEMENT_TYPE, $row['type']))
		{
			return Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
		}

		$href .= '&amp;id=' . $row['id'];

		if ($row['invisible'])
		{
			$icon = 'invisible.svg';
		}

		return '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.getScrollOffset();return AjaxRequest.toggleField(this,true)">' . Image::getHtml($icon, $label, 'data-icon="' . Image::getPath('visible.svg') . '" data-icon-disabled="' . Image::getPath('invisible.svg') . '" data-state="' . ($row['invisible'] ? 0 : 1) . '"') . '</a> ';
	}
}
