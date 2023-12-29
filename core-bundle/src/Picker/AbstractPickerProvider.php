<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Picker;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractPickerProvider implements PickerProviderInterface
{
    public function __construct(
        private readonly FactoryInterface $menuFactory,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getUrl(PickerConfig $config): string|null
    {
        return $this->generateUrl($config, false);
    }

    public function createMenuItem(PickerConfig $config): ItemInterface
    {
        $name = $this->getName();
        $label = $this->translator->trans('MSC.'.$name, [], 'contao_default');

        return $this->menuFactory->createItem($name, [
            'label' => $label ?: $name,
            'linkAttributes' => ['class' => $name],
            'current' => $this->isCurrent($config),
            'uri' => $this->generateUrl($config, true),
        ]);
    }

    public function isCurrent(PickerConfig $config): bool
    {
        return $config->getCurrent() === $this->getName();
    }

    /**
     * Returns the routing parameters for the back end picker.
     *
     * @return array<string, string|int>
     */
    abstract protected function getRouteParameters(PickerConfig|null $config = null): array;

    /**
     * Generates the URL for the picker.
     */
    private function generateUrl(PickerConfig $config, bool $ignoreValue): string|null
    {
        $params = [
            ...$this->getRouteParameters($ignoreValue ? null : $config),
            'popup' => '1',
            'picker' => $config->cloneForCurrent($this->getName())->urlEncode(),
        ];

        return $this->router->generate('contao_backend', $params);
    }
}
