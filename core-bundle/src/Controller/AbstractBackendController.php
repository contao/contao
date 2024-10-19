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
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractBackendController extends AbstractController
{
    /**
     * Renders a Twig template with additional context for "@Contao/be_main".
     *
     * If the request was initiated by a Turbo frame or was set to accept a Turbo
     * stream, no additional context will be created by default. To overwrite the
     * behavior set $includeChromeContext to true/false.
     */
    protected function render(string $view, array $parameters = [], Response|null $response = null, bool|null $includeChromeContext = null): Response
    {
        $getBackendContext = (new class() extends BackendMain {
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
        });

        /** @var Request $request */
        $request = System::getContainer()->get('request_stack')?->getCurrentRequest();

        if (\in_array('text/vnd.turbo-stream.html', $request->getAcceptableContentTypes(), true)) {
            // Setting the request format will add the correct ContentType header and make
            // sure Symfony renders error pages correctly.
            $request->setRequestFormat('turbo_stream');

            $includeChromeContext ??= false;
        }

        if ($request->headers->has('turbo-frame')) {
            $includeChromeContext ??= false;
        }

        if ($includeChromeContext ?? true) {
            $parameters = [...$getBackendContext(), ...$parameters];
        }

        return parent::render($view, $parameters, $response);
    }
}
