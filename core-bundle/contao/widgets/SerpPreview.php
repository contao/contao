<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\Routing\Exception\ExceptionInterface;

/**
 * @property array    $titleFields
 * @property array    $descriptionFields
 * @property string   $aliasField
 * @property callable $url_callback
 * @property callable $title_tag_callback
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
		$class = Model::getClassFromTable($this->strTable);
		$model = $class::findByPk($this->activeRecord->id);

		if (!$model instanceof Model)
		{
			throw new \RuntimeException('Could not fetch the associated model');
		}

		$id = $model->id;
		$title = StringUtil::substr(str_replace(array('&nbsp;', '&shy;'), array(' ', ''), $this->getTitle($model)), 64);
		$description = StringUtil::substr($this->getDescription($model), 160);
		$alias = $this->getAlias($model);

		try
		{
			// Get the URL with a %s placeholder for the alias or ID
			$url = $this->getUrl($model);
		}
		catch (ExceptionInterface $routingException)
		{
			return '<div class="serp-preview"><p class="tl_info">' . $GLOBALS['TL_LANG']['MSC']['noSerpPreview'] . '</p></div>';
		}

		list($baseUrl) = explode('%s', $url);
		$trail = implode(' › ', $this->convertUrlToItems($baseUrl));

		// Use the base URL for the index page
		if ($model instanceof PageModel && $alias == 'index')
		{
			$url = $trail;
		}
		else
		{
			$url = implode(' › ', $this->convertUrlToItems($baseUrl . ($alias ?: $model->id)));
		}

		// Get the input field suffix (edit multiple mode)
		$suffix = substr($this->objDca->inputName, \strlen($this->objDca->field));

		$titleField = $this->getTitleField($suffix);
		$titleFallbackField = $this->getTitleFallbackField($suffix);
		$aliasField = $this->getAliasField($suffix);
		$descriptionField = $this->getDescriptionField($suffix);
		$descriptionFallbackField = $this->getDescriptionFallbackField($suffix);

		if ($titleTag = $this->getTitleTag($model))
		{
			$title = StringUtil::substr(sprintf($titleTag, $title), 64);
		}

		return <<<EOT
			<div class="serp-preview">
			  <p id="serp_url_$id" class="url">$url</p>
			  <p id="serp_title_$id" class="title">$title</p>
			  <p id="serp_description_$id" class="description">$description</p>
			</div>
			<script>
			  window.addEvent('domready', function() {
			    new Contao.SerpPreview({
			      id: '$id',
			      trail: '$trail',
			      titleField: '$titleField',
			      titleFallbackField: '$titleFallbackField',
			      aliasField: '$aliasField',
			      descriptionField: '$descriptionField',
			      descriptionFallbackField: '$descriptionFallbackField',
			      titleTag: '$titleTag'
			    });
			  });
			</script>
			EOT;
	}

	private function getTitle(Model $model)
	{
		if (!isset($this->titleFields))
		{
			return (string) $model->title;
		}

		return (string) ($model->{$this->titleFields[0]} ?: $model->{$this->titleFields[1]});
	}

	private function getDescription(Model $model)
	{
		if (!isset($this->descriptionFields))
		{
			return (string) $model->description;
		}

		return (string) ($model->{$this->descriptionFields[0]} ?: $model->{$this->descriptionFields[1]});
	}

	private function getAlias(Model $model)
	{
		if (!isset($this->aliasField))
		{
			return $model->alias;
		}

		return $model->{$this->aliasField};
	}

	private function getUrl(Model $model): string
	{
		$aliasField = $this->aliasField ?: 'alias';
		$placeholder = bin2hex(random_bytes(10));

		// Pass a detached clone with the alias set to the placeholder
		$tempModel = $model->cloneOriginal();
		$tempModel->origAlias = $tempModel->$aliasField;
		$tempModel->$aliasField = $placeholder;
		$tempModel->preventSaving(false);

		if (\is_array($this->url_callback))
		{
			$url = System::importStatic($this->url_callback[0])->{$this->url_callback[1]}($tempModel);
		}
		elseif (\is_callable($this->url_callback))
		{
			$url = ($this->url_callback)($tempModel);
		}
		else
		{
			try
			{
				$url = System::getContainer()->get('contao.routing.content_url_generator')->generate(self::importStatic($tempModel));
			}
			catch (ExceptionInterface $exception)
			{
				throw new \LogicException('Unable to generate a content URL for the SERP widget, please provide the url_callback.', 0, $exception);
			}
		}

		return str_replace($placeholder, '%s', $url);
	}

	private function getTitleTag(Model $model)
	{
		if (!isset($this->title_tag_callback))
		{
			return '';
		}

		if (\is_array($this->title_tag_callback))
		{
			return System::importStatic($this->title_tag_callback[0])->{$this->title_tag_callback[1]}($model);
		}

		if (\is_callable($this->title_tag_callback))
		{
			return ($this->title_tag_callback)($model);
		}

		return '';
	}

	private function getTitleField($suffix)
	{
		if (!isset($this->titleFields[0]))
		{
			return 'ctrl_title' . $suffix;
		}

		return 'ctrl_' . $this->titleFields[0] . $suffix;
	}

	private function getTitleFallbackField($suffix)
	{
		if (!isset($this->titleFields[1]))
		{
			return '';
		}

		return 'ctrl_' . $this->titleFields[1] . $suffix;
	}

	private function getDescriptionField($suffix)
	{
		if (!isset($this->descriptionFields[0]))
		{
			return 'ctrl_description' . $suffix;
		}

		return 'ctrl_' . $this->descriptionFields[0] . $suffix;
	}

	private function getDescriptionFallbackField($suffix)
	{
		if (!isset($this->descriptionFields[1]))
		{
			return '';
		}

		return 'ctrl_' . $this->descriptionFields[1] . $suffix;
	}

	private function getAliasField($suffix)
	{
		if (!isset($this->aliasField))
		{
			return 'ctrl_alias' . $suffix;
		}

		return 'ctrl_' . $this->aliasField . $suffix;
	}

	private function convertUrlToItems($url): array
	{
		$chunks = parse_url($url);
		$steps = array_filter(explode('/', $chunks['path']));

		if (isset($chunks['host']))
		{
			$steps = array_merge(array($chunks['host']), $steps);
		}

		return $steps;
	}
}
