<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

class Collection extends Widget
{
	protected $blnSubmitInput = true;

	protected $strTemplate = 'be_widget';

	protected array $arrFields = array();

	private array $widgets = array();

	public function __construct($arrAttributes = null)
	{
		parent::__construct($arrAttributes);

		$this->preserveTags = true;
		$this->decodeEntities = true;

		System::loadLanguageFile('default');
	}

	public function __set($strKey, $varValue): void
	{
		switch ($strKey)
		{
			case 'mandatory':
				if ($varValue)
				{
					$this->arrAttributes['required'] = 'required';
				}
				else
				{
					unset($this->arrAttributes['required']);
				}
				parent::__set($strKey, $varValue);
				break;

			case 'fields':
				$this->arrFields = $varValue;
				break;

			case 'style':
				$this->style = $varValue ?? null;
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	public function __get($strKey)
	{
		return match ($strKey)
		{
			'fields' => $this->arrFields,
			default => parent::__get($strKey),
		};
	}

	public function validate(): void
	{
		$varValue = array();

		foreach ($this->arrFields as $key => $options)
		{
			/** @var Widget $widget */
			list($widget) = $this->prepareWidget($key, $this->varValue[$key] ?? null, $options);

			if (null === $widget)
			{
				continue;
			}

			$widget->validate();

			if ($widget->hasErrors())
			{
				$this->addError($GLOBALS['TL_LANG']['ERR']['general']);
			}
			else
			{
				$varValue[$key] = $widget->value;
			}
		}

		if ($this->hasErrors())
		{
			$this->class = 'error';
		}

		$this->varValue = $varValue;
	}

	public function generate(): string
	{
		// Make sure there is at least an empty array
		if (!\is_array($this->varValue) || array() === $this->varValue)
		{
			$this->varValue = array('');
		}

		$columns = array();
		$header = array();
		$footer = array();

		foreach ($this->arrFields as $key => $options)
		{
			if (\is_array($options['input_field_callback'] ?? null))
			{
				$widget = System::importStatic($options['input_field_callback'][0])->{$options['input_field_callback'][1]}($this->objDca);

				$header[] = array();
				$footer[] = array('description' => $widget->description ?? '');
				$columns[] = array(...Widget::getAttributesFromDca($options, $key), 'widget' => $widget);
				continue;
			}

			if (\is_callable($options['input_field_callback'] ?? null))
			{
				$widget = $options['input_field_callback']($this->objDca);

				$header[] = array();
				$footer[] = array('description' => $widget->description ?? '');
				$columns[] = array(...Widget::getAttributesFromDca($options, $key), 'widget' => $widget);
				continue;
			}

			list($widget, $data) = $this->prepareWidget($key, $this->varValue[$key] ?? null, $options);

			if (null !== $widget)
			{
				if ('be_widget' === $widget->template)
				{
					$header[] = array('label' => $widget->label ?? '', 'mandatory' => $widget->mandatory);
					$widget->label = null;
					$widget->template = $this->strTemplate;
				}
				else
				{
					$header[] = array();
				}

				$footer[] = array('description' => $widget->description ?? '');
				$columns[] = array(...$data, 'widget' => $widget->generateWithError(true));
			}
		}

		return System::getContainer()->get('twig')->render('@Contao/backend/widget/collection.html.twig', array(
			'id' => $this->strId,
			'style' => $this->style,
			'header' => $header,
			'showHeader' => !$this->allEmpty($header, 'label'),
			'footer' => $footer,
			'showFooter' => !$this->allEmpty($footer, 'description'),
			'columns' => $columns,
		));
	}

	public static function getAttributesFromDca($arrData, $strName, $varValue = null, $strField = '', $strTable = '', $objDca = null): array
	{
		if (isset($arrData['fields']) && !isset($attributes['fields']))
		{
			$arrData['eval']['fields'] = $arrData['fields'];
		}

		return parent::getAttributesFromDca($arrData, $strName, $varValue, $strField, $strTable, $objDca);
	}

	/**
	 * @return array{0: Widget|null, 1: array}
	 */
	private function prepareWidget(string $key, mixed $value, array $options): array
	{
		if (isset($this->widgets[$key]))
		{
			return $this->widgets[$key];
		}

		if (!isset($options['inputType']))
		{
			return array(null, array());
		}

		/** @var class-string<Widget> $widgetClass */
		$widgetClass = $GLOBALS['BE_FFL'][$options['inputType']];

		if (!class_exists($widgetClass))
		{
			return array(null, array());
		}

		$data = $widgetClass::getAttributesFromDca($options, $key, $value, $this->strField, $this->strTable, $this->objDca);

		$data['name'] = $this->strId . '[' . $data['name'] . ']';
		$data['id'] = $data['name'];

		return $this->widgets[$key] = array(new $widgetClass($data), $data);
	}

	private function allEmpty(array $values, string $key): bool
	{
		foreach ($values as $value)
		{
			if (!empty($value[$key]))
			{
				return false;
			}
		}

		return true;
	}
}
