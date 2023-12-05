<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Routing\Content;

use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\PageModel;
use Nyholm\Psr7\Uri;

final class ContentUrlResult
{
    private bool $redirect = false;

    public function __construct(public readonly object|string|null $content)
    {
        if (\is_string($content) && !(new Uri($content))->getScheme()) {
            throw new \InvalidArgumentException('ContentUrlResult must not be an relative URL.');
        }
    }

    public function isAbstained(): bool
    {
        return null === $this->content;
    }

    public function isRedirect(): bool
    {
        return $this->redirect;
    }

    public function hasTargetUrl(): bool
    {
        return \is_string($this->content);
    }

    public function getTargetUrl(): string
    {
        if (!$this->hasTargetUrl()) {
            throw new \BadMethodCallException('ContentUrlResult does not have a target URL.');
        }

        return $this->content;
    }

    /**
     * Provides no result to continue the resolver loop.
     */
    public static function abstain(): self
    {
        return new self(null);
    }

    /**
     * Result is a URL string which is possibly relative and could contain insert tags that should be resolved.
     */
    public static function url(string $url): self
    {
        if (!$url) {
            throw new ForwardPageNotFoundException('Empty target URL');
        }

        $result = new self(new StringUrl($url));
        $result->redirect = true;

        return $result;
    }

    /**
     * Restarts the resolver process to find a URL for the given content.
     *
     * Same as with an HTTP redirect, this will not include any parameters given
     * to the generate() method in the final URL.
     */
    public static function redirect(object|null $content): self
    {
        if (null === $content) {
            throw new ForwardPageNotFoundException();
        }

        $result = new self($content);
        $result->redirect = true;

        return $result;
    }

    /**
     * Returns a page model as the target page. The ContentUrlGenerator will then generate the URL for this target
     * page with parameters for the content.
     */
    public static function resolve(PageModel|null $content): self
    {
        if (!$content) {
            throw new ForwardPageNotFoundException();
        }

        return new self($content);
    }
}
