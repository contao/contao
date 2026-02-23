<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig;

use Symfony\Component\HttpFoundation\Response;

/**
 * This class is a simple container object for template data.
 */
final class FragmentTemplate
{
    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    /**
     * @param \Closure(self, Response|null): Response $onGetResponse
     *
     * @internal
     */
    public function __construct(
        private string $templateName,
        private readonly \Closure $onGetResponse,
    ) {
    }

    /**
     * @param string $key
     */
    public function __set($key, $value): void
    {
        $this->set($key, $value);
    }

    /**
     * @param string $key
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    public function set(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $this->context[$key] ?? throw new \RuntimeException(\sprintf('Key "%s" does not exist.', $key));
    }

    public function has(string $key): bool
    {
        return isset($this->context[$key]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->context = $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->context;
    }

    public function setName(string $name): void
    {
        $this->templateName = $name;
    }

    public function getName(): string
    {
        return $this->templateName;
    }

    /**
     * Renders the template and returns a new Response, that has the rendered output
     * set as content, as well as the appropriate headers that allows our
     * SubrequestCacheSubscriber to merge it with others of the same page.
     *
     * For modern fragments, the behavior is identical to calling render() on the
     * AbstractFragmentController. Like with render(), you can pass a prebuilt
     * Response if you want to have full control - no headers will be set then.
     */
    public function getResponse(Response|null $preBuiltResponse = null): Response
    {
        return ($this->onGetResponse)($this, $preBuiltResponse);
    }

    // We need to extend from the legacy Template class to keep existing type hints
    // working. In the future, when people migrated their usages, we will drop the
    // base class and the following overrides, that are only there to prevent usage
    // of the base class functionalities.
}
