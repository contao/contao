<?php

namespace Contao;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\String\HtmlAttributes;

class View
{
	protected string $mode = '';

	protected string $intMode;

	protected $dc;

	protected $table;

	public function __construct($dc, $table, $intMode)
	{
		$this->dc = $dc;
		$this->table = $table;
		$this->intMode = $intMode;
	}

	public function getContext()
	{
		return [
			'mode' => $this->mode,
			'request' => System::getContainer()->get('request_stack')->getCurrentRequest()
		];
	}

	public function getClipboardStuff(){
		$twig = System::getContainer()->get('twig');
		$arrClipboard = System::getContainer()->get('contao.data_container.clipboard_manager')->get($this->table);
		$blnClipboard = null !== $arrClipboard;

		$arrHeader = [];

		if($blnClipboard){
			$arrHeader['help'] = $twig->render('@Contao/backend/listing/be_hint.html.twig', ['label' => $GLOBALS['TL_LANG']['MSC']['selectNewPosition'], 'context' => $this->getContext()]);
		}

		if($this->dc->strPickerFieldType == 'checkbox'){
			$arrHeader['breadcrumbs'] = $twig->render('@Contao/backend/listing/be_select_all.html.twig', ['label' => $GLOBALS['TL_LANG']['MSC']['selectAll'], 'context' => $this->getContext()]);
		}
		return $arrHeader;
	}

	public function renderSelectForm($children, $strActions = null, HtmlAttributes|array $attributes = [], $context = '')
	{
		$twig = System::getContainer()->get('twig');

		if(!$context){
			$context = $this->getContext();
		}

		$objAttributes = new HtmlAttributes([
			'id' => 'tl_select',
			'class' => (Input::get('act') == 'select') ? ' unselectable' : '',
			'method' => 'post',
			'novalidate' => true
		]);
		$objAttributes->mergeWith($attributes);

		return $twig->render('@Contao/backend/listing/be_select_form.html.twig', [
			'attributes' => $objAttributes->addClass('tl_form'),
			'submit' => 'tl_select',
			'rt' => htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
			'children' => $children,
			'actions' => $strActions,
			'emptyLabel' => $GLOBALS['TL_LANG']['MSC']['noResult'],
			'context' => $this->getContext()
		]);
	}
}
