<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Patchwork\Utf8;

/**
 * Front end module "close account".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleCloseAccount extends Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_closeAccount';

	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['closeAccount'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Return if there is no logged in user
		if (!FE_USER_LOGGED_IN)
		{
			return '';
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$this->import(FrontendUser::class, 'User');

		// Initialize the password widget
		$arrField = array
		(
			'name' => 'password',
			'inputType' => 'text',
			'label' => $GLOBALS['TL_LANG']['MSC']['password'][0],
			'eval' => array('hideInput'=>true, 'preserveTags'=>true, 'mandatory'=>true, 'required'=>true)
		);

		$objWidget = new FormTextField(FormTextField::getAttributesFromDca($arrField, $arrField['name']));
		$objWidget->rowClass = 'row_0 row_first even';

		$strFormId = 'tl_close_account_' . $this->id;

		// Validate widget
		if (Input::post('FORM_SUBMIT') == $strFormId)
		{
			$objWidget->validate();

			// Validate the password
			if (!$objWidget->hasErrors() && !password_verify($objWidget->value, $this->User->password))
			{
				$objWidget->value = '';
				$objWidget->addError($GLOBALS['TL_LANG']['ERR']['invalidPass']);
			}

			// Close account
			if (!$objWidget->hasErrors())
			{
				// HOOK: send account ID
				if (isset($GLOBALS['TL_HOOKS']['closeAccount']) && \is_array($GLOBALS['TL_HOOKS']['closeAccount']))
				{
					foreach ($GLOBALS['TL_HOOKS']['closeAccount'] as $callback)
					{
						$this->import($callback[0]);
						$this->{$callback[0]}->{$callback[1]}($this->User->id, $this->reg_close, $this);
					}
				}

				$objMember = MemberModel::findByPk($this->User->id);

				// Remove the account
				if ($this->reg_close == 'close_delete')
				{
					$objMember->delete();
					$this->log('User account ID ' . $this->User->id . ' (' . Idna::decodeEmail($this->User->email) . ') has been deleted', __METHOD__, TL_ACCESS);
				}
				// Deactivate the account
				else
				{
					$objMember->disable = 1;
					$objMember->tstamp = time();
					$objMember->save();
					$this->log('User account ID ' . $this->User->id . ' (' . Idna::decodeEmail($this->User->email) . ') has been deactivated', __METHOD__, TL_ACCESS);
				}

				$container = System::getContainer();

				// Log out the user (see #93)
				$container->get('security.token_storage')->setToken(null);
				$container->get('session')->invalidate();

				// Check whether there is a jumpTo page
				if (($objJumpTo = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
				{
					$this->jumpToOrReload($objJumpTo->row());
				}

				$this->reload();
			}
		}

		$this->Template->fields = $objWidget->parse();

		$this->Template->formId = $strFormId;
		$this->Template->action = Environment::get('indexFreeRequest');
		$this->Template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['closeAccount']);
		$this->Template->rowLast = 'row_1 row_last odd';
	}
}

class_alias(ModuleCloseAccount::class, 'ModuleCloseAccount');
