<?php declare(strict_types=1);
/**
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 01.12.2021
 * Time: 09:14
 * File: Garage.php
 * @package ItswCar\Models
 */

namespace ItswCar\Models;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 *
 * @ORM\Entity(repositoryClass="ItswCar\Models\Repository")
 * @ORM\Table(name="itsw_garage", *
 *     indexes={
 *          @ORM\Index(name="get_by_userId", columns={"user_id"}),
 *          @ORM\Index(name="get_by_tecdocId", columns={"tecdoc_id"}),
 *          @ORM\Index(name="get_inactive", columns={"active"}, options={"where": "(active = 0)"}),
 *          @ORM\Index(name="get_active", columns={"active"}, options={"where": "(active = 1)"})
 * })
 */
class Garage extends ModelEntity {
	/**
	 * @var \ItswCar\Models\Car
	 * @ORM\ManyToOne(targetEntity="ItswCar\Models\Car", inversedBy="garage")
	 * @ORM\JoinColumn(name="tecdoc_id", referencedColumnName="tecdoc_id", nullable=false, unique=false)
	 */
	protected Car $car;
	
	/**
	 * @var int
	 * @ORM\Id
	 * @ORM\Column(name="user_id", type="integer", nullable=false)
	 */
	protected int $userId;
	
	/**
	 * @var int
	 * @ORM\Id
	 * @ORM\Column(name="tecdoc_id", type="integer", nullable=false)
	 */
	protected int $tecdocId;
	
	/**
	 * @var bool
	 * @ORM\Column(name="active", type="boolean", nullable=false)
	 */
	protected bool $active = TRUE;
	
	/**
	 * @param $car
	 * @return \ItswCar\Models\Garage
	 */
	public function setCar($car): Garage {
		return $this->setManyToOne(
			$car,
			Car::class,
			'garage'
		);
	}
	
	/**
	 * @return \ItswCar\Models\Car
	 */
	public function getCar(): Car {
		return $this->car;
	}
	
	/**
	 * @param int $userId
	 * @return $this
	 */
	public function setUserId(int $userId): Garage {
		$this->userId = $userId;
		
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getUserId(): int {
		return $this->userId;
	}
	
	/**
	 * @param int $tecdocId
	 * @return $this
	 */
	public function setTecdocId(int $tecdocId): Garage {
		$this->tecdocId = $tecdocId;
		
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getTecdocId(): int {
		return $this->tecdocId;
	}
	
	/**
	 * @param bool $active
	 * @return $this
	 */
	public function setActive(bool $active): Garage {
		$this->active = $active;
		
		return $this;
	}
	
	/**
	 * @return bool
	 */
	public function getActive(): bool {
		return $this->active;
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