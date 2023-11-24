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
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\Environment;
use Contao\Input;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractBackendController extends AbstractController
{
    /**
     * Renders a Twig template with additional context for "@Contao/be_main".
     */
    protected function render(string $view, array $parameters = [], Response|null $response = null): Response
    {
        $backendContext = (new class() extends BackendMain {
            public function __invoke(): array
            {
                $this->Template = new BackendTemplate('be_main');
                $this->Template->version = $GLOBALS['TL_LANG']['MSC']['version'].' '.ContaoCoreBundle::getVersion();

                // Handle ajax request
                if (Input::post('action') && Environment::get('isAjaxRequest')) {
                    $this->objAjax = new Ajax(Input::post('action'));
                    $this->objAjax->executePreActions();
                }

                $this->Template->setData($this->compileTemplateData($this->Template->getData()));

                // Make sure the compile function is executed that adds additional context (see #4224)
                $this->Template->getResponse();

                return $this->Template->getData();
            }
        })();

        return parent::render($view, [...$backendContext, ...$parameters], $response);
    }
}
