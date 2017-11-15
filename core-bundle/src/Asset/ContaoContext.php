<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Asset;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\PageModel;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ContaoContext implements ContextInterface
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $field;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param ContaoFrameworkInterface $framework
     * @param RequestStack             $requestStack
     * @param string                   $field
     * @param bool                     $debug
     */
    public function __construct(ContaoFrameworkInterface $framework, RequestStack $requestStack, string $field, bool $debug = false)
    {
        $this->framework = $framework;
        $this->requestStack = $requestStack;
        $this->field = $field;
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePath()
    {
        if ($this->debug) {
            return '';
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || '' === ($staticUrl = $this->getFieldValue($this->getPage()))) {
            return '';
        }

        $protocol = $this->isSecure() ? 'https' : 'http';
        $relative = preg_replace('@https?://@', '', $staticUrl);

        return sprintf('%s://%s%s', $protocol, $relative, $request->getBasePath());
    }

    /**
     * {@inheritdoc}
     */
    public function isSecure(): bool
    {
        $page = $this->getPage();

        if (null !== $page) {
            return (bool) $page->loadDetails()->rootUseSSL;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return false;
        }

        return $request->isSecure();
    }

    /**
     * Gets the current page model.
     *
     * @return PageModel|null
     */
    private function getPage(): ?PageModel
    {
        if (isset($GLOBALS['objPage']) && $GLOBALS['objPage'] instanceof PageModel) {
            return $GLOBALS['objPage'];
        }

        return null;
    }

    /**
     * Gets field value from page model or global config.
     *
     * @param PageModel|null $page
     *
     * @return string
     */
    private function getFieldValue(?PageModel $page): string
    {
        if (null !== $page) {
            return (string) $page->{$this->field};
        }

        $this->framework->initialize();

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        return (string) $config->get($this->field);
    }
}
