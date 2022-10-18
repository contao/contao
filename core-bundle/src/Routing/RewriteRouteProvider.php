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
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $collection = new RouteCollection();

        $rewrites = $this->connection->executeQuery("SELECT * FROM tl_url_rewrite WHERE disable=''");

        foreach ($rewrites->iterateAssociative() as $rule) {
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

            $route = new Route(
                $rule['requestPath'],
                ['_controller' => UrlRewriteController::class, UrlRewriteController::ATTRIBUTE_NAME => $rule],
                $requirements,
                ['utf8' => true],
                $rule['requestHost'] ?? null,
                [],
                [],
                $condition
            );

            $collection->add('tl_url_rewrite.'.$rule['id'], $route);
        }

        return $collection;
    }

    public function getRouteByName($name): Route
    {
        throw new RouteNotFoundException('This router does not support routes by name');
    }

    public function getRoutesByNames(?array $names = null): iterable
    {
        return [];
    }
}
