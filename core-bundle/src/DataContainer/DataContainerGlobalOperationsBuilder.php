<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer;

use Contao\Backend;
use Contao\Controller;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\System;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * @internal
 */
class DataContainerGlobalOperationsBuilder implements \Stringable
{
    private string $table;

    private array|null $operations = null;

    public function __construct(
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __toString(): string
    {
        if (!$this->operations) {
            return '';
        }

        return $this->twig->render('@Contao/backend/data_container/global_operations.html.twig', [
            'operations' => $this->operations,
        ]);
    }

    public function initialize(string $table): self
    {
        if (null !== $this->operations) {
            throw new \RuntimeException(self::class.' has already been initialized.');
        }

        $builder = clone $this;
        $builder->table = $table;
        $builder->operations = [];

        return $builder;
    }

    /**
     * @param array{html: string}|array{
     *     href: string,
     *     title: string,
     *     attributes?: HtmlAttributes,
     * } $operation
     */
    public function prepend(array $operation): self
    {
        $this->ensureInitialized();

        array_unshift($this->operations, $operation);

        return $this;
    }

    /**
     * @param array{html: string}|array{
     *     href: string,
     *     label: string,
     *     title?: string,
     *     attributes?: HtmlAttributes,
     * } $operation
     */
    public function append(array $operation): self
    {
        $this->ensureInitialized();

        $this->operations[] = $operation;

        return $this;
    }

    public function addBackButton(string|null $href = null): self
    {
        $this->ensureInitialized();

        if (null === $href) {
            $href = System::getReferer(true);
        } elseif (!str_contains($href, '=') || str_contains($href, '?')) {
            $href = $this->urlGenerator->generate('contao_backend').'?'.$href;
        }

        $this->append([
            'href' => $href,
            'label' => $GLOBALS['TL_LANG']['MSC']['backBT'],
            'title' => $GLOBALS['TL_LANG']['MSC']['backBTTitle'],
            'attributes' => (new HtmlAttributes())->addClass('header_back')->set('accesskey', 'b')->set('data-action', 'contao--scroll-offset#discard'),
        ]);

        return $this;
    }

    public function addClearClipboardButton(): self
    {
        $this->ensureInitialized();

        $this->append([
            'href' => Backend::addToUrl('clipboard=1'),
            'label' => $GLOBALS['TL_LANG']['MSC']['clearClipboard'],
            'attributes' => (new HtmlAttributes())->addClass('header_clipboard')->set('accesskey', 'x'),
        ]);

        return $this;
    }

    public function addNewButton(string $href): self
    {
        $this->ensureInitialized();

        $labelNew = $GLOBALS['TL_LANG'][$this->table]['new'] ?? $GLOBALS['TL_LANG']['DCA']['new'];

        $this->append([
            'href' => $href,
            'label' => $labelNew[0] ?? '',
            'title' => $labelNew[1] ?? '',
            'attributes' => (new HtmlAttributes())->addClass('header_new')->set('accesskey', 'n')->set('data-action', 'contao--scroll-offset#store'),
        ]);

        return $this;
    }

    public function addGlobalButtons(DataContainer $dataContainer, callable|null $legacyCallback = null): self
    {
        $this->ensureInitialized();

        if (!\is_array($GLOBALS['TL_DCA'][$this->table]['list']['global_operations'] ?? null)) {
            return $this;
        }

        foreach ($GLOBALS['TL_DCA'][$this->table]['list']['global_operations'] as $k => $v) {
            if (!($v['showOnSelect'] ?? null) && 'select' === Input::get('act')) {
                continue;
            }

            $v = \is_array($v) ? $v : [$v];
            $operation = $this->generateOperation($k, $v, $dataContainer, $legacyCallback);

            if ($operation) {
                $this->append($operation);
            }
        }

        return $this;
    }

    private function generateOperation(string $name, array $operation, DataContainer $dataContainer, callable|null $legacyCallback = null): array|null
    {
        $config = new DataContainerOperation($name, $operation, null, $dataContainer);

        // Call a custom function instead of using the default button
        if (\is_array($operation['button_callback'] ?? null)) {
            $callback = System::importStatic($operation['button_callback'][0]);
            $ref = new \ReflectionMethod($callback, $operation['button_callback'][1]);

            if (
                1 === $ref->getNumberOfParameters()
                && ($type = $ref->getParameters()[0]->getType())
                && $type instanceof \ReflectionNamedType
                && DataContainerOperation::class === $type->getName()
            ) {
                $callback->{$operation['button_callback'][1]}($config);
            } else {
                if (!$legacyCallback) {
                    throw new \RuntimeException('Cannot handle legacy button_callback, provide the $legacyCallback');
                }

                $legacyCallback($config);
            }
        } elseif (\is_callable($operation['button_callback'] ?? null)) {
            $ref = new \ReflectionFunction($operation['button_callback']);

            if (
                1 === $ref->getNumberOfParameters()
                && ($type = $ref->getParameters()[0]->getType())
                && $type instanceof \ReflectionNamedType
                && DataContainerOperation::class === $type->getName()
            ) {
                $operation['button_callback']($config);
            } else {
                if (!$legacyCallback) {
                    throw new \RuntimeException('Cannot handle legacy button_callback, provide the $legacyCallback');
                }

                $legacyCallback($config);
            }
        }

        if (null !== ($html = $config->getHtml())) {
            if ('' === $html) {
                return null;
            }

            return [
                'html' => $html,
            ];
        }

        $href = $this->generateHref($config);

        if ($config['icon'] ?? null) {
            $config['icon'] = Image::getPath($config['icon']);
            $config['attributes']->addClass('header_icon');
            $config['attributes']->addStyle(\sprintf('background-image:url(\'%s\')', Controller::addAssetsUrlTo($config['icon'])));
        }

        return [
            'href' => $href,
            'label' => $config['label'],
            'title' => $config['title'],
            'attributes' => $config['attributes'],
        ];
    }

    private function generateHref(DataContainerOperation $config): string|null
    {
        if (null !== $config->getUrl()) {
            return $config->getUrl();
        }

        if (!empty($config['route'])) {
            return $this->urlGenerator->generate($config['route']);
        }

        if (isset($config['href'])) {
            if (!str_contains($config['href'], '=') || str_contains($config['href'], '?')) {
                return $config['href'];
            }

            return Backend::addToUrl($config['href'], addRequestToken: !($config['prefetch'] ?? false));
        }

        return null;
    }

    private function ensureInitialized(): void
    {
        if (null === $this->operations) {
            throw new \RuntimeException(self::class.' has not been initialized yet.');
        }
    }
}
