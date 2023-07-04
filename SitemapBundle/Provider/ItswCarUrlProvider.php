<?php declare(strict_types=1);
/**
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 21.09.2021
 * Time: 09:05
 * File: ItswCarUrlProvider.php
 * @package ItswCar\SitemapBundle\Provider
 */

namespace ItswCar\SitemapBundle\Provider;

use ItswCar\Models\Car;
use ItswCar\Helpers\SeoHelper;
use Shopware\Bundle\SitemapBundle\Struct\Url;
use Shopware\Bundle\SitemapBundle\UrlProviderInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use Shopware\Components\Routing;
use Shopware\Components\Model\ModelManager;

use ItswCar\Traits\LoggingTrait;

class ItswCarUrlProvider implements UrlProviderInterface {
	use LoggingTrait;
	
	/**
	 * @var \Shopware\Components\Model\ModelManager
	 */
	private ModelManager $modelManager;
	/**
	 * @var \ItswCar\Helpers\SeoHelper
	 */
	private SeoHelper $seoHelper;
	/**
	 * @var int
	 */
	private int $batchSize;
	/**
	 * @var int|null
	 */
	private ?int $lastTecdocId;
	
	
	public function __construct(ModelManager $modelManager, SeoHelper $seoHelper, int $batchSize) {
		$this->modelManager = $modelManager;
		$this->seoHelper = $seoHelper;
		$this->batchSize = $batchSize;
	}
	/**
	 * @inheritDoc
	 */
	public function getUrls(Routing\Context $routingContext, ShopContextInterface $shopContext): ?array {
		$builder = $this->modelManager->getRepository(Car::class)
			->getCarsQueryBuilder([
				'select' => [
					'cars'
				],
				'conditions' => [
					'cars.active' => 1,
					'manufacturer.active' => 1,
					'model.active' => 1,
					'type.active' => 1
 				]
			])
			->join('cars.manufacturer', 'manufacturer')
			->join('cars.model', 'model')
			->join('cars.type', 'type')
			->setMaxResults($this->batchSize)
			->orderBy('cars.tecdocId', 'ASC');
		
		if ($this->lastTecdocId) {
			$builder
				->andWhere('cars.tecdocId > :tecdocId')
				->setParameter('tecdocId', $this->lastTecdocId);
		}
		
		$cars = $builder
			->getQuery()
			->getArrayResult();
		
		$urls = [];
		
		if (empty($cars)) {
			return $urls;
		}
		
		foreach($cars as $car) {
			$urls[] = new Url(
				$this->seoHelper->completeUrl($this->seoHelper->getCarSeoUrl($car['manufacturerId'], $car['modelId'], $car['tecdocId'])),
				new \Datetime(),
				'monthly',
				Car::class,
				NULL
			);
			$this->lastTecdocId = $car['tecdocId'];
		}
		
        reset($cars);
		
		$this->debug(__METHOD__, [
			'lastTecdocId' => $this->lastTecdocId
		]);
		
        return $urls;
	}
	
	/**
	 * @inheritDoc
	 */
	public function reset(): void {
		$this->lastTecdocId = NULL;
	}
}