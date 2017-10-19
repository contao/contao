<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DataCollector;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
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
     * @var array
     */
    private $packages;

    /**
     * @param array $packages
     */
    public function __construct(array $packages)
    {
        $this->packages = $packages;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null): void
    {
        if (isset($this->packages['contao/core-bundle'])) {
            $this->data = ['contao_version' => $this->packages['contao/core-bundle']];
        }

        $this->addSummaryData();

        if (isset($GLOBALS['TL_DEBUG'])) {
            $this->data = array_merge($this->data, $GLOBALS['TL_DEBUG']);
        }
    }

    /**
     * Returns the Contao version and build number.
     *
     * @return string
     */
    public function getContaoVersion(): string
    {
        if (!isset($this->data['contao_version'])) {
            return '';
        }

        return $this->data['contao_version'];
    }

    /**
     * Returns the summary.
     *
     * @return array
     */
    public function getSummary(): array
    {
        return $this->getData('summary');
    }

    /**
     * Returns the set classes.
     *
     * @return array
     */
    public function getClassesSet(): array
    {
        $data = $this->getData('classes_set');

        sort($data);

        return $data;
    }

    /**
     * Returns the aliased classes.
     *
     * @return array
     */
    public function getClassesAliased(): array
    {
        $data = $this->getData('classes_aliased');

        ksort($data);

        return $data;
    }

    /**
     * Returns the composerized classes.
     *
     * @return array
     */
    public function getClassesComposerized(): array
    {
        $data = $this->getData('classes_composerized');

        ksort($data);

        return $data;
    }

    /**
     * Returns the additional data added by unknown sources.
     *
     * @return array
     */
    public function getAdditionalData(): array
    {
        $data = $this->data;

        if (!\is_array($data)) {
            return [];
        }

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
     * Returns the debug data as array.
     *
     * @param string $key
     *
     * @return array
     */
    private function getData($key): array
    {
        if (!isset($this->data[$key]) || !\is_array($this->data[$key])) {
            return [];
        }

        return $this->data[$key];
    }

    /**
     * Adds the summary data.
     */
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

    /**
     * Returns the page layout name (front end only).
     *
     * @return string
     */
    private function getLayoutName(): string
    {
        $layout = $this->getLayout();

        if (null === $layout) {
            return '';
        }

        return sprintf('%s (ID %s)', $layout->name, $layout->id);
    }

    /**
     * Returns the template name (front end only).
     *
     * @return string
     */
    private function getTemplateName(): string
    {
        $layout = $this->getLayout();

        if (null === $layout) {
            return '';
        }

        return $layout->template;
    }

    /**
     * Returns the layout model (front end only).
     *
     * @return LayoutModel|null
     */
    private function getLayout(): ?LayoutModel
    {
        if (!isset($GLOBALS['objPage'])) {
            return null;
        }

        /* @var PageModel $objPage */
        $objPage = $GLOBALS['objPage'];

        /** @var LayoutModel $layout */
        $layout = $this->framework->getAdapter(LayoutModel::class);

        return $layout->findByPk($objPage->layoutId);
    }
}
