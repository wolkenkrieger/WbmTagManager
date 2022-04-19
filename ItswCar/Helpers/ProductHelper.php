<?php declare(strict_types=1);
/**
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 22.10.2021
 * Time: 08:07
 * File: ProductHelper.php
 * @package ItswCar\Helpers
 */

namespace ItswCar\Helpers;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use ItswCar\Traits\LoggingTrait;
use ItswCar\Models\ArticlePrices;
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
	 * @param \Shopware\Models\Article\Article $product
	 * @param string                           $userGroup
	 * @return float
	 */
	public function getProductRealPrice(ProductModel $product, string $userGroup = 'EK'): float {
		$productPrice = 0;
		
		foreach($product->getMainDetail()->getPrices() as $price) {
			if ($price->getCustomerGroup()->getKey() === $userGroup) {
				$productPrice = $price->getPrice();
				if ($discount = $price->getCustomerGroup()->getDiscount()) {
					$productPrice -= ($productPrice / 100 * $discount);
				}
				break;
			}
		}
		
		$productPrice *= (($product->getTax()->getTax() + 100) / 100);
		
		return (float)$productPrice;
	}
	
	/**
	 * @param \Shopware\Models\Article\Article $product
	 * @return void
	 * @throws \Doctrine\ORM\ORMException
	 */
	public function setProductPriceHistory(ProductModel $product): void {
		$priceHistory = NULL;
		$tax = $product->getTax()->getTax();
		
		foreach($product->getMainDetail()->getPrices() as $price) {
			$productPrice = $price->getPrice();
			
			/*
			if ($discount = $price->getCustomerGroup()->getDiscount()) {
				$productPrice -= ($productPrice / 100 * $discount);
			}
			*/
			
			$customerGroupKey = $price->getCustomerGroup()->getKey();
			$articleDetailsId = $price->getDetail()->getId();
			
			$result = $this->manager->getRepository(ArticlePrices::class)->getDefaultQuery([
				'select' => [
					'prices'
				],
				'from' => [
					'prices' => ArticlePrices::class
				],
				'conditions' => [
					'prices.tax' => $tax,
					'prices.price' => $productPrice,
					'prices.articleDetailsId' => $articleDetailsId,
					'prices.customerGroupKey' => $customerGroupKey
				]
			])
				->setMaxResults(1)
				->getResult();
			
			if (count($result)) {
				continue;
			}
			
			$priceHistory = new ArticlePrices([
				'articleDetailsId' => $articleDetailsId,
				'customerGroupKey' => $customerGroupKey,
				'date' => new \DateTimeImmutable(),
				'price' => $productPrice,
				'tax' => $tax
			]);
			
			$this->manager->persist($priceHistory);
		}
		
		$this->manager->flush($priceHistory);
	}
	
	/**
	 * @param int    $articleDetailsId
	 * @param string $customerGroupKey
	 * @param int    $interval
	 * @return float|null
	 * @throws \Exception
	 */
	public function getMinimumPrice(int $articleDetailsId, string $customerGroupKey = 'EK', int $interval = 30) {
		$now = new \DateTimeImmutable();
		$past = $now->sub(new \DateInterval(sprintf('P%dD', $interval + 1)));
		
		try {
			$historyPrice = $this->manager->getRepository(ArticlePrices::class)->getDefaultQuery([
				'select' => [
					'MIN(prices.price) AS price_net'
				],
				'from' => [
					'prices' => ArticlePrices::class
				],
				'conditions' => [
					'prices.articleDetailsId' => $articleDetailsId,
					'prices.customerGroupKey' => $customerGroupKey,
					'prices.date >' => $past
				]
			])
				->getSingleScalarResult();
		} catch (\Exception $exception) {
			$this->error($exception);
			
			return NULL;
		}
		
		return !is_null($historyPrice) ? (float)$historyPrice : NULL;
	}
	
	/**
	 * @param int   $min
	 * @param float $max
	 * @return float
	 */
	private function getPriceFactor(int $min = 1, float $max = 1.654): float {
		return $min + mt_rand() / mt_getrandmax() * ($max - $min);
	}
	
	/**
	 * @param $moneyfloat
	 * @return float|int
	 */
	public function roundFloat($moneyfloat = null) {
		if (is_numeric($moneyfloat)) {
			$moneyfloat = sprintf('%F', $moneyfloat);
		}
		$money_str = explode('.', $moneyfloat);
		if (empty($money_str[1])) {
			$money_str[1] = 0;
		}
		$money_str[1] = substr((string) $money_str[1], 0, 3); // convert to rounded (to the nearest thousandth) string
		
		$value = (float) ($money_str[0] . '.' . $money_str[1]);
		
		// round handles "-0" different since PHP 7.4, @see https://bugs.php.net/bug.php?id=78660
		if ($value === -0.0) {
			return 0;
		}
		
		return round($value, 2);
	}
	
	/**
	 * @param $price
	 * @return array|string|string[]
	 */
	public function formatPrice($price) {
		$price = str_replace(',', '.', (string) $price);
		$price = $this->sRound($price);
		$price = str_replace('.', ',', (string) $price); // Replaces points with commas
		$commaPos = strpos($price, ',');
		if ($commaPos) {
			$part = substr($price, $commaPos + 1, \strlen($price) - $commaPos);
			switch (\strlen($part)) {
				case 1:
					$price .= '0';
					break;
				case 2:
					break;
			}
		} else if (!$price) {
			$price = '0';
		} else {
			$price .= ',00';
		}
		
		return $price;
	}
}