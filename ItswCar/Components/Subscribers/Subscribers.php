<?php
declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   16.12.2020
 * Zeit:    17:45
 * Datei:   Subscribers.php
 * @package ItswCar\Components\Subscribers
 */

namespace ItswCar\Components\Subscribers;

use Enlight\Event\SubscriberInterface;
use ItswCar\Components\Eventhandlers\Eventhandlers as Eventhandler;
use ItswCar\Components\Eventhandlers\CategoryConditionHandler;
use ItswCar\Components\Services\Services;
use Shopware\Components\DependencyInjection\Container;


class Subscribers implements SubscriberInterface {
	/**
	 * @var \ItswCar\Components\Eventhandlers
	 */
	protected $eventHandler;
	/**
	 * @var \Shopware\Components\DependencyInjection\Container
	 */
	protected $container;
	/**
	 * @var \ItswCar\Components\Services\Services
	 */
	protected $service;
	
	/**
	 * Subscribers constructor.
	 * @param \Shopware\Components\DependencyInjection\Container $container
	 * @param \ItswCar\Components\Services\Services              $service
	 */
	public function __construct(Container $container, Services $service) {
		$this->container = $container;
		$this->service = $service;
		
		$this->eventHandler = new Eventhandler($this->service);
	}
	
	/**
	 * @inheritDoc
	 */
	public static function getSubscribedEvents(): array {
		return [
			'Enlight_Controller_Action_PostDispatchSecure_Frontend'         => 'onPostDispatchSecureFrontend',
			'Enlight_Controller_Front_RouteShutdown'                        => 'onFrontRouteShutdown',
			'Shopware_SearchBundleDBAL_Collect_Condition_Handlers'          => 'onCollectConditionHandlers',
			'Legacy_Struct_Converter_Convert_Category'                      => 'onAfterConvertCategoryByLegacyStructConverter',
			'sCategories::convertCategory::after'                           => 'onAfterConvertCategory',
		
			//'Enlight_Controller_Action_PostDispatchSecure_Frontend_Detail'  => 'onPostDispatchSecureFrontendDetail',
			//'Enlight_Controller_Action_PostDispatchSecure_Frontend_Listing' => 'onPostDispatchSecureFrontendListing',
		
		];
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $actionEventArgs
	 */
	public function onPostDispatchSecureFrontend(\Enlight_Controller_ActionEventArgs $actionEventArgs): void {
		$this->eventHandler->onPostDispatchSecureFrontend($actionEventArgs);
	}
	
	/**
	 * @param \Enlight_Controller_EventArgs $controllerEventArgs
	 */
	public function onFrontRouteShutdown(\Enlight_Controller_EventArgs $controllerEventArgs): void {
		$this->eventHandler->onFrontRouteShutdown($controllerEventArgs);
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $actionEventArgs
	 */
	public function onPostDispatchSecureFrontendListing(\Enlight_Controller_ActionEventArgs $actionEventArgs): void {
		$this->eventHandler->onPostDispatchSecureFrontendListing($actionEventArgs);
	}
	
	/**
	 * @return \ItswCar\Components\Eventhandlers\CategoryConditionHandler
	 */
	public function onCollectConditionHandlers(): CategoryConditionHandler {
		return new CategoryConditionHandler($this->service);
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $eventArgs
	 */
	public function onAfterConvertCategoryByLegacyStructConverter(\Enlight_Event_EventArgs $eventArgs): void {
		$this->eventHandler->onAfterConvertCategoryByLegacyStructConverter($eventArgs);
	}
	
	/**
	 * @param \Enlight_Hook_HookArgs $hookArgs
	 */
	public function onAfterConvertCategory(\Enlight_Hook_HookArgs $hookArgs): void {
		$this->eventHandler->onAfterConvertCategory($hookArgs);
	}
}