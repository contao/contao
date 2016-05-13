<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Monolog;

use Contao\CoreBundle\Framework\ScopeAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * ContaoTableProcessor
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoTableProcessor
{
    use ScopeAwareTrait;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var bool
     */
    private $anonymizeIp;

    /**
     * Constructor.
     *
     * @param RequestStack          $requestStack
     * @param TokenStorageInterface $tokenStorage
     * @param bool                  $anonymizeIp
     */
    public function __construct(RequestStack $requestStack, TokenStorageInterface $tokenStorage, $anonymizeIp = true)
    {
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->anonymizeIp = $anonymizeIp;
    }

    /**
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record)
    {
        if (!isset($record['context']['contao']) || !$record['context']['contao'] instanceof ContaoContext) {
            return $record;
        }

        $context = $record['context']['contao'];
        $request = $this->requestStack->getCurrentRequest();

        $this->updateIp($context, $request);
        $this->updateBrowser($context, $request);
        $this->updateUsername($context);
        $this->updateSource($context);

        $record['extra']['contao'] = $context;
        unset($record['context']['contao']);

        return $record;
    }

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

    private function updateBrowser(ContaoContext $context, Request $request = null)
    {
        if (null !== $context->getBrowser()) {
            return;
        }

        $context->setBrowser(null === $request ? 'N/A' : $request->server->get('HTTP_USER_AGENT'));
    }

    private function updateUsername(ContaoContext $context)
    {
        if (null !== $context->getUsername()) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        $context->setUsername(null === $token ? 'N/A' : $token->getUsername());
    }

    private function updateSource(ContaoContext $context)
    {
        if (null !== $context->getSource()) {
            return;
        }

        $context->setSource($this->isBackendScope() ? 'BE' : 'FE');
    }

    /**
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
