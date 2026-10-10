<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tool;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\PageModel;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class FrontendTools
{
    // A conservative ceiling that fits typical pages without letting one result
    // dominate the model context.
    private const int MAX_CONTENT_LENGTH = 256 * 1024;

    public function __construct(
        private readonly HttpKernelInterface $httpKernel,
        private readonly RequestStack $requestStack,
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
        private readonly Security $security,
    ) {
    }

    #[McpTool(name: 'contao_frontend_inspect', description: 'Render a Contao page as the current backend user would see it in frontend preview, including unpublished content. For publishable content, prefer creating or updating it as unpublished, inspect the affected page with this tool, and publish only after verifying the result. Returns response metadata and up to '.(self::MAX_CONTENT_LENGTH / 1024).' KiB of HTML.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function inspect(#[Schema(minimum: 1)] int $page): array
    {
        if (!$this->security->isGranted('ROLE_USER')) {
            throw new ToolCallException('Frontend inspection requires an authenticated backend user.');
        }

        $parent = $this->requestStack->getCurrentRequest();

        if (!$parent) {
            throw new ToolCallException('Frontend inspection requires an HTTP request.');
        }

        $pageAdapter = $this->framework->getAdapter(PageModel::class);

        if (!$pageModel = $pageAdapter->findWithDetails($page)) {
            throw new ToolCallException(\sprintf('Could not create a frontend preview URL for page ID %d.', $page));
        }

        try {
            $url = $this->urlGenerator->generate($pageModel, [], UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (ExceptionInterface $exception) {
            throw new ToolCallException(\sprintf('Could not create a frontend preview URL for page ID %d.', $page), previous: $exception);
        }

        $session = new Session(new MockArraySessionStorage());
        $session->set('_security_contao_backend', serialize($this->security->getToken()));
        $session->set(FrontendPreviewAuthenticator::SESSION_NAME, ['showUnpublished' => true]);

        $request = Request::create(
            $url,
            'GET',
            server: array_intersect_key($parent->server->all(), array_flip(['SCRIPT_NAME', 'SCRIPT_FILENAME'])),
        );

        $request->attributes->set('_preview', true);
        $request->setSession($session);
        $request->cookies->set($session->getName(), $session->getId());

        $response = $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST);

        $contentType = $response->headers->get('Content-Type');

        if (null !== $contentType && !str_contains($contentType, 'text/html')) {
            throw new ToolCallException(\sprintf('The frontend returned unsupported content type "%s".', $contentType));
        }

        $content = $response->getContent() ?: '';
        $isTruncated = \strlen($content) > self::MAX_CONTENT_LENGTH;

        return [
            'url' => $url,
            'status' => $response->getStatusCode(),
            'contentType' => $contentType,
            'html' => $isTruncated ? substr($content, 0, self::MAX_CONTENT_LENGTH) : $content,
            'truncated' => $isTruncated,
            'location' => $response->headers->get('Location'),
        ];
    }
}
