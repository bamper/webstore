<?php

namespace Ecortex\ProductManagerBundle\Entity;

use Ecortex\ProductManagerBundle\Entity\FileMasterStructure;

/**
 * ImportFile
 */
class ImportFile
{
    private $name;
    private $handle;
    private $structure;
    private $findItem = [];
    private $lineCount;


    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return integer
     */
    public function getLineCount() {
        return $this->lineCount;
    }

    /**
     * @param $lineCount integer
     * @return $this
     */
    public function setLineCount($lineCount) {
        $this->lineCount = $lineCount;
        return $this;
    }

    public function getHandle() {
        return $this->handle;
    }

    public function setHandle($handle) {
        $this->handle = $handle;
        return $this;
    }

    public function setStructure(FileMasterStructure $masterStructure) {
        $this->structure = $masterStructure;
        return $this;
    }

    /**
     * @return FileMasterStructure
     */
    public  function getStructure() {
        return $this->structure;
    }

    public function addFindItem(FindItem $findItem) {
        $this->findItem[$findItem->getNameOsStructureItem()] = $findItem;
        return $this;
    }

    public function clearFindItem() {
        $this->findItem = [];
        return $this;
    }

    public function countFindItem() {
        return count($this->findItem);
    }

    /**
     * @return array
     */
    public function getFindItems() {
        return $this->findItem;
    }

    /**
     * @param $findItemName string
     * @return FindItem
     */
    public function getFindItem($findItemName) {

        return $this->findItem[$findItemName];
    }

}
