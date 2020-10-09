<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\ServiceAnnotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTagInterface;

/**
 * Annotation class for @Page().
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 *
 * @see \Symfony\Component\Routing\Annotation\Route
 */
final class Page implements ServiceTagInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $contentComposition = true;

    /**
     * @var string|null
     */
    private $path;

    /**
     * @var string|null
     */
    private $urlSuffix;

    /**
     * @var array
     */
    private $requirements = [];

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var array
     */
    private $defaults = [];

    /**
     * @var array
     */
    private $methods = [];

    public function __construct(array $data)
    {
        if (isset($data['value'])) {
            $data['type'] = $data['value'];
            unset($data['value']);
        }

        if (isset($data['locale'])) {
            $data['defaults']['_locale'] = $data['locale'];
            unset($data['locale']);
        }

        if (isset($data['format'])) {
            $data['defaults']['_format'] = $data['format'];
            unset($data['format']);
        }

        if (isset($data['utf8'])) {
            $data['options']['utf8'] = filter_var($data['utf8'], FILTER_VALIDATE_BOOLEAN) ?: false;
            unset($data['utf8']);
        }

        foreach ($data as $key => $value) {
            $method = 'set'.str_replace('_', '', $key);

            if (!method_exists($this, $method)) {
                throw new \BadMethodCallException(sprintf('Unknown property "%s" on annotation "%s".', $key, static::class));
            }

            $this->$method($value);
        }
    }

    public function getName(): string
    {
        return 'contao.page';
    }

    public function getAttributes(): array
    {
        return [
            'type' => $this->type,
            'contentComposition' => $this->contentComposition,
            'path' => $this->path,
            'urlSuffix' => $this->urlSuffix,
            'requirements' => $this->requirements,
            'options' => $this->options,
            'defaults' => $this->defaults,
            'methods' => $this->methods,
        ];
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getContentComposition(): bool
    {
        return $this->contentComposition;
    }

    public function setContentComposition(bool $contentComposition): void
    {
        $this->contentComposition = $contentComposition;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getUrlSuffix(): ?string
    {
        return $this->urlSuffix;
    }

    public function setUrlSuffix(string $urlSuffix): void
    {
        $this->urlSuffix = $urlSuffix;
    }

    public function setRequirements(array $requirements): void
    {
        $this->requirements = $requirements;
    }

    public function getRequirements(): array
    {
        return $this->requirements;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setDefaults(array $defaults): void
    {
        $this->defaults = $defaults;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * @param string|array<string> $methods
     */
    public function setMethods($methods): void
    {
        $this->methods = \is_array($methods) ? $methods : [$methods];
    }

    public function getMethods(): array
    {
        return $this->methods;
    }
}
