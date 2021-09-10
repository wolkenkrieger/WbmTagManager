<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   16.12.2020
 * Zeit:    17:42
 * Datei:   Eventhandlers.php
 * @package ItswCar\Components\Eventhandlers
 */

namespace ItswCar\Components\Eventhandlers;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use InvalidArgumentException;
use ItswCar\Components\Services\Services;
use ItswCar\Models\Car;
use Shopware\Bundle\AttributeBundle\Service\DataLoader;
use Shopware\Bundle\AttributeBundle\Service\DataPersister;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Attribute\OrderBasket;
use Shopware\Models\Order\Basket;
use Shopware\Components\DependencyInjection\Container;

class Eventhandlers {
	protected Services $service;
	protected string $pluginDir;
	protected array $config;
	private ModelManager $modelManager;
	private DataLoader $attributeLoader;
	private DataPersister $attributePersister;
	protected Container $container;
	
	/**
	 * @param \Shopware\Components\DependencyInjection\Container     $container
	 * @param \ItswCar\Components\Services\Services                  $service
	 * @param \Shopware\Components\Model\ModelManager                $modelManager
	 * @param \Shopware\Bundle\AttributeBundle\Service\DataLoader    $attributeLoader
	 * @param \Shopware\Bundle\AttributeBundle\Service\DataPersister $attributePersister
	 * @param string                                                 $pluginDir
	 * @param array                                                  $config
	 */
	public function __construct(Container $container,
	                            Services $service,
	                            ModelManager $modelManager,
	                            DataLoader $attributeLoader,
	                            DataPersister $attributePersister,
	                            string $pluginDir,
	                            array $config) {
		$this->container = $container;
		$this->service = $service;
		$this->pluginDir = $pluginDir;
		$this->config = $config;
		$this->modelManager = $modelManager;
		$this->attributeLoader = $attributeLoader;
		$this->attributePersister = $attributePersister;
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $actionEventArgs
	 * @throws \Exception
	 */
	public function onPostDispatchSecureFrontend(\Enlight_Controller_ActionEventArgs $actionEventArgs): void {
		$subject = $actionEventArgs->getSubject();
		$request = $actionEventArgs->getRequest();
		
		$basketData = [];
		if ($request->getActionName() === 'finish') {
			$basketData = [];
			$basket = $subject->View()->getAssign('sBasket');
			$basketData['shippingdate'] = $this->getShippingDate($basket);
			$basketData['gtins'] = $this->getGTINs($basket);
		}
		
		$templateVars = [
			'session' => $this->service->getSessionData(),
			'basketdata' => $basketData,
			'google' => $this->getGoogleConfigOptions()
		];
		
		$subject->View()->assign('ITSW', $templateVars, TRUE);
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $actionEventArgs
	 */
	public function onPreDispatchFrontend(\Enlight_Controller_ActionEventArgs $actionEventArgs): void {
		$subject = $actionEventArgs->getSubject();
		
		$subject->View()->assign('ITSW-MAINTENANCEMODE', $this->service->getServiceMode());
		$subject->View()->assign('ITSW-DEVELOPMENTMODE', $this->service->getDevelopmentMode());
	}
	
	/**
	 * @param \Enlight_Controller_EventArgs $controllerEventArgs
	 */
	public function onFrontRouteShutdown(\Enlight_Controller_EventArgs $controllerEventArgs): void {
	
	}
	
	/**
	 * @param \Enlight_Controller_EventArgs $controllerEventArgs
	 */
	public function onFrontRouteStartup(\Enlight_Controller_EventArgs $controllerEventArgs): void {
		$sessionData = $this->service->getSessionData();
		$queryPath = $controllerEventArgs->getRequest()->getPathInfo();
		if (!$queryPath || $queryPath === '/' || stripos($queryPath, 'carfinder') !== FALSE) {
			return;
		}
		
		$queryPathParts = explode('/', $queryPath);
		
		$queryPathParts = array_filter($queryPathParts, static function ($value) {
			return ($value !== NULL && $value !== FALSE && $value !== '');
		});
		
		foreach($queryPathParts as $index => $queryPathPart) {
			$matches = $controllerEventArgs->getSubject()->Router()->match($queryPathPart);
			if (is_array($matches)) {
				if (isset($matches['m']) || isset($matches['mo']) || isset($matches['car'])) {
					/*
					if ( stripos($queryPath, 'carfinder') === FALSE) {
						unset($queryPathParts[$index]);
					}
					*/
					unset($queryPathParts[$index]);
					if (((int)$matches['car'] && !$sessionData['car']) || ((int)$matches['car'] !== $sessionData['car'])) {
						try {
							$query = $this->modelManager->getRepository(Car::class)
								->getIdsByTecdocIdQueryBuilder((int)$matches['car'])
								->getQuery()
								->useQueryCache(TRUE);
							
							if (!is_null($car = $query->getOneOrNullResult())) {
								$sessionData = [
									'manufacturer' => $car['manufacturerId'],
									'model' => $car['modelId'],
									'type' => $car['typeId'],
									'car' => $car['tecdocId']
								];
								
								$this->service->setSessionData($sessionData);
							}
						} catch(NonUniqueResultException $nonUniqueResultException) {
							$this->setLog($nonUniqueResultException);
						} catch (\JsonException $jsonException) {
							$this->setLog($jsonException);
						}
					}
				}
			}
		}
		
		$uri = trim(implode('/', $queryPathParts), '/'). '/';
		
		$matches = $controllerEventArgs->getSubject()->Router()->match($uri);
		
		$controllerEventArgs->getRequest()->clearParams();
		$controllerEventArgs->getRequest()->setParams($matches);
		$controllerEventArgs->getRequest()->setControllerName($matches['controller']);
		$controllerEventArgs->getRequest()->setModuleName($matches['module']);
		$controllerEventArgs->getRequest()->setActionName($matches['action']);
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $eventArgs
	 */
	public function onAfterConvertCategoryByLegacyStructConverter(\Enlight_Event_EventArgs $eventArgs): void {
		$eventArgs->setReturn($this->setCategoryLink($eventArgs->getReturn()));
	}
	
	/**
	 * @param \Enlight_Hook_HookArgs $hookArgs
	 */
	public function onAfterConvertCategory(\Enlight_Hook_HookArgs $hookArgs): void {
		$hookArgs->setReturn($this->setCategoryLink($hookArgs->getReturn()));
	}
	
	/**
	 * @param \Enlight_Hook_HookArgs $hookArgs
	 */
	public function onAfterGetCategoriesByParent(\Enlight_Hook_HookArgs $hookArgs): void {
		$categories = $hookArgs->getReturn();
		
		foreach($categories as &$category) {
			$category = $this->setCategoryLink($category);
		}
		
		unset($category);
		$hookArgs->setReturn($categories);
	}
	
	/**
	 * @param \Enlight_Hook_HookArgs $hookArgs
	 */
	public function onAfterGetArticleByCategory(\Enlight_Hook_HookArgs $hookArgs): void {
		$return = $hookArgs->getReturn();
		$articles = $return['sArticles']??[];
		foreach($articles as &$article) {
			$this->setPseudoprice($article);
			$article['linkDetails'] = ($this->service->getArticleSeoUrl($article['articleID']) ?: $article['linkDetails']);
		}
		unset($article);
		$return['sArticles'] = $articles;
		$hookArgs->setReturn($return);
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $eventArgs
	 */
	public function onConvertListProduct(\Enlight_Event_EventArgs $eventArgs): void {
		$article = $eventArgs->getReturn();
		$this->setPseudoprice($article);
		$article['linkDetails'] = ($this->service->getArticleSeoUrl($article['articleID']) ?: $article['linkDetails']);
		$eventArgs->setReturn($article);
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $actionEventArgs
	 */
	public function onPostDispatchSecureFrontendListing(\Enlight_Controller_ActionEventArgs $actionEventArgs): void {
		/*
		 * @toDo: something magic :)
		 */
	}
	
	/**
	 * @param \Enlight_Hook_HookArgs $hookArgs
	 */
	public function onAfterGetArticleById(\Enlight_Hook_HookArgs $hookArgs): void {
		$article = $hookArgs->getReturn();
		
		$titlePart = implode(' ', [
			$article['ordernumber'],
			(!in_array(mb_strtolower($article['supplierName']), [
				'autoteile wiesel',
				'atw',
				'autoteile-wiesel'
			]) ? implode(' ', [
				$article['supplierName'],
				$article['articleName']
			]) : $article['articleName'])
		]);
		
		$this->setPseudoprice($article);
		
		$article['seoTitle'] = implode(' ', [
			$titlePart,
			'- Jetzt Kaufen!'
		]);
		
		$article['seoDescription'] = implode(' &star; ', [
			sprintf('%s günstig kaufen', $titlePart),
			sprintf('Sparen Sie jetzt bis zu %d%%', $article['pseudopricePercent']),
			'Versandkostenfreie Lieferung in Deutschland'
		]);
		
		$article['linkDetails'] = ($this->service->getArticleSeoUrl($article['articleID']) ?: $article['linkDetails']);
		
		if (empty($article['keywords'])) {
			$article['keywords'] = $this->getKeywords($article['description_long']);
		}
		
		//echo "<pre>"; var_dump($article); echo "</pre>"; die;
		
		$hookArgs->setReturn($article);
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $eventArgs
	 */
	public function onPostDispatchSecureBackendForm(\Enlight_Controller_ActionEventArgs $eventArgs): void {
		$controller = $eventArgs->getSubject();
		$view = $controller->View();
		$request = $controller->Request();
		$view->addTemplateDir($this->pluginDir . '/Resources/views');
		
		if ($request->getActionName() === 'load') {
			$view->extendsTemplate('backend/extend_form/view/main/fieldgrid.js');
		}
	}
	
	/**
	 * @param $category
	 * @return mixed
	 */
	private function setCategoryLink($category) {
		if (!$category['external']) {
			$category['link'] = ($this->service->getCategorySeoUrl($category['id'] ?: $category['link']));
		}
		
		return $category;
	}
	
	/**
	 * @param $price
	 * @param $tax
	 * @param $discount
	 * @return string
	 */
	private function getPrice($price, $tax, $discount): string {
		$price /= (1 - ($discount / 100));
		return Shopware()->Modules()->Articles()->sFormatPrice($price);
	}
	
	/**
	 * @param $price
	 * @param $tax
	 * @param $discount
	 * @return float|int
	 */
	private function getPriceNum($price, $tax, $discount) {
		$price /= (1 - ($discount / 100));
		return Shopware()->Modules()->Articles()->sRound($price);
	}
	
	/**
	 * @param $realPrice
	 * @param $fakePrice
	 * @return false|float
	 */
	private function getFakePriceDiscountPercent($realPrice, $fakePrice) {
		$p = (100 / $fakePrice) * $realPrice;
		
		if ($p < 100) {
			return floor(100 - $p);
		}
		
		return floor($p);
	}
	
	/**
	 * @param array $basket
	 * @return array
	 */
	private function getGTINs(array $basket): array {
		$gtins = [];
		
		foreach($basket['content'] as $article) {
			$gtins[] = $article['ean'];
		}
		
		return array_unique(array_filter($gtins, static function($value) {return !is_null($value) && $value !== '';}));
	}
	
	/**
	 * @param array $basket
	 * @return string
	 * @throws \Exception
	 */
	private function getShippingDate(array $basket): string {
		$today = new \DateTime(date('Y-m-d'));
		$shippingDate = $today->add(new \DateInterval('P'.$this->getMaxShippingTime($basket). 'D'));
		$shippingDayOfWeek = $shippingDate->format('N');
		switch($shippingDayOfWeek) {
			case 6: return $shippingDate->add(new \DateInterval('P2D'))->format('Y-m-d');
			case 7: return $shippingDate->add(new \DateInterval('P1D'))->format('Y-m-d');
		}
		return $shippingDate->format('Y-m-d');
	}
	
	/**
	 * @param array $basket
	 * @return int
	 */
	private function getMaxShippingTime(array $basket): int {
		if ($this->isUnavailableProductInBasket($basket)) {
			$maxShippingTime = $this->config['google_default_shipping_time_not_in_stock']??14;
			
			return (int)$maxShippingTime;
		}
		
		$maxShippingTime = 1;
		foreach($basket['content'] as $article) {
			if ($article['shippingtime'] > $maxShippingTime) {
				$maxShippingTime = $article['shippingtime'];
			}
		}
		
		return (int)$maxShippingTime;
	}
	
	/**
	 * @param array $basket
	 * @return bool
	 */
	private function isUnavailableProductInBasket(array $basket): bool {
		if (!$basket['content']) {
			return FALSE;
		}
		
		foreach ($basket['content'] as $article) {
			if ($article['instock'] === NULL) {
				continue;
			}
			
			if ($article['instock'] <= 0) {
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * @throws \JsonException
	 */
	private function getGoogleConfigOptions(): array {
		
		if (isset($this->config['merchant_info']) && $this->config['merchant_info']) {
			$merchantInfo = json_decode($this->config['merchant_info'], TRUE, 512, JSON_THROW_ON_ERROR);
			if (is_null($merchantInfo)) {
				throw new InvalidArgumentException();
			}
		}
		
		if (isset($this->config['service_account']) && $this->config['service_account']) {
			$accountInfo = json_decode($this->config['service_account'], TRUE, 512, JSON_THROW_ON_ERROR);
			if (is_null($accountInfo)) {
				throw new InvalidArgumentException();
			}
		}
		
		return array_merge($merchantInfo, $accountInfo, [
			'showbadge' => $this->config['google_show_badge'],
			'badgeposition' => $this->config['google_badge_position'],
			'surveyoptinstyle' => $this->config['google_survey_opt_in_style']
		]);
	}
	
	/**
	 * @param $article
	 */
	public function setPseudoprice(&$article): void {
		if ($fakePrice = $article['attributes']['core']['fake_price'] ?? NULL) {
			$article['has_pseudoprice'] = TRUE;
			$article['pseudoprice'] = $fakePrice;
			$article['pseudoprice_numeric'] = (float)$fakePrice;
			$article['pseudopricePercent'] = $this->getFakePriceDiscountPercent($article['price_numeric'], $article['pseudoprice_numeric']);
		} else if ($discount = $this->service->getUserGroupDiscount()) {
			$article['has_pseudoprice'] = TRUE;
			$article['pseudoprice'] = $this->getPrice($article['price_numeric'], $article['tax'], $discount);
			$article['pseudoprice_numeric'] = $this->getPriceNum($article['price_numeric'], $article['tax'], $discount);
			$article['pseudopricePercent'] = $this->getFakePriceDiscountPercent($article['price_numeric'], $article['pseudoprice_numeric']);
		}
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $eventArgs
	 */
	public function onBasketUpdateCartItemsUpdated(\Enlight_Event_EventArgs $eventArgs): void {
		$sessionData = $this->service->getSessionData();
		
		if (empty($sessionData) || is_null($sessionData['car'])) {
			return;
		}
		
		try {
			$query = $this->modelManager->getRepository(Car::class)->getCarsQuery([
				'select' => 'cars',
				'conditions' => [
					'cars.tecdocId' => $sessionData['car']
				]
			]);
			
			$query->useQueryCache(TRUE);
			$query->setMaxResults(1);
			
			if (is_null($car = $query->getOneOrNullResult())) {
				return;
			}
			
			$carDisplay = sprintf('%s %s %s (%d/%d - %d/%d)', $car->getManufacturer()->getDisplay(), $car->getModel()->getDisplay(), $car->getType()->getDisplay(), $car->getBuildFromMonth(), $car->getBuildFromYear(), $car->getBuildToMonth(), $car->getBuildToYear());
		} catch (NonUniqueResultException $nonUniqueResultException) {
			return;
		}
		
		$ids = [];
		
		foreach($eventArgs->get('updateableItems') as $item) {
			$id = $item->getId();
			
			if (in_array($id, $ids, TRUE)) {
				continue;
			}
			
			array_push($ids, $id);
			
			if (is_object($basketEntity = $this->modelManager->getRepository(Basket::class)->find($id)) &&
				is_null($basketEntity->getAttribute()->getTecdocId())) {
				
				try {
					$orderBasketAttributeEntity = $this->modelManager->getRepository(OrderBasket::class)->findOneBy([
						'orderBasketId' => $basketEntity->getId()
					]);
					
					if (is_object($orderBasketAttributeEntity)) {
						$orderBasketAttributeEntity->setTecdocId($sessionData['car']);
						$orderBasketAttributeEntity->setCarDisplay($carDisplay);
						$this->modelManager->persist($orderBasketAttributeEntity);
					} else {
						$orderBasketAttributeEntity = new OrderBasket();
						$orderBasketAttributeEntity->setOrderBasketId($basketEntity->getId());
						$orderBasketAttributeEntity->setTecdocId($sessionData['car']);
						$orderBasketAttributeEntity->setCarDisplay($carDisplay);
						$this->modelManager->persist($orderBasketAttributeEntity);
						$basketEntity->setAttribute($orderBasketAttributeEntity);
					}
					$this->modelManager->flush();
				} catch (ORMException $exception) {}
			}
		}
	}
	
	/**
	 * @param string $source
	 * @return string
	 */
	private function getKeywords(string $source): string {
		$dom = new \DOMDocument();
		$dom->loadHTML(mb_convert_encoding($source, 'HTML-ENTITIES', 'UTF-8'));
		$badWords = explode(',', $this->container->get(\Shopware_Components_Config::class)->get('badwords'));
		$words = [];
		$listElements = $dom->getElementsByTagName('li');
		
		foreach($listElements as $listElement) {
			$nodeValue = str_ireplace([
				',',
				';',
				':'
			], ' ', strip_tags(html_entity_decode($listElement->nodeValue, ENT_COMPAT | ENT_HTML401, 'UTF-8')));
			
			if ($this->isStopWord($nodeValue)) {
				continue;
			}
			
			$words = array_merge($words, explode(' ', $nodeValue));
		}
		
		if (!is_null($oeElement = $dom->getElementById('description_oe'))) {
			$nodeValue = str_ireplace([
				',',
				';',
				':'
			], ' ', strip_tags(html_entity_decode($oeElement->nodeValue, ENT_COMPAT | ENT_HTML401, 'UTF-8')));
			
			$words = array_merge($words, explode(' ', $nodeValue));
		}
		
		$words = array_count_values(array_diff($words, $badWords));
		foreach (array_keys($words) as $word) {
			if (strlen((string)$word) < 2) {
				unset($words[$word]);
			}
		}
		arsort($words);
		
		return htmlspecialchars(
			implode(', ', array_slice(array_keys($words), 0, 20)),
			ENT_QUOTES,
			'UTF-8',
			false
		);
	}
	
	/**
	 * @param string $source
	 * @return bool
	 */
	private function isStopWord(string $source): bool {
		$stopWords = [
			'qualität',
			'lieferumfang'
		];
		
		foreach($stopWords as $stopWord) {
			if (mb_stripos($source, $stopWord) !== FALSE || stripos($source, $stopWord) !== FALSE) {
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * @param \Exception $e
	 */
	private function setLog(\Exception $e): void {
		$this->service->pluginLogger->critical($e->getMessage(), [
			'code' => $e->getCode(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => $e->getTraceAsString()
		]);
	}
}