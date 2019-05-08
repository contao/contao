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
use Contao\CoreBundle\Fragment\Reference\FragmentReference;
use Contao\PageModel;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler as BaseFragmentHandler;

class FragmentHandler extends BaseFragmentHandler
{
    /**
     * @var ContainerInterface
     */
    private $renderers;

    /**
     * @var BaseFragmentHandler
     */
    private $fragmentHandler;

    /**
     * @var FragmentRegistryInterface
     */
    private $fragmentRegistry;

    /**
     * @var ContainerInterface
     */
    private $preHandlers;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var array
     */
    private $initialized = [];

    public function __construct(ContainerInterface $renderers, BaseFragmentHandler $fragmentHandler, RequestStack $requestStack, FragmentRegistryInterface $fragmentRegistry, ContainerInterface $preHandlers, bool $debug = false)
    {
        $this->renderers = $renderers;
        $this->fragmentHandler = $fragmentHandler;
        $this->fragmentRegistry = $fragmentRegistry;
        $this->preHandlers = $preHandlers;
        $this->requestStack = $requestStack;

        parent::__construct($requestStack, [], $debug);
    }

    /**
     * {@inheritdoc}
     */
    public function render($uri, $renderer = 'inline', array $options = []): ?string
    {
        if (!$uri instanceof FragmentReference) {
            return $this->fragmentHandler->render($uri, $this->getRenderer($renderer), $options);
        }

        $config = $this->fragmentRegistry->get($uri->controller);

        if (null === $config) {
            throw new UnknownFragmentException(sprintf('Invalid fragment identifier "%s"', $uri->controller));
        }

        $this->preHandleFragment($uri, $config);

        $renderer = $config->getRenderer();

        if (!isset($this->initialized[$renderer]) && $this->renderers->has($renderer)) {
            $this->addRenderer($this->renderers->get($renderer));
            $this->initialized[$renderer] = true;
        }

        return parent::render($uri, $this->getRenderer($renderer), $config->getOptions());
    }

    /**
     * {@inheritdoc}
     */
    protected function deliver(Response $response): ?string
    {
        try {
            return parent::deliver($response);
        } catch (\RuntimeException $e) {
            throw new ResponseException($response, $e);
        }
    }

    /**
     * Forces the renderer to the "inline" renderer if the request
     * contains cookies. Cookies are not passed on between
     * surrogate requests so subsequent requests might fail.
     * Imagine the main request with two ESI fragments.
     * If the main request resets a cookie (such as for example it is
     * done for the rememberme cookie), the subsequent ESI fragment
     * requests will not receive the updated cookie but instead still
     * the one of the main request, causing the cookie validation to
     * fail). Contao implicitly deactivates caching anyway if any
     * cookie is present so it makes no sense to render a fragment
     * with any other renderer than "inline".
     */
    private function getRenderer(string $renderer): string
    {
        if ('inline' === $renderer || null === ($request = $this->requestStack->getCurrentRequest())) {
            return $renderer;
        }

        if ($request->cookies->count()) {
            return 'inline';
        }

        return $renderer;
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
}
