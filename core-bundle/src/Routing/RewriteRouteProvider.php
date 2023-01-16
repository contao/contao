<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Routing;

use Contao\CoreBundle\Controller\UrlRewriteController;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RewriteRouteProvider implements RouteProviderInterface
{
    use RouteIdTrait;

    public function __construct(private readonly Connection $connection)
    {
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $collection = new RouteCollection();

        $rewrites = $this->connection->executeQuery("SELECT * FROM tl_url_rewrite WHERE disable=''");

        foreach ($rewrites->iterateAssociative() as $rule) {
            $collection->add(
                'tl_url_rewrite.'.$rule['id'],
                $this->createRoute($rule)
            );
        }

        return $collection;
    }

    public function getRouteByName($name): Route
    {
        return $this->getRoutesByNames([$name])[0];
    }

    public function getRoutesByNames(?array $names = null): iterable
    {
        if (null === $names) {
            $rules = $this->connection->fetchAllAssociative("SELECT * FROM tl_url_rewrite WHERE disable=''");
        } else {
            $ids = $this->getIdsFromRouteNames($names, 'tl_url_rewrite');

            if (empty($ids)) {
                throw new RouteNotFoundException('Route name does not match a URL rewrite ID');
            }

            $rules = $this->connection->fetchAllAssociative(
                "SELECT * FROM tl_url_rewrite WHERE id IN (?) AND disable=''",
                [$ids],
                [Connection::PARAM_INT_ARRAY]
            );
        }

        $routes = [];

        foreach ($rules as $rule) {
            $routes[] = $this->createRoute($rule);
        }

        return $routes;
    }

    private function createRoute(array $rule): Route
    {
        $requirements = [];
        $condition = null;

        switch ($rule['type']) {
            case 'basic':
                foreach (StringUtil::deserialize($rule['requestRequirements'], true) as $requirement) {
                    if ('' !== $requirement['key'] && '' !== $requirement['value']) {
                        $requirements[$requirement['key']] = $requirement['value'];
                    }
                }
                break;

            case 'expert':
                $condition = $rule['requestCondition'] ?? null;
                break;
        }

        return new Route(
            $rule['requestPath'],
            ['_controller' => UrlRewriteController::class, UrlRewriteController::ATTRIBUTE_NAME => $rule],
            $requirements,
            ['utf8' => true],
            $rule['requestHost'] ?? null,
            [],
            [],
            $condition
        );
    }
}
