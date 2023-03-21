<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Monolog;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ContaoTableProcessor implements ProcessorInterface
{
    private RequestStack $requestStack;
    private TokenStorageInterface $tokenStorage;
    private ScopeMatcher $scopeMatcher;

    /**
     * @internal
     */
    public function __construct(RequestStack $requestStack, TokenStorageInterface $tokenStorage, ScopeMatcher $scopeMatcher)
    {
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * Move the Contao context into the "extra" section.
     */
    public function __invoke(array $record): array
    {
        if (!isset($record['context']['contao']) || !$record['context']['contao'] instanceof ContaoContext) {
            return $record;
        }

        $context = $record['context']['contao'];
        $request = $this->requestStack->getCurrentRequest();
        $level = $record['level'];

        $this->updateAction($context, $level);
        $this->updateBrowser($context, $request);
        $this->updateUsername($context);
        $this->updateSource($context, $request);

        $record['extra']['contao'] = $context;
        unset($record['context']['contao']);

        return $record;
    }

    private function updateAction(ContaoContext $context, int $level): void
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

    private function updateBrowser(ContaoContext $context, Request $request = null): void
    {
        if (null !== $context->getBrowser()) {
            return;
        }

        $context->setBrowser(null === $request ? 'N/A' : (string) $request->server->get('HTTP_USER_AGENT'));
    }

    private function updateUsername(ContaoContext $context): void
    {
        if (null !== $context->getUsername()) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        $context->setUsername(null === $token ? 'N/A' : $token->getUserIdentifier());
    }

    private function updateSource(ContaoContext $context, Request $request = null): void
    {
        if (null !== $context->getSource()) {
            return;
        }

        $context->setSource(null !== $request && $this->scopeMatcher->isBackendRequest($request) ? 'BE' : 'FE');
    }
}
