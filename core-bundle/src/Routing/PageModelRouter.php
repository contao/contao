<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing;

use Contao\PageModel;
use Symfony\Cmf\Component\Routing\ChainedRouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext;

class PageModelRouter implements ChainedRouterInterface
{
    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var RequestContext
     */
    private $context;

    public function __construct(UrlGenerator $urlGenerator, RequestStack $requestStack)
    {
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): RequestContext
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     *
     * @param PageModel $pageModel
     * @param array     $parameters
     * @param string    $referenceType
     */
    public function generate($pageModel, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        $pageModel->loadDetails();

        $parameters['_locale'] = $pageModel->rootLanguage;
        $parameters['_domain'] = $pageModel->domain;
        $parameters['_ssl'] = (bool) $pageModel->rootUseSSL;

        $strUrl = $this->urlGenerator->generate(($pageModel->alias ?: $pageModel->id), $parameters, $referenceType);

        // Make the URL relative to the base path
        if (0 === strncmp($strUrl, '/', 1))
        {
            if (!$request = $this->requestStack->getCurrentRequest()) {
                throw new \RuntimeException('The request stack did not contain a request.');
            }

            $strUrl = substr($strUrl, \strlen($request->getBasePath()) + 1);
        }

        return $strUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($name): bool
    {
        return $name instanceof PageModel;
    }

    /**
     * {@inheritdoc}
     *
     * @param PageModel $pageModel
     */
    public function getRouteDebugMessage($pageModel, array $parameters = []): string
    {
        return 'Page ID '.$pageModel->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        throw new \LogicException('Not implemented');
    }
}
