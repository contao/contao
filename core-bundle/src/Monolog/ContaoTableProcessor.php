<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Monolog;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Monolog processor for Contao.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoTableProcessor
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var bool
     */
    private $anonymizeIp;

    /**
     * Constructor.
     *
     * @param RequestStack          $requestStack
     * @param TokenStorageInterface $tokenStorage
     * @param ScopeMatcher          $scopeMatcher
     * @param bool                  $anonymizeIp
     */
    public function __construct(RequestStack $requestStack, TokenStorageInterface $tokenStorage, ScopeMatcher $scopeMatcher, $anonymizeIp = true)
    {
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->scopeMatcher = $scopeMatcher;
        $this->anonymizeIp = $anonymizeIp;
    }

    /**
     * Move the Contao context into the "extra" section.
     *
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record)
    {
        if (!isset($record['context']['contao']) || !($record['context']['contao'] instanceof ContaoContext)) {
            return $record;
        }

        $context = $record['context']['contao'];
        $request = $this->requestStack->getCurrentRequest();
        $level = isset($record['level']) ? $record['level'] : 0;

        $this->updateAction($context, $level);
        $this->updateIp($context, $request);
        $this->updateBrowser($context, $request);
        $this->updateUsername($context);
        $this->updateSource($context, $request);

        $record['extra']['contao'] = $context;
        unset($record['context']['contao']);

        return $record;
    }

    /**
     * Sets the action.
     *
     * @param ContaoContext $context
     * @param int           $level
     */
    private function updateAction(ContaoContext $context, $level)
    {
        if (null !== $context->getAction()) {
            return;
        }

        if ($level >= Logger::ERROR) {
            $context->setAction(ContaoContext::ERROR);
        } else {
            $context->setAction(ContaoContext::GENERAL);
        }
    }

    /**
     * Sets the IP adress.
     *
     * @param ContaoContext $context
     * @param Request|null  $request
     */
    private function updateIp(ContaoContext $context, Request $request = null)
    {
        $ip = $context->getIp();

        if (null === $ip) {
            $ip = null === $request ? '127.0.0.1' : $request->getClientIp();
        }

        if ($this->anonymizeIp) {
            $ip = $this->anonymizeIp($ip);
        }

        $context->setIp($ip);
    }

    /**
     * Sets the browser.
     *
     * @param ContaoContext $context
     * @param Request|null  $request
     */
    private function updateBrowser(ContaoContext $context, Request $request = null)
    {
        if (null !== $context->getBrowser()) {
            return;
        }

        $context->setBrowser(null === $request ? 'N/A' : $request->server->get('HTTP_USER_AGENT'));
    }

    /**
     * Sets the username.
     *
     * @param ContaoContext $context
     */
    private function updateUsername(ContaoContext $context)
    {
        if (null !== $context->getUsername()) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        $context->setUsername(null === $token ? 'N/A' : $token->getUsername());
    }

    /**
     * Sets the source.
     *
     * @param ContaoContext $context
     * @param Request|null  $request
     */
    private function updateSource(ContaoContext $context, Request $request = null)
    {
        if (null !== $context->getSource()) {
            return;
        }

        $context->setSource(null !== $request && $this->scopeMatcher->isBackendRequest($request) ? 'BE' : 'FE');
    }

    /**
     * Anonymizes the IP adress.
     *
     * @param string $ip
     *
     * @return string
     */
    private function anonymizeIp($ip)
    {
        if ('127.0.0.1' === $ip || '::1' === $ip) {
            return $ip;
        }

        if (false !== strpos($ip, ':')) {
            return substr_replace($ip, ':0000', strrpos($ip, ':'));
        }

        return substr_replace($ip, '.0', strrpos($ip, '.'));
    }
}
