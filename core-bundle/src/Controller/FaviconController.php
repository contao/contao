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

use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\PageModel;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
#[Route(defaults: ['_scope' => 'frontend'])]
class FaviconController
{
    public function __construct(private ContaoFramework $framework, private string $projectDir, private EntityCacheTags $entityCacheTags)
    {
    }

    #[Route('/favicon.ico')]
    public function __invoke(Request $request): Response
    {
        $this->framework->initialize();

        $pageModel = $this->framework->getAdapter(PageModel::class);

        $rootPage = $pageModel->findPublishedFallbackByHostname(
            $request->getHost(),
            ['fallbackToEmpty' => true]
        );

        if (null === $rootPage || null === ($favicon = $rootPage->favicon)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $filesModel = $this->framework->getAdapter(FilesModel::class);
        $faviconModel = $filesModel->findByUuid($favicon);

        if (null === $faviconModel) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        // Cache the response for 1 year and tag it, so it is invalidated when the settings are edited
        $response = new BinaryFileResponse(Path::join($this->projectDir, $faviconModel->path));
        $response->setSharedMaxAge(31556952);

        switch ($faviconModel->extension) {
            case 'svg':
                $response->headers->set('Content-Type', 'image/svg+xml');
                break;

            case 'ico':
                $response->headers->set('Content-Type', 'image/x-icon');
                break;
        }

        $this->entityCacheTags->tagWithModelInstance($rootPage);

        return $response;
    }
}
