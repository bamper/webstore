<?php

namespace Ecortex\ProductManagerBundle\Entity;



/**
 * FindItem
 */
class FindItem
{
    private $nameOfStructureItem;
    private $values = [];

    /**
     * @param $name
     */
    public function __construct($name) {
        $this->setNameOfStructureItem($name);
    }

    public function getNameOsStructureItem() {
        return $this->nameOfStructureItem;
    }

    public function setNameOfStructureItem($name) {
        $this->nameOfStructureItem = $name;
        return $this;
    }

    /**
     * @param $values integer
     * @return $this
     */
    public function addValues($values) {
        $this->values[] = $values;
        return $this;
    }

    /**
     * @return int
     */
    public  function getCountValues() {
        return count($this->values);
    }

    /**
     * @return array
     */
    public  function getValues() {
        return $this->values;
    }

}
