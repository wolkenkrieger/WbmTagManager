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
		
		$subject->View()->assign('ITSW-SESSION', $this->service->getSessionData());
	}
	
	/**
	 * @param \Enlight_Controller_EventArgs $controllerEventArgs
	 */
	public function onFrontRouteShutdown(\Enlight_Controller_EventArgs $controllerEventArgs): void {
		if ($controllerEventArgs->getRequest()->getModuleName() !== 'frontend') {
			return;
		}
		
		$urlPieces = explode('/', $controllerEventArgs->getRequest()->getRequestUri());
		$urlPieces = array_filter($urlPieces, function ($value) {
			return ($value !== NULL && $value !== FALSE && $value !== '');
		});
		
		$parsedUrl = parse_url(end($urlPieces));
		if (isset($parsedUrl['query']) && $parsedUrl['query']) {
			// alles ab ? in der uri wegwerfen
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
		
		
		//var_dump($routerParts);
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
	
	public function onPostDispatchSecureFrontendListing(\Enlight_Controller_ActionEventArgs $actionEventArgs): void {
	
	}
	
	/**
	 * @param $category
	 * @return mixed
	 */
	private function setCategoryLink($category) {
		if (!$category['external']) {
			$seoUrl = $this->service->getCategorySeoUrl($category['id']);
			
			if ($seoUrl) {
				$category['link'] = $seoUrl;
			}
		}
		
		return $category;
	}
}