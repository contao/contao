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
    protected const DEFAULT_INSERTTAG = '{{link_url::%s}}';

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

        $insertTagChunks = explode('%s', $this->getInsertTag($config, self::DEFAULT_INSERTTAG), 2);

        return false !== strpos($config->getValue(), $insertTagChunks[0]);
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

        $insertTagChunks = explode('%s', $this->getInsertTag($config, self::DEFAULT_INSERTTAG), 2);

        if ($value && false !== strpos($value, $insertTagChunks[0])) {
            $attributes['value'] = str_replace($insertTagChunks, '', $value);
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

        return sprintf($this->getInsertTag($config, self::DEFAULT_INSERTTAG), $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config = null): array
    {
        return ['do' => 'page'];
    }
}
