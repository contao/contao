<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Validates the request token if the request is a Contao request.
 */
class RequestTokenListener
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var string
     */
    private $csrfTokenName;

    public function __construct(ContaoFramework $framework, ScopeMatcher $scopeMatcher, CsrfTokenManagerInterface $csrfTokenManager, string $csrfTokenName)
    {
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->csrfTokenName = $csrfTokenName;
    }

    /**
     * @throws InvalidRequestTokenException
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        // Only check the request token if a) the request is a POST request, b)
        // the request is not an Ajax request, c) the _token_check attribute is
        // not false and d) the _token_check attribute is set or the request is
        // a Contao request
        if (
            'POST' !== $request->getRealMethod()
            || $request->isXmlHttpRequest()
            || false === $request->attributes->get('_token_check')
            || (!$request->attributes->has('_token_check') && !$this->scopeMatcher->isContaoRequest($request))
        ) {
            return;
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        if (\defined('BYPASS_TOKEN_CHECK')) {
            @trigger_error('Defining the BYPASS_TOKEN_CHECK constant has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

            return;
        }

        if ($config->get('disableRefererCheck')) {
            @trigger_error('Using the "disableRefererCheck" setting has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

            return;
        }

        if ($config->get('requestTokenWhitelist')) {
            @trigger_error('Using the "requestTokenWhitelist" setting has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

            $hostname = gethostbyaddr($request->getClientIp());

            foreach ($config->get('requestTokenWhitelist') as $domain) {
                if ($domain === $hostname || preg_match('/\.' . preg_quote($domain, '/') . '$/', $hostname)) {
                    return;
                }
            }
        }

        $token = new CsrfToken($this->csrfTokenName, $request->request->get('REQUEST_TOKEN'));

        if ($this->csrfTokenManager->isTokenValid($token)) {
            return;
        }

        throw new InvalidRequestTokenException('Invalid CSRF token. Please reload the page and try again.');
    }
}
