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
use ItswCar\Traits\LoggingTrait;


class Subscribers implements SubscriberInterface {
	use LoggingTrait;
	
	protected string $pluginDir;
	
	/**
	 * @param string $pluginDir
	 */
	public function __construct(string $pluginDir)	{
		$this->pluginDir = $pluginDir;
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
			'Enlight_Controller_Action_PostDispatchSecure_Backend_Form'     => 'onPostDispatchSecureBackendForm',
			'Shopware_Modules_Basket_UpdateCartItems_Updated'               => 'onBasketUpdateCartItemsUpdated',
			
			'Shopware_CronJob_ItswHandleGoogleMerchantCenterQueue'          => 'onCronHandleGoogleMerchantCenterQueue',
			'Shopware_Controllers_Widgets_Listing_fetchPagination_preFetch' => 'onListingFetchPaginationPreFetch',
		];
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $actionEventArgs
	 */
	public function onPreDispatchFrontend(\Enlight_Controller_ActionEventArgs  $actionEventArgs): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onPreDispatchFrontend($actionEventArgs);
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $actionEventArgs
	 * @throws \Exception
	 */
	public function onPostDispatchSecureFrontend(\Enlight_Controller_ActionEventArgs $actionEventArgs): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onPostDispatchSecureFrontend($actionEventArgs);
	}
	
	/**
	 * @param \Enlight_Controller_EventArgs $controllerEventArgs
	 */
	public function onFrontRouteStartup(\Enlight_Controller_EventArgs $controllerEventArgs): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onFrontRouteStartup($controllerEventArgs);
	}
	
	/**
	 * @param \Enlight_Controller_EventArgs $controllerEventArgs
	 */
	public function onFrontRouteShutdown(\Enlight_Controller_EventArgs $controllerEventArgs): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onFrontRouteShutdown($controllerEventArgs);
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $actionEventArgs
	 */
	public function onPostDispatchSecureFrontendListing(\Enlight_Controller_ActionEventArgs $actionEventArgs): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onPostDispatchSecureFrontendListing($actionEventArgs);
	}
	
	/**
	 * @return \ItswCar\Components\Eventhandlers\CategoryConditionHandler|null
	 */
	public function onCollectConditionHandlers(): ?CategoryConditionHandler {
		return new CategoryConditionHandler();
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $eventArgs
	 */
	public function onAfterConvertCategoryByLegacyStructConverter(\Enlight_Event_EventArgs $eventArgs): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onAfterConvertCategoryByLegacyStructConverter($eventArgs);
	}
	
	/**
	 * @param \Enlight_Hook_HookArgs $hookArgs
	 */
	public function onAfterConvertCategory(\Enlight_Hook_HookArgs $hookArgs): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onAfterConvertCategory($hookArgs);
	}
	
	/**
	 * @param \Enlight_Hook_HookArgs $hookArgs
	 */
	public function onAfterGetCategoriesByParent(\Enlight_Hook_HookArgs $hookArgs): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onAfterGetCategoriesByParent($hookArgs);
	}
	
	/**
	 * @param \Enlight_Hook_HookArgs $hookArgs
	 */
	public function onAfterGetArticleByCategory(\Enlight_Hook_HookArgs $hookArgs): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onAfterGetArticleByCategory($hookArgs);
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $eventArgs
	 */
	public function onConvertListProduct(\Enlight_Event_EventArgs $eventArgs): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onConvertListProduct($eventArgs);
	}
	
	/**
	 * @param \Enlight_Hook_HookArgs $hookArgs
	 */
	public function onAfterGetArticleById(\Enlight_Hook_HookArgs $hookArgs): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onAfterGetArticleById($hookArgs);
	}
	
	/**
	 * @param \Enlight_Controller_ActionEventArgs $eventArgs
	 */
	public function onPostDispatchSecureBackendForm(\Enlight_Controller_ActionEventArgs $eventArgs): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onPostDispatchSecureBackendForm($eventArgs);
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $eventArgs
	 */
	public function onBasketUpdateCartItemsUpdated(\Enlight_Event_EventArgs $eventArgs): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onBasketUpdateCartItemsUpdated($eventArgs);
	}
	
	/**
	 * @param \Shopware_Components_Cron_CronJob $cronJob
	 */
	public function onCronHandleGoogleMerchantCenterQueue(\Shopware_Components_Cron_CronJob $cronJob): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onCronHandleGoogleMerchantCenterQueue($cronJob);
	}
	
	/**
	 * @param \Enlight_Controller_EventArgs $controllerEventArgs
	 */
	public function onListingFetchPaginationPreFetch(\Enlight_Controller_EventArgs $controllerEventArgs): void {
		$this->debug(__METHOD__, $controllerEventArgs);
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onListingFetchPaginationPreFetch($controllerEventArgs);
	}
}