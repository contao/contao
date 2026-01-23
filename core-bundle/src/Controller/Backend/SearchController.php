<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\Backend;

use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\Query;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @experimental
 */
#[Route(
    '%contao.backend.route_prefix%/search',
    name: '_contao_backend_search.stream',
    defaults: ['_scope' => 'backend', '_store_referrer' => false, '_token_check' => false],
    methods: ['GET', 'POST'],
    condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
)]
class SearchController extends AbstractBackendController
{
    public function __construct(
        private readonly Security $security,
        private readonly BackendSearch $backendSearch,
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

        return $this->render('@Contao/backend/search/show_results.stream.html.twig', [
            'query' => $query,
            'hits' => $result->getHits(),
            'typeFacets' => $result->getTypeFacets(),
            'tagFacets' => $result->getTagFacets(),
        ]);
    }
}
