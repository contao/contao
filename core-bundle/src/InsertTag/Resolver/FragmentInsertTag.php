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
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ParsedInsertTag;
use Contao\CoreBundle\Routing\PageFinder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

#[AsInsertTag('fragment')]
class FragmentInsertTag implements InsertTagResolverNestedParsedInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly FragmentHandler $fragmentHandler,
        private readonly PageFinder $pageFinder,
    ) {
    }

    public function __invoke(ParsedInsertTag $insertTag): InsertTagResult
    {
        $attributes = ['insertTag' => substr($insertTag->getParameters()->serialize(), 2)];

        if ($scope = $this->requestStack->getCurrentRequest()?->attributes->get('_scope')) {
            $attributes['_scope'] = $scope;
        }

        // Pass the root page ID to the insert tags controller to have the right context
        // when replacing the nested insert tags while maintaining good cacheability
        if ($pageId = $this->pageFinder->getCurrentPage()?->rootId) {
            $attributes['pageModel'] = $pageId;
        }

        $esiTag = $this->fragmentHandler->render(
            new ControllerReference(InsertTagsController::class.'::renderAction', $attributes),
            'esi',
            ['ignore_errors' => false], // see #48
        );

        return new InsertTagResult($esiTag, OutputType::html);
    }
}
