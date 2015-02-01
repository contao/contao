<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Collection Contao debug information for web profiler.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoDataCollector extends DataCollector
{
    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = $GLOBALS['TL_DEBUG'];
    }

    /**
     * Get class aliases.
     *
     * @return array The aliased classes.
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
                'original' => $original
            ];
        }

        ksort($aliases);

        return $aliases;
    }

    /**
     * Get classes set.
     *
     * @return array The classes set.
     */
    public function getClassesSet()
    {
        $data = $this->getData('classes_set');

        sort($data);

        return $data;
    }

    /**
     * Get database queries.
     *
     * @return array The database queries.
     */
    public function getDatabaseQueries()
    {
        return $this->getData('database_queries');
    }

    /**
     * Get additional data added by unknown sources.
     *
     * @return array The additional data.
     */
    public function getAdditionalData()
    {
        $data = $this->data;

        if (!is_array($data)) {
            return [];
        }

        unset($data['classes_aliased']);
        unset($data['classes_set']);
        unset($data['database_queries']);

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
     * Get data from the debug information, making sure it's an array
     *
     * @param string $key
     *
     * @return array
     */
    private function getData($key)
    {
        $data = $this->data[$key];

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }
}
