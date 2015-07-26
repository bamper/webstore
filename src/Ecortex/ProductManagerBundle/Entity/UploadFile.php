<?php

namespace Ecortex\ProductManagerBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class UploadFile
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="url", type="string", length=255)
     */
    private $url;

    /**
     * @var UploadedFile
     * @Assert\NotBlank
     */
    private $file;



    public function getFile() {
        return $this->file;
    }

    public function setFile(UploadedFile $file = null) {
        $this->file = $file;
    }



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
     * Set url
     *
     * @param string $url
     * @return UploadFile
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    public function getMimeType() {
        return $this->file->getClientMimeType();
    }

    public function upload() {
        if (null === $this->file) {
            return;
        }
        $name = $this->file->getClientOriginalName();
        $this->file->move($this->getUploadRootDir(), $name);
        $this->url = $name;
    }

    public function getUploadDir() {
        return 'uploads/imports';
    }

    protected function getUploadRootDir() {
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }
}
