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

use ItswCar\Components\Services\Services;

class Eventhandlers {
	/**
	 * @var \ItswCar\Components\Services\Services
	 */
	protected $service;
	
	protected $pluginDir;
	
	/**
	 * Eventhandlers constructor.
	 * @param \ItswCar\Components\Services\Services $service
	 * @param                                       $pluginDir
	 */
	public function __construct(Services $service, $pluginDir) {
		$this->service = $service;
		$this->pluginDir = $pluginDir;
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $actionEventArgs
	 */
	public function onPostDispatchSecureFrontend(\Enlight_Controller_ActionEventArgs $actionEventArgs): void {
		$subject = $actionEventArgs->getSubject();
		
		$subject->View()->assign('ITSW-SESSION', $this->service->getSessionData(), TRUE);
		$subject->View()->assign('ITSW-MAINTENANCEMODE', $this->service->getServiceMode(), TRUE);
		$subject->View()->assign('ITSW-DEVELOPMENTMODE', $this->service->getDevelopmentMode(), TRUE);
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
			$article['linkDetails'] = ($this->service->getArticleSeoUrl($article['articleID']) ?: $article['linkDetails']);
			if ($discount = $this->service->getUserGroupDiscount()) {
				$article['has_pseudoprice'] = TRUE;
				$article['pseudoprice'] = $this->getPrice($article['price_numeric'], $article['tax'], $discount);
				$article['pseudoprice_numeric'] = $this->getPriceNum($article['price_numeric'], $article['tax'], $discount);
				$article['pseudopricePercent'] = $discount;
			}
		}
		unset($article);
		$return['sArticles'] = $articles;
		$hookArgs->setReturn($return);
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $eventArgs
	 */
	public function onConvertListProduct(\Enlight_Event_EventArgs $eventArgs) {
		$article = $eventArgs->getReturn();
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
		
		$article['linkDetails'] = ($this->service->getArticleSeoUrl($article['articleID']) ?: $article['linkDetails']);
		if ($discount = $this->service->getUserGroupDiscount()) {
			$article['has_pseudoprice'] = TRUE;
			$article['pseudoprice'] = $this->getPrice($article['price_numeric'], $article['tax'], $discount);
			$article['pseudoprice_numeric'] = $this->getPriceNum($article['price_numeric'], $article['tax'], $discount);
			$article['pseudopricePercent'] = $discount;
		}
		
		$hookArgs->setReturn($article);
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $eventArgs
	 */
	public function onPostDispatchSecureBackendForm(\Enlight_Controller_ActionEventArgs $eventArgs) {
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
	private function getPrice($price, $tax, $discount) {
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
}