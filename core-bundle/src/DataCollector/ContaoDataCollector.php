<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DataCollector;

use Contao\Model\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Collects debug information for the web profiler.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * @todo Rework when we have the Doctrine driver
 */
class ContaoDataCollector extends DataCollector
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $bundles;

    /**
     * @var array
     */
    private $packages;

    /**
     * Constructor.
     *
     * @param array $packages Installed Composer packages and versions
     */
    public function __construct(ContainerInterface $container, array $bundles, array $packages)
    {
        $this->container = $container;
        $this->bundles   = $bundles;
        $this->packages  = $packages;
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
     * Returns the Contao version and build.
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
        $data    = $this->getData('classes_aliased');

        foreach ($data as $v) {
            $alias    = $v;
            $original = '';
            $pos      = strpos($v, '<span');

            if (false !== $pos) {
                $alias    = trim(substr($v, 0, $pos));
                $original = trim(strip_tags(substr($v, $pos)), ' ()');
            }

            $aliases[$alias] = [
                'alias'    => $alias,
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

        unset($data['summary']);
        unset($data['contao_version']);
        unset($data['classes_aliased']);
        unset($data['classes_set']);
        unset($data['database_queries']);
        unset($data['unknown_insert_tags']);
        unset($data['unknown_insert_tag_flags']);

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
     * Builds the summary data.
     */
    private function addSummaryData()
    {
        $framework    = false;
        $modelCount   = '';

        if (isset($GLOBALS['TL_DEBUG'])) {
            $modelCount = Registry::getInstance()->count();
            $framework  = true;
        }

        $this->data['summary'] = [
            'scope'          => $this->getContainerScope(),
            'framework'      => $framework,
            'modules'        => $this->getModules(),
            'models'         => $modelCount,
        ];
    }

    /**
     * Gets the scope from the container.
     *
     * @return string
     */
    private function getContainerScope()
    {
        if ($this->container->isScopeActive('frontend')) {
            return 'frontend';
        }

        if ($this->container->isScopeActive('backend')) {
            return 'backend';
        }

        return '';
    }

    /**
     * Gets a list of Contao modules (in system/modules).
     *
     * @return array
     */
    private function getModules()
    {
        $modules = [];

        foreach ($this->bundles as $name => $class) {
            if ('Contao\\CoreBundle\\HttpKernel\\Bundle\\ContaoModuleBundle' === $class) {
                $modules[] = $name;
            }
        }

        return $modules;
    }
}
