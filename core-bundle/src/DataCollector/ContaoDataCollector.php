<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DataCollector;

use Contao\CoreBundle\Framework\ScopeAwareTrait;
use Contao\LayoutModel;
use Contao\Model\Registry;
use Contao\PageModel;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
    use ScopeAwareTrait;

    /**
     * @var array
     */
    private $packages;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container The container object
     * @param array              $packages  The Composer packages
     */
    public function __construct(ContainerInterface $container, array $packages)
    {
        $this->container = $container;
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
     * @return string The version number
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
     * @return array The summary
     */
    public function getSummary()
    {
        return $this->getData('summary');
    }

    /**
     * Returns the aliased classes.
     *
     * @return array The aliased classes
     */
    public function getClassesAliased()
    {
        $aliases = [];
        $data = $this->getData('classes_aliased');

        foreach ($data as $class) {
            $alias = $class;
            $original = '';
            $pos = strpos($class, '<span');

            if (false !== $pos) {
                $alias = trim(substr($class, 0, $pos));
                $original = trim(strip_tags(substr($class, $pos)), ' ()');
            }

            $aliases[$alias] = [
                'alias' => $alias,
                'original' => $original,
            ];
        }

        ksort($aliases);

        return $aliases;
    }

    /**
     * Returns the set classes.
     *
     * @return array The set classes
     */
    public function getClassesSet()
    {
        $data = $this->getData('classes_set');

        sort($data);

        return $data;
    }

    /**
     * Returns the unknown insert tags.
     *
     * @return array The insert tags
     */
    public function getUnknownInsertTags()
    {
        return $this->getData('unknown_insert_tags');
    }

    /**
     * Returns the unknown insert tag flags.
     *
     * @return array The insert tag flags
     */
    public function getUnknownInsertTagFlags()
    {
        return $this->getData('unknown_insert_tag_flags');
    }

    /**
     * Returns the additional data added by unknown sources.
     *
     * @return array The additional data
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
            $data['classes_aliased'],
            $data['classes_set'],
            $data['database_queries'],
            $data['unknown_insert_tags'],
            $data['unknown_insert_tag_flags']
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
     * @param string $key The key
     *
     * @return array The debug data
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
            'version'   => $this->getContaoVersion(),
            'framework' => $framework,
            'models'    => $modelCount,
            'frontend'  => isset($GLOBALS['objPage']),
            'preview'   => defined('BE_USER_LOGGED_IN') && true === BE_USER_LOGGED_IN,
            'layout'    => $this->getLayoutName(),
            'template'  => $this->getTemplateName(),
        ];
    }

    /**
     * Returns the name of the current page layout (front end only).
     *
     * @return string The layout name
     */
    private function getLayoutName()
    {
        $layout = $this->getLayout();

        if (null === $layout) {
            return '';
        }

        return sprintf('%s (ID %s)', $layout->name, $layout->id);
    }

    private function getTemplateName()
    {
        $layout = $this->getLayout();

        if (null === $layout) {
            return '';
        }

        return $layout->template;
    }

    /**
     * @return LayoutModel|null
     * @throws \Exception
     */
    private function getLayout()
    {
        /** @var PageModel $objPage */
        global $objPage;

        /** @var LayoutModel $layout */
        if (null === $objPage) {
            return null;
        }

        return $objPage->getRelated('layout');
    }
}
