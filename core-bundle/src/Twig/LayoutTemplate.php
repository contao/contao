<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig;

use Symfony\Component\HttpFoundation\Response;

final class LayoutTemplate
{
    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    /**
     * @param \Closure(self, Response|null):Response $onGetResponse
     *
     * @internal
     */
    public function __construct(
        private string $templateName,
        private readonly \Closure $onGetResponse,
    ) {
    }

    public function set(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $this->context[$key] ?? throw new \RuntimeException(sprintf('Key "%s" does not exist.', $key));
    }

    public function has(string $key): bool
    {
        return isset($this->context[$key]);
    }

    public function setSlot(string $name, string $value): void
    {
        $this->context['_slots'][$name] = $value;
    }

    public function getSlot(string $name): mixed
    {
        return $this->context['_slots'][$name] ?? throw new \RuntimeException(sprintf('Slot "%s" does not exist.', $name));
    }

    public function hasSlot(string $name): bool
    {
        return isset($this->context['_slots'][$name]);
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

    public function getResponse(Response|null $preBuiltResponse = null): Response
    {
        return ($this->onGetResponse)($this, $preBuiltResponse);
    }
}
