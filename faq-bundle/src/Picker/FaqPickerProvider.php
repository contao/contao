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
use Contao\CoreBundle\Picker\AbstractInsertTagPickerProvider;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Knp\Menu\FactoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class FaqPickerProvider extends AbstractInsertTagPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * @var Security
     */
    private $security;

    /**
     * @internal Do not inherit from this class; decorate the "contao_faq.picker.faq_provider" service instead
     */
    public function __construct(FactoryInterface $menuFactory, RouterInterface $router, ?TranslatorInterface $translator, Security $security)
    {
        parent::__construct($menuFactory, $router, $translator);

        $this->security = $security;
    }

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
        return 'link' === $context && $this->security->isGranted('contao_user.modules', 'faq');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsValue(PickerConfig $config): bool
    {
        return $this->isMatchingInsertTag($config);
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
            $attributes['value'] = $this->getInsertTagValue($config);
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function convertDcaValue(PickerConfig $config, $value): string
    {
        return sprintf($this->getInsertTag($config), $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config = null): array
    {
        $params = ['do' => 'faq'];

        if (null === $config || !$config->getValue() || !$this->supportsValue($config)) {
            return $params;
        }

        if (null !== ($faqId = $this->getFaqCategoryId($this->getInsertTagValue($config)))) {
            $params['table'] = 'tl_faq';
            $params['id'] = $faqId;
        }

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultInsertTag(): string
    {
        return '{{faq_url::%s}}';
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
