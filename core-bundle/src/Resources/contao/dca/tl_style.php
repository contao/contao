<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_style'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_style_sheet',
		'enableVersioning'            => true,
		'onload_callback' => array
		(
			array('tl_style', 'checkPermission'),
			array('tl_style', 'updateStyleSheet')
		),
		'oncopy_callback' => array
		(
			array('tl_style', 'scheduleUpdate')
		),
		'oncut_callback' => array
		(
			array('tl_style', 'scheduleUpdate')
		),
		'ondelete_callback' => array
		(
			array('tl_style', 'scheduleUpdate')
		),
		'onsubmit_callback' => array
		(
			array('tl_style', 'scheduleUpdate')
		),
		'onrestore_version_callback' => array
		(
			array('tl_style', 'updateAfterRestore')
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
			'fields'                  => array('sorting'),
			'panelLayout'             => 'filter;search,limit',
			'headerFields'            => array('name', 'tstamp', 'media'),
			'child_record_callback'   => array('StyleSheets', 'compileDefinition')
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
				'label'               => &$GLOBALS['TL_LANG']['tl_style']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_style']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_style']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_style']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_style']['toggle'],
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s,\'tl_style\')"',
				'button_callback'     => array('tl_style', 'toggleIcon')
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_style']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('size', 'positioning', 'alignment', 'background', 'border', 'font', 'list'),
		'default'                     => '{selector_legend},selector,category,comment;{size_legend},size;{position_legend},positioning;{align_legend},alignment;{background_legend},background;{border_legend},border;{font_legend},font;{list_legend},list;{custom_legend:hide},own',
	),

	// Subpalettes
	'subpalettes' => array
	(
		'size'                        => 'width,height,minwidth,minheight,maxwidth,maxheight',
		'positioning'                 => 'trbl,position,floating,clear,overflow,display',
		'alignment'                   => 'margin,padding,align,verticalalign,textalign,whitespace',
		'background'                  => 'bgcolor,bgimage,bgposition,bgrepeat,shadowsize,shadowcolor,gradientAngle,gradientColors',
		'border'                      => 'borderwidth,borderstyle,bordercolor,borderradius,bordercollapse,borderspacing',
		'font'                        => 'fontfamily,fontsize,fontcolor,lineheight,fontstyle,texttransform,textindent,letterspacing,wordspacing',
		'list'                        => 'liststyletype,liststyleimage'
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
			'foreignKey'              => 'tl_style_sheet.name',
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
		'selector' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['selector'],
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>1022, 'decodeEntities'=>true, 'style'=>'height:60px'),
			'sql'                     => "varchar(1022) NOT NULL default ''"
		),
		'category' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['category'],
			'search'                  => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>32, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'load_callback' => array
			(
				array('tl_style', 'checkCategory')
			),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'comment' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['comment'],
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'size' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['size'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'width' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['width'],
			'inputType'               => 'inputUnit',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_auto_inherit', 'maxlength' => 20, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'height' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['height'],
			'inputType'               => 'inputUnit',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_auto_inherit', 'maxlength' => 20, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'minwidth' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['minwidth'],
			'inputType'               => 'inputUnit',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_inherit', 'maxlength' => 20, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'minheight' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['minheight'],
			'inputType'               => 'inputUnit',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_inherit', 'maxlength' => 20, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'maxwidth' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['maxwidth'],
			'inputType'               => 'inputUnit',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_inherit_none', 'maxlength' => 20, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'maxheight' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['maxheight'],
			'inputType'               => 'inputUnit',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_inherit_none', 'maxlength' => 20, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'positioning' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['positioning'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'trbl' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['trbl'],
			'inputType'               => 'trbl',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_auto_inherit', 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'position' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['position'],
			'inputType'               => 'select',
			'options'                 => array('absolute', 'relative', 'static', 'fixed'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'floating' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['floating'],
			'inputType'               => 'select',
			'options'                 => array('left', 'right', 'none'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'clear' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['clear'],
			'inputType'               => 'select',
			'options'                 => array('both', 'left', 'right', 'none'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'overflow' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['overflow'],
			'inputType'               => 'select',
			'options'                 => array('auto', 'hidden', 'scroll', 'visible'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'display' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['display'],
			'inputType'               => 'select',
			'options'                 => array('block', 'inline', 'inline-block', 'list-item', 'run-in', 'compact', 'none', 'table', 'inline-table', 'table-row', 'table-cell', 'table-row-group', 'table-header-group', 'table-footer-group', 'table-column', 'table-column-group', 'table-caption'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'alignment' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['alignment'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'margin' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['margin'],
			'inputType'               => 'trbl',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_auto_inherit', 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'padding' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['padding'],
			'inputType'               => 'trbl',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_inherit', 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'align' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['align'],
			'inputType'               => 'select',
			'options'                 => array('left', 'center', 'right'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'verticalalign' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['verticalalign'],
			'inputType'               => 'select',
			'options'                 => array('top', 'text-top', 'middle', 'text-bottom', 'baseline', 'bottom'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'textalign' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['textalign'],
			'inputType'               => 'select',
			'options'                 => array('left', 'center', 'right', 'justify'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'whitespace' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['whitespace'],
			'inputType'               => 'select',
			'options'                 => array('normal', 'nowrap', 'pre', 'pre-line', 'pre-wrap'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(8) NOT NULL default ''"
		),
		'background' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['background'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'bgcolor' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['bgcolor'],
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>6, 'multiple'=>true, 'size'=>2, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'bgimage' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['bgimage'],
			'inputType'               => 'text',
			'eval'                    => array('dcaPicker'=>array('do'=>'files', 'context'=>'file', 'icon'=>'pickfile.svg', 'fieldType'=>'radio', 'filesOnly'=>true, 'extensions'=>Contao\Config::get('validImageTypes')), 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'bgposition' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['bgposition'],
			'inputType'               => 'select',
			'options'                 => array('left top', 'left center', 'left bottom', 'center top', 'center center', 'center bottom', 'right top', 'right center', 'right bottom'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'bgrepeat' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['bgrepeat'],
			'inputType'               => 'select',
			'options'                 => array('repeat', 'repeat-x', 'repeat-y', 'no-repeat'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'shadowsize' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['shadowsize'],
			'inputType'               => 'trbl',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_', 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'shadowcolor' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['shadowcolor'],
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>6, 'multiple'=>true, 'size'=>2, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'gradientAngle' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['gradientAngle'],
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>32, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'gradientColors' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['gradientColors'],
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>4, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'border' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['border'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'borderwidth' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['borderwidth'],
			'inputType'               => 'trbl',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_inherit', 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'borderstyle' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['borderstyle'],
			'inputType'               => 'select',
			'options'                 => array('solid', 'dotted', 'dashed', 'double', 'groove', 'ridge', 'inset', 'outset', 'hidden'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'bordercolor' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['bordercolor'],
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>6, 'multiple'=>true, 'size'=>2, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'borderradius' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['borderradius'],
			'inputType'               => 'trbl',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_', 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'bordercollapse' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['bordercollapse'],
			'inputType'               => 'select',
			'options'                 => array('collapse', 'separate'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'borderspacing' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['borderspacing'],
			'inputType'               => 'inputUnit',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_inherit', 'maxlength' => 20, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'font' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['font'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'fontfamily' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['fontfamily'],
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'fontsize' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['fontsize'],
			'inputType'               => 'inputUnit',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_inherit', 'maxlength' => 20, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'fontcolor' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['fontcolor'],
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>6, 'multiple'=>true, 'size'=>2, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'lineheight' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['lineheight'],
			'inputType'               => 'inputUnit',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_normal_inherit', 'maxlength' => 20, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'fontstyle' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['fontstyle'],
			'inputType'               => 'checkbox',
			'options'                 => array('bold', 'italic', 'normal', 'underline', 'notUnderlined', 'line-through', 'overline', 'small-caps'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_style'],
			'eval'                    => array('multiple'=>true, 'tl_class'=>'clr'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'texttransform' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['texttransform'],
			'inputType'               => 'select',
			'options'                 => array('uppercase', 'lowercase', 'capitalize', 'none'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_style'],
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'textindent' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['textindent'],
			'inputType'               => 'inputUnit',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_inherit', 'maxlength' => 20, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'letterspacing' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['letterspacing'],
			'inputType'               => 'inputUnit',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_normal_inherit', 'maxlength' => 20, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'wordspacing' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['wordspacing'],
			'inputType'               => 'inputUnit',
			'options'                 => $GLOBALS['TL_CSS_UNITS'],
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit_normal_inherit', 'maxlength' => 20, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'list' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['list'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'liststyletype' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['liststyletype'],
			'inputType'               => 'select',
			'options'                 => array('disc', 'circle', 'square', 'decimal', 'upper-roman', 'lower-roman', 'upper-alpha', 'lower-alpha', 'none'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_style'],
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'liststyleimage' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['liststyleimage'],
			'inputType'               => 'text',
			'eval'                    => array('dcaPicker'=>array('do'=>'files', 'context'=>'file', 'icon'=>'pickfile.svg', 'fieldType'=>'radio', 'filesOnly'=>true, 'extensions'=>Contao\Config::get('validImageTypes')), 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'own' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['own'],
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('preserveTags'=>true, 'style'=>'height:120px'),
			'sql'                     => "text NULL"
		),
		'invisible' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style']['invisible'],
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class tl_style extends Contao\Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('Contao\BackendUser', 'User');
	}

	/**
	 * Check permissions to edit the table
	 *
	 * @throws Contao\CoreBundle\Exception\AccessDeniedException
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		if (!$this->User->hasAccess('css', 'themes'))
		{
			throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to access the style sheets module.');
		}
	}

	/**
	 * Automatically set the category if not set
	 *
	 * @param mixed $varValue
	 *
	 * @return string
	 */
	public function checkCategory($varValue)
	{
		// Do not change the value if it has been set already
		if (\strlen($varValue) || Contao\Input::post('FORM_SUBMIT') == 'tl_style')
		{
			return $varValue;
		}

		/** @var Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface $objSessionBag */
		$objSessionBag = Contao\System::getContainer()->get('session')->getBag('contao_backend');

		$key = 'tl_style_' . CURRENT_ID;
		$filter = $objSessionBag->get('filter');

		// Return the current category
		if (\strlen($filter[$key]['category']))
		{
			return $filter[$key]['category'];
		}

		return '';
	}

	/**
	 * Check for modified style sheets and update them if necessary
	 */
	public function updateStyleSheet()
	{
		/** @var Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
		$objSession = Contao\System::getContainer()->get('session');

		$session = $objSession->get('style_sheet_updater');

		if (empty($session) || !\is_array($session))
		{
			return;
		}

		$this->import('Contao\StyleSheets', 'StyleSheets');

		foreach ($session as $id)
		{
			$this->StyleSheets->updateStyleSheet($id);
		}

		$objSession->set('style_sheet_updater', null);
	}

	/**
	 * Schedule a style sheet update
	 *
	 * This method is triggered when a single style or multiple styles are
	 * modified (edit/editAll), duplicated (copy/copyAll), moved (cut/cutAll)
	 * or deleted (delete/deleteAll).
	 */
	public function scheduleUpdate()
	{
		// Return if there is no ID
		if (!CURRENT_ID || Contao\Input::get('act') == 'copy')
		{
			return;
		}

		/** @var Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
		$objSession = Contao\System::getContainer()->get('session');

		// Store the ID in the session
		$session = $objSession->get('style_sheet_updater');
		$session[] = CURRENT_ID;
		$objSession->set('style_sheet_updater', array_unique($session));
	}

	/**
	 * Update a style sheet after a version has been restored
	 *
	 * @param integer $id
	 * @param string  $table
	 * @param array   $data
	 */
	public function updateAfterRestore($id, $table, $data)
	{
		if ($table != 'tl_style')
		{
			return;
		}

		// Update the timestamp of the style sheet
		$this->Database->prepare("UPDATE tl_style_sheet SET tstamp=? WHERE id=?")
					   ->execute(time(), $data['pid']);

		// Update the CSS file
		$this->import('Contao\StyleSheets', 'StyleSheets');
		$this->StyleSheets->updateStyleSheet($data['pid']);
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
		if (\strlen(Contao\Input::get('tid')))
		{
			$this->toggleVisibility(Contao\Input::get('tid'), (Contao\Input::get('state') == 1), (@func_get_arg(12) ?: null));
			$this->redirect($this->getReferer());
		}

		$href .= '&amp;tid='.$row['id'].'&amp;state='.$row['invisible'];

		if ($row['invisible'])
		{
			$icon = 'invisible.svg';
		}

		return '<a href="'.$this->addToUrl($href).'" title="'.Contao\StringUtil::specialchars($title).'"'.$attributes.'>'.Contao\Image::getHtml($icon, $label, 'data-state="' . ($row['invisible'] ? 0 : 1) . '"').'</a> ';
	}

	/**
	 * Toggle the visibility of a format definition
	 *
	 * @param integer              $intId
	 * @param boolean              $blnVisible
	 * @param Contao\DataContainer $dc
	 */
	public function toggleVisibility($intId, $blnVisible, Contao\DataContainer $dc=null)
	{
		// Set the ID and action
		Contao\Input::setGet('id', $intId);
		Contao\Input::setGet('act', 'toggle');

		if ($dc)
		{
			$dc->id = $intId; // see #8043
		}

		// Trigger the onload_callback
		if (\is_array($GLOBALS['TL_DCA']['tl_style']['config']['onload_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_style']['config']['onload_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}($dc);
				}
				elseif (\is_callable($callback))
				{
					$callback($dc);
				}
			}
		}

		// Set the current record
		if ($dc)
		{
			$objRow = $this->Database->prepare("SELECT * FROM tl_style WHERE id=?")
									 ->limit(1)
									 ->execute($intId);

			if ($objRow->numRows)
			{
				$dc->activeRecord = $objRow;
			}
		}

		$objVersions = new Contao\Versions('tl_style', $intId);
		$objVersions->initialize();

		// Reverse the logic (styles have invisible=1)
		$blnVisible = !$blnVisible;

		// Trigger the save_callback
		if (\is_array($GLOBALS['TL_DCA']['tl_style']['fields']['invisible']['save_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_style']['fields']['invisible']['save_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $dc);
				}
				elseif (\is_callable($callback))
				{
					$blnVisible = $callback($blnVisible, $dc);
				}
			}
		}

		$time = time();

		// Update the database
		$this->Database->prepare("UPDATE tl_style SET tstamp=$time, invisible='" . ($blnVisible ? '1' : '') . "' WHERE id=?")
					   ->execute($intId);

		if ($dc)
		{
			$dc->activeRecord->tstamp = $time;
			$dc->activeRecord->invisible = ($blnVisible ? '1' : '');
		}

		// Trigger the onsubmit_callback
		if (\is_array($GLOBALS['TL_DCA']['tl_style']['config']['onsubmit_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_style']['config']['onsubmit_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}($dc);
				}
				elseif (\is_callable($callback))
				{
					$callback($dc);
				}
			}
		}

		$objVersions->create();

		// The onsubmit_callback has triggered scheduleUpdate(), so run updateStyleSheet() now (see #1052)
		$this->updateStyleSheet();
	}
}
