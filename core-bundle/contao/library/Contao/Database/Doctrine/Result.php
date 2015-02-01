<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Library
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\Database\Doctrine;

/**
 * Doctrine database result class
 *
 * @author Tristan Lins <https://github.com/tristanlins>
 */
class Result extends \Database\Result
{
    /**
     * Current result
     *
     * @var \Doctrine\DBAL\Statement
     */
    protected $resResult;

    /**
     * We need to cache the complete result, because doctrine does not support seeking.
     *
     * @var array
     */
    protected $resultSet;

    /**
     * This is the index of the next fetch'able row.
     *
     * @var int
     */
    protected $index = 0;

    /**
     * @param Statement $statement
     * @param string    $strQuery
     */
    public function __construct($statement, $strQuery)
    {
        parent::__construct($statement, $strQuery);
        $this->resultSet = $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    protected function fetch_row()
    {
        if ($this->index >= count($this->resultSet)) {
            return null;
        }

        return array_values($this->resultSet[$this->index++]);
    }

    /**
     * {@inheritdoc}
     */
    protected function fetch_assoc()
    {
        if ($this->index >= count($this->resultSet)) {
            return null;
        }

        return $this->resultSet[$this->index++];
    }

    /**
     * {@inheritdoc}
     */
    protected function num_rows()
    {
        return count($this->resultSet);
    }

    /**
     * {@inheritdoc}
     */
    protected function num_fields()
    {
        return $this->resResult->columnCount();
    }

    /**
     * {@inheritdoc}
     */
    protected function fetch_field($intOffset)
    {
        if ($this->index >= count($this->resultSet)) {
            return null;
        }

        $row = $this->fetch_row();
        return $row[$intOffset];
    }

    /**
     * {@inheritdoc}
     */
    protected function data_seek($index)
    {
        if ($index < 0) {
            throw new \OutOfBoundsException("Invalid index $index (must be >= 0)");
        }

        $intTotal = $this->num_rows();

        if ($intTotal <= 0) {
            return; // see #6319
        }

        if ($index >= $intTotal) {
            throw new \OutOfBoundsException("Invalid index $index (only $intTotal rows in the result set)");
        }

        $this->index = $index;
    }

    /**
     * {@inheritdoc}
     */
    public function free()
    {
        $this->resResult->closeCursor();
        unset($this->resultSet);
    }
}

// Backwards compatibility
class_alias('Contao\\Database\\Doctrine\\Result', 'Database_Result');
