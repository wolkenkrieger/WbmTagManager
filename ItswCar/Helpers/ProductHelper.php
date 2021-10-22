<?php declare(strict_types=1);
/**
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 22.10.2021
 * Time: 08:07
 * File: ProductHelper.php
 * @package ItswCar\Helpers
 */

namespace ItswCar\Helpers;

use ItswCar\Traits\LoggingTrait;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Attribute\Article as Attribute;
use Shopware\Models\Article\Article as ProductModel;

class ProductHelper {
	use LoggingTrait;
	
	public ModelManager $manager;
	
	/**
	 *
	 */
	public function __construct() {
		$this->manager = Shopware()->Models();
	}
	
	/**
	 * @param \Shopware\Models\Article\Article $product
	 * @return bool
	 */
	public function setProductFakePrice(ProductModel $product): bool {
		try {
			$attribute = $this->manager->getRepository(Attribute::class)
				->findOneBy([
					'articleDetailId' => $product->getMainDetail()->getId()
				]);
			
			if ($attribute) {
				$productPrice = 0;
				
				foreach($product->getMainDetail()->getPrices() as $price) {
					if ($price->getCustomerGroup()->getKey() === 'EK') {
						$productPrice = $price->getPrice();
						if ($discount = $price->getCustomerGroup()->getDiscount()) {
							$productPrice -= ($productPrice / 100 * $discount);
						}
						break;
					}
				}
				
				$productPrice *= (($product->getTax()->getTax() + 100) / 100);
				$fakePrice = $productPrice * $this->getPriceFactor(1, 1.543);
				
				$this->manager->persist($attribute);
				$attribute->setFakePrice($fakePrice);
				$this->manager->flush($attribute);
			}
		} catch (\Exception $exception) {
			$this->error($exception);
			
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * @param int   $min
	 * @param float $max
	 * @return float
	 */
	private function getPriceFactor(int $min = 1, float $max = 1.654): float {
		return $min + mt_rand() / mt_getrandmax() * ($max - $min);
	}
}