<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Routing\Content;

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

    public static function abstain(): self
    {
        return new self(null);
    }

    public static function absoluteUrl(string $url): self
    {
        return new self($url);
    }

    public static function redirect(object|null $content): self
    {
        $result = new self($content);
        $result->redirect = true;

        return $result;
    }

    public static function resolve(PageModel|null $content): self
    {
        return new self($content);
    }
}
