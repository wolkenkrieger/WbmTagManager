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
use Shopware\Components\Plugin\Configuration\CachedReader;
use Shopware\Models\Shop\Shop;


class Subscribers implements SubscriberInterface {
	
	protected Eventhandler $eventHandler;
	protected Container $container;
	protected Services $service;
	protected string $pluginDir;
	protected array $config;
	protected Shop $shop;
	
	/**
	 * @param \Shopware\Components\DependencyInjection\Container $container
	 * @param \ItswCar\Components\Services\Services              $service
	 * @param string                                             $pluginDir
	 * @param string                                             $pluginName
	 */
	public function __construct(Container $container, Services $service, string $pluginDir, string $pluginName) {
		$this->container = $container;
		$this->service = $service;
		$this->pluginDir = $pluginDir;
		
		if ($this->container->initialized('shop')) {
			$this->shop = $this->container->get('shop');
		} else {
			$this->shop = $this->container->get('models')->getRepository(Shop::class)->getActiveDefault();
		}
		
		$this->config = $this->container->get(CachedReader::class)->getByPluginName($pluginName, $this->shop->getID());
		$this->eventHandler = new Eventhandler($service, $pluginDir, $this->config);
	}
	
	/**
	 * @inheritDoc
	 */
	public static function getSubscribedEvents(): array {
		return [
			'Enlight_Controller_Action_PostDispatchSecure_Frontend'         => 'onPostDispatchSecureFrontend',
			'Enlight_Controller_Front_RouteStartup'                         => 'onFrontRouteStartup',
			'Enlight_Controller_Front_RouteShutdown'                        => 'onFrontRouteShutdown',
			'Shopware_SearchBundleDBAL_Collect_Condition_Handlers'          => 'onCollectConditionHandlers',
			'Legacy_Struct_Converter_Convert_Category'                      => 'onAfterConvertCategoryByLegacyStructConverter',
			'sCategories::convertCategory::after'                           => 'onAfterConvertCategory',
			'sCategories::sGetCategoriesByParent::after'                    => 'onAfterGetCategoriesByParent',
			'sArticles::sGetArticlesByCategory::after'                      => 'onAfterGetArticleByCategory',
			'Legacy_Struct_Converter_Convert_List_Product'                  => 'onConvertListProduct',
			'sArticles::sGetArticleById::after'                             => 'onAfterGetArticleById',
			
			//'Enlight_Controller_Action_PreDispatch_Frontend'                => 'onPreDispatchFrontend',
			//'Enlight_Controller_Action_PostDispatchSecure_Frontend_Detail'  => 'onPostDispatchSecureFrontendDetail',
			//'Enlight_Controller_Action_PostDispatchSecure_Frontend_Listing' => 'onPostDispatchSecureFrontendListing',
			'Enlight_Controller_Action_PostDispatchSecure_Backend_Form'     => 'onPostDispatchSecureBackendForm'
		
		];
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $actionEventArgs
	 */
	public function onPreDispatchFrontend(\Enlight_Controller_ActionEventArgs  $actionEventArgs): void {
		$this->eventHandler->onPreDispatchFrontend($actionEventArgs);
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $actionEventArgs
	 * @throws \Exception
	 */
	public function onPostDispatchSecureFrontend(\Enlight_Controller_ActionEventArgs $actionEventArgs): void {
		$this->eventHandler->onPostDispatchSecureFrontend($actionEventArgs);
	}
	
	/**
	 * @param \Enlight_Controller_EventArgs $controllerEventArgs
	 */
	public function onFrontRouteStartup(\Enlight_Controller_EventArgs $controllerEventArgs): void {
		$this->eventHandler->onFrontRouterStartup($controllerEventArgs);
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
	 * @return \ItswCar\Components\Eventhandlers\CategoryConditionHandler|null
	 */
	public function onCollectConditionHandlers(): ?CategoryConditionHandler {
		if ($this->container->initialized('shop')) {
			return new CategoryConditionHandler($this->service);
		}
		
		return NULL;
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
	
	/**
	 * @param \Enlight_Hook_HookArgs $hookArgs
	 */
	public function onAfterGetCategoriesByParent(\Enlight_Hook_HookArgs $hookArgs): void {
		$this->eventHandler->onAfterGetCategoriesByParent($hookArgs);
	}
	
	/**
	 * @param \Enlight_Hook_HookArgs $hookArgs
	 */
	public function onAfterGetArticleByCategory(\Enlight_Hook_HookArgs $hookArgs): void {
		$this->eventHandler->onAfterGetArticleByCategory($hookArgs);
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $eventArgs
	 */
	public function onConvertListProduct(\Enlight_Event_EventArgs $eventArgs): void {
		$this->eventHandler->onConvertListProduct($eventArgs);
	}
	
	/**
	 * @param \Enlight_Hook_HookArgs $hookArgs
	 */
	public function onAfterGetArticleById(\Enlight_Hook_HookArgs $hookArgs): void {
		$this->eventHandler->onAfterGetArticleById($hookArgs);
	}
	
	/**
	 * @param \Enlight_Controller_EventArgs $eventArgs
	 */
	public function onPostDispatchSecureBackendForm(\Enlight_Controller_ActionEventArgs $eventArgs): void {
		$this->eventHandler->onPostDispatchSecureBackendForm($eventArgs);
	}
}