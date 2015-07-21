<?php

namespace Ecortex\ProductManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Product
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="uniProduct1",columns={"ref", "provider_id"})})
 * @ORM\Entity(repositoryClass="Ecortex\ProductManagerBundle\Entity\ProductRepository")
 * @UniqueEntity(fields={"ref", "provider"}, message="Le produit de ce fournisseur est déjà listé")
 * @ORM\HasLifecycleCallBacks()
 */
class Product
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
     * @ORM\Column(name="ref", type="string", length=255)
     */
    private $ref;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

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
     * @ORM\ManyToOne(targetEntity="Ecortex\ProductManagerBundle\Entity\Provider")
     * @ORM\JoinColumn(nullable=false)
     */
    private $provider;

    /**
     * @ORM\OneToMany(targetEntity="Ecortex\ProductManagerBundle\Entity\RangePrice", mappedBy="product", cascade={"persist"})
     */
    private $rangePrices;

    /**
     * @ORM\OneToOne(targetEntity="Ecortex\ProductManagerBundle\Entity\Image")
     * @ORM\JoinColumn(nullable=true)
     */
    private $image;

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
     * @return Product
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
     * Set ref
     *
     * @param string $ref
     * @return Product
     */
    public function setRef($ref)
    {
        $this->ref = $ref;

        return $this;
    }

    /**
     * Get ref
     *
     * @return string 
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Product
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->date = new \DateTime();
        $this->rangePrices = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set provider
     *
     * @param \Ecortex\ProductManagerBundle\Entity\Provider $provider
     * @return Product
     */
    public function setProvider(\Ecortex\ProductManagerBundle\Entity\Provider $provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Get provider
     *
     * @return \Ecortex\ProductManagerBundle\Entity\Provider 
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Add rangePrices
     *
     * @param \Ecortex\ProductManagerBundle\Entity\RangePrice $rangePrices
     * @return Product
     */
    public function addRangePrice(\Ecortex\ProductManagerBundle\Entity\RangePrice $rangePrices)
    {
        $this->rangePrices[] = $rangePrices;
        $rangePrices->setProduct($this);
        return $this;
    }

    /**
     * Remove rangePrices
     *
     * @param \Ecortex\ProductManagerBundle\Entity\RangePrice $rangePrices
     */
    public function removeRangePrice(\Ecortex\ProductManagerBundle\Entity\RangePrice $rangePrices)
    {
        $this->rangePrices->removeElement($rangePrices);
    }

    /**
     * Get rangePrices
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRangePrices()
    {
        return $this->rangePrices;
    }

    /**
     * Set image
     *
     * @param \Ecortex\ProductManagerBundle\Entity\Image $image
     * @return Product
     */
    public function setImage(\Ecortex\ProductManagerBundle\Entity\Image $image = null)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return \Ecortex\ProductManagerBundle\Entity\Image 
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Product
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
     * @return Product
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
}
