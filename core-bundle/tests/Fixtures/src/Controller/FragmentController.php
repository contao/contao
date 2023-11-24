<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fixtures\Controller;

use Contao\CoreBundle\Controller\AbstractFragmentController;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Model;
use Symfony\Component\HttpFoundation\Response;

class FragmentController extends AbstractFragmentController
{
    public function doCreateTemplate(Model $model): FragmentTemplate
    {
        return $this->createTemplate($model);
    }

    public function doRender(string|null $view = null, array $parameters = [], Response|null $response = null): Response
    {
        return $this->render($view, $parameters, $response);
    }
}
