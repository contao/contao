<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DataCollector;

use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Framework\ScopeAwareTrait;
use Contao\LayoutModel;
use Contao\Model\Registry;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Collects debug information for the web profiler.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoDataCollector extends DataCollector
{
    use FrameworkAwareTrait;
    use ScopeAwareTrait;

    /**
     * @var array
     */
    private $packages;

    /**
     * Constructor.
     *
     * @param array $packages
     */
    public function __construct(array $packages)
    {
        $this->packages = $packages;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
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
    public function getContaoVersion()
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
    public function getSummary()
    {
        return $this->getData('summary');
    }

    /**
     * Returns the set classes.
     *
     * @return array
     */
    public function getClassesSet()
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
    public function getClassesAliased()
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
    public function getClassesComposerized()
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
    public function getAdditionalData()
    {
        $data = $this->data;

        if (!is_array($data)) {
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
    public function getName()
    {
        return 'contao';
    }

    /**
     * Returns the debug data as array.
     *
     * @param string $key
     *
     * @return array
     */
    private function getData($key)
    {
        if (!isset($this->data[$key]) || !is_array($this->data[$key])) {
            return [];
        }

        return $this->data[$key];
    }

    /**
     * Adds the summary data.
     */
    private function addSummaryData()
    {
        $framework = false;
        $modelCount = '0';

        if (isset($GLOBALS['TL_DEBUG'])) {
            $framework = true;
            $modelCount = Registry::getInstance()->count();
        }

        $this->data['summary'] = [
            'version' => $this->getContaoVersion(),
            'framework' => $framework,
            'models' => $modelCount,
            'frontend' => isset($GLOBALS['objPage']),
            'preview' => defined('BE_USER_LOGGED_IN') && true === BE_USER_LOGGED_IN,
            'layout' => $this->getLayoutName(),
            'template' => $this->getTemplateName(),
        ];
    }

    /**
     * Returns the page layout name (front end only).
     *
     * @return string
     */
    private function getLayoutName()
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
    private function getTemplateName()
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
    private function getLayout()
    {
        /* @var PageModel $objPage */
        global $objPage;

        if (null === $objPage) {
            return null;
        }

        /** @var LayoutModel $layout */
        $layout = $this->framework->getAdapter(LayoutModel::class);

        return $layout->findByPk($objPage->layoutId);
    }
}
