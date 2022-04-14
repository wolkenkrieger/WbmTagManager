<?php declare(strict_types=1);
/**
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 12.04.2022
 * Time: 07:30
 * File: ArticlePrices.php
 * @package ItswCar\Models
 */

namespace ItswCar\Models;

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * This model stores the article price history
 *
 * @ORM\Entity(repositoryClass="ItswCar\Models\Repository")
 * @ORM\Table(name="itsw_article_prices", indexes={
 *     @ORM\Index(columns={"article_details_id"}),
 *     @ORM\Index(columns={"date"}),
 *     @ORM\Index(columns={"customer_group_key"})
 * })
 */
class ArticlePrices extends ModelEntity {
	
	/**
	 * @var int
	 * @ORM\Id
	 * @ORM\Column(name="id", type="integer", nullable=false)
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private int $id;
	
	/**
	 * @var int
	 * @ORM\Column(name="article_details_id", type="integer", nullable=false)
	 */
	private int $articleDetailsId;
	
	/**
	 * @var string
	 * @ORM\Column(name="customer_group_key", type="string", nullable=false)
	 */
	private string $customerGroupKey = 'EK';
	
	/**
	 * @var \DateTimeImmutable
	 * @ORM\Column(name="date", type="date_immutable", nullable=false)
	 */
	private \DateTimeImmutable $date;
	
	/**
	 * @var float
	 * @ORM\Column(name="price", type="float", nullable=false)
	 */
	private float $price;
	
	/**
	 * @var string
	 * @ORM\Column(name="tax", type="decimal", precision=10, scale=2, nullable=false)
	 */
	private string $tax = '0.0';
	
	/**
	 * @param array|null $data
	 */
	public function __construct(?array $data = NULL) {
		if (is_array($data)) {
			$this->fromArray($data);
		}
		
		$this->date = new \DateTimeImmutable();
	}
	
	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}
	
	/**
	 * @param int $id
	 * @return $this
	 */
	public function setId(int $id): ArticlePrices {
		$this->id = $id;
		
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getArticleDetailsId(): int {
		return $this->articleDetailsId;
	}
	
	/**
	 * @param int $articleDetailsId
	 * @return $this
	 */
	public function setArticleDateilsId(int $articleDetailsId): ArticlePrices {
		$this->articleDetailsId = $articleDetailsId;
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getCustomerGroupKey(): string {
		return $this->customerGroupKey;
	}
	
	/**
	 * @param string $customerGroupKey
	 * @return $this
	 */
	public function setCustomerGroupKey(string $customerGroupKey): ArticlePrices {
		$this->customerGroupKey = $customerGroupKey;
		
		return $this;
	}
	
	/**
	 * @return \DateTimeImmutable
	 */
	public function getDate(): \DateTimeImmutable {
		return $this->date;
	}
	
	/**
	 * @param \DateTimeImmutable $date
	 * @return $this
	 */
	public function setDate(\DateTimeImmutable $date): ArticlePrices {
		$this->date = $date;
		
		return $this;
	}
	
	/**
	 * @return float
	 */
	public function getPrice(): float {
		return $this->price;
	}
	
	/**
	 * @param float $price
	 * @return $this
	 */
	public function setPrice(float $price): ArticlePrices {
		$this->price = $price;
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getTax(): string {
		return $this->tax;
	}
	
	/**
	 * @param $tax
	 * @return $this
	 */
	public function setTax($tax): ArticlePrices {
		$this->tax = $tax;
		
		return $this;
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
	
	/**
	 * @param array $array
	 * @return void
	 */
	public function fromArray(array $array): void {
		foreach ($array as $k => $v) {
			if (property_exists($this, $k)) {
				$this->{$k} = $v;
			}
		}
	}
	
	public function toArray(): array {
		return get_object_vars($this);
	}
}