<?php declare(strict_types=1);
/**
 * Autor:    Rico Wunglück <development@itsw.dev>
 * Datum:    13.09.2021
 * Zeit:    07:39
 * Datei:    GoogleMerchantCenterQueue.php
 * @package ItswCar\Models
 */

namespace ItswCar\Models;

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="ItswCar\Models\Repository")
 * @ORM\Table(name="itsw_gmc_queue", indexes={
 *     @ORM\Index(name="get_by_id", columns={"id"}),
 *     @ORM\Index(name="get_by_article_id", columns={"article_id"})
 * })
 */
class GoogleMerchantCenterQueue extends ModelEntity {
	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="integer", nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	protected int $id;
	
	/**
	 * @var int
	 *
	 * @ORM\Column(name="article_id", type="integer", nullable=false)
	 */
	protected int $articleId;
	
	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="created", type="datetime", nullable=false)
	 */
	protected \DateTime $created;
	
	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="handled", type="datetime", nullable=true)
	 */
	protected \DateTime $handled;
	
	/**
	 * @var string
	 *
	 * @ORM\Column(name="google_product_id", type="string", nullable=true, length=64)
	 */
	protected string $googleProductId;
	
	/**
	 * @var string
	 *
	 * @ORM\Column(name="response", type="text", nullable=true)
	 */
	protected string $response;
	
	/**
	 * @var string
	 *
	 * @ORM\Column(name="job_type", type="string", nullable=false, length=20)
	 */
	protected string $jobType;
	
	
	/**
	 *
	 */
	public function __construct(?array $data = NULL) {
		if (is_array($data)) {
			$this->fromArray($data);
		}
		
		$this->created = new \DateTime();
	}
	
	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}
	
	/**
	 * @param int $articleId
	 * @return $this
	 */
	public function setArticleId(int $articleId): GoogleMerchantCenterQueue {
		$this->articleId = $articleId;
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getArticleId(): int {
		return $this->articleId;
	}
	
	/**
	 * @return \DateTime
	 */
	public function getCreated(): \DateTime {
		return $this->created;
	}
	
	/**
	 * @param \DateTime|null $handled
	 * @return $this
	 */
	public function setHandled(?\DateTime $handled): GoogleMerchantCenterQueue {
		if ($handled instanceof \DateTime) {
			$this->handled = $handled;
		} else {
			$this->handled = new \DateTime();
		}
		
		return $this;
	}
	
	/**
	 * @param string $googleProductId
	 * @return $this
	 */
	public function setGoogleProductId(string $googleProductId): GoogleMerchantCenterQueue {
		$this->googleProductId = $googleProductId;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getGoogleProductId(): string {
		return $this->googleProductId;
	}
	
	/**
	 * @param string $response
	 * @return $this
	 */
	public function setResponse(string $response): GoogleMerchantCenterQueue {
		$this->response = $response;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getResponse(): string {
		return $this->response;
	}
	
	/**
	 * @param string $jobType
	 * @return $this
	 */
	public function setJobType(string $jobType): GoogleMerchantCenterQueue {
		$this->jobType = $jobType;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getJobType(): string {
		return $this->jobType;
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