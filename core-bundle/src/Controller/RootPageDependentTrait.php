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

use Contao\ContentModel;
use Contao\CoreBundle\Fragment\FragmentCompositor;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Model;
use Contao\ModuleModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
trait RootPageDependentTrait
{
    private function renderRootPageDependent(FragmentTemplate $template, Request $request, Model $model, mixed $value, array $classes): Response
    {
        if (!$pageModel = $this->getPageModel()) {
            return new Response();
        }

        $elements = StringUtil::deserialize($value, true);
        $id = $elements[$pageModel->rootId] ?? null;

        if (!$id) {
            return new Response();
        }

        if ($isElement = str_starts_with($id, 'content-')) {
            $id = substr($id, 8);
        }

        if (!$fragmentModel = $this->getContaoAdapter($isElement ? ContentModel::class : ModuleModel::class)->findById($id)) {
            return new Response();
        }

        $fragmentModel = $fragmentModel->cloneDetached();
        $cssID = StringUtil::deserialize($fragmentModel->cssID, true);
        $modelCssID = StringUtil::deserialize($model->cssID, true);

        // Override the CSS ID (see #305)
        if (!empty($modelCssID[0])) {
            $cssID[0] = $modelCssID[0];
        }

        if ($idAttribute = $request->attributes->get('templateProperties', [])['cssID'] ?? null) {
            $cssID[0] = substr($idAttribute, 5, -1);
        }

        // Merge the CSS classes (see #6011)
        $cssID[1] = implode(' ', array_filter(array_map(trim(...), [$cssID[1] ?? '', $modelCssID[1] ?? '', ...$classes])));
        $fragmentModel->cssID = $cssID;

        $section = $template->getData()['section'] ?? $template->getData()['inColumn'] ?? 'main';

        // Create a fragment reference to be rendered in the template
        $reference = $isElement
            ? new ContentElementReference($fragmentModel, $section, inline: true)
            : new FrontendModuleReference($fragmentModel, $section, inline: true);

        // Compile the nested fragments for the content element, if applicable
        if ($reference instanceof ContentElementReference && $fragmentModel->id) {
            $reference->setNestedFragments($this->container->get('contao.fragment.compositor')->getNestedFragments($reference->controller, (int) ($fragmentModel->origId ?: $fragmentModel->id)));
        }

        $template->set('fragment', $reference);
        $template->set('is_content_element', $isElement);

        return $template->getResponse();
    }

    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            'contao.fragment.compositor' => FragmentCompositor::class,
        ];
    }
}
