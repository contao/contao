<?php

namespace Contao;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\String\HtmlAttributes;

abstract class View
{
	protected string $mode = '';

	protected $dc;

	public function __construct($dc)
	{
		$this->dc = $dc;
	}

	public function getContext()
	{
		return [
			'mode' => $this->mode,
			'request' => System::getContainer()->get('request_stack')->getCurrentRequest()
		];
	}

	protected function renderSelectForm($children, $strActions)
	{
		$twig = System::getContainer()->get('twig');

		$objAttributes = new HtmlAttributes([
			'id' => 'tl_select',
			'class' => (Input::get('act') == 'select') ? ' unselectable' : '',
			'method' => 'post',
			'novalidate' => true
		]);

		return $twig->render('@Contao/backend/listing/be_form.html.twig', [
			'attributes' => $objAttributes->addClass('tl_form'),
			'submit' => 'tl_select',
			'rt' => htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue()),
			'children' => $children,
			'actions' => $strActions,
			'emptyLabel' => $GLOBALS['TL_LANG']['MSC']['noResult'],
			'context' => $this->getContext()
		]);
	}
}
