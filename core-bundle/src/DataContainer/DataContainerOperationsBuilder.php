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
    private array|null $operations = null;

    public function __construct(
        private readonly Environment $twig,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __toString(): string
    {
        if (null === $this->operations) {
            return '';
        }

        return $this->twig->render('@Contao/backend/data_container/operations.html.twig', [
            'operations' => $this->operations,
        ]);
    }

    public function initializeButtons(string $table, array $record, DataContainer $dataContainer, callable|null $legacyCallback = null): self
    {
        if (null !== $this->operations) {
            throw new \RuntimeException(self::class.' has already been initialized.');
        }

        $builder = clone $this;
        $builder->operations = [];

        if (!\is_array($GLOBALS['TL_DCA'][$table]['list']['operations'] ?? null)) {
            return $this;
        }

        foreach ($GLOBALS['TL_DCA'][$table]['list']['operations'] as $k => $v) {
            $v = \is_array($v) ? $v : [$v];
            $operation = $this->generateOperation($k, $v, $table, $record, $dataContainer, $legacyCallback);

            if ($operation) {
                $builder->operations[] = $operation;
            }
        }

        return $builder;
    }

    public function initializeHeaderButtons(string $table, array $record, DataContainer $dataContainer, callable|null $legacyCallback = null): self
    {
        if (null !== $this->operations) {
            throw new \RuntimeException(self::class.' has already been initialized.');
        }

        $builder = clone $this;
        $builder->operations = [];

        if (!\is_array($GLOBALS['TL_DCA'][$table]['list']['operations'] ?? null)) {
            return $this;
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

            $operation = $this->generateOperation($k, $v, $table, $record, $dataContainer, $legacyCallback);

            if ($operation) {
                $builder->operations[] = $operation;
            }
        }

        return $builder;
    }

    public function prepend(array $operation): self
    {
        if (null === $this->operations) {
            throw new \RuntimeException(self::class.' has not been initialized yet.');
        }

        array_unshift($this->operations, $operation);

        return $this;
    }

    public function append(array $operation): self
    {
        if (null === $this->operations) {
            throw new \RuntimeException(self::class.' has not been initialized yet.');
        }

        $this->operations[] = $operation;

        return $this;
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
                'primary' => (bool) ($config['primary'] ?? false),
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
            'primary' => (bool) ($config['primary'] ?? false),
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
            return Backend::addToUrl($config['href'].'&amp;id='.$record['id'].(Input::get('nb') ? '&amp;nc=1' : '').($isPopup ? '&amp;popup=1' : ''));
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
            'attributes' => ' data-title="'.StringUtil::specialchars($config['title']).'" data-title-disabled="'.StringUtil::specialchars($titleDisabled).'" data-action="contao--scroll-offset#store" onclick="return AjaxRequest.toggleField(this,'.('visible.svg' === $icon ? 'true' : 'false').')"',
            'icon' => Image::getHtml($state ? $icon : $_icon, $config['label'], 'data-icon="'.$icon.'" data-icon-disabled="'.$_icon.'" data-state="'.$state.'"'),
            'primary' => (bool) ($config['primary'] ?? false),
        ];
    }
}
