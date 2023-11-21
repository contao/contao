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
use Contao\CoreBundle\Routing\PageFinder;
use Contao\FilesModel;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
#[Route('/favicon.ico', defaults: ['_scope' => 'frontend'])]
class FaviconController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly PageFinder $pageFinder,
        private readonly string $projectDir,
        private readonly EntityCacheTags $entityCacheTags,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $rootPage = $this->pageFinder->findRootPageForHostAndLanguage($request->getHost());

        if (!$rootPage || null === ($favicon = $rootPage->favicon)) {
            throw new NotFoundHttpException();
        }

        $this->framework->initialize();

        $filesModel = $this->framework->getAdapter(FilesModel::class);

        if (!$faviconModel = $filesModel->findByUuid($favicon)) {
            throw new NotFoundHttpException();
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

            case 'png':
                $response->headers->set('Content-Type', 'image/png');
                break;
        }

        $this->entityCacheTags->tagWithModelInstance($rootPage);

        return $response;
    }
}
