<?php

namespace AppBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Timestampable\Traits\Timestampable;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *   attributes={
 *     "denormalization_context"={"groups"={"categories"}},
 *     "normalization_context"={"groups"={"categories"}}
 *   },
 * )
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
     * @var Restaurant
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
}
