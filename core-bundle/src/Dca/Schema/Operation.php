<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Schema;

use Contao\Backend;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Image;
use Contao\StringUtil;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Object representation of an operation.
 */
class Operation extends Schema implements ServiceSubscriberSchemaInterface
{
    protected array $schemaClasses = [
        'button_callback' => Callback::class,
    ];

    private ContainerInterface $locator;

    /**
     * @return Callback<mixed, string>
     */
    public function buttonCallback(): Callback
    {
        return $this->getSchema('button_callback', Callback::class);
    }

    /**
     * @return Callback<mixed, bool>
     */
    public function permissionCallback(): Callback
    {
        return $this->getSchema('permission_callback', Callback::class);
    }

    public function parseLabel(string $id = ''): string
    {
        if ($label = $this->get('label')) {
            if (\is_array($label)) {
                $label = $label[0] ?? null;
            }

            $label = sprintf($label, $id);
        }

        return $label ?: $this->getName();
    }

    public function parseTitle(string $id = ''): string
    {
        if ($label = $this->get('label')) {
            $label = \is_array($label) ? sprintf($label[1] ?? $label[0] ?? '', $id) : sprintf($label, $id);
        }

        return $label ?: $this->getName();
    }

    public function parseToggleReverseTitle(string $id = ''): string
    {
        if ($label = $this->get('label')) {
            $label = \is_array($label) ? sprintf($label[2] ?? $label[0] ?? '', $id) : sprintf($label, $id);
        }

        return $label ?: $this->getName();
    }

    public function parseAttributes(string $id): string
    {
        $attributes = ltrim(sprintf($this->get('attributes') ?? '', $id, $id));
        $classes = $this->getName().($this->get('class') ? ' '.$this->get('class') : '');

        if (str_contains($attributes, 'class="')) {
            $attributes = ' '.str_replace('class="', 'class="'.$classes.' ', $attributes);
        } else {
            $attributes = ' class="'.$classes.'" '.$attributes;
        }

        return rtrim($attributes);
    }

    public function parseHref(array $params = []): string
    {
        if ($route = $this->get('route')) {
            return $this->locator->get('router')->generate($route, $params);
        }

        $href = $this->get('href');

        if ($id = $params['id'] ?? null) {
            $href .= '&amp;id='.$id;
        }

        if ($params['popup'] ?? null) {
            $href .= '&amp;popup=1';
        }

        if ($params['nb'] ?? null) {
            $href .= '&amp;nc=1';
        }

        return $this->locator->get('contao.framework')->getAdapter(Backend::class)->addToUrl($href);
    }

    public function parseIcon(string $id = '', int $toggleState = 0): string
    {
        $icon = $this->get('icon');
        $attributes = '';

        if ($this->isDisabled()) {
            $icon = str_replace('.svg', '--disabled.svg', $icon);
        }

        if ($this->isToggle()) {
            $field = $this->getDca()->fields()->field($this->getParam('field'));

            if ($this->isReverse() || $field->isReverseToggle()) {
                $toggleState = $toggleState ? 0 : 1;
            }

            $_icon = pathinfo($icon, PATHINFO_FILENAME).'_.'.pathinfo($icon, PATHINFO_EXTENSION);

            if (str_contains($icon, '/')) {
                $_icon = \dirname($icon).'/'.$_icon;
            }

            if ('visible.svg' === $icon) {
                $_icon = 'invisible.svg';
            }

            $attributes .= ' data-icon="'.$icon.'" data-icon-disabled="'.$_icon.'" data-state="'.$toggleState.'"';

            if (0 === $toggleState) {
                $icon = $_icon;
            }
        }

        return $this->locator->get('contao.framework')->getAdapter(Image::class)->getHtml($icon, $this->parseLabel($id), trim($attributes));
    }

    public function setLocator(ContainerInterface $locator): void
    {
        $this->locator = $locator;
    }

    public function isToggle(): bool
    {
        return 'toggle' === $this->getParam('act') && null !== $this->getParam('field');
    }

    public function isReverse(): bool
    {
        return $this->get('reverse') ?? false;
    }

    public function getParams(): array
    {
        $params = [];
        parse_str(StringUtil::decodeEntities($this->parseHref()), $params);

        return $params;
    }

    public function getParam(string $key): string|null
    {
        return $this->getParams()[$key] ?? null;
    }

    public function isHidden(): bool
    {
        return $this->is('hidden');
    }

    public function isDisabled(): bool
    {
        return $this->is('disabled');
    }

    public function setDisabled(bool $disabled): void
    {
        $this->data->set('disabled', $disabled);
    }

    public static function getSubscribedServices(): array
    {
        return [
            'contao.framework' => ContaoFramework::class,
            'router' => RouterInterface::class,
        ];
    }
}
