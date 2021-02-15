<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   11.12.2020
 * Zeit:    13:17
 * Datei:   Model.php
 * @package ItswCar\Models
 */

namespace ItswCar\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * This model stores several information about a TecDoc registered models
 *
 * @ORM\Entity()
 * @ORM\Table(name="itsw_models", indexes={@ORM\Index(name="name_idx", columns={"name"})})
 */
class Model extends ModelEntity {
	/**
	 * @var ArrayCollection<\ItswCar\Models\Car>
	 *
	 * @ORM\OneToMany(targetEntity="ItswCar\Models\Car", mappedBy="model")
	 * @ORM\JoinColumn(name="id", referencedColumnName="model_id")
	 */
	private $cars;
	
	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="integer", nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $id;
	
	/**
	 * @var string
	 * @ORM\Column(name="name", type="string", nullable=false, length=65)
	 */
	private $name;
	
	/**
	 * @var string
	 * @ORM\Column(name="display", type="string", nullable=true, length=65)
	 */
	private $display;
	
	/**
	 * @var bool
	 * @ORM\Column(name="active", type="boolean", nullable=false)
	 */
	private $active = TRUE;
	
	/**
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getCars(): ArrayCollection {
		return $this->cars;
	}
	
	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}
	
	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}
	
	/**
	 * @param string $name
	 * @return $this
	 */
	public function setName(string $name): Model {
		$this->name = $name;
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getDisplay(): string {
		return $this->display?:$this->name;
	}
	
	/**
	 * @param string $display
	 * @return $this
	 */
	public function setDisplay(string $display): Model {
		$this->display = $display;
		
		return $this;
	}
	
	/**
	 * @return bool
	 */
	public function getActive(): bool {
		return $this->active;
	}
	
	/**
	 * @param bool $active
	 * @return $this
	 */
	public function setActive(bool $active): Model {
		$this->active = $active;
		
		return $this;
	}
	
	/**
	 * @return array
	 */
	public function toArray(): array {
		return get_object_vars($this);
	}
	
	/**
	 * @param $property
	 * @return mixed
	 */
	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}
	
	/**
	 * @param $property
	 * @param $value
	 * @return mixed
	 */
	public function __set($property, $value) {
		if (property_exists($this, $property)) {
			$this->$property = $value;
			
			return $this->$property;
		}
	}
	
	/**
	 * @param $property
	 * @return bool
	 */
	public function __isset($property) {
		return isset($this->$property);
	}
}