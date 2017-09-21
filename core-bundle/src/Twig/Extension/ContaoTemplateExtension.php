<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Twig\Extension;

use Contao\BackendCustom;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\RequestStack;

class ContaoTemplateExtension extends \Twig_Extension
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @param RequestStack             $requestStack
     * @param ContaoFrameworkInterface $framework
     * @param ScopeMatcher             $scopeMatcher
     */
    public function __construct(RequestStack $requestStack, ContaoFrameworkInterface $framework, ScopeMatcher $scopeMatcher)
    {
        $this->requestStack = $requestStack;
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('render_contao_backend_template', [$this, 'renderContaoBackendTemplate']),
        ];
    }

    /**
     * Renders a Contao back end template with the given blocks.
     *
     * @param array $blocks
     *
     * @return string
     */
    public function renderContaoBackendTemplate(array $blocks = []): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$this->scopeMatcher->isBackendRequest($request)) {
            return '';
        }

        /** @var BackendCustom $controller */
        $controller = $this->framework->createInstance(BackendCustom::class);
        $template = $controller->getTemplateObject();

        foreach ($blocks as $key => $content) {
            $template->{$key} = $content;
        }

        $response = $controller->run();

        return $response->getContent();
    }
}
