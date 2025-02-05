<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag\Resolver;

use Contao\CoreBundle\Controller\InsertTagsController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\Fragment\FragmentHandler;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ParsedInsertTag;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerReference;

#[AsInsertTag('fragment')]
class FragmentInsertTag implements InsertTagResolverNestedParsedInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly FragmentHandler $fragmentHandler,
    ) {
    }

    public function __invoke(ParsedInsertTag $insertTag): InsertTagResult
    {
        $attributes = ['insertTag' => substr($insertTag->getParameters()->serialize(), 2)];

        if ($scope = $this->requestStack->getCurrentRequest()?->attributes->get('_scope')) {
            $attributes['_scope'] = $scope;
        }

        $esiTag = $this->fragmentHandler->render(
            new ControllerReference(InsertTagsController::class.'::renderAction', $attributes),
            'esi',
            ['ignore_errors' => false], // see #48
        );

        dump($esiTag);

        return new InsertTagResult($esiTag, OutputType::html);
    }

    private function getPageModel(): PageModel|null
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            return null;
        }

        $pageModel = $request->attributes->get('pageModel');

        if ($pageModel instanceof PageModel) {
            return $pageModel;
        }

        return null;
    }
}
