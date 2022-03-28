<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\AbstractFragmentController;
use Contao\CoreBundle\EventListener\SubrequestCacheSubscriber;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractContentElementController extends AbstractFragmentController
{
    public function __invoke(Request $request, ContentModel $model, string $section, array $classes = null): Response
    {
        if (null !== ($response = $this->getUpcomingResponse($request, $model))) {
            return $response;
        }

        $type = $this->getType();
        $template = $this->createTemplate($model, 'ce_'.$type);

        $this->addHeadlineToTemplate($template, $model->headline);
        $this->addCssAttributesToTemplate($template, 'ce_'.$type, $model->cssID, $classes);
        $this->addPropertiesToTemplate($template, $request->attributes->get('templateProperties', []));
        $this->addSectionToTemplate($template, $section);
        $this->tagResponse(['contao.db.tl_content.'.$model->id]);

        $response = $this->getResponse($template, $model, $request);

        if (null === $response) {
            $response = $template->getResponse();
        }

        $time = time();
        if (!$response->headers->has('Cache-Control') && '' !== $model->stop && $model->stop > $time) {
            $response->setPublic();
            $response->setMaxAge((int) $model->stop - $time);
        }

        return $response;
    }

    protected function addSharedMaxAgeToResponse(Response $response, ContentModel $model): void
    {
        $time = time();
        $min = [];

        if ('' !== $model->start && $model->start > $time) {
            $min[] = (int) $model->start - $time;
        }

        if ('' !== $model->stop && $model->stop > $time) {
            $min[] = (int) $model->stop - $time;
        }

        if (empty($min)) {
            return;
        }

        $response->setSharedMaxAge(min($min));
    }

    protected function getUpcomingResponse(Request $request, ContentModel $model): ?Response
    {
        if (!$this->container->get('contao.routing.scope_matcher')->isFrontendRequest($request)) {
            return null;
        }

        if ($this->container->get('contao.security.token_checker')->isPreviewMode()) {
            return null;
        }

        $time = time();

        if ('' !== $model->stop && $model->stop < $time) {
            $response = new Response();
            $response->headers->remove('Cache-Control');

            return $response;
        }

        if ('' !== $model->start && $model->start > $time) {
            $response = new Response();
            $response->headers->set(SubrequestCacheSubscriber::MERGE_CACHE_HEADER, true);
            $response->setPublic();
            $response->setMaxAge($model->start - $time);

            return $response;
        }

        return null;
    }

    abstract protected function getResponse(Template $template, ContentModel $model, Request $request): ?Response;
}
