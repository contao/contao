<?php

namespace Contao\CoreBundle\Security\TwoFactor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\HttpUtils;

class FrontendAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /**
     * @var HttpUtils
     */
    private $httpUtils;

    public function __construct(HttpUtils $httpUtils)
    {
        $this->httpUtils = $httpUtils;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $user = $token->getUser();
        $request->getSession()->remove(Security::AUTHENTICATION_ERROR);

        // Enable 2FA
        $user->useTwoFactor = '1';
        $user->save();

        return $this->httpUtils->createRedirectResponse($request, $this->determineRedirectTargetUrl($request));
    }

    private function determineRedirectTargetUrl(Request $request): string
    {
        return $request->request->get('_target_path');
    }
}
