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
		
		if (array_key_exists('car', $this->sessionData) && $this->sessionData['car']) {
			$query->innerJoin(
				'variant',
				'itsw_article_car_links',
				'carLinks',
				'(carLinks.article_details_id = variant.id AND carLinks.tecdoc_id = :tecdocId)'
			)
				->setParameter('tecdocId', $this->sessionData['car']);
			
			//var_dump($query->getSQL()); die;
		}
	}
}