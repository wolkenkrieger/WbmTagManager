<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   15.03.2021
 * Zeit:    18:51
 * Datei:   CategoryService.php
 * @package ItswCar\Components\Services
 */

namespace ItswCar\Components\Services;

use Shopware\Bundle\StoreFrontBundle\Service\CategoryServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\Category;
use Shopware\Bundle\StoreFrontBundle\Struct;
use ItswCar\Components\Services\Services;

class CategoryService implements CategoryServiceInterface{
	/**
	 * @var \Shopware\Bundle\StoreFrontBundle\Service\CategoryServiceInterface
	 */
	private $originalService;
	/**
	 * @var \ItswCar\Components\Services\Services
	 */
	private $service;
	
	/**
	 * CategoryService constructor.
	 * @param \Shopware\Bundle\StoreFrontBundle\Service\CategoryServiceInterface $originalService
	 * @param \ItswCar\Components\Services\Services                              $service
	 */
	public function __construct(CategoryServiceInterface $originalService, Services $service) {
		$this->originalService = $originalService;
		$this->service = $service;
	}
	
	/**
	 * @param int[]                                                         $ids
	 * @param \Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface $context
	 * @return array
	 */
	public function getList($ids, Struct\ShopContextInterface $context) {
		return $this->originalService->getList($ids, $context);
	}
	
	/**
	 * @param int                                                           $id
	 * @param \Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface $context
	 * @return \Shopware\Bundle\StoreFrontBundle\Struct\Category
	 */
	public function get($id, Struct\ShopContextInterface $context) {
		 return $this->originalService->get($id, $context);
	}
	
	/**
	 * @param array                                                         $products
	 * @param \Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface $context
	 * @return array
	 */
	public function getProductsCategories(array $products, Struct\ShopContextInterface $context) {
		return $this->originalService->getProductsCategories($products, $context);
	}
}