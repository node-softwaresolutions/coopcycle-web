<?php

namespace AppBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Timestampable\Traits\Timestampable;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *   attributes={
 *     "denormalization_context"={"groups"={"categories"}},
 *     "normalization_context"={"groups"={"categories"}}
 *   },
 * )
 * @Vich\Uploadable
 */
class RestaurantCategory
{
    use Timestampable;

    /**
     * @Groups({"categories"})
     */
    private $id;

    /**
     * @Groups({"categories"})
     */
    protected $name;

    /**
     * @Groups({"categories"})
     */
    protected $description;

    /**
     * @Vich\UploadableField(mapping="category_image", fileNameProperty="imageName")
     * @Assert\File(
     *   maxSize = "1024k",
     *   mimeTypes = {"image/jpg", "image/jpeg", "image/png"}
     * )
     * @var File
     */
    private $imageFile;

    /**
     * @var string
     */
    private $imageName;

    /**
     * @var Restaurant
     * @Groups({"categories"})
     */
    protected $restaurants;

    public function __construct()
    {
        $this->restaurants = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    public function getRestaurants()
    {
        return $this->restaurants;
    }

    public function addRestaurant(Restaurant $restaurant)
    {
        $this->restaurants->add($restaurant);
    }

    public function setImageName($imageName)
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|UploadedFile|null $image
     */
    public function setImageFile(File $image = null)
    {
        $this->imageFile = $image;

        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * @return File|null
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }

}
