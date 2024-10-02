<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\Query;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * @experimental
 */
#[Route(
    '%contao.backend.route_prefix%/search',
    name: 'contao_backend_search',
    defaults: ['_scope' => 'backend', '_allow_preview' => true, '_store_referrer' => false],
    methods: ['GET'],
)]
class BackendSearchController
{
    public function __construct(
        private readonly Security $security,
        private readonly BackendSearch $backendSearch,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->security->isGranted('ROLE_USER')) {
            throw new AccessDeniedHttpException();
        }

        $query = new Query(
            $request->query->getInt('perPage', 20),
            $request->query->getString('keywords') ?: null,
            $request->query->getString('type') ?: null,
            $request->query->getString('tag') ?: null,
        );

        $result = $this->backendSearch->search($query);

        return new Response($this->twig->render('@Contao/backend/search/result.html.twig', [
            'hits' => $result->getHits(),
        ]));
    }
}
