<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Security\TwoFactor;

use Scheb\TwoFactorBundle\Security\Http\Authentication\AuthenticationRequiredHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class FrontendAuthenticationRequiredHandler implements AuthenticationRequiredHandlerInterface
{
    use TargetPathTrait;

    private const DEFAULT_OPTIONS = [

    ];

    /**
     * @var HttpUtils
     */
    private $httpUtils;

    /**
     * @var string
     */
    private $firewallName;

    /**
     * @var string[]
     */
    private $options;

    public function __construct(HttpUtils $httpUtils, string $firewallName, array $options)
    {
        $this->httpUtils = $httpUtils;
        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);
        $this->firewallName = $firewallName;
    }

    public function onAuthenticationRequired(Request $request, TokenInterface $token): Response
    {
        // Do not save the target path when the current one is the one for checking the authentication code. Then it's
        // another redirect which happens in multi-factor scenarios.
        if (!$this->isCheckAuthCodeRequest($request) && $request->hasSession() && $request->isMethodSafe(false) && !$request->isXmlHttpRequest()) {
            $this->saveTargetPath($request->getSession(), $this->firewallName, $request->getUri());
        }

        return $this->httpUtils->createRedirectResponse($request, $request->['auth_form_path']);
    }

    private function isCheckAuthCodeRequest(Request $request): bool
    {
        return $this->httpUtils->checkRequestPath($request, $this->options['check_path']);
    }
}
