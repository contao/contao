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
    public function getBasePath(): string
    {
        if ($this->debug) {
            return '';
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || '' === ($staticUrl = $this->getFieldValue($this->getPageModel()))) {
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
        $page = $this->getPageModel();

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
        if (isset($GLOBALS['objPage']) && $GLOBALS['objPage'] instanceof PageModel) {
            return $GLOBALS['objPage'];
        }

        return null;
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
