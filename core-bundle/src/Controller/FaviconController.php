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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\PageModel;
use FOS\HttpCache\ResponseTagger;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Webmozart\PathUtil\Path;

/**
 * @Route(defaults={"_scope" = "frontend"})
 *
 * @internal
 */
class FaviconController
{
    /**
     * @var ContaoFramework
     */
    private $contaoFramework;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var ResponseTagger|null
     */
    private $responseTagger;

    public function __construct(ContaoFramework $contaoFramework, string $projectDir, ResponseTagger $responseTagger = null)
    {
        $this->contaoFramework = $contaoFramework;
        $this->projectDir = $projectDir;
        $this->responseTagger = $responseTagger;
    }

    /**
     * @Route("/favicon.ico")
     */
    public function __invoke(Request $request): Response
    {
        $this->contaoFramework->initialize();

        /** @var PageModel $pageModel */
        $pageModel = $this->contaoFramework->getAdapter(PageModel::class);

        /** @var PageModel|null $rootPage */
        $rootPage = $pageModel->findPublishedFallbackByHostname(
            $request->getHost(),
            ['fallbackToEmpty' => true]
        );

        if (null === $rootPage || null === ($favicon = $rootPage->favicon)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        /** @var FilesModel $filesModel */
        $filesModel = $this->contaoFramework->getAdapter(FilesModel::class);
        $faviconModel = $filesModel->findByUuid($favicon);

        if (null === $faviconModel) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        // Cache the response for 1 year and tag it so it is invalidated when the settings are edited
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

        if (null !== $this->responseTagger) {
            $this->responseTagger->addTags(['contao.db.tl_page.'.$rootPage->id]);
        }

        return $response;
    }
}
