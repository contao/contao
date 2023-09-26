<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

/**
 * Class ModuleFaqList
 *
 * @property array $faq_categories
 * @property int   $faq_readerModule
 */
class ModuleFaqList extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_faqlist';

	/**
	 * Target pages
	 * @var array
	 */
	protected $arrTargets = array();

	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['faqlist'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		$this->faq_categories = StringUtil::deserialize($this->faq_categories);

		// Return if there are no categories
		if (empty($this->faq_categories) || !\is_array($this->faq_categories))
		{
			return '';
		}

		// Show the FAQ reader if an item has been selected
		if ($this->faq_readerModule > 0 && (isset($_GET['items']) || (Config::get('useAutoItem') && isset($_GET['auto_item']))))
		{
			return $this->getFrontendModule($this->faq_readerModule, $this->strColumn);
		}

		// Tag the FAQ categories (see #2137)
		if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
		{
			$responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
			$responseTagger->addTags(array_map(static function ($id) { return 'contao.db.tl_faq_category.' . $id; }, $this->faq_categories));
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$objFaq = FaqModel::findPublishedByPids($this->faq_categories);

		if ($objFaq === null)
		{
			$this->Template->faq = array();

			return;
		}

		$tags = array();
		$arrFaq = array_fill_keys($this->faq_categories, array());

		// Add FAQs
		while ($objFaq->next())
		{
			$arrTemp = $objFaq->row();
			$arrTemp['title'] = StringUtil::specialchars($objFaq->question, true);
			$arrTemp['href'] = $this->generateFaqLink($objFaq);

			/** @var FaqCategoryModel $objPid */
			$objPid = $objFaq->getRelated('pid');

			if (empty($arrFaq[$objFaq->pid]))
			{
				$arrFaq[$objFaq->pid] = $objPid->row();
			}

			$arrFaq[$objFaq->pid]['items'][] = $arrTemp;

			$tags[] = 'contao.db.tl_faq.' . $objFaq->id;
		}

		// Tag the FAQs (see #2137)
		if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
		{
			$responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
			$responseTagger->addTags($tags);
		}

		$arrFaq = array_values(array_filter($arrFaq));

		$cat_count = 0;
		$cat_limit = \count($arrFaq);

		// Add classes
		foreach ($arrFaq as $k=>$v)
		{
			$count = 0;
			$limit = \count($v['items']);

			for ($i=0; $i<$limit; $i++)
			{
				$arrFaq[$k]['items'][$i]['class'] = trim(((++$count == 1) ? ' first' : '') . (($count >= $limit) ? ' last' : '') . ((($count % 2) == 0) ? ' odd' : ' even'));
			}

			$arrFaq[$k]['class'] = trim(((++$cat_count == 1) ? ' first' : '') . (($cat_count >= $cat_limit) ? ' last' : '') . ((($cat_count % 2) == 0) ? ' odd' : ' even'));
		}

		$this->Template->faq = $arrFaq;
	}

	/**
	 * Create links and remember pages that have been processed
	 *
	 * @param FaqModel $objFaq
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	protected function generateFaqLink($objFaq)
	{
		/** @var FaqCategoryModel $objCategory */
		$objCategory = $objFaq->getRelated('pid');
		$jumpTo = (int) $objCategory->jumpTo;

		// A jumpTo page is not mandatory for FAQ categories (see #6226) but required for the FAQ list module
		if ($jumpTo < 1)
		{
			throw new \Exception("FAQ categories without redirect page cannot be used in an FAQ list");
		}

		// Get the URL from the jumpTo page of the category
		if (!isset($this->arrTargets[$jumpTo]))
		{
			$this->arrTargets[$jumpTo] = StringUtil::ampersand(Environment::get('request'));

			if ($jumpTo > 0 && ($objTarget = PageModel::findByPk($jumpTo)) !== null)
			{
				/** @var PageModel $objTarget */
				$this->arrTargets[$jumpTo] = StringUtil::ampersand($objTarget->getFrontendUrl(Config::get('useAutoItem') ? '/%s' : '/items/%s'));
			}
		}

		return sprintf(preg_replace('/%(?!s)/', '%%', $this->arrTargets[$jumpTo]), ($objFaq->alias ?: $objFaq->id));
	}
}

class_alias(ModuleFaqList::class, 'ModuleFaqList');
