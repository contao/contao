<?php

namespace Contao\Fixtures;

class DcaExtractor
{
    public static function getInstance()
    {
        return new static();
    }

    public function isDbTable()
    {
        return true;
    }

    public function getMeta()
    {
        return [];
    }

    public function getFields()
    {
        return ['id' => 'int(10) unsigned NOT NULL auto_increment'];
    }

    public function getOrderFields()
    {
        return [];
    }

    public function getKeys()
    {
        return [];
    }

    public function getRelations()
    {
        return [];
    }
}
