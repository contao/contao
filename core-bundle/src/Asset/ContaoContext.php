<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Asset;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal Do not use this class in your code; use the "contao.assets.assets_context" or "contao.assets.files_context" service instead
 */
class ContaoContext implements ContextInterface
{
    private RequestStack $requestStack;
    private ContaoFramework $framework;
    private string $field;
    private bool $debug;

    public function __construct(RequestStack $requestStack, ContaoFramework $framework, string $field, bool $debug = false)
    {
        $this->requestStack = $requestStack;
        $this->framework = $framework;
        $this->field = $field;
        $this->debug = $debug;
    }

    public function getBasePath(): string
    {
        if (null === ($request = $this->requestStack->getMainRequest())) {
            return '';
        }

        if ($this->debug || '' === ($staticUrl = $this->getFieldValue($this->getPageModel()))) {
            return $request->getBasePath();
        }

        $protocol = $this->isSecure() ? 'https' : 'http';
        $relative = preg_replace('@https?://@', '', $staticUrl);

        return sprintf('%s://%s%s', $protocol, $relative, $request->getBasePath());
    }

    public function isSecure(): bool
    {
        $page = $this->getPageModel();

        if (null !== $page) {
            return (bool) $page->loadDetails()->rootUseSSL;
        }

        $request = $this->requestStack->getMainRequest();

        if (null === $request) {
            return false;
        }

        return $request->isSecure();
    }

    /**
     * Returns the base path with a trailing slash if not empty.
     */
    public function getStaticUrl(): string
    {
        if ($path = $this->getBasePath()) {
            return $path.'/';
        }

        return '';
    }

    private function getPageModel(): ?PageModel
    {
        $request = $this->requestStack->getMainRequest();

        if (null === $request || !$request->attributes->has('pageModel')) {
            if (isset($GLOBALS['objPage']) && $GLOBALS['objPage'] instanceof PageModel) {
                return $GLOBALS['objPage'];
            }

            return null;
        }

        $pageModel = $request->attributes->get('pageModel');

        if ($pageModel instanceof PageModel) {
            return $pageModel;
        }

        if (
            isset($GLOBALS['objPage'])
            && $GLOBALS['objPage'] instanceof PageModel
            && (int) $GLOBALS['objPage']->id === (int) $pageModel
        ) {
            return $GLOBALS['objPage'];
        }

        $this->framework->initialize();

        return $this->framework->getAdapter(PageModel::class)->findByPk((int) $pageModel);
    }

    /**
     * Returns a field value from the page model.
     */
    private function getFieldValue(?PageModel $page): string
    {
        if (null === $page) {
            return '';
        }

        return (string) $page->{$this->field};
    }
}
