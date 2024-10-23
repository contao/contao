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
use Contao\ContentTable;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use Contao\MemberGroupModel;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_content'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'enableVersioning'            => true,
		'ctable'                      => array('tl_content'),
		'dynamicPtable'               => true,
		'markAsCopy'                  => 'headline',
		'onload_callback'             => array
		(
			array('tl_content', 'adjustDcaByType'),
			array('tl_content', 'showJsLibraryHint'),
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index',
				'ptable,pid,invisible,start,stop' => 'index',
				'type' => 'index'
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
			'defaultSearchField'      => 'text',
			'headerFields'            => array('title', 'type', 'author', 'tstamp', 'start', 'stop'),
			'child_record_callback'   => array('tl_content', 'addCteType'),
			'renderAsGrid'            => true,
			'limitHeight'             => 160
		),
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('type', 'addImage', 'sortable', 'useImage', 'overwriteMeta', 'overwriteLink', 'protected', 'splashImage', 'markdownSource', 'showPreview', 'addTextTracks'),
		'default'                     => '{type_legend},type',
		'headline'                    => '{type_legend},type,headline;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'text'                        => '{type_legend},type,headline;{text_legend},text;{image_legend},addImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'html'                        => '{type_legend},type;{text_legend},html;{template_legend:hide},customTpl;{protected_legend:hide},protected;{invisible_legend:hide},invisible,start,stop',
		'unfiltered_html'             => '{type_legend},type;{text_legend},unfilteredHtml;{template_legend:hide},customTpl;{protected_legend:hide},protected;{invisible_legend:hide},invisible,start,stop',
		'list'                        => '{type_legend},type,headline;{list_legend},listtype,listitems;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'description_list'            => '{type_legend},type,headline;{list_legend},data;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'table'                       => '{type_legend},type,headline;{table_legend},tableitems;{tconfig_legend},summary,thead,tfoot,tleft;{sortable_legend:hide},sortable;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'accordion'                   => '{type_legend},type,headline;{accordion_legend},closeSections;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'element_group'               => '{type_legend},type,headline;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'accordionStart'              => '{type_legend},type;{moo_legend},mooHeadline,mooStyle,mooClasses;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'accordionStop'               => '{type_legend},type;{moo_legend},mooClasses;{template_legend:hide},customTpl;{protected_legend:hide},protected;{invisible_legend:hide},invisible,start,stop',
		'accordionSingle'             => '{type_legend},type;{moo_legend},mooHeadline,mooStyle,mooClasses;{text_legend},text;{image_legend},addImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'swiper'                      => '{type_legend},type,headline;{slider_legend},sliderDelay,sliderSpeed,sliderStartSlide,sliderContinuous;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'sliderStart'                 => '{type_legend},type,headline;{slider_legend},sliderDelay,sliderSpeed,sliderStartSlide,sliderContinuous;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'sliderStop'                  => '{type_legend},type;{template_legend:hide},customTpl;{protected_legend:hide},protected;{invisible_legend:hide},invisible,start,stop',
		'code'                        => '{type_legend},type,headline;{text_legend},highlight,code;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'markdown'                    => '{type_legend},type,headline;{text_legend},markdownSource;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'template'                    => '{type_legend},type,headline;{template_legend},data,customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'hyperlink'                   => '{type_legend},type,headline;{link_legend},url,target,linkTitle,embed,titleText,rel;{imglink_legend:hide},useImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'toplink'                     => '{type_legend},type;{link_legend},linkTitle;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'image'                       => '{type_legend},type,headline;{source_legend},singleSRC,size,fullsize,overwriteMeta;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'gallery'                     => '{type_legend},type,headline;{source_legend},multiSRC,useHomeDir,sortBy,metaIgnore;{image_legend},size,perRow,perPage,numberOfItems,fullsize;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'player'                      => '{type_legend},type,headline;{source_legend},playerSRC;{texttrack_legend},addTextTracks;{player_legend},playerOptions,playerSize,playerPreload,playerCaption,playerStart,playerStop;{poster_legend:hide},posterSRC;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'youtube'                     => '{type_legend},type,headline;{source_legend},youtube;{player_legend},youtubeOptions,playerTitle,playerSize,playerAspect,playerCaption,playerStart,playerStop;{splash_legend},splashImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'vimeo'                       => '{type_legend},type,headline;{source_legend},vimeo;{player_legend},vimeoOptions,playerTitle,playerSize,playerAspect,playerCaption,playerStart,playerColor;{splash_legend},splashImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'download'                    => '{type_legend},type,headline;{source_legend},singleSRC;{download_legend},inline,overwriteLink;{preview_legend},showPreview;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'downloads'                   => '{type_legend},type,headline;{source_legend},multiSRC,useHomeDir;{download_legend},inline,sortBy,metaIgnore;{preview_legend},showPreview;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'alias'                       => '{type_legend},type;{include_legend},cteAlias;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'article'                     => '{type_legend},type;{include_legend},articleAlias;{protected_legend:hide},protected;{invisible_legend:hide},invisible,start,stop',
		'teaser'                      => '{type_legend},type;{include_legend},article;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'form'                        => '{type_legend},type,headline;{include_legend},form;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop',
		'module'                      => '{type_legend},type;{include_legend},module;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop'
	),

	// Sub-palettes
	'subpalettes' => array
	(
		'addImage'                    => 'singleSRC,fullsize,size,floating,overwriteMeta',
		'sortable'                    => 'sortIndex,sortOrder',
		'useImage'                    => 'singleSRC,size,overwriteMeta',
		'overwriteMeta'               => 'alt,imageTitle,imageUrl,caption',
		'overwriteLink'               => 'linkTitle,titleText',
		'protected'                   => 'groups',
		'splashImage'                 => 'singleSRC,size',
		'markdownSource_sourceText'   => 'code',
		'markdownSource_sourceFile'   => 'singleSRC',
		'showPreview'                 => 'size,fullsize,numberOfItems',
		'addTextTracks'               => 'textTrackSRC'
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
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'ptable' => array
		(
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default 'tl_article'"
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
			'reference'               => &$GLOBALS['TL_LANG']['CTE'],
			'eval'                    => array('helpwizard'=>true, 'chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50'),
			'sql'                     => array('name'=>'type', 'type'=>'string', 'length'=>64, 'default'=>'text', 'customSchemaOptions'=>array('collation'=>'ascii_bin'))
		),
		'headline' => array
		(
			'search'                  => true,
			'inputType'               => 'inputUnit',
			'options'                 => array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'),
			'eval'                    => array('maxlength'=>200, 'basicEntities'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(255) NOT NULL default 'a:2:{s:5:\"value\";s:0:\"\";s:4:\"unit\";s:2:\"h2\";}'"
		),
		'sectionHeadline' => array
		(
			'search'                  => true,
			'inputType'               => 'inputUnit',
			'options'                 => array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'),
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default 'a:2:{s:5:\"value\";s:0:\"\";s:4:\"unit\";s:2:\"h2\";}'"
		),
		'text' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>true, 'basicEntities'=>true, 'rte'=>'tinyMCE', 'helpwizard'=>true),
			'explanation'             => 'insertTags',
			'sql'                     => "mediumtext NULL"
		),
		'addImage' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'showPreview' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true, 'tl_class' => 'clr'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'inline' => array(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'overwriteMeta' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'singleSRC' => array
		(
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
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'imageTitle' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'size' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
			'inputType'               => 'imageSize',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(128) COLLATE ascii_bin NOT NULL default ''"
		),
		'imageUrl' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => "text NULL"
		),
		'fullsize' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'caption' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'allowHtml'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'floating' => array
		(
			'inputType'               => 'radioTable',
			'options'                 => array('above', 'left', 'right', 'below'),
			'eval'                    => array('cols'=>4, 'tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default 'above'"
		),
		'html' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('allowHtml'=>true, 'class'=>'monospace', 'rte'=>'ace|html', 'helpwizard'=>true),
			'explanation'             => 'insertTags',
			'sql'                     => "mediumtext NULL"
		),
		'unfilteredHtml' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('useRawRequestData'=>true, 'class'=>'monospace', 'rte'=>'ace|html', 'helpwizard'=>true),
			'explanation'             => 'insertTags',
			'sql'                     => "mediumtext NULL"
		),
		'listtype' => array
		(
			'inputType'               => 'select',
			'options'                 => array('ordered', 'unordered'),
			'eval'                    => array('tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content'],
			'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default ''"
		),
		'listitems' => array
		(
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
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'thead' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w25 clr'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'tfoot' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w25'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'tleft' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w25'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'sortable' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'sortIndex' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'sortOrder' => array
		(
			'inputType'               => 'select',
			'options'                 => array('ascending', 'descending'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default 'ascending'"
		),
		'closeSections' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'mooHeadline' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'allowHtml'=>true, 'tl_class'=>'long'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'mooStyle' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'mooClasses' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'rgxp'=>'alnum', 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'highlight' => array
		(
			'inputType'               => 'select',
			'options'                 => array('Apache', 'Bash', 'C#', 'C++', 'CSS', 'Diff', 'HTML', 'HTTP', 'Ini', 'JSON', 'Java', 'JavaScript', 'Markdown', 'Nginx', 'Perl', 'PHP', 'PowerShell', 'Python', 'Ruby', 'SCSS', 'SQL', 'Twig', 'YAML', 'XML'),
			'eval'                    => array('includeBlankOption'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default ''"
		),
		'markdownSource' => array
		(
			'inputType'               => 'select',
			'options'                 => array('sourceText', 'sourceFile'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content']['markdownSource'],
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(12) COLLATE ascii_bin NOT NULL default 'sourceText'"
		),
		'code' => array
		(
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
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => "text NULL"
		),
		'target' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['target'],
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'overwriteLink' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'titleText' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'linkTitle' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'embed' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'rel' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>64, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'useImage' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'multiSRC' => array
		(
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'isSortable' => true, 'files'=>true),
			'sql'                     => "blob NULL",
			'load_callback' => array
			(
				array('tl_content', 'setMultiSrcFlags')
			)
		),
		'useHomeDir' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'perRow' => array
		(
			'inputType'               => 'select',
			'options'                 => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12),
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 4"
		),
		'perPage' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'numberOfItems' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['numberOfItems'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'sortBy' => array
		(
			'inputType'               => 'select',
			'options'                 => array('custom', 'name_asc', 'name_desc', 'date_asc', 'date_desc', 'random'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content'],
			'eval'                    => array('tl_class'=>'w50 clr'),
			'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default ''"
		),
		'metaIgnore' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'galleryTpl' => array
		(
			'inputType'               => 'select',
			'options_callback' => static function () {
				return Controller::getTemplateGroup('gallery_');
			},
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
		),
		'customTpl' => array
		(
			'inputType'               => 'select',
			'eval'                    => array('chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
		),
		'playerSRC' => array
		(
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'files'=>true, 'mandatory'=>true),
			'sql'                     => "blob NULL"
		),
		'youtube' => array
		(
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
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_content', 'extractVimeoId')
			),
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
		),
		'posterSRC' => array
		(
			'inputType'               => 'fileTree',
			'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio'),
			'sql'                     => "binary(16) NULL"
		),
		'playerSize' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
		),
		'playerOptions' => array
		(
			'inputType'               => 'checkbox',
			'options'                 => array('player_autoplay', 'player_nocontrols', 'player_loop', 'player_playsinline', 'player_muted'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content'],
			'eval'                    => array('multiple'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'playerStart' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>16, 'nospace'=>true, 'tl_class'=>'w25'),
			'sql'                     => "varchar(16) NOT NULL default ''"
		),
		'playerStop' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>16, 'nospace'=>true, 'tl_class'=>'w25'),
			'sql'                     => "varchar(16) NOT NULL default ''"
		),
		'playerTitle' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'playerCaption' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'playerAspect' => array
		(
			'inputType'               => 'select',
			'options'                 => array('16:9', '16:10', '21:9', '4:3', '3:2'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content']['player_aspect'],
			'eval'                    => array('includeBlankOption' => true, 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(8) COLLATE ascii_bin NOT NULL default ''"
		),
		'addTextTracks' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'textTrackSRC' => array
		(
			'inputType'               => 'fileTree',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true, 'fieldType'=>'checkbox', 'submitOnChange'=>true, 'filesOnly'=>true, 'extensions'=>'vtt', 'isSortable'=>true),
			'sql'                     => "blob NULL"
		),
		'splashImage' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'playerPreload' => array
		(
			'inputType'               => 'select',
			'options'                 => array('auto', 'metadata', 'none'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content']['player_preload'],
			'eval'                    => array('includeBlankOption' => true, 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(8) COLLATE ascii_bin NOT NULL default ''"
		),
		'playerColor' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>6, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w25 wizard'),
			'sql'                     => "varchar(6) COLLATE ascii_bin NOT NULL default ''"
		),
		'youtubeOptions' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['playerOptions'],
			'inputType'               => 'checkbox',
			'options'                 => array('youtube_autoplay', 'youtube_controls', 'youtube_cc_load_policy', 'youtube_fs', 'youtube_hl', 'youtube_iv_load_policy', 'youtube_modestbranding', 'youtube_rel', 'youtube_nocookie', 'youtube_loop'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content'],
			'eval'                    => array('multiple'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'vimeoOptions' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['playerOptions'],
			'inputType'               => 'checkbox',
			'options'                 => array('vimeo_autoplay', 'vimeo_loop', 'vimeo_portrait', 'vimeo_title', 'vimeo_byline', 'vimeo_dnt'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_content'],
			'eval'                    => array('multiple'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'sliderDelay' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w25'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'sliderSpeed' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w25'),
			'sql'                     => "int(10) unsigned NOT NULL default 300"
		),
		'sliderStartSlide' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w25'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'sliderContinuous' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w25'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'data' => array
		(
			'inputType'               => 'keyValueWizard',
			'sql'                     => "text NULL"
		),
		'cteAlias' => array
		(
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
			'inputType'               => 'picker',
			'foreignKey'              => 'tl_article.title',
			'eval'                    => array('mandatory'=>true, 'tl_class'=>'clr'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'form' => array
		(
			'inputType'               => 'select',
			'foreignKey'              => 'tl_form.title',
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
			'inputType'               => 'select',
			'foreignKey'              => 'tl_module.name',
			'eval'                    => array('mandatory'=>true, 'chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50 wizard'),
			'wizard' => array
			(
				array('tl_content', 'editModule')
			),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
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
		'invisible' => array
		(
			'reverseToggle'           => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'start' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) COLLATE ascii_bin NOT NULL default ''"
		),
		'stop' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) COLLATE ascii_bin NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_content extends Backend
{
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
		$objCte = ContentModel::findById($dc->id);

		if ($objCte === null)
		{
			return;
		}

		switch ($objCte->type)
		{
			case 'hyperlink':
				unset($GLOBALS['TL_DCA']['tl_content']['fields']['imageUrl']);
				break;

			case 'download':
			case 'downloads':
				$GLOBALS['TL_DCA']['tl_content']['fields']['size']['eval']['mandatory'] = true;
				break;
		}

		if (System::getContainer()->get('contao.fragment.compositor')->supportsNesting('contao.content_element.' . $objCte->type))
		{
			$GLOBALS['TL_DCA']['tl_content']['config']['switchToEdit'] = true;
		}
	}

	/**
	 * Show a hint if a JavaScript library needs to be included in the page layout
	 *
	 * @param object $dc
	 */
	public function showJsLibraryHint($dc)
	{
		if (Input::isPost() || Input::get('act') != 'edit')
		{
			return;
		}

		$security = System::getContainer()->get('security.helper');

		// Return if the user cannot access the layout module (see #6190)
		if (!$security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'themes') || !$security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_LAYOUTS))
		{
			return;
		}

		$objCte = ContentModel::findById($dc->id);

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
				if ($objCte->sortable && ($GLOBALS['TL_CTE']['texts']['table'] ?? null) === ContentTable::class)
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

		// Remove the class if it is a wrapper element
		if (in_array($arrRow['type'], $GLOBALS['TL_WRAPPERS']['start']) || in_array($arrRow['type'], $GLOBALS['TL_WRAPPERS']['separator']) || in_array($arrRow['type'], $GLOBALS['TL_WRAPPERS']['stop']))
		{
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

			$key .= ' icon-protected';
			$type .= ' (' . $GLOBALS['TL_LANG']['MSC']['protected'] . ($groupNames ? ': ' . implode(', ', $groupNames) : '') . ')';
		}

		// Add the headline level (see #5858)
		if ($arrRow['type'] == 'headline' && is_array($headline = StringUtil::deserialize($arrRow['headline'])))
		{
			$type .= ' (' . $headline['unit'] . ')';
		}

		if ($arrRow['start'] && $arrRow['stop'])
		{
			$type .= ' <span class="visibility">(' . sprintf($GLOBALS['TL_LANG']['MSC']['showFromTo'], Date::parse(Config::get('datimFormat'), $arrRow['start']), Date::parse(Config::get('datimFormat'), $arrRow['stop'])) . ')</span>';
		}
		elseif ($arrRow['start'])
		{
			$type .= ' <span class="visibility">(' . sprintf($GLOBALS['TL_LANG']['MSC']['showFrom'], Date::parse(Config::get('datimFormat'), $arrRow['start'])) . ')</span>';
		}
		elseif ($arrRow['stop'])
		{
			$type .= ' <span class="visibility">(' . sprintf($GLOBALS['TL_LANG']['MSC']['showTo'], Date::parse(Config::get('datimFormat'), $arrRow['stop'])) . ')</span>';
		}

		$objModel = new ContentModel();
		$objModel->setRow($arrRow);

		$class = 'cte_preview';
		$preview = StringUtil::insertTagToSrc($this->getContentElement($objModel));

		// Strip HTML comments to check if the preview is empty
		if (trim(preg_replace('/<!--(.|\s)*?-->/', '', $preview)) == '')
		{
			$class .= ' empty';
		}

		return '
<div class="cte_type ' . $key . '">' . $type . '</div>
<div class="' . $class . '">' . $preview . '</div>';
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
		$href = System::getContainer()->get('router')->generate('contao_backend', array('do'=>'form', 'table'=>'tl_form_field', 'id'=>$dc->value, 'popup'=>'1', 'nb'=>'1'));

		return ' <a href="' . StringUtil::specialcharsUrl($href) . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", $title)) . '\',\'url\':this.href});return false">' . Image::getHtml('edit.svg', $title) . '</a>';
	}

	/**
	 * Get all forms and return them as array
	 *
	 * @return array
	 */
	public function getForms()
	{
		$user = BackendUser::getInstance();

		if (!$user->isAdmin && !is_array($user->forms))
		{
			return array();
		}

		$arrForms = array();
		$objForms = Database::getInstance()->execute("SELECT id, title FROM tl_form ORDER BY title");
		$security = System::getContainer()->get('security.helper');

		while ($objForms->next())
		{
			if ($security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FORM, $objForms->id))
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

		// DataContainer::getCurrentRecord() will check permission on the record
		try
		{
			$module = $dc->getCurrentRecord($dc->value, 'tl_module');
		}
		catch (AccessDeniedException)
		{
			return '';
		}

		$title = sprintf($GLOBALS['TL_LANG']['tl_content']['editalias'], $module['id']);
		$href = System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$module['id'], 'popup'=>'1', 'nb'=>'1'));

		return ' <a href="' . StringUtil::specialcharsUrl($href) . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", $title)) . '\',\'url\':this.href});return false">' . Image::getHtml('edit.svg', $title) . '</a>';
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
			case 'Twig':
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
		return ' <a href="' . $this->addToUrl('key=list') . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['lw_import'][1]) . '" data-action="contao--scroll-offset#store">' . Image::getHtml('tablewizard.svg', $GLOBALS['TL_LANG']['MSC']['tw_import'][0]) . '</a>';
	}

	/**
	 * Add a link to the table items import wizard
	 *
	 * @return string
	 */
	public function tableImportWizard()
	{
		return ' <a href="' . $this->addToUrl('key=table') . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['tw_import'][1]) . '" data-action="contao--scroll-offset#store">' . Image::getHtml('tablewizard.svg', $GLOBALS['TL_LANG']['MSC']['tw_import'][0]) . '</a> ' . Image::getHtml('demagnify.svg', '', 'title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['tw_shrink']) . '" style="cursor:pointer" onclick="Backend.tableWizardResize(0.9)"') . Image::getHtml('magnify.svg', '', 'title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['tw_expand']) . '" style="cursor:pointer" onclick="Backend.tableWizardResize(1.1)"');
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
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['mandatory'] = !$dc->activeRecord->useHomeDir;
					break;

				case 'downloads':
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['isDownloads'] = true;
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['extensions'] = Config::get('allowedDownload');
					$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['mandatory'] = !$dc->activeRecord->useHomeDir;
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

			if (preg_match('%vimeo\.com/(?:channels/(?:\w+/)?|groups/(?:[^/]+)/videos/|album/(?:\d+)/video/|video/)?(\d+)(?:$|/|\?)%i', $varValue, $matches))
			{
				// Unlisted video privacy hash
				if (preg_match('%[?&]h=([0-9a-z]+)%i', $varValue, $matchesHash))
				{
					$varValue = $matches[1] . '?h=' . $matchesHash[1];
				}
				else
				{
					$varValue = $matches[1];
				}
			}
		}

		return $varValue;
	}
}
