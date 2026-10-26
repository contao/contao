<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fragment;

use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FragmentReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\PageModel;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler as BaseFragmentHandler;

class FragmentHandler extends BaseFragmentHandler
{
    private array $initialized = [];

    /**
     * @internal
     */
    public function __construct(
        /**
         * @phpstan-ignore property.phpDocType
         */
        private readonly ContainerInterface $renderers,
        private readonly BaseFragmentHandler $fragmentHandler,
        private readonly RequestStack $requestStack,
        private readonly FragmentRegistryInterface $fragmentRegistry,
        private readonly ContainerInterface $preHandlers,
        private readonly ScopeMatcher $scopeMatcher,
        bool $debug = false,
    ) {
        parent::__construct($requestStack, [], $debug);
    }

    public function render(ControllerReference|string $uri, string $renderer = 'inline', array $options = []): string|null
    {
        if (!$uri instanceof FragmentReference) {
            return $this->fragmentHandler->render($uri, $renderer, $options);
        }

        if (!$config = $this->fragmentRegistry->get($uri->controller)) {
            throw new UnknownFragmentException(\sprintf('Invalid fragment identifier "%s"', $uri->controller));
        }

        $this->preHandleFragment($uri, $config);

        $renderer = $config->getRenderer();

        if ('inline' !== $renderer && $this->containsNonScalars($uri->attributes)) {
            $renderer = 'forward';
        }

        // Always render content elements and frontend modules inline in the backend. This way
        // fragments do not react to parameters of the backend request, since Symfony's default
        // `inline` renderer does not copy the original request for the subrequest.
        if ($uri instanceof ContentElementReference || $uri instanceof FrontendModuleReference) {
            if ($request = $this->requestStack->getCurrentRequest()) {
                if ($this->scopeMatcher->isBackendRequest($request)) {
                    $renderer = 'inline';
                }
            }
        }

        if (!isset($this->initialized[$renderer]) && $this->renderers->has($renderer)) {
            $this->addRenderer($this->renderers->get($renderer));
            $this->initialized[$renderer] = true;
        }

        return parent::render($uri, $renderer, $config->getOptions());
    }

    protected function deliver(Response $response): string|null
    {
        try {
            return parent::deliver($response);
        } catch (\RuntimeException $e) {
            throw new ResponseException($response, $e);
        }
    }

    /**
     * Adds generic attributes and query parameters before rendering.
     */
    private function preHandleFragment(FragmentReference $uri, FragmentConfig $config): void
    {
        if (!isset($uri->attributes['pageModel']) && $this->hasGlobalPageObject()) {
            $uri->attributes['pageModel'] = $GLOBALS['objPage']->id;
        }

        if ($this->preHandlers->has($uri->controller)) {
            /** @var FragmentPreHandlerInterface $preHandler */
            $preHandler = $this->preHandlers->get($uri->controller);
            $preHandler->preHandleFragment($uri, $config);
        }
    }

    private function hasGlobalPageObject(): bool
    {
        return isset($GLOBALS['objPage']) && $GLOBALS['objPage'] instanceof PageModel;
    }

    private function containsNonScalars(array $values): bool
    {
        foreach ($values as $value) {
            if (\is_array($value)) {
                return $this->containsNonScalars($value);
            }

            if (!\is_scalar($value) && null !== $value) {
                return true;
            }
        }

        return false;
    }
}
