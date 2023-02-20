<?php

namespace Contao\CoreBundle\Security\Authenticator;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendUser;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\FacebookUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class FacebookAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
        private Connection $db,
        private ContaoFramework $framework
    )
    {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_facebook_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('facebook');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                /** @var FacebookUser $facebookUser */
                $facebookUser = $client->fetchUserFromToken($accessToken);
                $this->framework->initialize();

                /** @var FrontendUser $frontendUser */
                $frontendUser = $this->framework->getAdapter(FrontendUser::class);

                $existingMember = $this->db->fetchOne('SELECT username FROM tl_member WHERE facebookId = ? OR email = ?', [$facebookUser->getId(), $facebookUser->getEmail()]);

                if ($existingMember) {                 
                    return $frontendUser->loadUserByIdentifier($existingMember);
                }

                $this->db->insert('tl_member', [
                    'tstamp' => time(),
                    'facebookId' => $facebookUser->getId(),
                    'firstname' => $facebookUser->getFirstName(),
                    'lastname' => $facebookUser->getLastName(),
                    'email' => $facebookUser->getEmail(),
                    'username' => $facebookUser->getEmail(),
                    'login' => 1,
                    'groups' => serialize([1]),
                ]);

                return $frontendUser->loadUserByIdentifier($facebookUser->getEmail());
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($request->getSchemeAndHttpHost());
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }
}
