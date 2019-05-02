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

class ArticlePickerProvider extends AbstractPickerProvider implements DcaPickerProviderInterface
{
    protected const DEFAULT_INSERTTAG = '{{article_url::%s}}';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'articlePicker';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsContext($context): bool
    {
        return 'link' === $context && $this->getUser()->hasAccess('article', 'modules');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsValue(PickerConfig $config): bool
    {
        return false !== strpos($config->getValue(), $this->getInsertTagChunks($config, self::DEFAULT_INSERTTAG)[0]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaTable(): string
    {
        return 'tl_article';
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaAttributes(PickerConfig $config): array
    {
        $attributes = ['fieldType' => 'radio'];

        if ($source = $config->getExtra('source')) {
            $attributes['preserveRecord'] = $source;
        }

        if ($this->supportsValue($config)) {
            $attributes['value'] = str_replace(
                $this->getInsertTagChunks($config, self::DEFAULT_INSERTTAG),
                '',
                $config->getValue()
            );
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function convertDcaValue(PickerConfig $config, $value): string
    {
        return sprintf($this->getInsertTag($config, self::DEFAULT_INSERTTAG), $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config = null): array
    {
        return ['do' => 'article'];
    }
}
