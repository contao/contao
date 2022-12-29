<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\Security;

use Contao\CoreBundle\Filesystem\Dbafs\UnableToResolveUuidException;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Slug\Slug;
use Contao\FilesModel;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\OAuthBundle\ClientGenerator;
use Doctrine\DBAL\Connection;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Uid\Uuid;

class Authenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientGenerator $clientGenerator,
        private Connection $db,
        private ContaoFramework $framework,
        private RequestStack $requestStack,
        private VirtualFilesystem $filesStorage,
        private Slug $slug,
        private string $projectDir
    )
    {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'contao_oauth_check';
    }

    public function authenticate(Request $request): Passport
    {
        $session = $request->getSession();
        $clientId = (int) $session->get('_oauth_client_id');
        $client = $this->clientGenerator->getClientById($session->get('_oauth_client_id'));
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $clientId, $client, $session): UserInterface {
                $user = $client->fetchUserFromToken($accessToken);

                $this->framework->initialize();

                /** @var FrontendUser $frontendUser */
                $frontendUser = $this->framework->getAdapter(FrontendUser::class);

                $existingMember = $this->db->fetchOne("
                    SELECT m.username
                      FROM tl_member AS m, tl_member_oauth AS o
                     WHERE o.pid = m.id
                       AND o.oauthClient = ?
                       AND o.oauthId = ?
                ", [$clientId, $user->getId()]);

                if (false !== $existingMember) {                 
                    return $frontendUser->loadUserByIdentifier($existingMember);
                }

                $module = ModuleModel::findByPk($session->get('_oauth_module_id'));

                if (null === $module) {
                    throw new \RuntimeException('Invalid module ID in session.');
                }

                $this->db->insert('tl_member', [
                    'tstamp' => time(),
                    'login' => 1,
                    'groups' => $module->reg_groups,
                    'username' => $user->getId(),
                    'dateAdded' => time(),
                ]);

                $userId = $this->db->lastInsertId();

                $this->db->insert('tl_member_oauth', [
                    'tstamp' => time(),
                    'pid' => $userId,
                    'oauthClient' => $clientId,
                    'oauthId' => $user->getId(),
                ]);

                $user = $frontendUser->loadUserByIdentifier($user->getId());

                if ($module->reg_assignDir) {
                    $this->assignHomeDir($user, $module);
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $session = $request->getSession();

        $url = $session->get('_oauth_redirect', $request->getSchemeAndHttpHost());

        $session->remove('_oauth_redirect');
        $session->remove('_oauth_client_id');
        $session->remove('_oauth_module_id');

        return new RedirectResponse($url);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $session = $request->getSession();
        $session->remove('_oauth_redirect');
        $session->remove('_oauth_client_id');
        $session->remove('_oauth_module_id');

        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    private function assignHomeDir(FrontendUser $user, ModuleModel $model): void
    {
        try {
            $homeDir = $this->filesStorage->get(Uuid::fromBinary($model->reg_homeDir));
            $userDir = $this->slug->generate($user->username);

            while ($this->filesStorage->directoryExists(Path::join($homeDir->getPath(), $userDir))) {
                $userDir .= '_'.$user->id;
            }

            $finalDir = Path::join($homeDir->getPath(), $userDir);

            $this->filesStorage->createDirectory($finalDir);

            $userDirModel = FilesModel::findByPath('files/'.Path::normalize($finalDir));

            $user->assignDir = 1;
            $user->homeDir = $userDirModel->uuid;
            $user->save();
        } catch (UnableToResolveUuidException $e) {
            // Do nothing
        }
    }
}
