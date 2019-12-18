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
 * @property string          $serpClass
 * @property string|array    $serpTitle
 * @property string|array    $serpDescription
 * @property string|callable $serpUrl
 * @property string|array    $serpAlias
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
		$class = $this->serpClass ?? Model::getClassFromTable($this->strTable);
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
		$url = $model->alias == 'index' ? $baseUrl : sprintf($url, $model->alias ?: $model->id);

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
		if (!isset($this->serpTitle))
		{
			return $model->title;
		}

		if (\is_array($this->serpTitle))
		{
			return $model->{$this->serpTitle[0]} ?: $model->{$this->serpTitle[1]};
		}

		return $model->{$this->serpTitle};
	}

	private function getDescription(Model $model)
	{
		if (!isset($this->serpDescription))
		{
			return $model->description;
		}

		if (\is_array($this->serpDescription))
		{
			return $model->{$this->serpDescription[0]} ?: $model->{$this->serpDescription[1]};
		}

		return $model->{$this->serpDescription};
	}

	/**
	 * @todo Use the router to generate the URL in a future version (see #831)
	 */
	private function getUrl(Model $model)
	{
		if (!isset($this->serpUrl))
		{
			throw new \Exception('No SERP widget URL given');
		}

		if (\is_string($this->serpUrl))
		{
			return $this->serpUrl;
		}

		$placeholder = bin2hex(random_bytes(10));

		// Pass a detached clone with the alias set to the placeholder
		$tempModel = clone $model;
		$tempModel->origAlias = $tempModel->alias;
		$tempModel->alias = $placeholder;
		$tempModel->preventSaving(false);

		if (\is_array($this->serpUrl))
		{
			$this->import($this->serpUrl[0]);
			$url = $this->{$this->serpUrl[0]}->{$this->serpUrl[1]}($tempModel);
		}
		elseif (\is_callable($this->serpUrl))
		{
			$url = \call_user_func($this->serpUrl, $tempModel);
		}
		else
		{
			throw new \Exception('Please provide the SERP widget URL as string or callable');
		}

		return str_replace($placeholder, '%s', $url);
	}

	private function getTitleField($suffix)
	{
		if (!isset($this->serpTitle))
		{
			return 'ctrl_title' . $suffix;
		}

		if (\is_array($this->serpTitle))
		{
			return 'ctrl_' . $this->serpTitle[0] . $suffix;
		}

		return 'ctrl_' . $this->serpTitle . $suffix;
	}

	private function getTitleFallbackField($suffix)
	{
		if (!isset($this->serpTitle) || !\is_array($this->serpTitle))
		{
			return '';
		}

		return 'ctrl_' . $this->serpTitle[1] . $suffix;
	}

	private function getAliasField($suffix)
	{
		if (!isset($this->serpAlias))
		{
			return 'ctrl_alias' . $suffix;
		}

		return 'ctrl_' . $this->serpAlias . $suffix;
	}

	private function getDescriptionField($suffix)
	{
		if (!isset($this->serpDescription))
		{
			return 'ctrl_description' . $suffix;
		}

		if (\is_array($this->serpDescription))
		{
			return 'ctrl_' . $this->serpDescription[0] . $suffix;
		}

		return 'ctrl_' . $this->serpDescription . $suffix;
	}

	private function getDescriptionFallbackField($suffix)
	{
		if (!isset($this->serpDescription) || !\is_array($this->serpDescription))
		{
			return '';
		}

		return 'ctrl_' . $this->serpDescription[1] . $suffix;
	}
}
