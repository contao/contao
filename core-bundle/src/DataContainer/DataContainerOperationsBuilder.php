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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * @internal
 */
class DataContainerOperationsBuilder implements \Stringable
{
    private int|string|null $id = null;

    /**
     * @var list<array{html: string, primary?: bool}|array{
     *     href: string,
     *     popup?: bool,
     *     title: string,
     *     label: string,
     *     attributes: HtmlAttributes,
     *     icon: string,
     *     primary?: bool|null
     * }>
     */
    private array|null $operations = null;

    public function __construct(
        private readonly Environment $twig,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __toString(): string
    {
        if (!$this->operations) {
            return '';
        }

        return $this->twig->render('@Contao/backend/data_container/operations.html.twig', [
            'id' => $this->id,
            'operations' => $this->operations,
            'has_primary' => [] !== array_filter(array_column($this->operations, 'primary'), static fn ($v) => null !== $v),
        ]);
    }

    public function initialize(int|string|null $id = null): self
    {
        if (null !== $this->operations) {
            throw new \RuntimeException(self::class.' has already been initialized.');
        }

        $builder = clone $this;
        $builder->id = $id;
        $builder->operations = [];

        return $builder;
    }

    public function initializeWithButtons(string $table, array $record, DataContainer $dataContainer, callable|null $legacyCallback = null): self
    {
        $builder = $this->initialize($record['id'] ?? null);

        if (!\is_array($GLOBALS['TL_DCA'][$table]['list']['operations'] ?? null)) {
            return $builder;
        }

        foreach ($GLOBALS['TL_DCA'][$table]['list']['operations'] as $k => $v) {
            $v = \is_array($v) ? $v : [$v];
            $operation = $builder->generateOperation($k, $v, $table, $record, $dataContainer, $legacyCallback);

            if ($operation) {
                $builder->append($operation);
            }
        }

        return $builder;
    }

    public function initializeWithHeaderButtons(string $table, array $record, DataContainer $dataContainer, callable|null $legacyCallback = null): self
    {
        $builder = $this->initialize($record['id'] ?? null);

        if (!\is_array($GLOBALS['TL_DCA'][$table]['list']['operations'] ?? null)) {
            return $builder;
        }

        foreach ($GLOBALS['TL_DCA'][$table]['list']['operations'] as $k => $v) {
            // Show edit operation in the header by default (backwards compatibility)
            if ('edit' === $k && !isset($v['showInHeader'])) {
                $v['showInHeader'] = true;
            }

            if (empty($v['showInHeader']) || ('select' === Input::get('act') && !($v['showOnSelect'] ?? null))) {
                continue;
            }

            $v = \is_array($v) ? $v : [$v];

            // Add the parent table to the href
            if (isset($v['href'])) {
                $v['href'] .= '&amp;table='.$table;
            } else {
                $v['href'] = 'table='.$table;
            }

            $operation = $builder->generateOperation($k, $v, $table, $record, $dataContainer, $legacyCallback);

            if ($operation) {
                $builder->append($operation);
            }
        }

        return $builder;
    }

    /**
     * @param array{html: string, primary?: bool}|array{
     *     href: string,
     *     popup?: bool,
     *     title: string,
     *     label: string,
     *     attributes: HtmlAttributes,
     *     icon: string,
     *     primary?: bool|null
     * } $operation
     */
    public function prepend(array $operation, bool $parseHtml = false): self
    {
        if (null === $this->operations) {
            throw new \RuntimeException(self::class.' has not been initialized yet.');
        }

        if ($parseHtml) {
            array_unshift($this->operations, ...$this->parseOperationsHtml($operation));
        } else {
            array_unshift($this->operations, $operation);
        }

        return $this;
    }

    /**
     * @param array{html: string, primary?: bool}|array{
     *     href: string,
     *     popup?: bool,
     *     title: string,
     *     label: string,
     *     attributes: HtmlAttributes,
     *     icon: string,
     *     primary?: bool|null
     * } $operation
     */
    public function append(array $operation, bool $parseHtml = false): self
    {
        if (null === $this->operations) {
            throw new \RuntimeException(self::class.' has not been initialized yet.');
        }

        if ($parseHtml) {
            array_push($this->operations, ...$this->parseOperationsHtml($operation));
        } else {
            $this->operations[] = $operation;
        }

        return $this;
    }

    /**
     * Generate multiple operations if the given operation is using HTML.
     */
    private function parseOperationsHtml(array $operation): array
    {
        if (!isset($operation['html']) || '' === trim((string) $operation['html'])) {
            return [$operation];
        }

        $xml = new \DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->loadHTML('<?xml encoding="UTF-8">'.$operation['html']);

        $body = $xml->getElementsByTagName('body')[0];

        if ($body->childNodes->length < 2) {
            return [$operation];
        }

        $operations = [];
        $current = null;

        foreach ($body->childNodes as $node) {
            if ($node instanceof \DOMText) {
                if ('' === trim($html = $xml->saveHTML($node))) {
                    continue;
                }

                if ($current) {
                    $current['html'] .= $html;
                    continue;
                }
            }

            if ($current) {
                $operations[] = $current;
            }

            $current = $operation;
            $current['html'] = $xml->saveHTML($node);

            if ('a' === strtolower($node->nodeName)) {
                $operations[] = $current;
                $current = null;
            }
        }

        $operations[] = $current;

        return $operations;
    }

    private function generateOperation(string $name, array $operation, string $table, array $record, DataContainer $dataContainer, callable|null $legacyCallback = null): array|null
    {
        $config = new DataContainerOperation($name, $operation, $record, $dataContainer);

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
            } elseif ($legacyCallback) {
                $legacyCallback($config);
            } else {
                throw new \RuntimeException('Cannot handle legacy button_callback, provide the $legacyCallback');
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
            } elseif ($legacyCallback) {
                $legacyCallback($config);
            } else {
                throw new \RuntimeException('Cannot handle legacy button_callback, provide the $legacyCallback');
            }
        }

        if (null !== ($html = $config->getHtml())) {
            if ('' === $html) {
                return null;
            }

            return [
                'html' => $html,
                'primary' => $config['primary'] ?? null,
            ];
        }

        $isPopup = $this->isPopup($name);
        $href = $this->generateHref($config, $record, $isPopup);

        if (false !== ($toggle = $this->handleToggle($config, $table, $record, $operation, $href))) {
            return $toggle;
        }

        return [
            'href' => $href,
            'title' => $config['title'],
            'popup' => $isPopup,
            'label' => $config['label'],
            'attributes' => $config['attributes'],
            'icon' => Image::getHtml($config['icon'], $config['label']),
            'primary' => $config['primary'] ?? null,
        ];
    }

    private function generateHref(DataContainerOperation $config, array $record, bool $isPopup): string|null
    {
        if (null !== $config->getUrl()) {
            return $config->getUrl();
        }

        if (!empty($config['route'])) {
            $params = ['id' => $record['id']];

            if ($isPopup) {
                $params['popup'] = '1';
            }

            return $this->urlGenerator->generate($config['route'], $params);
        }

        if (isset($config['href'])) {
            return Backend::addToUrl($config['href'].'&amp;id='.$record['id'].(Input::get('nb') ? '&amp;nc=1' : '').($isPopup ? '&amp;popup=1' : ''), addRequestToken: !($config['prefetch'] ?? false));
        }

        return null;
    }

    private function isPopup(string $name): bool
    {
        return 'show' === $name;
    }

    /**
     * Returns true if this was a toggle operation (which is added to $operations).
     */
    private function handleToggle(DataContainerOperation $config, string $table, array $record, array $operation, string|null $href): array|false|null
    {
        parse_str(StringUtil::decodeEntities($config['href'] ?? $operation['href'] ?? ''), $params);

        if ('toggle' !== ($params['act'] ?? null) || !isset($params['field'])) {
            return false;
        }

        // Do not generate the toggle icon if the user does not have access to the field
        if (
            (
                true !== ($GLOBALS['TL_DCA'][$table]['fields'][$params['field']]['toggle'] ?? false)
                && true !== ($GLOBALS['TL_DCA'][$table]['fields'][$params['field']]['reverseToggle'] ?? false)
            ) || (
                DataContainer::isFieldExcluded($table, $params['field'])
                && !$this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $table.'::'.$params['field'])
            )
        ) {
            return null;
        }

        $icon = $config['icon'];
        $_icon = pathinfo($config['icon'], PATHINFO_FILENAME).'_.'.pathinfo($config['icon'], PATHINFO_EXTENSION);

        if (str_contains($config['icon'], '/')) {
            $_icon = \dirname($config['icon']).'/'.$_icon;
        }

        if ('visible.svg' === $icon) {
            $_icon = 'invisible.svg';
        } elseif ('featured.svg' === $icon) {
            $_icon = 'unfeatured.svg';
        }

        $state = $record[$params['field']] ? 1 : 0;

        if (($config['reverse'] ?? false) || ($GLOBALS['TL_DCA'][$table]['fields'][$params['field']]['reverseToggle'] ?? false)) {
            $state = $record[$params['field']] ? 0 : 1;
        }

        if (isset($config['titleDisabled'])) {
            $titleDisabled = $config['titleDisabled'];
        } else {
            $titleDisabled = \is_array($operation['label']) && isset($operation['label'][2]) ? \sprintf($operation['label'][2], $record['id']) : $config['title'];
        }

        return [
            'href' => $href,
            'title' => $state ? $config['title'] : $titleDisabled,
            'label' => $config['label'],
            'attributes' => $config['attributes']->set('data-action', 'contao--scroll-offset#store')->set('onclick', 'return AjaxRequest.toggleField(this,'.('visible.svg' === $icon ? 'true' : 'false').')'),
            'icon' => Image::getHtml($state ? $icon : $_icon, $state ? $config['title'] : $titleDisabled, 'data-icon="'.$icon.'" data-icon-disabled="'.$_icon.'" data-state="'.$state.'" data-alt="'.StringUtil::specialchars($config['title']).'" data-alt-disabled="'.StringUtil::specialchars($titleDisabled).'"'),
            'primary' => $config['primary'] ?? null,
        ];
    }
}
