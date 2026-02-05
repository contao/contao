<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fixtures\Controller\Page;

use Contao\CoreBundle\Controller\Page\AbstractLayoutPageController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\CoreBundle\Twig\LayoutTemplate;
use Contao\LayoutModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsPage]
class LayoutPageController extends AbstractLayoutPageController
{
    /**
     * @template T
     *
     * @param class-string<T> $serviceId
     *
     * @return T
     */
    public function getResponseContextService(string $serviceId)
    {
        $page = $this->container->get('contao.routing.page_finder')->getCurrentPage();

        return parent::getResponseContext($page)->get($serviceId);
    }

    protected function getResponse(LayoutTemplate $template, LayoutModel $model, Request $request): Response
    {
        $data = $template->getData();

        // The response context is evaluated lazily on access
        $data['response_context'] = iterator_to_array($template->getData()['response_context']->all());

        return new JsonResponse([...$data, 'templateName' => $template->getName()]);
    }
}
