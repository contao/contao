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
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement]
class PasskeyLoginController extends AbstractContentElementController
{
    public function __construct(private readonly ContentUrlGenerator $contentUrlGenerator)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if ($page = $this->getPageModel()) {
            $template->success_redirect = $this->contentUrlGenerator->generate($page);
        }

        return $template->getResponse();
    }
}
