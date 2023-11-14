<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataCollector;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\LayoutModel;
use Contao\Model\Registry;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * @internal
 */
class ContaoDataCollector extends DataCollector implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    public function __construct(private readonly TokenChecker $tokenChecker)
    {
    }

    #[\Override]
    public function collect(Request $request, Response $response, \Throwable|null $exception = null): void
    {
        $this->data = ['contao_version' => ContaoCoreBundle::getVersion()];

        $this->addSummaryData();

        if (isset($GLOBALS['TL_DEBUG'])) {
            $this->data = [...$this->data, ...$GLOBALS['TL_DEBUG']];
        }
    }

    public function getContaoVersion(): string
    {
        return $this->data['contao_version'];
    }

    /**
     * @return array<string, string|bool>
     */
    public function getSummary(): array
    {
        return $this->getData('summary');
    }

    /**
     * @return array<string|bool>
     */
    public function getClassesSet(): array
    {
        $data = $this->getData('classes_set');

        sort($data);

        return $data;
    }

    /**
     * @return array<string|bool>
     */
    public function getClassesAliased(): array
    {
        $data = $this->getData('classes_aliased');

        ksort($data);

        return $data;
    }

    /**
     * @return array<string|bool>
     */
    public function getClassesComposerized(): array
    {
        $data = $this->getData('classes_composerized');

        ksort($data);

        return $data;
    }

    public function getAdditionalData(): array
    {
        $data = $this->data;

        unset(
            $data['summary'],
            $data['contao_version'],
            $data['classes_set'],
            $data['classes_aliased'],
            $data['classes_composerized'],
            $data['database_queries'],
        );

        return $data;
    }

    #[\Override]
    public function getName(): string
    {
        return 'contao';
    }

    #[\Override]
    public function reset(): void
    {
        $this->data = [];
    }

    /**
     * @return array<string, string|bool>
     */
    private function getData(string $key): array
    {
        if (!isset($this->data[$key]) || !\is_array($this->data[$key])) {
            return [];
        }

        return $this->data[$key];
    }

    private function addSummaryData(): void
    {
        $framework = false;
        $modelCount = 0;

        if (isset($GLOBALS['TL_DEBUG'])) {
            $framework = true;
            $modelCount = Registry::getInstance()->count();
        }

        $this->data['summary'] = [
            'version' => $this->getContaoVersion(),
            'framework' => $framework,
            'models' => $modelCount,
            'frontend' => isset($GLOBALS['objPage']),
            'preview' => $this->tokenChecker->isPreviewMode(),
            'layout' => $this->getLayoutName(),
            'template' => $this->getTemplateName(),
        ];
    }

    private function getLayoutName(): string
    {
        if (!$layout = $this->getLayout()) {
            return '';
        }

        return sprintf('%s (ID %s)', StringUtil::decodeEntities($layout->name), $layout->id);
    }

    private function getTemplateName(): string
    {
        if (!$layout = $this->getLayout()) {
            return '';
        }

        return $layout->template;
    }

    private function getLayout(): LayoutModel|null
    {
        if (!isset($GLOBALS['objPage'])) {
            return null;
        }

        /** @var PageModel $objPage */
        $objPage = $GLOBALS['objPage'];

        if (!$objPage->layoutId) {
            return null;
        }

        return $this->framework->getAdapter(LayoutModel::class)->findByPk($objPage->layoutId);
    }
}
