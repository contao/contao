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

use Contao\Ajax;
use Contao\BackendMain;
use Contao\BackendTemplate;
use Contao\CoreBundle\Util\PackageUtil;
use Contao\Environment;
use Contao\Input;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractCustomBackendController extends AbstractController
{
    /**
     * Render a Twig template with additional context for `@Contao/be_main`.
     */
    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        $backendContext = (new class() extends BackendMain {
            public function __invoke(): array
            {
                $this->Template = new BackendTemplate('be_main');
                $this->Template->version = $GLOBALS['TL_LANG']['MSC']['version'].' '.PackageUtil::getContaoVersion();

                // Handle ajax request
                if ($_POST && Environment::get('isAjaxRequest')) {
                    $this->objAjax = new Ajax(Input::post('action'));
                    $this->objAjax->executePreActions();
                }

                $this->output();

                return $this->Template->getData();
            }
        })();

        return parent::render($view, array_merge($backendContext, $parameters), $response);
    }
}
