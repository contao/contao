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
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Event\NewPasswordEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\OptInModel;
use Contao\PageModel;
use Contao\System;
use Contao\Versions;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsContentElement(category: 'miscellaneous')]
class ChangePasswordController extends AbstractContentElementController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ContaoCsrfTokenManager $contaoCsrfTokenManager,
        private readonly ParameterBagInterface $parameterBag,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly ContentUrlGenerator $contentUrlGenerator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RouterInterface $router,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $formId = 'tl_change_password_'.$model->id;
        $user = $this->getUser();

        $template->set('has_member', $user instanceof FrontendUser);
        $template->set('form_id', $formId);

        if (!$user instanceof FrontendUser) {
            return $template->getResponse();
        }

        $this->framework->initialize();

        $memberModelAdapter = $this->framework->getAdapter(MemberModel::class);
        $member = $memberModelAdapter->findById($user->id);

        if (!$member instanceof MemberModel) {
            return $template->getResponse();
        }

        $this->executeOnloadCallbacks();

        $form = $this->getChangePasswordForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $versions = $this->framework->createInstance(Versions::class, ['tl_member', $member->id]);
            $versions->setUsername($member->username);
            $versions->setEditUrl($this->router->generate('contao_backend', ['do' => 'member', 'act' => 'edit', 'id' => $member->id]));
            $versions->initialize();

            $passwordHasher = $this->passwordHasherFactory->getPasswordHasher(FrontendUser::class);
            $hashedPassword = $passwordHasher->hash($request->request->get('password'));

            $member->tstamp = time();
            $member->password = $hashedPassword;
            $member->save();

            // Delete unconfirmed "change password" tokens
            $tokens = $this->framework->getAdapter(OptInModel::class)->findUnconfirmedByRelatedTableAndId('tl_member', $member->id);

            foreach ($tokens ?? [] as $token) {
                $token->delete();
            }

            if ($GLOBALS['TL_DCA']['tl_member']['config']['enableVersioning'] ?? null) {
                $versions->create();
            }

            $this->eventDispatcher->dispatch(new NewPasswordEvent($member, $request->request->get('password'), $hashedPassword));

            $request->getSession()->migrate();
            $user->findBy('id', $member->id);

            if ($model->jumpTo) {
                $pageModelAdapter = $this->framework->getAdapter(PageModel::class);
                $page = $pageModelAdapter->findById($model->jumpTo);

                if ($page instanceof PageModel) {
                    return new RedirectResponse($this->contentUrlGenerator->generate($page));
                }
            }
        }

        $template->set('form', $form->createView());

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

    private function getChangePasswordForm(): FormInterface
    {
        return $this->createFormBuilder([], [
            'csrf_field_name' => 'REQUEST_TOKEN',
            'csrf_token_manager' => $this->contaoCsrfTokenManager,
            'csrf_token_id' => $this->parameterBag->get('contao.csrf_token_name'),
            'translation_domain' => 'contao_default',
        ])
            ->add(
                'oldpassword',
                PasswordType::class,
                [
                    'constraints' => [
                        new UserPassword(message: 'MSC.oldPasswordWrong'),
                    ],
                    'label' => 'MSC.oldPassword',
                    'required' => true,
                    'attr' => [
                        'mandatory' => true,
                        'autocomplete' => 'current-password',
                        'class' => 'text password mandatory',
                    ],
                ],
            )
            ->add(
                'newpassword',
                PasswordType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                        new Length(min: 8),
                    ],
                    'label' => 'MSC.newPassword',
                    'required' => true,
                    'attr' => [
                        'mandatory' => true,
                        'autocomplete' => 'new-password',
                        'class' => 'text password mandatory',
                    ],
                ],
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'MSC.changePassword',
                    'attr' => [
                        'class' => 'submit',
                    ],
                ],
            )
            ->getForm()
        ;
    }
}
