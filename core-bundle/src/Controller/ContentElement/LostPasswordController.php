<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Form\Type\LostPasswordType;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(category: 'miscellaneous')]
class LostPasswordController extends AbstractContentElementController
{
    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $this->framework->initialize();
        $this->framework->getAdapter(System::class)->loadLanguageFile('tl_member');

        $this->executeOnloadCallbacks();

        $form = $this->createForm(
            LostPasswordType::class,
            [],
            [
                'askForUsername' => !$model->reg_skipName,
                'addCaptcha' => !$model->disableCaptcha,
            ],
        );
        $form->handleRequest($request);

        // Set new password
        if (str_starts_with($request->query->get('token', ''), 'pw-')) {
            // $this->setNewPassword();

            return $template->getResponse();
        }

        $template->set('form', $form->createView());
        $template->set('ask_for_username', !$model->reg_skipName);
        $template->set('add_captcha', !$model->disableCaptcha);

        return $template->getResponse();
    }

    private function executeOnloadCallbacks(): void
    {
        $this->framework->getAdapter(Controller::class)->loadDataContainer('tl_member');

        if (\is_array($GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'] ?? null)) {
            foreach ($GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'] as $callback) {
                if (\is_array($callback)) {
                    $this->framework->getAdapter(System::class)->importStatic($callback[0])->{$callback[1]}();
                } elseif (\is_callable($callback)) {
                    $callback();
                }
            }
        }
    }
}
