<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="Car",indexes={
 *     @ORM\Index(name="exists_id", columns={"existsid"})
 * })
 **/
class Car {
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /** @ORM\Column(type="string") * */
    protected $existsId;

    /** @ORM\Column(type="string",nullable=true) * */
    protected $title;

    /** @ORM\Column(type="string",nullable=true) * */
    protected $mileage;

    /** @ORM\Column(type="string",nullable=true) * */
    protected $gearbox;

    /** @ORM\Column(type="string",nullable=true) * */
    protected $fuel;

    /** @ORM\Column(type="string",nullable=true) * */
    protected $power;

    /** @ORM\Column(type="string",nullable=true) * */
    protected $origin;

    /** @ORM\Column(type="json",nullable=true,options={"jsonb"=true}) * */
    protected $extra;

    /** @ORM\Column(type="datetimetz",nullable=true) * */
    protected $registered;

    public function __construct(array $properties = null) {
        if ($properties === null) {
            return;
        }
        foreach ($properties as $propName => $propValue) {
            // I'm not sure about allowing this code design in PHP. But it works)
            $this->__set($propName, $propValue);
        }
    }

    public function __get($name) {
        if (property_exists($this, $name)) {
            return $this[ $name ];
        } elseif (array_key_exists($name, $this->extra)) {
            return $this->extra[ $name ];
        }
        return null;
    }

    public function __set($name, $value) {
        if (property_exists($this, $name)) {
            return $this->$name = $value;
        } else {
            return $this->extra[ $name ] = $value;
        }
    }
}