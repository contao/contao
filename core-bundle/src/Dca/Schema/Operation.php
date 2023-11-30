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

        return $this->locator->get('framework')->getAdapter(Backend::class)->addToUrl($href);
    }

    public function parseIcon(string $id = ''): string
    {
        return $this->locator->get('framework')->getAdapter(Image::class)->getHtml($this->get('icon'), $this->parseLabel($id));
    }

    public function setLocator(ContainerInterface $locator): void
    {
        $this->locator = $locator;
    }

    public static function getSubscribedServices(): array
    {
        return [
            'framework' => ContaoFramework::class,
            'router' => RouterInterface::class,
        ];
    }
}
