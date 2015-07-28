<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Database;

use Doctrine\DBAL\Connection;

/**
 * Runs the version 4.0.0 update.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Version400Update implements VersionUpdateInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * Constructor.
     *
     * @param Connection $connection The database connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoca.
     */
    public function shouldBeRun()
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist('tl_layout')) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_layout');

        if (isset($columns['script'])) {
            return false;
        }

        return true;
    }

    /**
     * Runs the update.
     */
    public function run()
    {
        // FIXME: version 4.0.0 update
        /*
        // Adjust the framework agnostic scripts
        $this->Database->query('ALTER TABLE `tl_layout` ADD `scripts` text NULL');
        $objLayout = $this->Database->query("SELECT id, addJQuery, jquery, addMooTools, mootools FROM tl_layout WHERE framework!=''");

        while ($objLayout->next()) {
            $arrScripts = [];

            // Check whether j_slider is enabled
            if ($objLayout->addJQuery) {
                $jquery = deserialize($objLayout->jquery);

                if (!empty($jquery) && is_array($jquery)) {
                    if (($key = array_search('j_slider', $jquery)) !== false) {
                        $arrScripts[] = 'js_slider';
                        unset($jquery[$key]);

                        $this->Database->prepare('UPDATE tl_layout SET jquery=? WHERE id=?')
                                       ->execute(serialize(array_values($jquery)), $objLayout->id);
                    }
                }
            }

            // Check whether moo_slider is enabled
            if ($objLayout->addMooTools) {
                $mootools = deserialize($objLayout->mootools);

                if (!empty($mootools) && is_array($mootools)) {
                    if (($key = array_search('moo_slider', $mootools)) !== false) {
                        $arrScripts[] = 'js_slider';
                        unset($mootools[$key]);

                        $this->Database->prepare('UPDATE tl_layout SET mootools=? WHERE id=?')
                                       ->execute(serialize(array_values($mootools)), $objLayout->id);
                    }
                }
            }

            // Enable the js_slider template
            if (!empty($arrScripts)) {
                $this->Database->prepare('UPDATE tl_layout SET scripts=? WHERE id=?')
                               ->execute(serialize(array_values(array_unique($arrScripts))), $objLayout->id);
            }
        }
        */
    }
}
