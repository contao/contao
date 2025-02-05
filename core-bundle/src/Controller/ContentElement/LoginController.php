<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
use Contao\PageModel;
use Nyholm\Psr7\Uri;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsContentElement]
class LoginController extends AbstractContentElementController
{
    public function __construct(
        private readonly Security $security,
        private readonly UriSigner $uriSigner,
        private readonly LogoutUrlGenerator $logoutUrlGenerator,
        private readonly AuthenticationUtils $authenticationUtils,
        private readonly TranslatorInterface $translator,
        private readonly ContentUrlGenerator $contentUrlGenerator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ContaoFramework $framework,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if (($user = $this->security->getUser()) && !$user instanceof FrontendUser) {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }

        $targetPath = $this->getTargetPath($request);
        $isRemembered = $this->security->isGranted('IS_REMEMBERED');
        $isTwoFactorInProgress = $this->security->isGranted('IS_AUTHENTICATED_2FA_IN_PROGRESS');
        $page = $this->getPageModel();
        $redirect = $request->getUri();

        // The user can re-authenticate on the error_401 page or on the redirect page of
        // the error_401 page
        $canReauthenticate = 'error_401' === $page?->type || ($targetPath && $targetPath === $request->query->get('redirect'));

        // Show the logout button if the user is fully authenticated or cannot
        // re-authenticate on the current page
        if ($user instanceof FrontendUser && !$isTwoFactorInProgress && (!$isRemembered || !$canReauthenticate)) {
            // Redirect to last page visited
            if ($model->redirectBack && $targetPath) {
                $redirect = $targetPath;
            }

            // Redirect home if the page is protected
            elseif ($page?->protected) {
                $redirect = $request->getSchemeAndHttpHost().$request->getBasePath().'/';
            }

            $template->logout = true;
            $template->formId = 'tl_logout_'.$model->id;
            $template->slabel = $this->translator->trans('MSC.logout', [], 'contao_default');
            $template->action = $this->logoutUrlGenerator->getLogoutPath();
            $template->targetPath = $redirect;

            return $template->getResponse();
        }

        $exception = null;
        $lastUsername = '';

        // Only call the authentication utils if there is an active session to prevent
        // starting an empty session
        if ($request->hasSession() && ($request->hasPreviousSession() || $request->getSession()->isStarted())) {
            $exception = $this->authenticationUtils->getLastAuthenticationError();
            $lastUsername = $this->authenticationUtils->getLastUsername();

            // Store exception and username again to make it available for additional login
            // modules on the page (see contao/contao#6275)
            if ($exception) {
                $request->attributes->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
            }

            if ($lastUsername) {
                $request->attributes->set(SecurityRequestAttributes::LAST_USERNAME, $lastUsername);
            }
        }

        if ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
            $template->message = $this->translator->trans('ERR.tooManyLoginAttempts', [], 'contao_default');
        } elseif ($exception instanceof InvalidTwoFactorCodeException) {
            $template->message = $this->translator->trans('ERR.invalidTwoFactor', [], 'contao_default');
        } elseif ($exception instanceof AuthenticationException) {
            $template->message = $this->translator->trans('ERR.invalidLogin', [], 'contao_default');
        }

        $redirectBack = false;
        $pageAdapter = $this->framework->getAdapter(PageModel::class);

        // Redirect to the last page visited
        if ($model->redirectBack && $targetPath) {
            $redirectBack = true;
            $redirect = $targetPath;
        }

        // Redirect to the jumpTo page
        elseif ($targetPage = $pageAdapter->findById($model->jumpTo)) {
            $redirect = $this->contentUrlGenerator->generate($targetPage, [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $template->formId = 'tl_login_'.$model->id;
        $template->forceTargetPath = (int) $redirectBack;
        $template->targetPath = base64_encode($redirect);

        if ($isTwoFactorInProgress && $request) {
            // Dispatch 2FA form event to prepare 2FA providers
            $token = $this->security->getToken();
            $event = new TwoFactorAuthenticationEvent($request, $token);
            $this->eventDispatcher->dispatch($event, TwoFactorAuthenticationEvents::FORM);

            $template->twoFactorEnabled = true;
            $template->slabel = $this->translator->trans('MSC.continue', [], 'contao_default');

            return $template->getResponse();
        }

        if ($pwResetPage = $pageAdapter->findById($model->pwResetPage)) {
            $template->pwResetUrl = $this->contentUrlGenerator->generate($pwResetPage);
        }

        $template->slabel = $this->translator->trans('MSC.login', [], 'contao_default');
        $template->value = $lastUsername;
        $template->autologin = $model->autologin;
        $template->remembered = false;
        $template->redirect = $redirect;

        if ($isRemembered) {
            $template->slabel = $this->translator->trans('MSC.verify', [], 'contao_default');
            $template->value = $user->getUserIdentifier();
            $template->remembered = true;
        }

        return $template->getResponse();
    }

    private function getTargetPath(Request $request): string|null
    {
        // If the form was submitted and the credentials were wrong, take the target path
        // from the submitted data as otherwise it would take the current page
        if ($request->isMethod('POST')) {
            return base64_decode($request->request->get('_target_path'), true);
        }

        if ($request->query->has('redirect')) {
            if ($this->uriSigner->checkRequest($request)) {
                return $request->query->get('redirect');
            }
        } elseif ($referer = $request->headers->get('referer')) {
            $refererUri = new Uri($referer);
            $requestUri = new Uri($request->getUri());

            // Use the HTTP referer as a fallback, but only if scheme and host matches with
            // the current request (see #5860)
            if ($refererUri->getScheme() === $requestUri->getScheme() && $refererUri->getHost() === $requestUri->getHost() && $refererUri->getPort() === $requestUri->getPort()) {
                return (string) $refererUri;
            }
        }

        return null;
    }
}
