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

/**
 * @Route(defaults={"_scope" = "frontend"})
 */
class FaviconController
{
    /**
     * @var ContaoFramework
     */
    private $contaoFramework;

    /**
     * @var ResponseTagger|null
     */
    private $responseTagger;

    public function __construct(ContaoFramework $contaoFramework, ResponseTagger $responseTagger = null)
    {
        $this->contaoFramework = $contaoFramework;
        $this->responseTagger = $responseTagger;
    }

    /**
     * @Route("/favicon.ico", name="contao_favicon")
     */
    public function __invoke(Request $request): Response
    {
        $this->contaoFramework->initialize();

        $rootPage = $this->contaoFramework->getAdapter(PageModel::class)
            ->findPublishedFallbackByHostname($request->server->get('HTTP_HOST'), [], true)
        ;

        if (null === $rootPage || null === ($favicon = $rootPage->favicon)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $faviconModel = $this->contaoFramework->getAdapter(FilesModel::class)->findByUuid($favicon);

        if (null === $faviconModel) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        // Cache forever (= 1 year) in public cache and tag it so it's invalidated when the settings are edited.
        $response = new BinaryFileResponse($faviconModel->path);
        $response->setSharedMaxAge(31556952);

        if (null !== $this->responseTagger) {
            $this->responseTagger->addTags(['contao.db.tl_page.'.$rootPage->id]);
        }

        return $response;
    }
}
