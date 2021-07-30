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
     * @internal Do not inherit from this class; decorate the "contao.monolog.processor" service instead
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
    public function __invoke(array $records): array
    {
        if (!isset($records['context']['contao']) || !$records['context']['contao'] instanceof ContaoContext) {
            return $records;
        }

        $context = $records['context']['contao'];
        $request = $this->requestStack->getCurrentRequest();
        $level = $records['level'] ?? 0;

        $this->updateAction($context, $level);
        $this->updateBrowser($context, $request);
        $this->updateUsername($context);
        $this->updateSource($context, $request);

        $records['extra']['contao'] = $context;
        unset($records['context']['contao']);

        return $records;
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

        $context->setUsername(null === $token ? 'N/A' : $token->getUsername());
    }

    private function updateSource(ContaoContext $context, Request $request = null): void
    {
        if (null !== $context->getSource()) {
            return;
        }

        $context->setSource(null !== $request && $this->scopeMatcher->isBackendRequest($request) ? 'BE' : 'FE');
    }
}
