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

use Contao\Backend;
use Contao\BackendMain;
use Contao\BackendTemplate;
use Contao\Combiner;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractBackendController extends AbstractController
{
    /**
     * Renders a view. By default, a parameter "chrome" will be added that
     * contains the necessary data in order to extend from or directly render
     * the "@Contao/Backend/_chrome.html.twig" template; set $withChrome to
     * "false" to disable.
     */
    protected function render(string $view, array $parameters = [], Response $response = null, bool $withChrome = true): Response
    {
        $backendContext = (new class() extends BackendMain {
            public function __invoke(): array
            {
                $this->Template = (new class() extends BackendTemplate {
                    public function __construct()
                    {
                        parent::__construct('be_main');

                        $theme = Backend::getTheme();

                        $styleCombiner = new Combiner();
                        $styleCombiner->add('system/themes/'.$theme.'/fonts.min.css');
                        $styleCombiner->add('assets/colorpicker/css/mooRainbow.min.css');
                        $styleCombiner->add('assets/chosen/css/chosen.min.css');
                        $styleCombiner->add('assets/simplemodal/css/simplemodal.min.css');
                        $styleCombiner->add('assets/datepicker/css/datepicker.min.css');
                        $styleCombiner->add('system/themes/'.$theme.'/basic.min.css');
                        $styleCombiner->add('system/themes/'.$theme.'/main.min.css');

                        $javascriptCombiner = new Combiner();
                        $javascriptCombiner->add('assets/mootools/js/mootools.min.js');
                        $javascriptCombiner->add('assets/colorpicker/js/mooRainbow.min.js');
                        $javascriptCombiner->add('assets/chosen/js/chosen.min.js');
                        $javascriptCombiner->add('assets/simplemodal/js/simplemodal.min.js');
                        $javascriptCombiner->add('assets/datepicker/js/datepicker.min.js');
                        $javascriptCombiner->add('system/themes/'.$theme.'/hover.min.js');

                        $this->arrData = [
                            'combined_stylesheet_file' => $styleCombiner->getCombinedFile(),
                            'combined_javascript_file' => $javascriptCombiner->getCombinedFile(),
                            'locale_string' => $this->getLocaleString(),
                            'date_string' => $this->getDateString(),
                            'version' => $GLOBALS['TL_LANG']['MSC']['version'].' '.ContaoCoreBundle::getVersion(),
                            'language' => $GLOBALS['TL_LANGUAGE'],
                        ];

                        // Make sure the compile function is executed that adds additional context (see #4224)
                        $this->getResponse();
                    }
                });

                return $this->Template->getData();
            }
        });

        return parent::render(
            $view,
            $withChrome ?
                // Allow overwriting keys
                [...$parameters, 'chrome' => [...$backendContext(), ...$parameters['chrome'] ?? []]] :
                $parameters,
            $response,
        );
    }
}
