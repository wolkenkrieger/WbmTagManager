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

class CategoryService implements CategoryServiceInterface{
	/**
	 * @var \Shopware\Bundle\StoreFrontBundle\Service\CategoryServiceInterface
	 */
	private CategoryServiceInterface $originalService;
	
	
	/**
	 * CategoryService constructor.
	 * @param \Shopware\Bundle\StoreFrontBundle\Service\CategoryServiceInterface $originalService
	 * @param \ItswCar\Components\Services\Services                              $service
	 */
	public function __construct(CategoryServiceInterface $originalService, Services $service) {
		$this->originalService = $originalService;
	}
	
	/**
	 * @param int[]                                                         $ids
	 * @param \Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface $context
	 * @return array
	 */
	public function getList($ids, Struct\ShopContextInterface $context): array {
		return $this->originalService->getList($ids, $context);
	}
	
	/**
	 * @param int                                                           $id
	 * @param \Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface $context
	 * @return \Shopware\Bundle\StoreFrontBundle\Struct\Category
	 */
	public function get($id, Struct\ShopContextInterface $context): Category {
		 return $this->originalService->get($id, $context);
	}
	
	/**
	 * @param array                                                         $products
	 * @param \Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface $context
	 * @return array
	 */
	public function getProductsCategories(array $products, Struct\ShopContextInterface $context): array {
		return $this->originalService->getProductsCategories($products, $context);
	}
}