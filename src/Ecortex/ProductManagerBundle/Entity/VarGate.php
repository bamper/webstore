<?php

namespace Ecortex\ProductManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * VarGate
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Ecortex\ProductManagerBundle\Entity\VarGateRepository")
 */
class VarGate
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
     * @ORM\Column(name="sessid", type="string", length=255)
     */
    private $sessid;

    /**
     * @var array
     *
     * @ORM\Column(name="value", type="json_array")
     */
    private $value;


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
     * Set sessid
     *
     * @param string $sessid
     * @return VarGate
     */
    public function setSessid($sessid)
    {
        $this->sessid = $sessid;

        return $this;
    }

    /**
     * Get sessid
     *
     * @return string 
     */
    public function getSessid()
    {
        return $this->sessid;
    }



    /**
     * Set value
     *
     * @param array $value
     * @return VarGate
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return array 
     */
    public function getValue()
    {
        return $this->value;
    }
}
