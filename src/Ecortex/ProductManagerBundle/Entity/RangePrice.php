<?php

namespace Ecortex\ProductManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * RangePrice
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Ecortex\ProductManagerBundle\Entity\RangePriceRepository")
 */
class RangePrice
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
     * @var integer
     *
     * @ORM\Column(name="price", type="integer")
     */
    private $price;

    /**
     * @var integer
     *
     * @ORM\Column(name="min", type="integer")
     */
    private $min;

    /**
     * @var integer
     *
     * @ORM\Column(name="max", type="integer")
     */
    private $max;

    /**
     * @ORM\ManyToOne(targetEntity="Ecortex\ProductManagerBundle\Entity\Product", inversedBy="rangePrices")
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @ORM\OneToMany(targetEntity="Ecortex\ProductManagerBundle\Entity\ProductOption", mappedBy="rangePrice", cascade={"persist"})
     */
    private $productOptions;

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
     * Set price
     *
     * @param integer $price
     * @return RangePrice
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
     * Set min
     *
     * @param integer $min
     * @return RangePrice
     */
    public function setMin($min)
    {
        $this->min = $min;

        return $this;
    }

    /**
     * Get min
     *
     * @return integer 
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * Set max
     *
     * @param integer $max
     * @return RangePrice
     */
    public function setMax($max)
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Get max
     *
     * @return integer 
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Set product
     *
     * @param \Ecortex\ProductManagerBundle\Entity\Product $product
     * @return RangePrice
     */
    public function setProduct(\Ecortex\ProductManagerBundle\Entity\Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \Ecortex\ProductManagerBundle\Entity\Product 
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->productOptions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add productOptions
     *
     * @param \Ecortex\ProductManagerBundle\Entity\ProductOption $productOptions
     * @return RangePrice
     */
    public function addProductOption(\Ecortex\ProductManagerBundle\Entity\ProductOption $productOptions)
    {
        $this->productOptions[] = $productOptions;
        $productOptions->setRangePrice($this);
        return $this;
    }

    /**
     * Remove productOptions
     *
     * @param \Ecortex\ProductManagerBundle\Entity\ProductOption $productOptions
     */
    public function removeProductOption(\Ecortex\ProductManagerBundle\Entity\ProductOption $productOptions)
    {
        $this->productOptions->removeElement($productOptions);
    }

    /**
     * Get productOptions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getProductOptions()
    {
        return $this->productOptions;
    }
}
