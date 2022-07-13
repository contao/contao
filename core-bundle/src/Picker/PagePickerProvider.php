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
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class PagePickerProvider extends AbstractInsertTagPickerProvider implements DcaPickerProviderInterface
{
    private Security $security;

    /**
     * @internal Do not inherit from this class; decorate the "contao.picker.page_provider" service instead
     */
    public function __construct(FactoryInterface $menuFactory, RouterInterface $router, ?TranslatorInterface $translator, Security $security)
    {
        parent::__construct($menuFactory, $router, $translator);

        $this->security = $security;
    }

    public function getName(): string
    {
        return 'pagePicker';
    }

    public function supportsContext($context): bool
    {
        return \in_array($context, ['page', 'link'], true) && $this->security->isGranted('contao_user.modules', 'page');
    }

    public function supportsValue(PickerConfig $config): bool
    {
        if ('page' === $config->getContext()) {
            return is_numeric($config->getValue());
        }

        return $this->isMatchingInsertTag($config);
    }

    public function getDcaTable(): string
    {
        return 'tl_page';
    }

    public function getDcaAttributes(PickerConfig $config): array
    {
        $value = $config->getValue();
        $attributes = ['fieldType' => 'radio'];

        if ('page' === $config->getContext()) {
            if ($fieldType = $config->getExtra('fieldType')) {
                $attributes['fieldType'] = $fieldType;
            }

            if (\is_array($rootNodes = $config->getExtra('rootNodes'))) {
                $attributes['rootNodes'] = $rootNodes;
            }

            if ($value) {
                $attributes['value'] = array_map('\intval', explode(',', $value));
            }

            return $attributes;
        }

        if ($value && $this->isMatchingInsertTag($config)) {
            $attributes['value'] = $this->getInsertTagValue($config);

            if ($flags = $this->getInsertTagFlags($config)) {
                $attributes['flags'] = $flags;
            }
        }

        return $attributes;
    }

    /**
     * @param mixed $value
     *
     * @return string|int
     */
    public function convertDcaValue(PickerConfig $config, $value)
    {
        if ('page' === $config->getContext()) {
            return (int) $value;
        }

        return sprintf($this->getInsertTag($config), $value);
    }

    protected function getRouteParameters(PickerConfig $config = null): array
    {
        return ['do' => 'page'];
    }

    protected function getDefaultInsertTag(): string
    {
        return '{{link_url::%s}}';
    }
}
