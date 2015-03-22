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
use Contao\System;
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
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        if (!isset($GLOBALS['TL_DEBUG'])) {
            return;
        }

        $this->data = $GLOBALS['TL_DEBUG'];

        $this->addSummaryData();
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
     * Returns the database queries.
     *
     * @return array The database queries
     */
    public function getDatabaseQueries()
    {
        return $this->getData('database_queries');
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
        $data = $this->data[$key];

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    /**
     * Builds the summary data.
     */
    private function addSummaryData()
    {
        $intReturned = 0;
        $intAffected = 0;

        // Count the totals (see #3884)
        if (is_array($GLOBALS['TL_DEBUG']['database_queries'])) {
            foreach ($GLOBALS['TL_DEBUG']['database_queries'] as $k => $v) {
                $intReturned += $v['return_count'];
                $intAffected += $v['affected_count'];
                unset($GLOBALS['TL_DEBUG']['database_queries'][$k]['return_count']);
                unset($GLOBALS['TL_DEBUG']['database_queries'][$k]['affected_count']);
            }
        }

        $intElapsed = (microtime(true) - TL_START);

        $this->data['summary'] = [
            'execution_time' => System::getFormattedNumber(($intElapsed * 1000), 0),
            'memory'         => System::getReadableSize(memory_get_peak_usage()),
            'dbqueries'      => count($GLOBALS['TL_DEBUG']['database_queries']),
            'rows_returned'  => $intReturned,
            'rows_affected'  => $intAffected,
            'models'         => Registry::getInstance()->count(),
        ];
    }
}
