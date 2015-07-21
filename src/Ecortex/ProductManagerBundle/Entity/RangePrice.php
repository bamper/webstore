<?php

namespace Ecortex\ProductManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * RangePrice
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="uniRangePrice1",columns={"min", "max", "product_id"})})
 * @ORM\Entity(repositoryClass="Ecortex\ProductManagerBundle\Entity\RangePriceRepository")
 * @UniqueEntity(fields={"min", "max", "product"}, message="Cette tranche de prix est déjà listée")
 * @ORM\HasLifecycleCallBacks()
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
     * @var string
     *
     * @ORM\Column(name="price", type="decimal", scale=2)
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
     * @ORM\ManyToMany(targetEntity="Ecortex\ProductManagerBundle\Entity\Tag", cascade={"persist"})
     */
    private $tags;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     * @Assert\DateTime()
     */
    private $date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updateAt", type="datetime", nullable=true)
     */
    private $updateAt;

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
        $this->date = new \DateTime();
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

    /**
     * Set price
     *
     * @param string $price
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
     * @return string 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return RangePrice
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set updateAt
     *
     * @param \DateTime $updateAt
     * @return RangePrice
     */
    public function setUpdateAt($updateAt)
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    /**
     * Get updateAt
     *
     * @return \DateTime 
     */
    public function getUpdateAt()
    {
        return $this->updateAt;
    }

    /**
     * @ORM\PreUpdate
     */
    public function updateDate() {
        $this->setUpdateAt(new \DateTime());
    }

    /**
     * Add tags
     *
     * @param \Ecortex\ProductManagerBundle\Entity\Tag $tags
     * @return RangePrice
     */
    public function addTag(\Ecortex\ProductManagerBundle\Entity\Tag $tags)
    {
        $this->tags[] = $tags;

        return $this;
    }

    /**
     * Remove tags
     *
     * @param \Ecortex\ProductManagerBundle\Entity\Tag $tags
     */
    public function removeTag(\Ecortex\ProductManagerBundle\Entity\Tag $tags)
    {
        $this->tags->removeElement($tags);
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTags()
    {
        return $this->tags;
    }
}
