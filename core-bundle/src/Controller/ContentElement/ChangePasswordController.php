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
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Form\Type\ChangePasswordType;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(category: 'user')]
class ChangePasswordController extends AbstractChangePasswordContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof FrontendUser) {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }

        $this->container->get('contao.framework')->initialize();

        $memberModelAdapter = $this->container->get('contao.framework')->getAdapter(MemberModel::class);
        $member = $memberModelAdapter->findById($user->id);

        if (!$member instanceof MemberModel) {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }

        $this->executeOnloadCallbacks();

        $form = $this->createForm(ChangePasswordType::class, [], ['attr' => ['id' => 'tl_change_password_'.$model->id]]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->updatePassword($model, $member, $form);

            $request->getSession()->migrate();
            $user->findBy('id', $member->id);

            if ($model->jumpTo) {
                $pageModelAdapter = $this->container->get('contao.framework')->getAdapter(PageModel::class);
                $page = $pageModelAdapter->findById($model->jumpTo);

                if ($page instanceof PageModel) {
                    return new RedirectResponse($this->generateContentUrl($page));
                }
            }
        }

        $template->set('form', $form->createView());

        return $template->getResponse();
    }
}
