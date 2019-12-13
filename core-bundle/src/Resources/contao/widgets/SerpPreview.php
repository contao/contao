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

		// Get the URL with a %s placeholder for the alias or ID
		$url = $this->getUrl($model);
		list($baseUrl, $urlSuffix) = explode('%s', $url);
		$url = sprintf($url, $model->alias ?: $model->id);

		// Get the input field suffix (edit multiple mode)
		$suffix = substr($this->objDca->inputName, \strlen($this->objDca->field));

		$titleField = $this->getTitleField($suffix);
		$titleFallbackField = $this->getTitleFallbackField($suffix);
		$aliasField = $this->getAliasField($suffix);
		$descriptionField = $this->getDescriptionField($suffix);
		$descriptionFallbackField = $this->getDescriptionFallbackField($suffix);

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

	/**
	 * @todo Use the router to generate the URL in a future version (see #831)
	 */
	private function getUrl(Model $model)
	{
		if (!isset($this->serpPreview['url']))
		{
			throw new \Exception('Please provide the SERP widget URL as string or callable');
		}

		if (\is_callable($this->serpPreview['url']))
		{
			$placeholder = bin2hex(random_bytes(10));

			// Pass a detached clone with the alias set to the placeholder
			$tempModel = clone $model;
			$tempModel->origAlias = $tempModel->alias;
			$tempModel->alias = $placeholder;
			$tempModel->preventSaving(false);

			return str_replace($placeholder, '%s', $this->serpPreview['url']($tempModel));
		}

		return $this->serpPreview['url'];
	}

	private function getTitleField($suffix)
	{
		if (!isset($this->serpPreview['title']))
		{
			return 'ctrl_title' . $suffix;
		}

		if (\is_array($this->serpPreview['title']))
		{
			return 'ctrl_' . $this->serpPreview['title'][0] . $suffix;
		}

		return 'ctrl_' . $this->serpPreview['title'] . $suffix;
	}

	private function getTitleFallbackField($suffix)
	{
		if (!isset($this->serpPreview['title']) || !\is_array($this->serpPreview['title']))
		{
			return '';
		}

		return 'ctrl_' . $this->serpPreview['title'][1] . $suffix;
	}

	private function getAliasField($suffix)
	{
		if (!isset($this->serpPreview['alias']))
		{
			return 'ctrl_alias' . $suffix;
		}

		return 'ctrl_' . $this->serpPreview['alias'] . $suffix;
	}

	private function getDescriptionField($suffix)
	{
		if (!isset($this->serpPreview['description']))
		{
			return 'ctrl_description' . $suffix;
		}

		if (\is_array($this->serpPreview['description']))
		{
			return 'ctrl_' . $this->serpPreview['description'][0] . $suffix;
		}

		return 'ctrl_' . $this->serpPreview['description'] . $suffix;
	}

	private function getDescriptionFallbackField($suffix)
	{
		if (!isset($this->serpPreview['description']) || !\is_array($this->serpPreview['description']))
		{
			return '';
		}

		return 'ctrl_' . $this->serpPreview['description'][1] . $suffix;
	}
}
