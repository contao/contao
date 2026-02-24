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
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\FaqCategoryModel;
use Contao\System;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;

System::loadLanguageFile('tl_content');

$GLOBALS['TL_DCA']['tl_faq'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_faq_category',
		'enableVersioning'            => true,
		'markAsCopy'                  => 'question',
		'onload_callback' => array
		(
			array('tl_faq', 'removeMetaFields')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid,published' => 'index',
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
			'panelLayout'             => 'search,filter,limit',
			'defaultSearchField'      => 'question',
			'headerFields'            => array('title', 'headline', 'jumpTo', 'tstamp'),
			'renderAsGrid'            => true,
			'limitHeight'             => 160
		),
		'label' => array
		(
			'fields'                  => array('question', 'answer'),
			'format'                  => '<h2>%s</h2> %s',
			'label_callback'          => array('tl_faq', 'listQuestions'),
		),
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('addImage', 'addEnclosure', 'overwriteMeta'),
		'default'                     => '{title_legend},question,alias,author;{meta_legend},pageTitle,robots,description,serpPreview;{answer_legend},answer;{image_legend},addImage;{enclosure_legend:hide},addEnclosure;{expert_legend:hide},searchIndexer;{publish_legend},published'
	),

	// Sub-palettes
	'subpalettes' => array
	(
		'addImage'                    => 'singleSRC,fullsize,size,floating,overwriteMeta',
		'addEnclosure'                => 'enclosure',
		'overwriteMeta'               => 'alt,imageTitle,imageUrl,caption'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'autoincrement'=>true)
		),
		'pid' => array
		(
			'foreignKey'              => 'tl_faq_category.title',
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0),
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'sorting' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'tstamp' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'question' => array
		(
			'search'                  => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'basicEntities'=>true, 'maxlength'=>255, 'tl_class'=>'long'),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'alias' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_faq', 'generateAlias')
			),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'', 'customSchemaOptions'=>array('collation'=>'utf8mb4_bin'))
		),
		'author' => array
		(
			'default'                 => static fn () => BackendUser::getInstance()->id,
			'search'                  => true,
			'filter'                  => true,
			'flag'                    => DataContainer::SORT_ASC,
			'inputType'               => 'select',
			'foreignKey'              => 'tl_user.name',
			'eval'                    => array('doNotCopy'=>true, 'chosen'=>true, 'mandatory'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0),
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'answer' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>true, 'rte'=>'tinyMCE', 'basicEntities'=>true, 'helpwizard'=>true),
			'explanation'             => 'insertTags',
			'sql'                     => array('type'=>'text', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, 'notnull'=>false)
		),
		'pageTitle' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'robots' => array
		(
			'search'                  => true,
			'inputType'               => 'select',
			'options'                 => array('index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'),
			'eval'                    => array('tl_class'=>'w50', 'includeBlankOption' => true),
			'sql'                     => array('type'=>'string', 'length'=>32, 'default'=>'')
		),
		'description' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('style'=>'height:60px', 'decodeEntities'=>true, 'tl_class'=>'clr'),
			'sql'                     => array('type'=>'text', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, 'notnull'=>false)
		),
		'serpPreview' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['serpPreview'],
			'inputType'               => 'serpPreview',
			'eval'                    => array('titleFields'=>array('pageTitle', 'question'), 'descriptionFields'=>array('description', 'answer')),
			'sql'                     => null
		),
		'addImage' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type'=>'boolean', 'default'=>false)
		),
		'overwriteMeta' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['overwriteMeta'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => array('type'=>'boolean', 'default'=>false)
		),
		'singleSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['singleSRC'],
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'filesOnly'=>true, 'extensions'=>'%contao.image.valid_extensions%', 'mandatory'=>true),
			'sql'                     => array('type'=>'binary', 'length'=>16, 'fixed'=>true, 'notnull'=>false)
		),
		'alt' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['alt'],
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'text', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, 'notnull'=>false)
		),
		'imageTitle' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageTitle'],
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'text', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, 'notnull'=>false)
		),
		'size' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
			'inputType'               => 'imageSize',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50 clr'),
			'options_callback'        => array('contao.listener.image_size_options', '__invoke'),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'', 'customSchemaOptions'=>array('collation'=>'ascii_bin'))
		),
		'imageUrl' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageUrl'],
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'text', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, 'notnull'=>false)
		),
		'fullsize' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['fullsize'],
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type'=>'boolean', 'default'=>false)
		),
		'caption' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['caption'],
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'allowHtml'=>true, 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'text', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, 'notnull'=>false)
		),
		'floating' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['floating'],
			'inputType'               => 'radioTable',
			'options'                 => array('above', 'left', 'right', 'below'),
			'eval'                    => array('cols'=>4, 'tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'sql'                     => array('type'=>'string', 'length'=>12, 'default'=>'above')
		),
		'addEnclosure' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type'=>'boolean', 'default'=>false)
		),
		'enclosure' => array
		(
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'filesOnly'=>true, 'isDownloads'=>true, 'extensions'=>Config::get('allowedDownload'), 'mandatory'=>true, 'isSortable'=>true),
			'sql'                     => array('type'=>'blob', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull'=>false)
		),
		'searchIndexer' => array
		(
			'search'                  => true,
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['searchIndexer'],
			'inputType'               => 'select',
			'options'                 => array('always_index', 'never_index'),
			'eval'                    => array('maxlength'=>32, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'sql'                     => array('type'=>'string', 'length'=>32, 'default'=>'')
		),
		'published' => array
		(
			'toggle'                  => true,
			'filter'                  => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_DESC,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true),
			'sql'                     => array('type'=>'boolean', 'default'=>false)
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_faq extends Backend
{
	/**
	 * Remove the meta fields if the FAQ category does not have a jumpTo page
	 *
	 * @param DataContainer $dc
	 */
	public function removeMetaFields(DataContainer $dc)
	{
		$objFaqCategory = Database::getInstance()
			->prepare('SELECT c.jumpTo FROM tl_faq f LEFT JOIN tl_faq_category c ON f.pid=c.id WHERE f.id=?')
			->execute($dc->id);

		if (!$objFaqCategory->jumpTo)
		{
			PaletteManipulator::create()
				->removeField(array('searchIndexer'), 'expert_legend')
				->removeField(array('pageTitle', 'robots', 'description', 'serpPreview'), 'meta_legend')
				->applyToPalette('default', 'tl_faq');
		}
	}

	/**
	 * Auto-generate the FAQ alias if it has not been set yet
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
				->prepare("SELECT id FROM tl_faq WHERE alias=? AND id!=?")
				->execute($alias, $dc->id);

			return $result->numRows > 0;
		};

		// Generate alias if there is none
		if (!$varValue)
		{
			$varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->question, FaqCategoryModel::findById($dc->activeRecord->pid)->jumpTo, $aliasExists);
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
	 * Add the question as element metadata
	 *
	 * @param array  $arrRow
	 * @param string $label
	 *
	 * @return array
	 */
	public function listQuestions($arrRow, $label): array
	{
		$key = $arrRow['published'] ? 'published' : 'unpublished';

		return array($arrRow['question'], $label, $key);
	}
}
