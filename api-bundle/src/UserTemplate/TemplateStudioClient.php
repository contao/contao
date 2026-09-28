<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\UserTemplate;

use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TemplateStudioClient
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
        private readonly UrlGeneratorInterface $router,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function discover(string|null $themeSlug): JsonResponse
    {
        return $this->request(
            'GET',
            '_contao_template_studio_tree.stream',
            [],
            $themeSlug,
        );
    }

    public function read(string $identifier, string|null $themeSlug): JsonResponse
    {
        return $this->request(
            'GET',
            '_contao_template_studio_editor_tab.stream',
            [
                'identifier' => $identifier,
            ],
            $themeSlug,
        );
    }

    public function call(string $operation, string $identifier, string|null $themeSlug, array $parameters): JsonResponse
    {
        return $this->request(
            'POST',
            '_contao_template_studio_operation.stream',
            [
                'operation' => $operation,
                'identifier' => $identifier,
            ],
            $themeSlug,
            $parameters,
        );
    }

    /**
     * Issue a sub request to a Turbo stream resource.
     */
    private function request(string $method, string $route, array $routeParameters = [], string|null $themeSlug = null, array $parameters = []): JsonResponse
    {
        $parent = $this->requestStack->getCurrentRequest();

        if (!$parent) {
            throw new \LogicException('Template operations require a request context.');
        }

        $uri = $this->router->generate($route, $routeParameters);

        $request = Request::create($parent->getSchemeAndHttpHost().$uri, $method, $parameters);
        $request->attributes->add(['_route' => 'contao_backend', '_scope' => 'backend', '_contao_api' => true, '_locale' => $parent->getLocale()]);
        $request->headers->set('Accept', 'text/vnd.turbo-stream.html');

        // Keep backend UI state isolated from the API request
        $bag = new ArrayAttributeBag('_contao_be_attributes');
        $bag->setName('contao_backend');

        $session = new Session(new MockArraySessionStorage());
        $session->registerBag($bag);
        $session->start();

        $request->setSession($session);

        if (null !== $themeSlug) {
            $bag->set('template_studio_theme_slug', $themeSlug);
        }

        $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);

        if (!$response instanceof JsonResponse) {
            throw new UnprocessableEntityHttpException($response->getContent());
        }

        if (JsonResponse::HTTP_OK !== $response->getStatusCode()) {
            $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

            throw new $content['errorClass']($content['errorMessage']);
        }

        return $response;
    }
}
