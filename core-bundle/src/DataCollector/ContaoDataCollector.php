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

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Util\PackageUtil;
use Contao\LayoutModel;
use Contao\Model\Registry;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class ContaoDataCollector extends DataCollector implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null): void
    {
        try {
            $version = PackageUtil::getVersion('contao/core-bundle');
        } catch (\OutOfBoundsException $e) {
            $version = PackageUtil::getVersion('contao/contao');
        }

        $this->data = ['contao_version' => $version];

        $this->addSummaryData();

        if (isset($GLOBALS['TL_DEBUG'])) {
            $this->data = array_merge($this->data, $GLOBALS['TL_DEBUG']);
        }
    }

    public function getContaoVersion(): string
    {
        return $this->data['contao_version'];
    }

    /**
     * @return array<string,string|bool>
     */
    public function getSummary(): array
    {
        return $this->getData('summary');
    }

    /**
     * @return string[]
     */
    public function getClassesSet(): array
    {
        $data = $this->getData('classes_set');

        sort($data);

        return $data;
    }

    /**
     * @return string[]
     */
    public function getClassesAliased(): array
    {
        $data = $this->getData('classes_aliased');

        ksort($data);

        return $data;
    }

    /**
     * @return string[]
     */
    public function getClassesComposerized(): array
    {
        $data = $this->getData('classes_composerized');

        ksort($data);

        return $data;
    }

    /**
     * @return mixed[]
     */
    public function getAdditionalData(): array
    {
        $data = $this->data;

        unset(
            $data['summary'],
            $data['contao_version'],
            $data['classes_set'],
            $data['classes_aliased'],
            $data['classes_composerized'],
            $data['database_queries']
        );

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'contao';
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
        $this->data = [];
    }

    /**
     * @return array<string,string|bool>
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
            'preview' => \defined('BE_USER_LOGGED_IN') && true === BE_USER_LOGGED_IN,
            'layout' => $this->getLayoutName(),
            'template' => $this->getTemplateName(),
        ];
    }

    private function getLayoutName(): string
    {
        $layout = $this->getLayout();

        if (null === $layout) {
            return '';
        }

        return sprintf('%s (ID %s)', $layout->name, $layout->id);
    }

    private function getTemplateName(): string
    {
        $layout = $this->getLayout();

        if (null === $layout) {
            return '';
        }

        return $layout->template;
    }

    private function getLayout(): ?LayoutModel
    {
        if (!isset($GLOBALS['objPage'])) {
            return null;
        }

        /** @var PageModel $objPage */
        $objPage = $GLOBALS['objPage'];

        /** @var LayoutModel $layout */
        $layout = $this->framework->getAdapter(LayoutModel::class);

        return $layout->findByPk($objPage->layoutId);
    }
}
