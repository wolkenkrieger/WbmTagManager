<?php declare(strict_types=1);
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
use Shopware\Models\Customer\Group;


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
			'Enlight_Controller_Action_PreDispatch_Frontend'                => 'onPreDispatchFrontend',
			'Enlight_Controller_Action_PostDispatchSecure_Backend_Form'     => 'onPostDispatchSecureBackendForm',
			
			'Enlight_Controller_Front_RouteStartup'                         => 'onFrontRouteStartup',
			'Enlight_Controller_Front_RouteShutdown'                        => 'onFrontRouteShutdown',
			
			'Shopware_Controllers_Widgets_Listing_fetchPagination_preFetch' => 'onListingFetchPaginationPreFetch',
			
			'Shopware_SearchBundleDBAL_Collect_Condition_Handlers'          => 'onCollectConditionHandlers',
			
			'Legacy_Struct_Converter_Convert_Category'                      => 'onAfterConvertCategoryByLegacyStructConverter',
			'Legacy_Struct_Converter_Convert_List_Product'                  => 'onConvertListProduct',
			
			'sCategories::convertCategory::after'                           => 'onAfterConvertCategory',
			'sCategories::sGetCategoriesByParent::after'                    => 'onAfterGetCategoriesByParent',
			'sArticles::sGetArticlesByCategory::after'                      => 'onAfterGetArticleByCategory',
			'sArticles::sGetArticleById::after'                             => 'onAfterGetArticleById',
			
			'Shopware_Modules_Basket_UpdateCartItems_Updated'               => 'onBasketUpdateCartItemsUpdated',
			'Shopware_Modules_Basket_AddArticle_Start'                      => 'onBasketAddUpdateArticleStart',
			'Shopware_Modules_Basket_UpdateArticle_Start'                   => 'onBasketAddUpdateArticleStart',
			'Shopware_Modules_Order_SaveOrder_FilterAttributes'             => 'onOrderSaveOrderFilterAttributes',
			
			'Shopware_CronJob_ItswHandleGoogleMerchantCenterQueue'          => 'onCronHandleGoogleMerchantCenterQueue',
			'Shopware_CronJob_ItswCheckPrepaymentOrdersPaymentStatus'       => 'onCronHandleOrdersPaymentStatus',
			'Shopware_CronJob_ItswCheckEbayTemplateFolder'                  => 'onCronCheckEbayTemplateFolder',
			
			'Theme_Compiler_Collect_Javascript_Files_FilterResult'          => 'onCollectJavascriptFilesFilterResult',
			//'Enlight_Controller_Action_PostDispatchSecure_Frontend_Detail'  => 'onPostDispatchSecureFrontendDetail',
			//'Enlight_Controller_Action_PostDispatchSecure_Frontend_Listing' => 'onPostDispatchSecureFrontendListing',
			//'Shopware_Modules_Basket_UpdateArticle_FilterSqlDefaultParameters'=> 'onBasketUpdateArticleFilterSqlDefaultParameters',
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
	 * @param \Enlight_Event_EventArgs $eventArgs
	 */
	public function onBasketAddUpdateArticleStart(\Enlight_Event_EventArgs $eventArgs): void {
		(new Eventhandler($this->pluginDir))->onBasketAddUpdateArticleStart($eventArgs);
	}
	
	public function onBasketUpdateArticleFilterSqlDefaultParameters(\Enlight_Event_EventArgs $eventArgs) {
	
	}
	
	/**
	 * @param \Shopware_Components_Cron_CronJob $cronJob
	 * @return string
	 */
	public function onCronHandleGoogleMerchantCenterQueue(\Shopware_Components_Cron_CronJob $cronJob): string {
		$eventHandler = new Eventhandler($this->pluginDir);
		return $eventHandler->onCronHandleGoogleMerchantCenterQueue($cronJob);
	}
	
	/**
	 * @param \Shopware_Components_Cron_CronJob $cronJob
	 * @return string
	 */
	public function onCronHandleOrdersPaymentStatus(\Shopware_Components_Cron_CronJob $cronJob): string {
		$eventHandler = new Eventhandler($this->pluginDir);
		return $eventHandler->onCronHandleOrdersPaymentStatus($cronJob);
	}
	
	/**
	 * @param \Shopware_Components_Cron_CronJob $cronCronJob
	 * @return string
	 */
	public function onCronCheckEbayTemplateFolder(\Shopware_Components_Cron_CronJob $cronCronJob): string {
		$eventHandler = new Eventhandler($this->pluginDir);
		return $eventHandler->onCronCheckEbayTemplateFolder($cronCronJob);
	}
	
	/**
	 * @param \Enlight_Controller_EventArgs $controllerEventArgs
	 */
	public function onListingFetchPaginationPreFetch(\Enlight_Controller_EventArgs $controllerEventArgs): void {
		$this->debug(__METHOD__, $controllerEventArgs);
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onListingFetchPaginationPreFetch($controllerEventArgs);
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $eventArgs
	 */
	public function onOrderSaveOrderFilterAttributes(\Enlight_Event_EventArgs $eventArgs): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onOrderSaveOrderFilterAttributes($eventArgs);
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $eventArgs
	 * @return void
	 */
	public function onCollectJavascriptFilesFilterResult(\Enlight_Event_EventArgs $eventArgs): void {
		$eventHandler = new Eventhandler($this->pluginDir);
		$eventHandler->onCollectJavascriptFilesFilterResult($eventArgs);
	}
}