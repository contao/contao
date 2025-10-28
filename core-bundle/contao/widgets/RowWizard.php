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

/**
 * Provides methods to handle multiple rows, with each row containing multiple widgets.
 */
class RowWizard extends Widget
{
	protected $blnSubmitInput = true;

	protected $strTemplate = 'be_widget';

	protected array $arrFields = array();

	private int|null $min = null;

	private int|null $max = null;

	private bool $sortable = true;

	private array $actions = array('copy', 'delete');

	private array $widgets = array();

	public function __construct($arrAttributes = null)
	{
		parent::__construct($arrAttributes);

		$this->preserveTags = true;
		$this->decodeEntities = true;

		System::loadLanguageFile('default');
	}

	/**
	 * Add specific attributes.
	 */
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

			case 'min':
				$this->min = $varValue ?? null;
				break;

			case 'max':
				$this->max = $varValue ?? null;
				break;

			case 'sortable':
				$this->sortable = (bool) $varValue;
				break;

			case 'actions':
				if (\is_array($varValue))
				{
					$this->actions = array_intersect(array('copy', 'delete', 'enable'), $varValue);
				}
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
			'min' => $this->min,
			'max' => $this->max,
			'sortable' => $this->sortable,
			'actions' => $this->actions,
			default => parent::__get($strKey),
		};
	}

	public function validate(): void
	{
		$varValue = array();
		$varPost = $this->getPost($this->strId . '[_rows]') ?? array();

		for ($i = 0, $c = \count($varPost); $i < $c; ++$i)
		{
			foreach ($this->arrFields as $key => $options)
			{
				/** @var Widget $widget */
				list($widget) = $this->prepareWidget($key, $this->varValue[$i][$key] ?? null, $options, $i);

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
					$varValue[$i][$key] = $widget->value;
				}
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
			$this->varValue = array(array(''));
		}

		// Populate the rows if the initial count has not been reached
		if (null !== $this->min)
		{
			$rowCount = \count($this->varValue);

			while ($rowCount < $this->min)
			{
				$this->varValue[] = array('');
				++$rowCount;
			}
		}

		$header = $rows = array();

		for ($i = 0, $c = \count($this->varValue); $i < $c; ++$i)
		{
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

				list($widget, $data) = $this->prepareWidget($key, $this->varValue[$i][$key] ?? null, $options, $i);

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

			$rows[$i] = array(
				'columns' => $columns,
				'controls' => array(
					'enable' => $this->varValue[$i]['enable'] ?? false,
					'edit' => ($this->varValue[$i]['id'] ?? 0) > 0,
				),
			);
		}

		return System::getContainer()->get('twig')->render('@Contao/backend/widget/row_wizard.html.twig', array(
			'id' => $this->strId,
			'style' => $this->style,
			'header' => $header,
			'showHeader' => !$this->allEmpty($header, 'label'),
			'footer' => $footer,
			'showFooter' => !$this->allEmpty($footer, 'description'),
			'rows' => $rows,
			'min_rows' => $this->min,
			'max_rows' => $this->max,
			'sortable' => $this->sortable,
			'actions' => $this->actions,
		));
	}

	public static function getAttributesFromDca($arrData, $strName, $varValue = null, $strField = '', $strTable = '', $objDca = null): array
	{
		$attributes = parent::getAttributesFromDca($arrData, $strName, $varValue, $strField, $strTable, $objDca);

		if (isset($arrData['fields']) && !isset($attributes['fields']))
		{
			$attributes['fields'] = $arrData['fields'];
		}

		return $attributes;
	}

	/**
	 * @return array{0: Widget|null, 1: array<mixed>}
	 */
	private function prepareWidget(string $key, mixed $value, array $options, int $increment): array
	{
		if (isset($this->widgets[$increment][$key]))
		{
			return $this->widgets[$increment][$key];
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

		$data['name'] = $this->strId . '[' . $increment . '][' . $data['name'] . ']';

		if (\in_array($data['type'] ?? null, array('checkbox', 'label'), true))
		{
			$data['id'] = $data['name'];
		}
		else
		{
			$data['id'] .= '_' . $increment;
		}

		return $this->widgets[$increment][$key] = array(new $widgetClass($data), $data);
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
