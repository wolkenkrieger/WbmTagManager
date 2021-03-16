<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   26.01.2021
 * Zeit:    13:35
 * Datei:   CategoryConditionHandler.php
 * @package ItswCar\Components\Eventhandlers
 */

namespace ItswCar\Components\Eventhandlers;


use Doctrine\DBAL\Connection;
use Shopware\Bundle\SearchBundle\ConditionInterface;
use Shopware\Bundle\SearchBundleDBAL\QueryBuilder;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;

use ItswCar\Components\Services\Services;

class CategoryConditionHandler extends \Shopware\Bundle\SearchBundleDBAL\ConditionHandler\CategoryConditionHandler {
	protected $service;
	protected $sessionData = [];
	
	/**
	 * CategoryConditionHandler constructor.
	 * @param \ItswCar\Components\Services\Services $service
	 */
	public function __construct(Services $service) {
		if (!$this->service) {
			$this->service = $service;
		}
		
		if (!is_array($this->sessionData) || empty($this->sessionData)) {
			$this->sessionData = $this->service->getSessionData();
		}
	}
	
	/**
	 * @param \Shopware\Bundle\SearchBundle\ConditionInterface              $condition
	 * @param \Shopware\Bundle\SearchBundleDBAL\QueryBuilder                $query
	 * @param \Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface $context
	 */
	public function generateCondition(ConditionInterface $condition, QueryBuilder $query, ShopContextInterface $context): void {
		parent::generateCondition($condition, $query, $context);
		$this->service->getVariantIdsWithoutArticleCarLink();
		if (array_key_exists('car', $this->sessionData) && $this->sessionData['car']) {
			$variantIds = array_merge($this->getVariantIds(), $this->service->getVariantIdsWithoutArticleCarLink());
			$query->andWhere('product.main_detail_id IN (:variantIds)')
				->setParameter('variantIds', $variantIds, Connection::PARAM_INT_ARRAY);
		}
	}
	
	private function getVariantIds(): array {
		return $this->service->getVariantIdsByTecdocId($this->sessionData['car']);
	}
}