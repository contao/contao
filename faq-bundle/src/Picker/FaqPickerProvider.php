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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FaqPickerProvider extends AbstractInsertTagPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * @internal
     */
    public function __construct(
        FactoryInterface $menuFactory,
        RouterInterface $router,
        TranslatorInterface|null $translator,
        private readonly Security $security,
    ) {
        parent::__construct($menuFactory, $router, $translator);
    }

    #[\Override]
    public function getName(): string
    {
        return 'faqPicker';
    }

    #[\Override]
    public function supportsContext(string $context): bool
    {
        return 'link' === $context && $this->security->isGranted('contao_user.modules', 'faq');
    }

    #[\Override]
    public function supportsValue(PickerConfig $config): bool
    {
        return $this->isMatchingInsertTag($config);
    }

    public function getDcaTable(PickerConfig|null $config = null): string
    {
        return 'tl_faq';
    }

    public function getDcaAttributes(PickerConfig $config): array
    {
        $attributes = ['fieldType' => 'radio'];

        if ($this->supportsValue($config)) {
            $attributes['value'] = $this->getInsertTagValue($config);

            if ($flags = $this->getInsertTagFlags($config)) {
                $attributes['flags'] = $flags;
            }
        }

        return $attributes;
    }

    public function convertDcaValue(PickerConfig $config, mixed $value): string
    {
        return sprintf($this->getInsertTag($config), $value);
    }

    #[\Override]
    protected function getRouteParameters(PickerConfig|null $config = null): array
    {
        $params = ['do' => 'faq'];

        if (!$config || !$config->getValue() || !$this->supportsValue($config)) {
            return $params;
        }

        if (null !== ($faqId = $this->getFaqCategoryId($this->getInsertTagValue($config)))) {
            $params['table'] = 'tl_faq';
            $params['id'] = $faqId;
        }

        return $params;
    }

    #[\Override]
    protected function getDefaultInsertTag(): string
    {
        return '{{faq_url::%s}}';
    }

    private function getFaqCategoryId(int|string $id): int|null
    {
        $faqAdapter = $this->framework->getAdapter(FaqModel::class);
        $faqModel = $faqAdapter->findById($id);

        if (!$faqModel instanceof FaqModel) {
            return null;
        }

        $faqCategory = $faqModel->getRelated('pid');

        if (!$faqCategory instanceof FaqCategoryModel) {
            return null;
        }

        return (int) $faqCategory->id;
    }
}
