<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_settings'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => 'File',
		'closed'                      => true
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{global_legend},adminEmail;{date_legend},dateFormat,timeFormat,datimFormat,timeZone;{backend_legend:hide},doNotCollapse,resultsPerPage,maxResultsPerPage;{frontend_legend},folderUrl,doNotRedirectEmpty;{security_legend:hide},disableRefererCheck,allowedTags,allowedAttributes;{files_legend:hide},allowedDownload,gdMaxImgWidth,gdMaxImgHeight;{uploads_legend:hide},uploadTypes,maxFileSize,imageWidth,imageHeight;{cron_legend:hide},disableCron;{chmod_legend},defaultUser,defaultGroup,defaultChmod'
	),

	// Fields
	'fields' => array
	(
		'dateFormat' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'helpwizard'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'explanation'             => 'dateFormat'
		),
		'timeFormat' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50')
		),
		'datimFormat' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50')
		),
		'timeZone' => array
		(
			'inputType'               => 'select',
			'options_callback' => static function ()
			{
				return Contao\System::getTimeZones();
			},
			'eval'                    => array('chosen'=>true, 'tl_class'=>'w50')
		),
		'adminEmail' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'friendly', 'decodeEntities'=>true, 'tl_class'=>'w50')
		),
		'disableCron' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50')
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
		'doNotCollapse' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50')
		),
		'doNotRedirectEmpty' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50')
		),
		'folderUrl' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50')
		),
		'disableRefererCheck' => array
		(
			'inputType'               => 'checkbox',
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
				static function ($varValue)
				{
					$showWarning = false;

					foreach (Contao\StringUtil::deserialize($varValue, true) as $row)
					{
						if (in_array('*', Contao\StringUtil::trimsplit(',', $row['value']), true))
						{
							$showWarning = true;
							break;
						}
					}

					if ($showWarning)
					{
						$GLOBALS['TL_DCA']['tl_settings']['fields']['allowedAttributes']['label'][1] = '<span style="color: #c33;">' . $GLOBALS['TL_LANG']['tl_settings']['allowedAttributesWarning'] . '</span>';
					}

					return $varValue;
				},
			),
			'save_callback' => array
			(
				static function ($strValue)
				{
					$arrValue = Contao\StringUtil::deserialize($strValue, true);

					foreach ($arrValue as $intIndex => $arrRow)
					{
						$arrValue[$intIndex]['key'] = strtolower($arrRow['key']);
						$arrValue[$intIndex]['value'] = strtolower($arrRow['value']);
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
		'gdMaxImgWidth' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50 clr')
		),
		'gdMaxImgHeight' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50')
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
		'defaultUser' => array(
			'inputType'               => 'select',
			'foreignKey'              => 'tl_user.username',
			'eval'                    => array('chosen'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50')
		),
		'defaultGroup' => array(
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
