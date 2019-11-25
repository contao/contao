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
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\Date;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Error\Error as TwigError;

/**
 * This controller serves for the back end preview toolbar by providing the following ajax endpoints:
 * a) Return the toolbar html (dispatched in an ajax request to allow lazy loading and force back end scope)
 * b) Provide members' usernames for the datalist
 * c) Process the switch action (i.e. log in a specific front end user).
 *
 * @Route(defaults={"_scope" = "backend"})
 */
class BackendPreviewSwitchController
{
    private $contaoFramework;

    private $frontendPreviewAuthenticator;

    private $tokenChecker;

    private $connection;

    private $security;

    private $twig;

    private $tokenManager;

    private $csrfTokenName;

    private $router;

    public function __construct(
        ContaoFramework $contaoFramework,
        FrontendPreviewAuthenticator $frontendPreviewAuthenticator,
        TokenChecker $tokenChecker,
        Connection $connection,
        Security $security,
        TwigEnvironment $twig,
        RouterInterface $router,
        CsrfTokenManagerInterface $tokenManager,
        string $csrfTokenName
    ) {
        $this->contaoFramework = $contaoFramework;
        $this->frontendPreviewAuthenticator = $frontendPreviewAuthenticator;
        $this->tokenChecker = $tokenChecker;
        $this->connection = $connection;
        $this->security = $security;
        $this->twig = $twig;
        $this->router = $router;
        $this->tokenManager = $tokenManager;
        $this->csrfTokenName = $csrfTokenName;
    }

    /**
     * @Route("/contao/preview_switch", name="contao_backend_preview_switch")
     */
    public function __invoke(Request $request): Response
    {
        $this->contaoFramework->initialize(false);

        $user = $this->security->getUser();

        if (!($user instanceof BackendUser) || !$request->isXmlHttpRequest()) {
            throw new PageNotFoundException('Bad response');
        }

        if ($request->isMethod('GET')) {
            $toolbar = $this->renderToolbar($user);

            return Response::create($toolbar);
        }

        if ('tl_switch' === $request->request->get('FORM_SUBMIT')) {
            $this->authenticatePreview($user, $request);

            return Response::create();
        }

        if ('datalist_members' === $request->request->get('FORM_SUBMIT')) {
            $data = $this->getMembersDataList($user, $request);

            return JsonResponse::create($data);
        }

        return Response::create('', 404);
    }

    /**
     * @throws TwigError
     */
    private function renderToolbar(BackendUser $user): string
    {
        $canSwitchUser = ($user->isAdmin || (!empty($user->amg) && \is_array($user->amg)));
        $frontendUsername = $this->tokenChecker->getFrontendUsername();
        $showUnpublished = $this->tokenChecker->isPreviewMode();

        return $this->twig->render(
            '@ContaoCore/Frontend/preview_toolbar_base.html.twig',
            [
                'request_token' => $this->tokenManager->getToken($this->csrfTokenName)->getValue(),
                'action' => $this->router->generate('contao_backend_preview_switch'),
                'canSwitchUser' => $canSwitchUser,
                'user' => $frontendUsername,
                'show' => $showUnpublished,
            ]
        );
    }

    private function authenticatePreview(BackendUser $user, Request $request): void
    {
        $canSwitchUser = $this->isAllowedToAccessMembers($user);
        $frontendUsername = $this->tokenChecker->getFrontendUsername();
        $showUnpublished = 'hide' !== $request->request->get('unpublished');

        if ($canSwitchUser) {
            $frontendUsername = $request->request->get('user') ?: null;
        }

        if (null !== $frontendUsername) {
            $this->frontendPreviewAuthenticator->authenticateFrontendUser($frontendUsername, $showUnpublished);
        } else {
            $this->frontendPreviewAuthenticator->authenticateFrontendGuest($showUnpublished);
        }
    }

    private function getMembersDataList(BackendUser $user, Request $request): array
    {
        $andWhereGroups = '';

        if (!$this->isAllowedToAccessMembers($user)) {
            return [];
        }

        if (!$user->isAdmin) {
            $groups = array_map(
                static function ($groupId) {
                    return '%"'.(int) $groupId.'"%';
                },
                $user->amg
            );

            $andWhereGroups = "AND (groups LIKE '".implode("' OR GROUPS LIKE '", $groups)."')";
        }

        $time = Date::floorToMinute();

        // Get the active front end users
        $result = $this->connection->executeQuery(
            sprintf(
                <<<'SQL'
SELECT username 
FROM tl_member 
WHERE username LIKE ?
%s 
AND login='1' AND disable!='1' AND (start='' OR start<='%s') AND (stop='' OR stop>'%d')
ORDER BY username
SQL
                ,
                $andWhereGroups,
                $time,
                $time + 60
            ),
            [str_replace('%', '', $request->request->get('value')).'%']
        );

        return $result->fetchAll(FetchMode::COLUMN);
    }

    private function isAllowedToAccessMembers(BackendUser $user): bool
    {
        return $user->isAdmin || (!empty($user->amg) && \is_array($user->amg));
    }
}
