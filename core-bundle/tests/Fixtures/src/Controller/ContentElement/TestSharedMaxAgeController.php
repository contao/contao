<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fixtures\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestSharedMaxAgeController extends AbstractContentElementController
{
    #[\Override]
    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        $response = new JsonResponse($template->getData());

        $this->addSharedMaxAgeToResponse($response, $model);

        return $response;
    }
}
