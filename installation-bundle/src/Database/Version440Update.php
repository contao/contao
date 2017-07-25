<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Database;

use Contao\StringUtil;

/**
 * Runs the version 4.4.0 update.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Version440Update extends AbstractVersionUpdate
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun()
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_content'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_content');

        return !isset($columns['imagetitle']);
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        // Add the js_autofocus.html5 template
        $statement = $this->connection->query("SELECT id, scripts FROM tl_layout");

        while (false !== ($layout = $statement->fetch(\PDO::FETCH_OBJ))) {
            $scripts = StringUtil::deserialize($layout->scripts);

            if (!empty($scripts) && is_array($scripts)) {
                $scripts[] = 'js_autofocus';

                $stmt = $this->connection->prepare('UPDATE tl_layout SET scripts=:scripts WHERE id=:id');
                $stmt->execute([':scripts' => serialize(array_values(array_unique($scripts))), ':id' => $layout->id]);
            }
        }

        $schemaManager = $this->connection->getSchemaManager();

        // Enable the overwriteMeta field
        if ($schemaManager->tablesExist(['tl_calendar_events'])) {
            $this->connection->query("ALTER TABLE tl_calendar_events ADD overwriteMeta CHAR(1) DEFAULT '' NOT NULL");
            $this->connection->query("UPDATE tl_calendar_events SET overwriteMeta='1' WHERE alt!='' OR imageUrl!='' OR caption!=''");
        }

        if ($schemaManager->tablesExist(['tl_faq'])) {
            $this->connection->query("ALTER TABLE tl_faq ADD overwriteMeta CHAR(1) DEFAULT '' NOT NULL");
            $this->connection->query("UPDATE tl_faq SET overwriteMeta='1' WHERE alt!='' OR imageUrl!='' OR caption!=''");
        }

        if ($schemaManager->tablesExist(['tl_news'])) {
            $this->connection->query("ALTER TABLE tl_news ADD overwriteMeta CHAR(1) DEFAULT '' NOT NULL");
            $this->connection->query("UPDATE tl_news SET overwriteMeta='1' WHERE alt!='' OR imageUrl!='' OR caption!=''");
        }

        $this->connection->query("ALTER TABLE `tl_content` CHANGE `title` `imageTitle` varchar(255) NOT NULL DEFAULT ''");
        $this->connection->query("ALTER TABLE tl_content ADD overwriteMeta CHAR(1) DEFAULT '' NOT NULL");
        $this->connection->query("UPDATE tl_content SET overwriteMeta='1' WHERE alt!='' OR imageTitle!='' OR imageUrl!='' OR caption!=''");
    }
}
