<?php

namespace Ecortex\ProductManagerBundle\Entity\ImportFile;

use Ecortex\ProductManagerBundle\Entity\ImportFile\StructureItem;

/**
 * FileMasterStructure
 */
class FileMasterStructure
{
    /**
     * @var array
     */
    private $items = [];

    public function addItem(StructureItem $item) {
        $this->items[$item->getName()] = $item;
        return $this;
    }

    public  function getItemsCount() {
        return count($this->items);
    }

    /**
     * @return StructureItem
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * @return StructureItem
     */
    public function getItem($name) {
        return $this->items[$name];
    }

}
