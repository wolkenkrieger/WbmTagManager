<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   12.12.2020
 * Zeit:    15:09
 * Datei:   ArticleCarLinks.php
 * @package ItswCar\Models
 */

namespace ItswCar\Models;


use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * This model stores the linkage between an article a car
 *
 * @ORM\Entity(repositoryClass="ItswCar\Models\Repository")
 * @ORM\Table(name="itsw_article_car_links", indexes={
 *     @ORM\Index(name="get_by_articleDetailsId", columns={"article_details_id"}),
 *     @ORM\Index(name="get_by_tecdocId", columns={"tecdoc_id"}),
 *     @ORM\Index(name="group_by_articleDetailsId_tecdocId", columns={"article_details_id", "tecdoc_id"}),
 *     @ORM\Index(name="get_inactive", columns={"active"}, options={"where": "(active = 0)"}),
 * })
 */
class ArticleCarLinks extends ModelEntity {
	/**
	 * @var \ItswCar\Models\Car
	 *
	 * @ORM\ManyToOne(targetEntity="ItswCar\Models\Car", inversedBy="articleLinks")
	 * @ORM\JoinColumn(name="tecdoc_id", referencedColumnName="tecdoc_id")
	 */
	protected $car;
	
	/**
	 * @var int
	 * @ORM\Id
	 * @ORM\Column(name="article_details_id", type="integer", nullable=false)
	 */
	protected $articleDetailsId;
	
	/**
	 * @var int
	 * @ORM\Column(name="tecdoc_id", type="integer", nullable=false)
	 */
	protected $tecdocId;
	
	/**
	 * @var string
	 * @ORM\Column(name="restriction", type="string", nullable=false, length=1024)
	 */
	protected $restriction = '';
	
	/**
	 * @var bool
	 * @ORM\Column(name="active", type="boolean", nullable=false)
	 */
	protected $active = TRUE;
	
	/**
	 * @param $car
	 * @return \ItswCar\Models\ArticleCarLinks
	 */
	public function setCar($car): ArticleCarLinks {
		return $this->setManyToOne(
			$car,
			\ItswCar\Models\Car::class,
			'car',
			'articleLinks'
		);
	}
	
	/**
	 * @return \ItswCar\Models\Car
	 */
	public function getCar(): Car {
		return $this->car;
	}
	
	/**
	 * @param int $articleDetailsId
	 * @return $this
	 */
	public function setArticleDetailsId(int $articleDetailsId): ArticleCarLinks {
		$this->articleDetailsId = $articleDetailsId;
		
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getArticleDetailsId(): int {
		return $this->articleDetailsId;
	}
	
	/**
	 * @param int $tecdocId
	 * @return $this
	 */
	public function setTecdocId(int $tecdocId): ArticleCarLinks {
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
	 * @param string $restriction
	 * @return $this
	 */
	public function setRestriction(string $restriction): ArticleCarLinks {
		$this->restriction = $restriction;
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getRestriction(): string {
		return $this->restriction;
	}
	
	/**
	 * @param bool $active
	 * @return $this
	 */
	public function setActive(bool $active): ArticleCarLinks {
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
	public function __isset($property): bool {
		return isset($this->$property);
	}
}