<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\Backend;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FooController extends AbstractBackendController
{
    #[Route('/contao/foo', name: 'contao_foo', defaults: ['_scope' => 'backend'])]
    public function __invoke(): Response
    {
        return $this->render('@Contao/foo.html.twig');
    }
}
