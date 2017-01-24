<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Twig\Extension;

use Contao\BackendCustom;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Contao template extension.
 *
 * @author Jim Schmid <https://github.com/sheeep>
 */
class ContaoTemplateExtension extends \Twig_Extension
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ContaoFrameworkInterface
     */
    private $contaoFramework;

    /**
     * Constructor.
     *
     * @param RequestStack             $requestStack
     * @param ContaoFrameworkInterface $contaoFramework
     */
    public function __construct(RequestStack $requestStack, ContaoFrameworkInterface $contaoFramework)
    {
        $this->requestStack = $requestStack;
        $this->contaoFramework = $contaoFramework;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('render_contao_backend_template', [$this, 'renderContaoBackendTemplate'])
        ];
    }

    /**
     * Renders a Contao back end template with the given blocks.
     *
     * @param array $blocks
     *
     * @return string
     */
    public function renderContaoBackendTemplate(array $blocks = [])
    {
        $scope = $this->requestStack->getCurrentRequest()->attributes->get('_scope');

        if ('backend' !== $scope) {
            return '';
        }

        /** @var BackendCustom $controller */
        $controller = $this->contaoFramework->createInstance(BackendCustom::class);
        $template = $controller->getTemplateObject();

        foreach ($blocks as $key => $content) {
            $template->{$key} = $content;
        }

        $response = $controller->run();

        return $response->getContent();
    }
}
