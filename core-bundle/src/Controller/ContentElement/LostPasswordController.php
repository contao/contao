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
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Form\Type\ChangePasswordType;
use Contao\CoreBundle\Form\Type\LostPasswordType;
use Contao\CoreBundle\OptIn\OptInInterface;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Idna;
use Contao\MemberModel;
use Contao\PageModel;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsContentElement(category: 'miscellaneous')]
class LostPasswordController extends AbstractChangePasswordContentElementController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly RateLimiterFactoryInterface $rateLimiterFactory,
        private readonly OptInInterface $optIn,
        private readonly SimpleTokenParser $simpleTokenParser,
        private readonly LoggerInterface $logger,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $this->container->get('contao.framework')->initialize();
        $this->container->get('contao.framework')->getAdapter(System::class)->loadLanguageFile('tl_member');

        $this->executeOnloadCallbacks();

        $form = $this->createForm(
            LostPasswordType::class,
            [],
            [
                'askForUsername' => !$model->reg_skipName,
                'addCaptcha' => $model->enableCaptcha,
                'altchaAuto' => $model->altchaAuto,
                'altchaHideLogo' => $model->altchaHideLogo,
                'altchaHideFooter' => $model->altchaHideFooter,
                'altchaFloating' => $model->altchaFloating,
            ],
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var MemberModel $memberModelAdapter */
            $memberModelAdapter = $this->container->get('contao.framework')->getAdapter(MemberModel::class);
            $member = $model->reg_skipName ?
                $memberModelAdapter->findActiveByEmailAndUsername($form->get('email')->getData()) :
                $memberModelAdapter->findActiveByEmailAndUsername($form->get('email')->getData(), $form->get('username')->getData());

            if ($member instanceof MemberModel) {
                return $this->sendPasswordLink($member, $model, $request);
            }

            $template->set('error', $this->translator->trans('MSC.accountNotFound', [], 'contao_default'));
        }

        // Set new password
        if (str_starts_with($request->query->get('token', ''), 'pw-')) {
            return $this->setNewPassword($template, $model, $request);
        }

        $template->set('form', $form->createView());
        $template->set('ask_for_username', !$model->reg_skipName);
        $template->set('add_captcha', !$model->disableCaptcha);

        return $template->getResponse();
    }

    private function sendPasswordLink(MemberModel $member, ContentModel $model, Request $request): Response
    {
        $limiter = $this->rateLimiterFactory->create((string) $member->id);

        if (!$limiter->consume()->isAccepted()) {
            return $this->getErrorTemplateFoType('tooManyPasswordResetAttempts');
        }

        $optInToken = $this->optIn->create('pw', $member->email, ['tl_member' => [$member->id]]);
        $currentPage = $this->getPageModel();

        if (!$currentPage instanceof PageModel) {
            throw new PageNotFoundException('Page not found');
        }

        $data = $member->row();
        $data['activation'] = $optInToken->getIdentifier();
        $data['domain'] = Idna::decode($request->getHost());
        $data['link'] = Idna::decode($this->generateContentUrl($currentPage, ['token' => $optInToken->getIdentifier()], UrlGeneratorInterface::ABSOLUTE_URL));

        // Send the token
        $optInToken->send(
            \sprintf($this->translator->trans('MSC.passwordSubject', [], 'contao_default'), Idna::decode($request->getHost())),
            $this->simpleTokenParser->parse($model->reg_password, $data),
        );

        $this->logger->info('A new password has been requested for user ID '.$member->id.' ('.Idna::decodeEmail($member->email).')');

        /** @var PageModel $pageModelAdapter */
        $pageModelAdapter = $this->container->get('contao.framework')->getAdapter(PageModel::class);

        if ($jumpTo = $pageModelAdapter->findById($model->jumpTo)) {
            return new RedirectResponse($this->generateContentUrl($jumpTo));
        }

        return new RedirectResponse($this->generateContentUrl($currentPage));
    }

    private function setNewPassword(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $optInToken = $this->optIn->find($request->query->get('token'));

        if (!$optInToken || !$optInToken->isValid()) {
            return $this->getErrorTemplateFoType('invalidToken');
        }

        $related = $optInToken->getRelatedRecords();

        if (1 !== \count($related) || 'tl_member' !== key($related)) {
            return $this->getErrorTemplateFoType('invalidToken');
        }

        $ids = current($related);

        if (1 !== \count($ids)) {
            return $this->getErrorTemplateFoType('invalidToken');
        }

        $memberModelAdapter = $this->container->get('contao.framework')->getAdapter(MemberModel::class);
        $member = $memberModelAdapter->findById($ids[0]);

        if (null === $member) {
            return $this->getErrorTemplateFoType('invalidToken');
        }

        if ($optInToken->isConfirmed()) {
            return $this->getErrorTemplateFoType('tokenConfirmed');
        }

        if ($optInToken->getEmail() !== $member->email) {
            return $this->getErrorTemplateFoType('tokenEmailMismatch');
        }

        $form = $this->createForm(ChangePasswordType::class, []);
        $form->remove('oldpassword'); // Do not ask for the old password here, as we have a valid confirmation token.

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->updatePassword($model, $member, $form);

            if ($model->reg_jumpTo) {
                $pageModelAdapter = $this->container->get('contao.framework')->getAdapter(PageModel::class);
                $page = $pageModelAdapter->findById($model->reg_jumpTo);

                if ($page instanceof PageModel) {
                    return new RedirectResponse($this->generateContentUrl($page));
                }
            }

            $template = new FragmentTemplate('mod_message', static fn () => new Response());
            $template->set('type', 'confirm');
            $template->set('message', $this->translator->trans('MSC.newPasswordSet', [], 'contao_default'));

            return $template->getResponse();
        }

        $template->set('form', $form->createView());

        return $template->getResponse();
    }

    private function getErrorTemplateFoType(string $type): Response
    {
        $template = new FragmentTemplate('mod_message', static fn () => new Response());
        $template->set('type', 'error');
        $template->set('message', $this->translator->trans('MSC.'.$type, [], 'contao_default'));

        return $template->getResponse();
    }
}
