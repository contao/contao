<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag;

use Symfony\Component\HttpFoundation\Request;

final class HtmlHeadBag
{
    private string $title = '';
    private string $metaDescription = '';
    private string $metaRobots = 'index,follow';
    private string $canonicalUri = '';
    private array $keepParamsForCanonical = [];

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getMetaDescription(): string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getMetaRobots(): string
    {
        return $this->metaRobots;
    }

    public function setMetaRobots(string $metaRobots): self
    {
        $this->metaRobots = $metaRobots;

        return $this;
    }

    public function setKeepParamsForCanonical(array $keepParamsForCanonical): self
    {
        $this->keepParamsForCanonical = $keepParamsForCanonical;

        return $this;
    }

    public function getKeepParamsForCanonical(): array
    {
        return $this->keepParamsForCanonical;
    }

    public function addKeepParamsForCanonical(string $param): self
    {
        $this->keepParamsForCanonical[] = $param;

        return $this;
    }

    public function setCanonicalUri(string $canonicalUri): self
    {
        $this->canonicalUri = $canonicalUri;

        return $this;
    }

    public function getCanonicalUri(): string
    {
        return $this->canonicalUri;
    }

    public function getCanonicalUriForRequest(Request $request): string
    {
        if ($this->canonicalUri) {
            // Make sure the custom URI is normalized as well
            return Request::create($this->canonicalUri)->getUri();
        }

        $params = [];

        foreach ($request->query->all() as $originalParam => $value) {
            foreach ($this->getKeepParamsForCanonical() as $param) {
                $regex = sprintf('/^%s$/', str_replace('\*', '.*', preg_quote($param, '/')));

                if (preg_match($regex, (string) $originalParam)) {
                    $params[$originalParam] = $value;
                }
            }
        }

        $request = Request::create(
            $request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo(),
            $request->getMethod(),
            $params
        );

        return $request->getUri();
    }
}
