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
    /**
     * Invokes the controller.
     *
     * @param Request      $request
     * @param ContentModel $model
     * @param string       $section
     * @param array|null   $classes
     *
     * @return Response
     */
    public function __invoke(Request $request, ContentModel $model, string $section, array $classes = null)
    {
        $type = $this->getType();
        $template = $this->createTemplate($model, 'ce_'.$type);

        $this->addHeadlineToTemplate($template, $model->headline);
        $this->addCssAttributesToTemplate($template, 'ce_'.$type, $model->cssID, $classes);
        $this->addSectionToTemplate($template, $section);

        return $this->getResponse($template, $model, $request);
    }

    /**
     * Returns the response.
     *
     * @param Template|object $template
     * @param ContentModel    $model
     * @param Request         $request
     *
     * @return Response
     */
    abstract protected function getResponse(Template $template, ContentModel $model, Request $request): Response;
}
