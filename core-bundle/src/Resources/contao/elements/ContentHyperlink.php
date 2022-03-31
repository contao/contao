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
 * Front end content element "hyperlink".
 */
class ContentHyperlink extends ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_hyperlink';

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		if (0 === strncmp($this->url, 'mailto:', 7))
		{
			$this->url = StringUtil::encodeEmail($this->url);
		}
		else
		{
			$this->url = StringUtil::ampersand($this->url);
		}

		$embed = explode('%s', $this->embed);

		// Use an image instead of the title
		if ($this->useImage)
		{
			$figure = System::getContainer()
				->get('contao.image.studio')
				->createFigureBuilder()
				->from($this->singleSRC)
				->setSize($this->size)
				->setMetadata($this->objModel->getOverwriteMetadata())
				->buildIfResourceExists();

			if (null !== $figure)
			{
				$figure->applyLegacyTemplateData($this->Template);

				$this->Template->useImage = true;
			}
		}

		if ($this->rel)
		{
			$this->Template->attribute = ' data-lightbox="' . $this->rel . '"';
		}

		if (!$this->linkTitle)
		{
			$this->linkTitle = $this->url;
		}

		$this->Template->href = $this->url;
		$this->Template->embed_pre = $embed[0] ?? null;
		$this->Template->embed_post = $embed[1] ?? null;
		$this->Template->link = $this->linkTitle;
		$this->Template->target = '';
		$this->Template->rel = '';

		if ($this->titleText)
		{
			$this->Template->linkTitle = StringUtil::specialchars($this->titleText);
		}

		// Override the link target
		if ($this->target)
		{
			$this->Template->target = ' target="_blank"';
			$this->Template->rel = ' rel="noreferrer noopener"';
		}

		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		// Unset the title attributes in the back end (see #6258)
		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$this->Template->title = '';
			$this->Template->linkTitle = '';
		}
	}
}

class_alias(ContentHyperlink::class, 'ContentHyperlink');
