<?php declare(strict_types=1);
/**
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   19.08.2021
 * Zeit:    12:42
 * Datei:   ContentProduct.php
 * @package ItswCar\Components\Api\Resource\Google\ContentApi
 */

namespace ItswCar\Components\Google\ContentApi;

use Google_Service_ShoppingContent_Price;
use Google_Service_ShoppingContent_ProductShipping;
use Google_Service_ShoppingContent_CustomAttribute;
use Google_Service_ShoppingContent_Product;
use Shopware\Models\Article\Article as ProductModel;
use ItswCar\Traits\LoggingTrait;

class ContentProduct {
	use LoggingTrait;
	
	/** @var int  */
	protected const ROOT_CATEGORY_ID = 5;
	
	/** @var string  */
	protected const CHANNEL = 'online';
	
	/** @var string  */
	protected const CONTENT_LANGUAGE = 'de';
	
	/** @var string  */
	protected const TARGET_COUNTRY_DE = 'DE';
	
	/** @var string[]  */
	//protected const TARGET_COUNTRIES = ['DE', 'CH', 'AT'];
	protected const TARGET_COUNTRIES = ['DE', 'AT'];
	
	/** @var string  */
	protected const DEFAULT_DISPATCH = 'DHL';
	
	/** @var int  */
	protected const MAX_RETRIES = 5;
	
	/** @var \Shopware\Models\Article\Article  */
	private ProductModel $product;
	
	/** @var \ItswCar\Components\Google\ContentApi\ContentSession|null  */
	private ?ContentSession $session;
	
	/** @var int  */
	private int $shopID;
	
	/** @var mixed|object|\Symfony\Component\DependencyInjection\Container|null  */
	private $mediaService;
	
	/** @var bool  */
	private bool $force;
	
	/** @var \ItswCar\Helpers\TextHelper|mixed|object|\Symfony\Component\DependencyInjection\Container|null  */
	private $textHelper;

	/** @var \ItswCar\Helpers\ConfigHelper|mixed|object|\Symfony\Component\DependencyInjection\Container|null  */
	private $configHelper;
	
	/** @var \ItswCar\Helpers\ProductHelper|mixed|object|\Symfony\Component\DependencyInjection\Container|null  */
	private $productHelper;
	
	
	/**
	 * @param \Shopware\Models\Article\Article                          $product
	 * @param array                                                     $config
	 * @param int                                                       $shopID
	 * @param \ItswCar\Components\Google\ContentApi\ContentSession|null $session
	 * @param bool                                                      $force
	 * @throws \Google\Exception
	 * @throws \JsonException
	 */
	public function __construct(ProductModel $product, array $config, int $shopID = 1, ?ContentSession $session = NULL, bool $force = FALSE) {
		$this->product = $product;
		$this->shopID = $shopID;
		$this->force = $force;
		$this->mediaService = Shopware()->Container()->get('shopware_media.media_service');
		$this->textHelper = Shopware()->Container()->get('itsw.helper.text');
		$this->configHelper = Shopware()->Container()->get('itsw.helper.config');
		$this->productHelper = Shopware()->Container()->get('itsw.helper.product');
		
		if (is_null($session)) {
			$this->session = new ContentSession($config, $this->shopID);
		} else {
			$this->session = $session;
		}
		
	}
	
	/**
	 * @return \Google_Service_ShoppingContent_Product
	 * @throws \Doctrine\DBAL\Driver\Exception
	 * @throws \Doctrine\DBAL\Exception
	 */
	private function buildProduct(): Google_Service_ShoppingContent_Product {
		try {
			$shippingInfos = $this->configHelper->getShippingInfos();
		} catch (\Exception $exception) {
			$this->error($exception);
			$shippingInfos = [];
		}
		
		$productImageUrls = [];
		
		foreach($this->product->getImages() as $image) {
			try {
				$productImageUrls[] = $this->mediaService->getUrl($image->getMedia()->getPath());
			} catch (\Exception $exception) {
				$this->error($exception);
			}
		}
		
		$categories = [];
		$parentCategories = [];
		$rootCategoryId = ($rootCategory = $this->configHelper->getRootCategory()) ? $rootCategory->getId() : self::ROOT_CATEGORY_ID;
		
		foreach($this->product->getCategories() as $category) {
			$categories[$category->getLevel()][] = $category->getName();
			
			do {
				if ($category = $category->getParent()) {
					$parentCategories[$category->getLevel()][] = $category->getName();
				} else {
					break;
				}
			} while ($category->getId() === $rootCategoryId);
		}
		
		if (empty($parentCategories)) {
			$parentCategories = min($categories);
		} else {
			$parentCategories = min($parentCategories);
		}
		
		$categories = max($categories);
		
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
		
		$productPrice *= (((float)$this->product->getTax()->getTax() + 100) / 100);
		$discountProductPrice = $productPrice;
		
		if ($productPrice && $discount) {
			$discountProductPrice -= ($productPrice / 100 * $discount);
		}
		
		//$fakePrice = $productPrice * $this->getPriceFactor();
		//$fakePrice = $this->product->getMainDetail()->getAttribute()->getFakePrice()?:$productPrice;
		
		$description = $this->product->getDescriptionLong();

		$description = str_ireplace([
			'</ul><br><br><div id="description_oe">',
			'</div>'
		], [
			'<li>',
			'</li></ul>'
		],
			$this->textHelper->filterBadWords($description));
		
		try {
			$description = $this->productHelper->fixDescription($description, $this->product->getName());
		} catch (\Exception $exception) {
			$this->error($exception);
		}
		
		$productMpn = $this->product->getMainDetail()->getSupplierNumber()? : 'ATW-'.$this->product->getMainDetail()->getId();
		
		$product = new Google_Service_ShoppingContent_Product();
		
		if (!in_array(mb_strtolower($this->product->getSupplier()->getName()), [
			'autoteile wiesel',
			'atw',
			'autoteile-wiesel'
		])) {
			$product->setTitle($this->product->getSupplier()->getName() . ' ' . $this->textHelper->filterBadWords($this->product->getName()));
			$product->setBrand($this->product->getSupplier()->getName());
		} else {
			$product->setTitle($this->textHelper->filterBadWords($this->product->getName()));
			$product->setBrand('ATW');
		}
		
		
		$product->setOfferId($this->product->getMainDetail()->getNumber());
		$product->setId($this->buildProductId($product->getOfferId()));
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
		
		/*
		$price = new Price();
		$price->setValue(sprintf('%.2f', $fakePrice));
		$price->setCurrency('EUR');
		$product->setPrice($price);
		
		$discountPrice = new Price();
		$discountPrice->setValue(sprintf('%.2f', $discountProductPrice));
		$discountPrice->setCurrency('EUR');
		$product->setSalePrice($discountPrice);
		*/
		
		$discountPrice = new Google_Service_ShoppingContent_Price();
		$discountPrice->setValue(sprintf('%.2f', $discountProductPrice));
		$discountPrice->setCurrency('EUR');
		$product->setPrice($discountPrice);
		
		$productShipping = [];
		
		if (!empty($shippingInfos)) {
			foreach($shippingInfos as $shippingInfo) {
				if (in_array($shippingInfo['countryISO'], self::TARGET_COUNTRIES, TRUE)) {
					$shippingPrice = new Google_Service_ShoppingContent_Price();
					$shippingPrice->setValue((string)$shippingInfo['shippingCost']);
					$shippingPrice->setCurrency('EUR');
					
					$shipping = new Google_Service_ShoppingContent_ProductShipping();
					$shipping->setPrice($shippingPrice);
					$shipping->setCountry($shippingInfo['countryISO']);
					$shipping->setService($shippingInfo['dispatchName']);
					
					$productShipping[] = $shipping;
				}
			}
		} else {
			$shippingPrice = new Google_Service_ShoppingContent_Price();
			$shippingPrice->setValue('0.00');
			$shippingPrice->setCurrency('EUR');
			
			$shipping = new Google_Service_ShoppingContent_ProductShipping();
			$shipping->setPrice($shippingPrice);
			$shipping->setCountry(self::TARGET_COUNTRY_DE);
			$shipping->setService(self::DEFAULT_DISPATCH);
			
			$productShipping[] = $shipping;
		}
		
		$product->setShipping($productShipping);
		
		if (count($productImageUrls)) {
			$product->setAdditionalImageLinks($productImageUrls);
		}
		
		if (!$this->product->getActive() || !count($productImageUrls)) {
			$customAttribute = new Google_Service_ShoppingContent_CustomAttribute();
			$customAttribute->setName('active');
			$customAttribute->setValue(FALSE);
			$product->setCustomAttributes([$customAttribute]);
		}
		
		$product->setCustomLabel0($this->getLabel0($discountProductPrice));
		$product->setCustomLabel1($parentCategories[0]??NULL);
		$product->setCustomLabel2($categories[0]??NULL);
		$product->setCustomLabel3($productAvailability);
		
		
		$product->setAdsLabels(array_merge($parentCategories, $categories));
		
		return $product;
	}
	
	/**
	 * @return array
	 * @throws \Doctrine\DBAL\Driver\Exception
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function create(): array {
		$toDelete = FALSE;
		$contentProduct = $this->buildProduct();
		
		foreach ($contentProduct->getCustomAttributes() as $customAttribute) {
			if ($customAttribute->getName() === 'active' && $customAttribute->getValue() === FALSE) {
				$toDelete = TRUE;
				break;
			}
		}
		
		try {
			if ($toDelete) {
				$response = $this->session->service->products->delete($this->session->merchantId, $contentProduct->getId());
			} else {
				$response = $this->session->service->products->insert($this->session->merchantId, $contentProduct);
			}
		} catch (\Exception $exception) {
			try {
				$response = json_decode($exception->getMessage(), TRUE, 512, JSON_THROW_ON_ERROR);
			} catch (\JsonException $jsonException) {
				$response = $exception->getMessage();
			}
		}
		
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
	 * @throws \Doctrine\DBAL\Driver\Exception
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function update(): array {
		$toDelete = FALSE;
		$contentProduct = $this->buildProduct();
		
		foreach ($contentProduct->getCustomAttributes() as $customAttribute) {
			if ($customAttribute->getName() === 'active' && $customAttribute->getValue() === FALSE) {
				$toDelete = TRUE;
				break;
			}
		}
		
		try {
			if ($toDelete) {
				$response = $this->session->service->products->delete($this->session->merchantId, $contentProduct->getId());
			} else {
				$response = $this->session->service->products->insert($this->session->merchantId, $contentProduct);
			}
		} catch (\Exception $exception) {
			try {
				$response = json_decode($exception->getMessage(), TRUE, 512, JSON_THROW_ON_ERROR);
			} catch (\JsonException $jsonException) {
				$response = $exception->getMessage();
			}
		}
		
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
	 * @throws \Doctrine\DBAL\Exception
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
	public function buildProductId($offerId): string {
		return sprintf('%s:%s:%s:%s', self::CHANNEL, self::CONTENT_LANGUAGE,	self::TARGET_COUNTRY_DE, $offerId);
	}
	
	/**
	 * @return float
	 */
	private function getPriceFactor(): float {
		return ((float)mt_rand() / (float)mt_getrandmax()) + 1.1111;
	}
	
	/**
	 *
	 * @param float $articlePrice
	 * @return string
	 */
	private function getLabel0( float $articlePrice = 0 ):string {
		$prices = [
			10 => '1 - 10 EUR',
			20 => '10 - 20 EUR',
			50 => '20 - 50 EUR',
			100 => '50 - 100 EUR',
			200 => '100 - 200 EUR',
			400 => '200 - 400 EUR',
			600 => '400 - 600 EUR',
			10000 => '> 600 EUR',
		];
		$priceText = '';
		foreach( $prices as $price => $text ) {
			if( $price > $articlePrice ) {
				$priceText = $text;
				break;
			}
		}
		return $priceText;
	}
}