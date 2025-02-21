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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

abstract class AbstractBackendController extends AbstractController
{
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['request_stack'] = RequestStack::class;

        return $services;
    }

    /**
     * Renders a Twig template with additional context for "@Contao/be_main".
     *
     * If the request was initiated by a Turbo frame or was set to accept a Turbo
     * stream, no additional context will be created by default. To overwrite the
     * behavior set $includeChromeContext to true/false.
     */
    protected function render(string $view, array $parameters = [], Response|null $response = null, bool|null $includeChromeContext = null): Response
    {
        $getBackendContext = static fn () => (new class() extends BackendMain {
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

        $request = $this->getCurrentRequest();

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

        $response = parent::render($view, $parameters, $response);

        // Set the status code to 422 if a widget did not validate, so that Turbo can
        // handle form errors.
        if ($this->container->get('request_stack')?->getMainRequest()->attributes->has('_contao_widget_error')) {
            $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $response;
    }

    protected function getBackendSessionBag(): AttributeBagInterface|null
    {
        $sessionBag = $this->getCurrentRequest()->getSession()->getBag('contao_backend');

        return $sessionBag instanceof AttributeBagInterface ? $sessionBag : null;
    }

    private function getCurrentRequest(): Request
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }
}
