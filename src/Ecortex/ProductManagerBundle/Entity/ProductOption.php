<?php

namespace Ecortex\ProductManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductOption
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Ecortex\ProductManagerBundle\Entity\ProductOptionRepository")
 */
class ProductOption
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255)
     */
    private $value;

    /**
     * @var integer
     *
     * @ORM\Column(name="price", type="integer")
     */
    private $price;

    /**
     * @ORM\ManyToOne(targetEntity="Ecortex\ProductManagerBundle\Entity\RangePrice", inversedBy="productOptions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $rangePrice;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return ProductOption
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return ProductOption
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set price
     *
     * @param integer $price
     * @return ProductOption
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set rangePrice
     *
     * @param \Ecortex\ProductManagerBundle\Entity\RangePrice $rangePrice
     * @return ProductOption
     */
    public function setRangePrice(\Ecortex\ProductManagerBundle\Entity\RangePrice $rangePrice)
    {
        $this->rangePrice = $rangePrice;

        return $this;
    }

    /**
     * Get rangePrice
     *
     * @return \Ecortex\ProductManagerBundle\Entity\RangePrice 
     */
    public function getRangePrice()
    {
        return $this->rangePrice;
    }
}
