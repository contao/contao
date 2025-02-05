<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\BackendUser;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Date;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Error\Error as TwigError;

/**
 * This controller serves for the back end preview toolbar by providing the
 * following ajax endpoints:
 *
 * - Return the toolbar HTML (dispatched in an Ajax request to allow lazy loading and force back end scope)
 * - Provide the member usernames for the datalist
 * - Process the switch action (i.e. log in a specific front end user)
 */
#[Route('%contao.backend.route_prefix%/preview_switch', name: 'contao_backend_switch', defaults: ['_scope' => 'backend', '_allow_preview' => true, '_store_referrer' => false])]
class BackendPreviewSwitchController
{
    public function __construct(
        private readonly FrontendPreviewAuthenticator $previewAuthenticator,
        private readonly TokenChecker $tokenChecker,
        private readonly Connection $connection,
        private readonly Security $security,
        private readonly TwigEnvironment $twig,
        private readonly RouterInterface $router,
        private readonly ContaoCsrfTokenManager $tokenManager,
        private readonly TranslatorInterface $translator,
        private readonly array $backendAttributes = [],
        private readonly string $backendBadgeTitle = '',
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser || !$request->isXmlHttpRequest()) {
            return new Response('Bad Request', Response::HTTP_BAD_REQUEST);
        }

        if ($request->isMethod('GET')) {
            return new Response($this->renderToolbar());
        }

        if ('tl_switch' === $request->request->get('FORM_SUBMIT')) {
            return $this->authenticatePreview($request);
        }

        if ('datalist_members' === $request->request->get('FORM_SUBMIT')) {
            $data = $this->getMembersDataList($user, $request);

            return new JsonResponse($data);
        }

        return new Response('', Response::HTTP_BAD_REQUEST);
    }

    private function renderToolbar(): string
    {
        $canSwitchUser = $this->security->isGranted('ROLE_ALLOWED_TO_SWITCH_MEMBER');
        $frontendUsername = $this->tokenChecker->getFrontendUsername();
        $showUnpublished = $this->tokenChecker->isPreviewMode();
        $shareLink = '';

        if ($this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'preview_link')) {
            $shareLink = $this->router->generate('contao_backend', [
                'do' => 'preview_link',
                'act' => 'create',
                'showUnpublished' => $showUnpublished,
                'rt' => $this->tokenManager->getDefaultTokenValue(),
                'nb' => '1', // Do not show the "Save & Close" button
            ]);
        }

        try {
            return $this->twig->render('@ContaoCore/Frontend/preview_toolbar_base.html.twig', [
                'request_token' => $this->tokenManager->getDefaultTokenValue(),
                'action' => $this->router->generate('contao_backend_switch'),
                'canSwitchUser' => $canSwitchUser,
                'user' => $frontendUsername,
                'show' => $showUnpublished,
                'attributes' => $this->backendAttributes,
                'badgeTitle' => $this->backendBadgeTitle,
                'share' => $shareLink,
            ]);
        } catch (TwigError $e) {
            return 'Error while rendering twig template: '.$e->getMessage();
        }
    }

    private function authenticatePreview(Request $request): Response
    {
        $frontendUsername = $this->tokenChecker->getFrontendUsername();

        if ($this->security->isGranted('ROLE_ALLOWED_TO_SWITCH_MEMBER')) {
            $frontendUsername = $request->request->get('user');

            // Logout the current logged-in user if an empty user is submitted
            if ('' === $frontendUsername && null !== $this->tokenChecker->getFrontendUsername()) {
                $this->previewAuthenticator->removeFrontendAuthentication();
                $frontendUsername = null;
            }
        }

        $showUnpublished = 'hide' !== $request->request->get('unpublished');

        if ($frontendUsername) {
            if (!$this->previewAuthenticator->authenticateFrontendUser((string) $frontendUsername, $showUnpublished)) {
                $message = $this->translator->trans('ERR.previewSwitchInvalidUsername', [$frontendUsername], 'contao_default');

                return new Response($message, Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } else {
            $this->previewAuthenticator->authenticateFrontendGuest($showUnpublished);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    private function getMembersDataList(BackendUser $user, Request $request): array
    {
        $andWhereGroups = '';

        if (!$this->security->isGranted('ROLE_ALLOWED_TO_SWITCH_MEMBER')) {
            return [];
        }

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $groups = array_map(static fn ($groupId): string => '%"'.(int) $groupId.'"%', $user->amg);
            $andWhereGroups = "AND (`groups` LIKE '".implode("' OR `groups` LIKE '", $groups)."')";
        }

        $time = Date::floorToMinute();

        // Get the active front end users
        $query = "
            SELECT
                username
            FROM
                tl_member
            WHERE
                username LIKE ? $andWhereGroups
                AND login=1
                AND disable=0
                AND (start='' OR start<=$time)
                AND (stop='' OR stop>$time)
            ORDER BY
                username
        ";

        $query = $this->connection->getDatabasePlatform()->modifyLimitQuery($query, 20);

        return $this->connection
            ->executeQuery($query, [str_replace('%', '', $request->request->get('value')).'%'])
            ->fetchFirstColumn()
        ;
    }
}
