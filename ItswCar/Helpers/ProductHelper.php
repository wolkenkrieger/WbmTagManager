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
use ItswCar\Models\ArticleCarLinks;
use ItswCar\Traits\LoggingTrait;
use ItswCar\Models\ArticlePrices;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Attribute\Article as Attribute;
use Shopware\Models\Article\Article as ProductModel;

class ProductHelper {
	use LoggingTrait;
	
	/** @var int  */
	protected const MAX_DESCRIPTION_LENGTH = 4850;
	
	/** @var \Shopware\Components\Model\ModelManager */
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
	public function getMinimumPrice(int $articleDetailsId, string $customerGroupKey = 'EK', int $interval = 30): ?float {
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
				->setCacheable(FALSE)
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
	
	/**
	 * @throws \DOMException
	 */
	public function fixDescriptions(&$article): void {
		
		$name = $article['name']??$article['articleName']??'';
		
		if (isset($article['description'])) {
			$html = $this->fixDescription($article['description'], $name);
			$article['description'] = $html?:$article['description'];
		}
		
		if (isset($article['descriptionLong'])) {
			$html = $this->fixDescription($article['descriptionLong'], $name);
			$article['descriptionLong'] = $html?:$article['descriptionLong'];
		}
		
		if (isset($article['description_long'])) {
			$html = $this->fixDescription($article['description_long'], $name);
			$article['description_long'] = $html?:$article['description_long'];
		}
		
	}
	
	/**
	 * @param string $description
	 * @param string $articleName
	 * @return false|string
	 * @throws \DOMException
	 */
	public function fixDescription(string $description, string $articleName) {
		$dom = new \DOMDocument();
		$dom->loadHTML(mb_convert_encoding($description, 'HTML-ENTITIES', 'UTF-8'));
		
		$xPath = new \DOMXPath($dom);
		
		$nodes = $xPath->query('//li');
		$oe = FALSE;
		$add = TRUE;
		
		foreach($nodes as $node) {
			if (FALSE !== stripos($node->nodeValue, 'qualität:')) {
				if (FALSE !== stripos($node->nodeValue, 'erstausrüster')) {
					$oe = TRUE;
				}
				$node->parentNode->removeChild($node);
			}
			if (FALSE !== stripos($node->nodeValue, 'zustand:')) {
				$add = FALSE;
			}
			
			if (empty($node->nodeValue)) {
				if ($nodes->length === 1) {
					$node->nodeValue = $articleName;
				} else {
					$node->parentNode->removeChild($node);
				}
			}
		}
		
		if ($add && $nodes->count()) {
			$nodes->item($nodes->length - 1)->parentNode->appendChild($dom->createElement('li', sprintf('Zustand: Neuteil%s', $oe? ' in Erstausrüsterqualität': '')));
		}
		
		
		if ((FALSE !== ($html = $dom->saveHTML())) && (FALSE !== ($html = stristr($html, '<ul>'))) && (FALSE !== ($html = stristr($html, '</body>', TRUE)))) {
			return $html;
		}
		
		return $description;
	}
	
	/**
	 * @param string $description
	 * @param string $articleName
	 * @param array  $compatibilityList
	 * @return string
	 * @throws \DOMException
	 */
	public function fixDescriptionForGoogle(string $description, string $articleName, array $compatibilityList = []): string {
		$dom = new \DOMDocument();
		$dom->loadHTML(mb_convert_encoding($description, 'HTML-ENTITIES', 'UTF-8'));
		
		$xPath = new \DOMXPath($dom);
		
		$listNodes = $xPath->query('//li');
		$listEntries = $oeNumbers = [];
		
		foreach($listNodes as $listNode) {
			if ($listNode->nodeValue) {
				$listEntries[] = explode(':', $listNode->nodeValue, 2);
			}
		}
		
		if ($oeNumbersDiv = $dom->getElementById('description_oe')) {
			$oeNumbers = explode(':', $oeNumbersDiv->nodeValue);
			$oeNumbers = explode(',', trim(end($oeNumbers)));
		}
		
		$oeNumbers = array_filter(array_unique($oeNumbers));
		
		$oe = FALSE;
		$length = 0;
		
		if (empty($listEntries)) {
			$listEntries = [$articleName];
		}
		
		$textHelper = Shopware()->Container()->get('itsw.helper.text');
		
		$listEntries = array_filter($listEntries, static function ($listEntry) use (&$oe, &$length, $textHelper) {
			if ((FALSE !== stripos($listEntry[0], 'qualität')) || (FALSE !== stripos($listEntry[0], 'zustand'))) {
				if (isset($listEntry[1]) && (FALSE !== stripos($listEntry[1], 'erstausrüster'))) {
					$oe = TRUE;
				}
				return FALSE;
			}
			
			$length += $textHelper->getLength($listEntry);
			return TRUE;
		});
		
		$lastEntry = [
			'Zustand',
			sprintf('Neuteil%s', $oe ? ' in Erstausrüsterqualität' : '')
		];
		
		$listEntries[] = $lastEntry;
		
		$length += $textHelper->getLength($lastEntry);
		$oeNumbersString = '';
		
		foreach($oeNumbers as $oeNumber) {
			$oeNumber = str_ireplace([
				' ',
				'#',
				';',
				'(',
				')',
				'[',
				']'
			], '', trim($oeNumber));
			
			if (($length + $textHelper->getLength([
						'OE-Vergleichsnummer(n)',
						sprintf('%s, %s', $oeNumbersString, $oeNumber)
					])) < self::MAX_DESCRIPTION_LENGTH) {
				$oeNumbersString = sprintf('%s%s%s', $oeNumbersString, ($oeNumbersString ? ', ' : ''), $oeNumber);
			} else {
				break;
			}
		}
		
		$listEntries[] = [
			'OE-Vergleichsnummer(n)',
			$oeNumbersString
		];
		
		print_r($listEntries);die;
		
		
		$contentLength = 0;
		foreach($nodes as $node) {
			if (FALSE !== stripos($node->nodeValue, 'qualität:')) {
				if (FALSE !== stripos($node->nodeValue, 'erstausrüster')) {
					$oe = TRUE;
				}
				$node->parentNode->removeChild($node);
			}
			if (FALSE !== stripos($node->nodeValue, 'zustand:')) {
				$add = FALSE;
			}
			
			if (empty($node->nodeValue)) {
				if ($nodes->length === 1) {
					$node->nodeValue = $articleName;
				} else {
					$node->parentNode->removeChild($node);
				}
			}
		}
		
		if ($add && $nodes->count()) {
			$nodes->item($nodes->length - 1)->parentNode->appendChild($dom->createElement('li', sprintf('Zustand: Neuteil%s', $oe? ' in Erstausrüsterqualität': '')));
		}
		
		
		if ((FALSE !== ($html = $dom->saveHTML())) && (FALSE !== ($html = stristr($html, '<ul>'))) && (FALSE !== ($html = stristr($html, '</body>', TRUE)))) {
			return $html;
		}
		
		return $description;
	}
	
	/**
	 * @param             $article
	 * @param string|null $suffix
	 * @return array
	 */
	public function getCompatibilityList($article, ?string $suffix): array {
		if ($article instanceof ProductModel) {
			try {
				$articleDetailsId = $article->getMainDetail()->getId();
			} catch (\Exception $exception) {
				$this->error($exception);
				$articleDetailsId = 0;
			}
		} else {
			$articleDetailsId = $article;
		}
		
		$carLinks = $this->manager->getRepository(ArticleCarLinks::class)->findBy([
			'articleDetailsId' => $articleDetailsId
		]);
		
		$result = [];
		
		foreach ($carLinks as $carLink) {
			$car = $carLink->getCar();
			$result[$car->getManufacturer()->getDisplay()][$car->getModel()->getDisplay()][$car->getType()->getDisplay()] = $car->getBuildFromTo($suffix);
		}
		
		return $result;
	}
	
	
}