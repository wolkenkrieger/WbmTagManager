<?php declare(strict_types=1);
/**
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   19.08.2021
 * Zeit:    12:42
 * Datei:   ContentProduct.php
 * @package ItswCar\Components\Api\Resource\Google\ContentApi
 */

namespace ItswCar\Components\Api\Resource\Google\ContentApi;

use Google\Service\ShoppingContent\Product;
use Google\Service\ShoppingContent\Price;
use Google\Service\ShoppingContent\ProductShipping;
use Google\Service\ShoppingContent\ProductShippingWeight;
use ItswCar\Components\Api\Resource\Google\ContentApi\ContentSession;
use Shopware\Models\Article\Article as ProductModel;

class ContentProduct {
	protected const CHANNEL = 'online';
	protected const CONTENT_LANGUAGE = 'de';
	protected const TARGET_COUNTRY_DE = 'DE';
	protected const TARGET_COUNTRY_CH = 'CH';
	protected const TARGET_COUNTRY_AT = 'AT';
	protected const MAX_RETRIES = 5;
	
	private ProductModel $product;
	private ?ContentSession $session = NULL;
	private int $shopID;
	private $mediaService;
	private bool $force;
	
	
	/**
	 * @param \Shopware\Models\Article\Article $product
	 * @param array                            $config
	 * @param int                              $shopID
	 * @param bool                             $force
	 * @throws \Google\Exception
	 * @throws \JsonException
	 */
	public function __construct(ProductModel $product, array $config, int $shopID = 1, bool $force = FALSE) {
		$this->product = $product;
		$this->shopID = $shopID;
		$this->force = $force;
		$this->mediaService = Shopware()->Container()->get('shopware_media.media_service');
		
		if (is_null($this->session)) {
			$this->session = new ContentSession($config, $this->shopID);
		}
		
	}
	
	/**
	 * @return \Google\Service\ShoppingContent\Product
	 */
	private function buildProduct(): Product {
		$productImageUrls = [];
		
		foreach($this->product->getImages() as $image) {
			$productImageUrls[] = $this->mediaService->getUrl($image->getMedia()->getPath());
		}
		
		$productAvailability = $this->product->getMainDetail()->getInStock()? 'auf Lager' : 'nicht auf Lager';
		
		$productPrice = 0;
		$discount = 0;
		
		foreach($this->product->getMainDetail()->getPrices() as $price) {
			if ($price->getCustomerGroup()->getKey() === 'EK') {
				$productPrice = $price->getPrice();
				$discount = $price->getCustomerGroup()->getDiscount();
				break;
			}
		}
		
		$productPrice *= (($this->product->getTax()->getTax() + 100) / 100);
		$discountProductPrice = $productPrice;
		
		if ($productPrice && $discount) {
			$discountProductPrice -= ($productPrice / 100 * $discount);
		}
		
		$fakePrice = $productPrice * $this->getPriceFactor();
		
		$description = str_ireplace([
			'</ul><br><br><div id="description_oe">',
			'</div>'
		], [
			'<li>',
			'</li></ul>'
		],
			$this->product->getDescriptionLong());
		
		$productMpn = $this->product->getMainDetail()->getSupplierNumber()? : 'ATW-'.$this->product->getMainDetail()->getId();
		
		$product = new Product();
		
		if (!in_array(mb_strtolower($this->product->getSupplier()->getName()), [
			'autoteile wiesel',
			'atw',
			'autoteile-wiesel'
		])) {
			$product->setTitle($this->product->getSupplier()->getName() . ' ' . $this->product->getName());
			$product->setBrand($this->product->getSupplier()->getName());
		} else {
			$product->setTitle($this->product->getName());
			$product->setBrand('ATW');
		}
		
		
		$product->setOfferId($this->product->getMainDetail()->getNumber());
		$product->setDescription($description);
		$product->setLink(implode('/', [$this->session->websiteUrl, ltrim($this->getSeoLink(), '/')]));
		$product->setCanonicalLink(implode('/', [$this->session->websiteUrl, ltrim($this->getSeoLink(), '/')]));
		$product->setImageLink((string)array_shift($productImageUrls));
		
		$product->setContentLanguage(self::CONTENT_LANGUAGE);
		$product->setTargetCountry(self::TARGET_COUNTRY_DE);
		$product->setChannel(self::CHANNEL);
		$product->setAvailability($productAvailability);
		$product->setCondition('neu');
		$product->setGoogleProductCategory('Fahrzeuge & Teile > Fahrzeugersatzteile & -zubehör');
		$product->setGtin((string)$this->product->getMainDetail()->getEan());
		$product->setMpn($productMpn);
		$product->setIdentifierExists(!$this->product->getMainDetail()->getEan()?'nein':'ja');
		
		$price = new Price();
		$price->setValue(sprintf('%.2f', $fakePrice));
		$price->setCurrency('EUR');
		$product->setPrice($price);
		
		$discountPrice = new Price();
		$discountPrice->setValue(sprintf('%.2f', $discountProductPrice));
		$discountPrice->setCurrency('EUR');
		$product->setSalePrice($discountPrice);
		/*
		$shippingPrice = new Google_Service_ShoppingContent_Price();
		$shippingPrice->setValue('0.99');
		$shippingPrice->setCurrency('USD');
		
		$shipping = new Google_Service_ShoppingContent_ProductShipping();
		$shipping->setPrice($shippingPrice);
		$shipping->setCountry('US');
		$shipping->setService('Standard shipping');
		
		$product->setShipping([$shipping]);
		
		$shippingWeight =
			new Google_Service_ShoppingContent_ProductShippingWeight();
		$shippingWeight->setValue(200);
		$shippingWeight->setUnit('grams');
		
		$product->setShippingWeight($shippingWeight);
		*/
		
		if (count($productImageUrls)) {
			$product->setAdditionalImageLinks($productImageUrls);
		}
		
		return $product;
	}
	
	/**
	 * @return array
	 */
	public function create(): array {
		$contentProduct = $this->buildProduct();
		$response = $this->session->service->products->insert($this->session->merchantId, $contentProduct);
		
		if ($this->force) {
			$this->session->retry($this, 'get', $this->product->getMainDetail()->getNumber(), self::MAX_RETRIES);
		}
		
		return [
			'response' => $response,
			'contentProduct' => $contentProduct
		];
	}
	
	/**
	 * @return array
	 */
	public function update(): array {
		$contentProduct = $this->buildProduct();
		$response = $this->session->service->products->insert($this->session->merchantId, $contentProduct);
		
		if ($this->force) {
			$this->session->retry($this, 'get', $this->product->getMainDetail()->getNumber(), self::MAX_RETRIES);
		}
		
		return [
			'response' => $response,
			'contentProduct' => $contentProduct
		];
	}
	
	/**
	 * @param $sku
	 * @return \Google\Service\ShoppingContent\Product
	 */
	public function get($sku): Product {
		return $this->session->service->products->get($this->session->merchantId, $this->buildProductId($sku));
	}
	
	/**
	 * @return string
	 */
	private function getSeoLink(): string {
		$builder = Shopware()->Models()->getDBALQueryBuilder();
		
		$builder->select('r.path')
			->from('s_core_rewrite_urls', 'r')
			->where('r.org_path = "sViewport=detail&sArticle=":articleID')
			->andWhere('r.main = 1')
			->andWhere('r.subshopID = :shopID')
			->setParameters([
				':articleID' => $this->product->getId(),
				':shopID' => $this->shopID
			]);
		
		$result = $builder->execute()->fetchAll(\PDO::FETCH_COLUMN);
		
		if (!empty($result)) {
			return mb_strtolower($result[0]);
		}
		
		return 'detail/sArticle/' . $this->product->getId();
	}
	
	/**
	 * @param $offerId
	 * @return string
	 */
	private function buildProductId($offerId): string {
		return sprintf('%s:%s:%s:%s', self::CHANNEL, self::CONTENT_LANGUAGE,	self::TARGET_COUNTRY_DE, $offerId);
	}
	
	/**
	 * @return float
	 */
	private function getPriceFactor(): float {
		return ((float)rand() / (float)getrandmax()) + 1.1111;
	}
}