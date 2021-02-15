<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   11.12.2020
 * Zeit:    13:53
 * Datei:   KbaCodes.php
 * @package ItswCar\Models
 */

namespace ItswCar\Models;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * This model stores the registration codes of the Kraftfahrtbundesamt (KBA) for a car
 *
 * @ORM\Entity()
 * @ORM\Table(name="itsw_kba_codes",
 *     indexes={
 *     @ORM\Index(name="search_idx", columns={"tecdoc_id", "hsn", "tsn", "active"}),
 *     @ORM\Index(name="get_by_tecdoc_id", columns={"tecdoc_id"})
 * },
 *     uniqueConstraints={@ORM\UniqueConstraint(name="kba_codes_unique", columns={"tecdoc_id", "hsn", "tsn", "active"})}
 *     )
 */
class KbaCodes extends ModelEntity {
	/**
	 * @var \ItswCar\Models\Car
	 *
	 * @ORM\ManyToOne(targetEntity="ItswCar\Models\Car", inversedBy="codes")
	 * @ORM\JoinColumn(name="tecdoc_id", referencedColumnName="tecdoc_id", nullable=false)
	 */
	protected $car;
	
	/**
	 * @var int
	 * @ORM\Id
	 * @ORM\Column(name="tecdoc_id", type="integer", nullable=false)
	 */
	private $tecdocId;
	
	/**
	 * @var string
	 * @ORM\Id
	 * @ORM\Column(name="hsn", type="string", nullable=false, length=4)
	 */
	private $hsn;
	
	/**
	 * @var string
	 * @ORM\Id
	 * @ORM\Column(name="tsn", type="string", nullable=false, length=4)
	 */
	private $tsn;
	
	/**
	 * @var bool
	 * @ORM\Column(name="active", type="boolean", nullable=false)
	 */
	private $active = TRUE;
	
	/**
	 * @param $car
	 * @return \ItswCar\Models\KbaCodes
	 */
	public function setCar($car): KbaCodes {
		return $this->setManyToOne(
			$car,
			Car::class,
			'codes'
		);
	}
	
	/**
	 * @return \ItswCar\Models\Car|null
	 */
	public function getCar(): ?Car {
		return $this->car;
	}
	
	/**
	 * @return int
	 */
	public function getTecdocId(): int {
		return $this->tecdocId;
	}
	
	/**
	 * @param int $tecdocId
	 * @return $this
	 */
	public function setTecdocId(int $tecdocId): KbaCodes {
		$this->tecdocId = $tecdocId;
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getHsn(): string {
		return $this->hsn;
	}
	
	/**
	 * @param string $hsn
	 * @return $this
	 */
	public function setHsn(string $hsn): KbaCodes {
		$this->hsn = $hsn;
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getTsn(): string {
		return $this->tsn;
	}
	
	/**
	 * @param string $tsn
	 * @return $this
	 */
	public function setTsn(string $tsn): KbaCodes {
		$this->tsn = $tsn;
		
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
	public function setActive(bool $active): KbaCodes {
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