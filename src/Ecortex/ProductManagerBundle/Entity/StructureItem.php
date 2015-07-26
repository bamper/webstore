<?php

namespace Ecortex\ProductManagerBundle\Entity;



/**
 * StructureItem
 */
class StructureItem
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $columnNames;

    /**
     * @param null $name
     * @param array $columnNames
     */
    public function __construct($name = null, array $columnNames = []) {
        if ($name != null) {
            $this->setName($name);
        }
        if (count($columnNames)>0) {
            $this->columnNames = $columnNames;
        }
    }

    /**
     * @return array
     */
    public function getColumnNames() {
        return $this->columnNames;
    }

    /**
     * @return int
     */
    public function getColumnCount() {
        return count($this->columnNames);
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }
}
