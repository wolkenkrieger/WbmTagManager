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
	
	/**
	 * Eventhandlers constructor.
	 * @param \ItswCar\Components\Services\Services $service
	 */
	public function __construct(Services $service) {
		$this->service = $service;
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
	public function onFrontRouteShutdown(\Enlight_Controller_EventArgs $controllerEventArgs): void {
		if ($controllerEventArgs->getRequest()->getModuleName() !== 'frontend'
			|| in_array($controllerEventArgs->getRequest()->getControllerName(), [
				'custom',
				'forms',
				'campaign',
				'newsletter',
				'detail',
				'checkout',
				'basket'
			], TRUE)
		) {
			return;
		}
		
		$urlPieces = explode('/', $controllerEventArgs->getRequest()->getRequestUri());
		$urlPieces = array_filter($urlPieces, function ($value) {
			return ($value !== NULL && $value !== FALSE && $value !== '');
		});
		
		$querySave = '';
		$parsedUrl = parse_url(end($urlPieces));
		
		if ((isset($parsedUrl['query']) && $parsedUrl['query']) && !isset($parsedUrl['path'])) {
			$querySave = $parsedUrl['query'];
			unset($urlPieces[count($urlPieces)]);
		}
		
		$parsedFirst = parse_url(reset($urlPieces));
		$parsedFirst = $parsedFirst['path'];
		$parsedLast = parse_url(end($urlPieces));
		$parsedLast = $parsedLast['path'];
		
		try {
			$routerParts = array_merge(
				$controllerEventArgs->getSubject()->Router()->match($parsedFirst . '/' ),
				$controllerEventArgs->getSubject()->Router()->match($parsedLast . '/' ));
		} catch (\Exception $exception) {
			$this->service->setLog($exception);
			$routerParts = [];
		}
		
		$controllerEventArgs->getRequest()->setParams($routerParts);
		$controllerEventArgs->getRequest()->setControllerName($routerParts['controller']);
		$controllerEventArgs->getRequest()->setModuleName($routerParts['module']);
		$controllerEventArgs->getRequest()->setActionName($routerParts['action']);
		$controllerEventArgs->getRequest()->setQuery($querySave);
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