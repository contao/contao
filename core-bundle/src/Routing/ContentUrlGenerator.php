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
use Doctrine\ORM\Mapping\MappingException as OrmMappingException;
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
            $visited = [];
            [$target, $targetContent] = $this->resolveContent($visited, $content) + [null, null];

            if (\is_string($target)) {
                return $this->urlCache[$cacheKey] = $target;
            }

            if (!$target instanceof PageModel) {
                $this->throwRouteNotFoundException($this->getRouteKey($target));
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
     * @param array<string, object> $visited
     *
     * @throws RouteNotFoundException
     */
    private function resolveContent(array &$visited, object ...$contents): array
    {
        $content = $contents[0];
        $key = $this->getRouteKey($content);

        if (isset($visited[$key])) {
            throw new RouteNotFoundException(\sprintf('Circular reference while generating the URL for "%s".', $key));
        }

        // Keep the object alive so that its object ID cannot be reused
        $visited[$key] = $content;

        foreach ($this->urlResolvers as $resolver) {
            $result = $resolver->resolve($content);

            if (!$result) {
                continue;
            }

            if ($result->hasTargetUrl()) {
                return [$result->getTargetUrl()];
            }

            if ($result->isRedirect()) {
                return $this->resolveContent($visited, $result->content);
            }

            return $this->resolveContent($visited, $result->content, ...$contents);
        }

        if ($content instanceof PageModel) {
            return $contents;
        }

        $this->throwRouteNotFoundException($key);
    }

    private function getRouteKey(object $content): string
    {
        if ($content instanceof Model) {
            $identifier = $content->{$content::getPk()};
            $key = null !== $identifier ? \sprintf('%s.%s', $content::getTable(), $identifier) : null;
        } else {
            // If the content is a Doctrine ORM entity, try using its identifier
            $key = $this->getEntityRouteKey($content);
        }

        return $key ?? $content::class.'->'.spl_object_id($content);
    }

    private function getEntityRouteKey(object $content): string|null
    {
        try {
            $metadata = $this->entityManager->getClassMetadata($content::class);
        } catch (MappingException|OrmMappingException) {
            $metadata = null;
        }

        $fields = $metadata?->getIdentifierFieldNames();

        if (!$fields) {
            return null;
        }

        $unitOfWork = $this->entityManager->getUnitOfWork();
        $identifiers = $unitOfWork->isInIdentityMap($content) ? $unitOfWork->getEntityIdentifier($content) : $metadata->getIdentifierValues($content);

        $parts = [];

        foreach ($fields as $field) {
            $value = $identifiers[$field] ?? null;

            if (\is_object($value) && $metadata->hasAssociation($field)) {
                $value = $unitOfWork->getSingleIdentifierValue($value);
            }

            if (!\is_scalar($value) && !$value instanceof \Stringable) {
                return null;
            }

            $parts[$field] = (string) $value;
        }

        // A key like "record=7&locale=en" shows which record caused the error, even if a
        // value contains "&" or "=". Query encoding escapes those characters. A hash
        // would hide the values, and serialize() would add PHP type information and thus
        // make error messages less readable for humans
        $identifier = 1 === \count($parts) ? reset($parts) : http_build_query($parts, '', '&', PHP_QUERY_RFC3986);

        return \sprintf('%s.%s', $metadata->getTableName(), $identifier);
    }

    /**
     * @throws RouteNotFoundException
     */
    private function throwRouteNotFoundException(string $key): never
    {
        throw new RouteNotFoundException('No route for content: '.$key);
    }
}
