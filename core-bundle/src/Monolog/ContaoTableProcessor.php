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
use Contao\PageModel;
use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ContaoTableProcessor implements ProcessorInterface
{
    /**
     * @internal
     */
    public function __construct(
        private RequestStack $requestStack,
        private TokenStorageInterface $tokenStorage,
        private ScopeMatcher $scopeMatcher,
    ) {
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
        $this->updateUri($context, $request);
        $this->updatePageId($context, $request);

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

    private function updateBrowser(ContaoContext $context, Request|null $request = null): void
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

    private function updateSource(ContaoContext $context, Request|null $request = null): void
    {
        if (null !== $context->getSource()) {
            return;
        }

        $context->setSource(null !== $request && $this->scopeMatcher->isBackendRequest($request) ? 'BE' : 'FE');
    }

    private function updateUri(ContaoContext $context, Request|null $request = null): void
    {
        if (null === $request) {
            return;
        }

        $context->setUri($request->getUri());
    }

    private function updatePageId(ContaoContext $context, Request|null $request = null): void
    {
        if (null === $request || !$request->attributes->has('pageModel')) {
            return;
        }

        // The request contains either a PageModel or the ID of the page
        $page = $request->attributes->get('pageModel');

        $context->setPageId($page instanceof PageModel ? (int) $page->id : (int) $page);
    }
}
