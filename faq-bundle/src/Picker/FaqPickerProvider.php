<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Picker;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Picker\AbstractPickerProvider;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\FaqCategoryModel;
use Contao\FaqModel;

class FaqPickerProvider extends AbstractPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    protected const DEFAULT_INSERTTAG = '{{faq_url::%s}}';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'faqPicker';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsContext($context): bool
    {
        return 'link' === $context && $this->getUser()->hasAccess('faq', 'modules');
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
        return 'tl_faq';
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
        $params = ['do' => 'faq'];

        if (null === $config || !$config->getValue()) {
            return $params;
        }

        $insertTagChunks = $this->getInsertTagChunks($config, self::DEFAULT_INSERTTAG);

        if (false === strpos($config->getValue(), $insertTagChunks[0])) {
            return $params;
        }

        $value = str_replace($insertTagChunks, '', $config->getValue());

        if (null !== ($faqId = $this->getFaqCategoryId($value))) {
            $params['table'] = 'tl_faq';
            $params['id'] = $faqId;
        }

        return $params;
    }

    /**
     * @param int|string $id
     */
    private function getFaqCategoryId($id): ?int
    {
        /** @var FaqModel $faqAdapter */
        $faqAdapter = $this->framework->getAdapter(FaqModel::class);

        if (!($faqModel = $faqAdapter->findById($id)) instanceof FaqModel) {
            return null;
        }

        if (!($faqCategory = $faqModel->getRelated('pid')) instanceof FaqCategoryModel) {
            return null;
        }

        return (int) $faqCategory->id;
    }
}
