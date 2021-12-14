<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   11.12.2020
 * Zeit:    14:34
 * Datei:   Car.php
 * @package ItswCar\Models
 */

namespace ItswCar\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * This model stores several information about a car
 *
 * @ORM\Entity(repositoryClass="ItswCar\Models\Repository")
 * @ORM\Table(name="itsw_cars", indexes={
 *     @ORM\Index(name="manufacturer_idx", columns={"manufacturer_id"}),
 *     @ORM\Index(name="model_idx", columns={"model_id"}),
 *     @ORM\Index(name="type_idx", columns={"type_id"}),
 *     @ORM\Index(name="platform_idx", columns={"platform_id"}),
 *     @ORM\Index(name="group_manufacturer_model", columns={"manufacturer_id", "model_id"}),
 *     @ORM\Index(name="group_manufacturer_model_type", columns={"manufacturer_id", "model_id", "type_id"}),
 *     @ORM\Index(name="group_manufacturer_model_type_platform", columns={"manufacturer_id", "model_id", "type_id", "platform_id"}),
 *     @ORM\Index(name="get_inactive", columns={"active"}, options={"where": "(active = 0)"}),
 * })
 */
class Car extends ModelEntity {
	/**
	 * OWNING SIDE
	 *
	 * @var \ItswCar\Models\Manufacturer
	 *
	 * @ORM\ManyToOne(targetEntity="ItswCar\Models\Manufacturer", inversedBy="cars")
	 * @ORM\JoinColumn(name="manufacturer_id", referencedColumnName="id")
	 */
	protected $manufacturer;
	
	/**
	 * OWNING SIDE
	 *
	 * @var \ItswCar\Models\Model
	 *
	 * @ORM\ManyToOne(targetEntity="ItswCar\Models\Model", inversedBy="cars")
	 * @ORM\JoinColumn(name="model_id", referencedColumnName="id")
	 */
	protected $model;
	
	/**
	 * OWNING SIDE
	 *
	 * @var \ItswCar\Models\Type
	 *
	 * @ORM\ManyToOne(targetEntity="ItswCar\Models\Type", inversedBy="cars")
	 * @ORM\JoinColumn(name="type_id", referencedColumnName="id")
	 */
	protected $type;
	
	/**
	 * OWNING SIDE
	 *
	 * @var \ItswCar\Models\EbayPlatform
	 *
	 * @ORM\ManyToOne(targetEntity="ItswCar\Models\EbayPlatform", inversedBy="cars")
	 * @ORM\JoinColumn(name="platform_id", referencedColumnName="id")
	 */
	protected $platform;
	
	/**
	 * @var \Doctrine\Common\Collections\ArrayCollection<\ItswCar\Models\KbaCodes>|null
	 * @ORM\OneToMany(
	 *     targetEntity="ItswCar\Models\KbaCodes",
	 *     mappedBy="car",
	 *     orphanRemoval=true,
	 *     cascade={"persist", "remove"}
	 * )
	 */
	protected $codes;
	
	/**
	 * @var \Doctrine\Common\Collections\ArrayCollection<\ItswCar\Models\ArticleCarLinks>|null
	 * @ORM\OneToMany (
	 *     targetEntity="ItswCar\Models\ArticleCarLinks",
	 *     mappedBy="car",
	 *     orphanRemoval=true,
	 *     cascade={"persist", "remove"}
	 * )
	 */
	protected $articleLinks;
	
	/**
	 * @var \Doctrine\Common\Collections\ArrayCollection<\ItswCar\Models\Garage>|null
	 * @ORM\OneToMany (
	 *     targetEntity="ItswCar\Models\Garage",
	 *     mappedBy="car",
	 *     orphanRemoval=true,
	 *     cascade={"persist", "remove"}
	 * )
	 */
	protected $garage;
	
	/**
	 * @var int
	 * @ORM\Id
	 * @ORM\Column(name="tecdoc_id", type="integer", nullable=false, unique=true)
	 */
	protected int $tecdocId;
	
	/**
	 * @var int
	 * @ORM\Column(name="manufacturer_id", type="integer", nullable=false)
	 */
	protected int $manufacturerId;
	
	/**
	 * @var int
	 * @ORM\Column(name="model_id", type="integer", nullable=false)
	 */
	protected int $modelId;
	
	/**
	 * @var int
	 * @ORM\Column(name="type_id", type="integer", nullable=false)
	 */
	protected int $typeId;
	
	/**
	 * @var int
	 * @ORM\Column(name="platform_id", type="integer", nullable=false)
	 */
	protected int $platformId;
	
	/**
	 * @var \DateTimeImmutable
	 * @ORM\Column(name="build_from", type="datetime_immutable", nullable=false)
	 */
	protected \DateTimeImmutable $buildFrom;
	
	/**
	 * @var \DateTimeImmutable
	 * @ORM\Column(name="build_to", type="datetime_immutable", nullable=true)
	 */
	protected \DateTimeImmutable $buildTo;
	
	/**
	 * @var int
	 * @ORM\Column(name="ccm", type="integer", nullable=false)
	 */
	protected int $ccm;
	
	/**
	 * @var int
	 * @ORM\Column(name="kw", type="integer", nullable=false)
	 */
	protected int $kw;
	
	/**
	 * @var int
	 * @ORM\Column(name="ps", type="integer", nullable=false)
	 */
	protected int $ps;
	
	/**
	 * @var bool
	 * @ORM\Column(name="active", type="boolean", nullable=false)
	 */
	protected bool $active = TRUE;
	
	/**
	 * @var bool
	 * @ORM\Column(name="is_car", type="boolean", nullable=false)
	 */
	protected bool $isCar = TRUE;
	
	/**
	 * @var bool
	 * @ORM\Column(name="is_truck", type="boolean", nullable=false)
	 */
	protected bool $isTruck = FALSE;
	
	/**
	 * @var bool
	 * @ORM\Column(name="is_bike", type="boolean", nullable=false)
	 */
	protected bool $isBike = FALSE;
	/**
	 * @var string
	 */
	protected string $buildFromMonth;
	
	/**
	 * @var string
	 */
	protected string $buildFromYear;
	
	/**
	 * @var string
	 */
	protected string $buildToMonth;
	
	/**
	 * @var string
	 */
	protected string $buildToYear;
	
	public function __construct() {
		$this->codes = new ArrayCollection();
		$this->articleLinks = new ArrayCollection();
	}
	
	/**
	 * @param \ItswCar\Models\Manufacturer $manufacturer
	 * @return $this
	 */
	public function setManufacturer(Manufacturer $manufacturer): Car {
		$this->manufacturer = $manufacturer;
		
		return $this;
	}
	
	/**
	 * @return \ItswCar\Models\Manufacturer
	 */
	public function getManufacturer(): Manufacturer {
		return $this->manufacturer;
	}
	
	/**
	 * @param int $manufacturerId
	 * @return $this
	 */
	public function setManufacturerId(int $manufacturerId): Car {
		$this->manufacturerId = $manufacturerId;
		
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getManufacturerId(): int {
		return $this->manufacturerId;
	}
	
	/**
	 * @param \ItswCar\Models\Model $model
	 * @return $this
	 */
	public function setModel(Model $model): Car {
		$this->model = $model;
		
		return $this;
	}
	
	/**
	 * @return \ItswCar\Models\Model
	 */
	public function getModel(): Model {
		return $this->model;
	}
	
	/**
	 * @param int $modelId
	 * @return $this
	 */
	public function setModelId(int $modelId): Car {
		$this->modelId = $modelId;
		
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getModelId(): int {
		return $this->modelId;
	}
	
	/**
	 * @param \ItswCar\Models\Type $type
	 * @return $this
	 */
	public function setType(Type $type): Car {
		$this->type = $type;
		
		return $this;
	}
	
	/**
	 * @return \ItswCar\Models\Type
	 */
	public function getType(): Type {
		return $this->type;
	}
	
	/**
	 * @param int $typeId
	 * @return $this
	 */
	public function setTypeId(int $typeId): Car {
		$this->typeId = $typeId;
		
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getTypeId(): int {
		return $this->typeId;
	}
	
	/**
	 * @param \ItswCar\Models\EbayPlatform $ebayPlatform
	 * @return $this
	 */
	public function setPlatform(EbayPlatform $ebayPlatform): Car {
		$this->platform = $ebayPlatform;
		
		return $this;
	}
	
	/**
	 * @return \ItswCar\Models\EbayPlatform
	 */
	public function getPlatform(): EbayPlatform {
		return $this->platform;
	}
	
	/**
	 * @param int $platformId
	 * @return $this
	 */
	public function setPlatformId(int $platformId): Car {
		$this->platformId = $platformId;
		
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getPlatformId(): int {
		return $this->platformId;
	}
	
	/**
	 * @param \Doctrine\Common\Collections\ArrayCollection $codes
	 * @return \ItswCar\Models\Car
	 */
	public function setCodes(ArrayCollection $codes): Car {
		return $this->setOneToMany(
			$codes,
			KbaCodes::class,
			'codes',
			'car'
		);
	}
	
	/**
	 * @return \Doctrine\Common\Collections\ArrayCollection|null
	 */
	public function getCodes() {
		return $this->codes;
	}
	
	/**
	 * @param \ItswCar\Models\ArticleCarLinks[]|null $articleLinks
	 * @return \ItswCar\Models\Car
	 */
	public function setArticleLinks(?array $articleLinks): Car {
		return $this->setOneToMany(
			$articleLinks,
			ArticleCarLink::class,
			'articleLinks',
			'car'
		);
	}
	
	/**
	 * @return \Doctrine\Common\Collections\ArrayCollection|null
	 */
	public function getArticleLinks(): ?ArrayCollection {
		return $this->articleLinks;
	}
	
	
	/**
	 * @param int $tecdocId
	 * @return $this
	 */
	public function setTecdocId(int $tecdocId): Car {
		$this->tecdocId = $tecdocId;
		
		return $this;
	}
	
	/**
	 * @return int
	 */
	public  function getTecdocId(): int {
		return $this->tecdocId;
	}
	
	/**
	 * @param \DateTimeImmutable $buildFrom
	 * @return $this
	 */
	public function setBuildFrom(\DateTimeImmutable $buildFrom): Car {
		$this->buildFrom = $buildFrom;
		
		return $this;
	}
	
	/**
	 * @return \DateTimeImmutable
	 */
	public function getBuildFrom(): \DateTimeImmutable {
		return $this->buildFrom;
	}
	
	/**
	 * @return string
	 */
	public function getBuildFromMonth(): string {
		$this->buildFromMonth = $this->buildFrom->format("m");
		
		return $this->buildFromMonth;
	}
	
	/**
	 * @return string
	 */
	public function getBuildFromYear(): string {
		$this->buildFromYear = $this->buildFrom->format("Y");
		
		return $this->buildFromYear;
	}
	
	/**
	 * @param \DateTimeImmutable $buildTo
	 * @return $this
	 */
	public function setBuildTo(\DateTimeImmutable $buildTo): Car {
		$this->buildTo = $buildTo;
		
		return $this;
	}
	
	/**
	 * @return \DateTimeImmutable
	 */
	public function getBuildTo(): \DateTimeImmutable {
		return $this->buildTo;
	}
	
	/**
	 * @return string
	 */
	public function getBuildToMonth(): string {
		$this->buildToMonth = $this->buildTo?$this->buildTo->format("m"):'';
		
		return $this->buildToMonth;
	}
	
	/**
	 * @return string
	 */
	public function getBuildToYear(): string {
		$this->buildToYear = $this->buildTo?$this->buildTo->format("Y"):'';
		
		return $this->buildToYear;
	}
	
	/**
	 * @param int $ccm
	 * @return $this
	 */
	public function setCcm(int $ccm): Car {
		$this->ccm = $ccm;
		
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getCcm(): int {
		return $this->ccm;
	}
	
	/**
	 * @param int $kw
	 * @return $this
	 */
	public function setKw(int $kw): Car {
		$this->kw = $kw;
		
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getKw(): int {
		return $this->kw;
	}
	
	/**
	 * @param int $ps
	 * @return $this
	 */
	public function setPs(int $ps): Car {
		$this->ps = $ps;
		
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getPs(): int {
		return $this->ps;
	}
	
	/**
	 * @param bool $active
	 * @return $this
	 */
	public function setActive(bool $active): Car {
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
	 * @param bool $isCar
	 * @return $this
	 */
	public function setIsCar(bool $isCar): Car {
		$this->isCar = $isCar;
		
		return $this;
	}
	
	/**
	 * @return bool
	 */
	public function getIsCar(): bool {
		return $this->isCar;
	}
	
	/**
	 * @param bool $isTruck
	 * @return $this
	 */
	public function setIsTruck(bool $isTruck): Car {
		$this->isCar = $isTruck;
		
		return $this;
	}
	
	/**
	 * @return bool
	 */
	public function getIsTruck(): bool {
		return $this->isTruck;
	}
	
	/**
	 * @param bool $isBike
	 * @return $this
	 */
	public function setIsBike(bool $isBike): Car {
		$this->isCar = $isBike;
		
		return $this;
	}
	
	/**
	 * @return bool
	 */
	public function getIsBike(): bool {
		return $this->isBike;
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