<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DC_File;
use Contao\StringUtil;

$GLOBALS['TL_DCA']['tl_settings'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_File::class,
		'closed'                      => true
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{global_legend},adminEmail;{date_legend},dateFormat,timeFormat,datimFormat,timeZone;{backend_legend:hide},resultsPerPage,maxResultsPerPage;{security_legend:hide},allowedTags,allowedAttributes;{files_legend:hide},allowedDownload;{uploads_legend:hide},uploadTypes,maxFileSize,imageWidth,imageHeight;{chmod_legend},defaultUser,defaultGroup,defaultChmod'
	),

	// Fields
	'fields' => array
	(
		'dateFormat' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'helpwizard'=>true, 'decodeEntities'=>true, 'tl_class'=>'w25'),
			'explanation'             => 'dateFormat'
		),
		'timeFormat' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'tl_class'=>'w25')
		),
		'datimFormat' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'tl_class'=>'w25')
		),
		'timeZone' => array
		(
			'inputType'               => 'select',
			'options_callback' => static function () {
				return array_values(DateTimeZone::listIdentifiers());
			},
			'eval'                    => array('chosen'=>true, 'tl_class'=>'w25')
		),
		'adminEmail' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'friendly', 'decodeEntities'=>true, 'tl_class'=>'w50')
		),
		'resultsPerPage' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'natural', 'minval'=>1, 'nospace'=>true, 'tl_class'=>'w50 clr')
		),
		'maxResultsPerPage' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50')
		),
		'allowedTags' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('useRawRequestData'=>true, 'tl_class'=>'long')
		),
		'allowedAttributes' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['allowedAttributes'],
			'inputType'               => 'keyValueWizard',
			'eval'                    => array('tl_class'=>'clr'),
			'load_callback' => array
			(
				static function ($varValue) {
					$showWarning = false;

					foreach (StringUtil::deserialize($varValue, true) as $row)
					{
						if (in_array('*', StringUtil::trimsplit(',', $row['value']), true))
						{
							$showWarning = true;
							break;
						}
					}

					if ($showWarning)
					{
						$GLOBALS['TL_DCA']['tl_settings']['fields']['allowedAttributes']['label'][1] = '<span class="tl_red">' . $GLOBALS['TL_LANG']['tl_settings']['allowedAttributesWarning'] . '</span>';
					}

					return $varValue;
				},
			),
			'save_callback' => array
			(
				static function ($strValue) {
					$arrValue = StringUtil::deserialize($strValue, true);
					$arrAllowedAttributes = array();

					foreach ($arrValue as $arrRow)
					{
						foreach (StringUtil::trimsplit(',', strtolower($arrRow['key'])) as $strKey)
						{
							$arrAllowedAttributes[$strKey] = array_merge(
								$arrAllowedAttributes[$strKey] ?? array(),
								StringUtil::trimsplit(',', strtolower($arrRow['value']))
							);

							$arrAllowedAttributes[$strKey] = array_filter(array_unique($arrAllowedAttributes[$strKey]));
							sort($arrAllowedAttributes[$strKey]);
						}
					}

					ksort($arrAllowedAttributes);
					$arrValue = array();

					foreach ($arrAllowedAttributes as $strTag => $arrAttributes)
					{
						$arrValue[] = array
						(
							'key' => $strTag,
							'value' => implode(',', $arrAttributes),
						);
					}

					return serialize($arrValue);
				},
			),
		),
		'allowedDownload' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50')
		),
		'uploadTypes' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50')
		),
		'maxFileSize' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50')
		),
		'imageWidth' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50')
		),
		'imageHeight' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50')
		),
		'defaultUser' => array
		(
			'inputType'               => 'select',
			'foreignKey'              => 'tl_user.username',
			'eval'                    => array('chosen'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50')
		),
		'defaultGroup' => array
		(
			'inputType'               => 'select',
			'foreignKey'              => 'tl_user_group.name',
			'eval'                    => array('chosen'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50')
		),
		'defaultChmod' => array
		(
			'inputType'               => 'chmod',
			'eval'                    => array('tl_class'=>'clr')
		)
	)
);
