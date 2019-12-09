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
 * @property array $serpPreview
 */
class SerpPreview extends Widget
{
	/**
	 * @var string
	 */
	protected $strTemplate = 'be_widget';

	/**
	 * @return string
	 */
	public function generate()
	{
		/** @var Model $class */
		$class = $this->serpPreview['class'] ?? Model::getClassFromTable($this->strTable);
		$model = $class::findByPk($this->activeRecord->id);

		if (!$model instanceof Model)
		{
			throw new \RuntimeException('Could not fetch the associated model');
		}

		$id = $model->id;
		$title = StringUtil::substr($this->getTitle($model), 64);
		$description = StringUtil::substr($this->getDescription($model), 160);
		$url = $this->getUrl($model);
		list($baseUrl) = explode($model->alias ?: $model->id, $url);
		$urlSuffix = System::getContainer()->getParameter('contao.url_suffix');
		$suffix = substr($this->objDca->inputName, \strlen($this->objDca->field));
		$titleField = $this->getTitleField() . $suffix;
		$titleFallbackField = $this->getTitleFallbackField() . $suffix;
		$aliasField = $this->getAliasField() . $suffix;
		$descriptionField = $this->getDescriptionField() . $suffix;
		$descriptionFallbackField = $this->getDescriptionFallbackField() . $suffix;

		return <<<EOT
<div class="serp-preview">
  <p id="serp_title_$id" class="title">$title</p>
  <p id="serp_url_$id" class="url">$url</p>
  <p id="serp_description_$id" class="description">$description</p>
</div>
<script>
  window.addEvent('domready', function() {
    new Contao.SerpPreview({
      id: '$id',
      baseUrl: '$baseUrl',
      urlSuffix: '$urlSuffix',
      titleField: '$titleField',
      titleFallbackField: '$titleFallbackField',
      aliasField: '$aliasField',
      descriptionField: '$descriptionField',
      descriptionFallbackField: '$descriptionFallbackField'
    });
  });
</script>
EOT;
	}

	private function getTitle(Model $model)
	{
		if (!isset($this->serpPreview['title']))
		{
			return $model->title;
		}

		if (\is_array($this->serpPreview['title']))
		{
			return $model->{$this->serpPreview['title'][0]} ?: $model->{$this->serpPreview['title'][1]};
		}

		return $model->{$this->serpPreview['title']};
	}

	private function getDescription(Model $model)
	{
		if (!isset($this->serpPreview['description']))
		{
			return $model->description;
		}

		if (\is_array($this->serpPreview['description']))
		{
			return $model->{$this->serpPreview['description'][0]} ?: $model->{$this->serpPreview['description'][1]};
		}

		return $model->{$this->serpPreview['description']};
	}

	private function getUrl(Model $model)
	{
		if (isset($this->serpPreview['url']))
		{
			return $this->serpPreview['url'];
		}

		// FIXME: use the router to generate the URL (see #831)
		switch (true)
		{
			case $model instanceof PageModel:
				return $model->getAbsoluteUrl();

			case $model instanceof NewsModel:
				return News::generateNewsUrl($model, false, true);

			case $model instanceof CalendarEventsModel:
				return Events::generateEventUrl($model, true);

			default:
				throw new \RuntimeException(sprintf('Unsupported model class "%s"', \get_class($model)));
		}
	}

	private function getTitleField()
	{
		if (!isset($this->serpPreview['title']))
		{
			return 'ctrl_title';
		}

		if (\is_array($this->serpPreview['title']))
		{
			return 'ctrl_' . $this->serpPreview['title'][0];
		}

		return 'ctrl_' . $this->serpPreview['title'];
	}

	private function getTitleFallbackField()
	{
		if (!isset($this->serpPreview['title']) || !\is_array($this->serpPreview['title']))
		{
			return '';
		}

		return 'ctrl_' . $this->serpPreview['title'][1];
	}

	private function getAliasField()
	{
		if (!isset($this->serpPreview['alias']))
		{
			return 'ctrl_alias';
		}

		return 'ctrl_' . $this->serpPreview['alias'];
	}

	private function getDescriptionField()
	{
		if (!isset($this->serpPreview['description']))
		{
			return 'ctrl_description';
		}

		if (\is_array($this->serpPreview['description']))
		{
			return 'ctrl_' . $this->serpPreview['description'][0];
		}

		return 'ctrl_' . $this->serpPreview['description'];
	}

	private function getDescriptionFallbackField()
	{
		if (!isset($this->serpPreview['description']) || !\is_array($this->serpPreview['description']))
		{
			return '';
		}

		return 'ctrl_' . $this->serpPreview['description'][1];
	}
}
