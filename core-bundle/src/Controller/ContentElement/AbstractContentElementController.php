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
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractContentElementController extends AbstractFragmentController
{
    public function __invoke(Request $request, ContentModel $model, string $section, array $classes = null): Response
    {
        if (!$this->isVisible($model, $request)) {
            $response = new Response();
            $this->addSharedMaxAgeToResponse($response, $model);

            return $response;
        }

        $type = $this->getType();
        $template = $this->createTemplate($model, 'ce_'.$type);

        $this->addHeadlineToTemplate($template, $model->headline);
        $this->addCssAttributesToTemplate($template, 'ce_'.$type, $model->cssID, $classes);
        $this->addSectionToTemplate($template, $section);
        $this->tagResponse(['contao.db.tl_content.'.$model->id]);

        $response = $this->getResponse($template, $model, $request);

        if (null === $response) {
            $response = $template->getResponse();
        }

        $this->addSharedMaxAgeToResponse($response, $model);

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

    protected function isVisible(ContentModel $model, Request $request): bool
    {
        // We are in the back end, so show the element
        if ($this->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            return true;
        }

        $isInvisible = $model->invisible || ($model->start && $model->start > time()) || ($model->stop && $model->stop <= time());

        // The element is visible, so show it
        if (!$isInvisible) {
            return true;
        }

        $tokenChecker = $this->get('contao.security.token_checker');

        // Preview mode is enabled, so show the element
        if ($tokenChecker->hasBackendUser() && $tokenChecker->isPreviewMode()) {
            return true;
        }

        return false;
    }

    abstract protected function getResponse(Template $template, ContentModel $model, Request $request): ?Response;
}
