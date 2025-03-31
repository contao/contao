<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\RouteParametersException;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
		/** @var class-string<Model> $class */
		$class = Model::getClassFromTable($this->strTable);
		$model = $class::findById($this->activeRecord->id);

		if (!$model instanceof Model)
		{
			throw new \RuntimeException('Could not fetch the associated model');
		}

		try
		{
			// Get the URL with a %s placeholder for the alias or ID
			$url = $this->getUrl($model);

			list($baseUrl) = explode('%s', $url);
			$trail = implode(' › ', $this->convertUrlToItems($baseUrl));
		}
		catch (RouteParametersException)
		{
			return $this->render([
				'error' => $this->trans('MSC.noSerpPreview', [], 'contao_default'),
			]);
		}
		catch (ExceptionInterface) {
			$trail = '';
		}

		// Get the input field suffix (edit multiple mode)
		$suffix = substr($this->objDca->inputName, \strlen($this->objDca->field));

		return $this->render([
			'fields' => [
				'title' => [$this->getTitleField($suffix), $this->getTitleFallbackField($suffix)],
				'alias' => [$this->getAliasField($suffix)],
				'description' => [$this->getDescriptionField($suffix), $this->getDescriptionFallbackField($suffix)],
			],
			'id' => $model->id,
			'trail' => $trail,
			'titleTag' => $this->getTitleTag($model),
		]);
	}

	private function render($parameters = []): string {
		return System::getContainer()
			->get('twig')
			->render('@Contao/backend/widget/serp_preview.html.twig', $parameters)
		;
	}

	/**
	 * @throws ExceptionInterface
	 */
	private function getUrl(Model $model): string
	{
		$aliasField = $this->aliasField ?: 'alias';
		$placeholder = bin2hex(random_bytes(10));

		// Pass a detached clone with the alias set to the placeholder
		$tempModel = $model->cloneDetached();
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
				$url = System::getContainer()->get('contao.routing.content_url_generator')->generate($tempModel, array(), UrlGeneratorInterface::ABSOLUTE_URL);
			}
			catch (RouteNotFoundException $exception)
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
			return '%s';
		}

		if (\is_array($this->title_tag_callback))
		{
			return System::importStatic($this->title_tag_callback[0])->{$this->title_tag_callback[1]}($model);
		}

		if (\is_callable($this->title_tag_callback))
		{
			return ($this->title_tag_callback)($model);
		}

		return '%s';
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

		if (!isset($chunks['path']))
		{
			return array();
		}

		$steps = array_filter(explode('/', $chunks['path']));

		if (isset($chunks['host']))
		{
			$steps = array_merge(array($chunks['host']), $steps);
		}

		return $steps;
	}
}
