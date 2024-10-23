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

use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\Model;
use Contao\PageModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\MappingException;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Symfony\Contracts\Service\ResetInterface;

class ContentUrlGenerator implements ResetInterface, RequestContextAwareInterface
{
    /**
     * @var array<string, string|ExceptionInterface>
     */
    private array $urlCache = [];

    /**
     * @param iterable<ContentUrlResolverInterface> $urlResolvers
     */
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly PageRegistry $pageRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly iterable $urlResolvers,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function generate(object $content, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        try {
            $cacheKey = sha1(serialize($content)."\0".serialize($parameters)."\0".$referenceType);
        } catch (\Throwable) {
            // If $content or $parameters is not serializable, e.g. contains closures, simply
            // skip the cache.
            $cacheKey = null;
        }

        if ($cacheKey && isset($this->urlCache[$cacheKey])) {
            if ($this->urlCache[$cacheKey] instanceof ExceptionInterface) {
                throw $this->urlCache[$cacheKey];
            }

            return $this->urlCache[$cacheKey];
        }

        try {
            [$target, $targetContent] = $this->resolveContent($content) + [null, null];

            if (\is_string($target)) {
                return $this->urlCache[$cacheKey] = $target;
            }

            if (!$target instanceof PageModel) {
                $this->throwRouteNotFoundException($target);
            }

            $route = $this->pageRegistry->getRoute($target);

            if ($targetContent) {
                $route->setContent($targetContent);
                $route->setRouteKey($this->getRouteKey($targetContent));
            }

            // The original content has changed, parameters are not valid anymore
            if ($targetContent !== $content && $target !== $content) {
                $parameters = [];
            }

            $compiledRoute = $route->compile();
            $optionalParameters = [];

            if ($targetContent) {
                foreach ($this->urlResolvers as $resolver) {
                    foreach ($resolver->getParametersForContent($targetContent, $target) as $k => $v) {
                        if (isset($parameters[$k])) {
                            continue;
                        }

                        $optionalParameters[$k] = $v;
                    }
                }

                $optionalParameters = array_intersect_key($optionalParameters, array_flip($compiledRoute->getVariables()));
            }

            $url = $this->urlGenerator->generate(
                PageRoute::PAGE_BASED_ROUTE_NAME,
                [...$optionalParameters, ...$parameters, RouteObjectInterface::ROUTE_OBJECT => $route],
                $referenceType,
            );

            if ($cacheKey) {
                $this->urlCache[$cacheKey] = $url;
            }

            return $url;
        } catch (ExceptionInterface $exception) {
            if ($cacheKey) {
                $this->urlCache[$cacheKey] = $exception;
            }

            throw $exception;
        }
    }

    public function setContext(RequestContext $context): void
    {
        $this->urlGenerator->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->urlGenerator->getContext();
    }

    public function reset(): void
    {
        $this->urlCache = [];
    }

    /**
     * @throws RouteNotFoundException
     */
    private function resolveContent(object ...$contents): array
    {
        foreach ($this->urlResolvers as $resolver) {
            $result = $resolver->resolve($contents[0]);

            if (!$result) {
                continue;
            }

            if ($result->hasTargetUrl()) {
                return [$result->getTargetUrl()];
            }

            if ($result->isRedirect()) {
                return $this->resolveContent($result->content);
            }

            return $this->resolveContent($result->content, ...$contents);
        }

        if ($contents[0] instanceof PageModel) {
            return $contents;
        }

        $this->throwRouteNotFoundException($contents[0]);
    }

    private function getRouteKey(object $content): string
    {
        if (is_subclass_of($content, Model::class)) {
            return \sprintf('%s.%s', $content::getTable(), $content->{$content::getPk()});
        }

        // If the content is a Doctrine ORM entity, try using its identifier
        try {
            $metadata = $this->entityManager->getClassMetadata($content::class);
        } catch (MappingException) {
            $metadata = null;
        }

        if (!$metadata) {
            return $content::class.'->'.spl_object_id($content);
        }

        $identifier = $this->entityManager
            ->getUnitOfWork()
            ->getSingleIdentifierValue($content)
        ;

        return \sprintf('%s.%s', $metadata->getTableName(), $identifier);
    }

    /**
     * @throws RouteNotFoundException
     */
    private function throwRouteNotFoundException(object $content): never
    {
        throw new RouteNotFoundException('No route for content: '.$this->getRouteKey($content));
    }
}
