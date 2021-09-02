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

use InvalidArgumentException;
use ItswCar\Components\Services\Services;

class Eventhandlers {
	protected Services $service;
	protected string $pluginDir;
	protected array $config;
	
	/**
	 * @param \ItswCar\Components\Services\Services $service
	 * @param                                       $pluginDir
	 * @param                                       $config
	 */
	public function __construct(Services $service, $pluginDir, $config) {
		$this->service = $service;
		$this->pluginDir = $pluginDir;
		$this->config = $config;
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
	public function onFrontRouterStartup(\Enlight_Controller_EventArgs $controllerEventArgs): void {
		$requestUri = $controllerEventArgs->getRequest()->getRequestUri();
		$basePath = $controllerEventArgs->getRequest()->getBasePath();
		
		if ($basePath !== '') {
			$redirectUri = mb_strcut($requestUri, mb_strlen($basePath));
			$controllerEventArgs->getResponse()->setRedirect($redirectUri, 301);
		}
	}
	
	/**
	 * @param \Enlight_Controller_EventArgs $controllerEventArgs
	 */
	public function onFrontRouteShutdown(\Enlight_Controller_EventArgs $controllerEventArgs): void {
		$requestUri = $controllerEventArgs->getRequest()->getRequestUri();
		$queryPath = $controllerEventArgs->getRequest()->getPathInfo();
		
		if (!$queryPath || $queryPath === '/') {
			return;
		}
		
		$queryQuery = parse_url($requestUri, PHP_URL_QUERY);
		$queryFragment = parse_url($requestUri, PHP_URL_FRAGMENT);
		$queryPaths = explode('/', $queryPath);
		
		$queryPaths = array_filter($queryPaths, static function ($value) {
			return ($value !== NULL && $value !== FALSE && $value !== '');
		});
		
		foreach($queryPaths as $index => $queryPath) {
			$matches = $controllerEventArgs->getSubject()->Router()->match($queryPath);
			if (is_array($matches)) {
				if (isset($macthes['m']) || isset($matches['mo'])) {
					unset($queryPaths[$index]);
				}
			}
		}
		$uri = implode('/', $queryPaths);
		
		$matches = $controllerEventArgs->getSubject()->Router()->match($uri);
		$requestQueryAction = NULL;
		
		if ($queryQuery && stripos($queryQuery, 'action=') !== FALSE) {
			$queryParts = explode('&', $queryQuery);
			$requestQueryActions = explode('=', reset($queryParts));
			$requestQueryAction = end($requestQueryActions);
		}
		
		if ($requestQueryAction) {
			$matches['action'] = $requestQueryAction;
		}
		
		if ($queryQuery) {
			if ($queryFragment) {
				$controllerEventArgs->getRequest()->setQuery($queryQuery . '#' . $queryFragment);
			} else {
				$controllerEventArgs->getRequest()->setQuery($queryQuery);
			}
		}
		
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
		
		$this->setPseudoprice($article);
		$article['linkDetails'] = ($this->service->getArticleSeoUrl($article['articleID']) ?: $article['linkDetails']);
		
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
	public function setPseudoprice(&$article) {
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
}