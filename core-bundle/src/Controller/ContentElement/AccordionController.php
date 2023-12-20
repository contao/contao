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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(category: 'miscellaneous', nestedFragments: true)]
class AccordionController extends AbstractContentElementController
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $adapter = $this->framework->getAdapter(ContentModel::class);
        $elements = [];

        foreach ($template->get('nested_fragments') as $reference) {
            $model = $adapter->findByPk($reference->getContentModel());

            $elements[] = [
                'header' => $model->sectionHeadline,
                'reference' => $reference,
            ];
        }

        $template->set('elements', $elements);

        return $template->getResponse();
    }
}
