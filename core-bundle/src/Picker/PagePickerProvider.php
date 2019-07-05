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

class PagePickerProvider extends AbstractPickerProvider implements DcaPickerProviderInterface
{
    protected const INSERTTAG = '{{link_url::%s}}';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'pagePicker';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsContext($context): bool
    {
        return \in_array($context, ['page', 'link'], true) && $this->getUser()->hasAccess('page', 'modules');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsValue(PickerConfig $config): bool
    {
        if ('page' === $config->getContext()) {
            return is_numeric($config->getValue());
        }

        return $this->isMatchingTag($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaTable(): string
    {
        return 'tl_page';
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaAttributes(PickerConfig $config): array
    {
        $value = $config->getValue();
        $attributes = ['fieldType' => 'radio'];

        if ('page' === $config->getContext()) {
            if ($fieldType = $config->getExtra('fieldType')) {
                $attributes['fieldType'] = $fieldType;
            }

            if ($source = $config->getExtra('source')) {
                $attributes['preserveRecord'] = $source;
            }

            if (\is_array($rootNodes = $config->getExtra('rootNodes'))) {
                $attributes['rootNodes'] = $rootNodes;
            }

            if ($value) {
                $intval = static function ($val) {
                    return (int) $val;
                };

                $attributes['value'] = array_map($intval, explode(',', $value));
            }

            return $attributes;
        }

        $chunks = $this->getInsertTagChunks($config);

        if ($value && false !== strpos($value, $chunks[0])) {
            $attributes['value'] = str_replace($chunks, '', $value);
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function convertDcaValue(PickerConfig $config, $value)
    {
        if ('page' === $config->getContext()) {
            return (int) $value;
        }

        return sprintf($this->getInsertTag($config), $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config = null): array
    {
        return ['do' => 'page'];
    }
}
