<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   08.12.2020
 * Zeit:    09:10
 * Datei:   ItswCar.php
 * @package ItswCar
 */


namespace ItswCar;

use Doctrine\ORM\Tools\SchemaTool;
use ItswCar\Models\EbayPlatform;
use ItswCar\Models\KbaCodes;
use ItswCar\Models\Manufacturer;
use ItswCar\Models\Model;
use ItswCar\Models\Type;
use ItswCar\Models\Car;
use ItswCar\Models\ArticleCarLinks;
use ItswCar\Models\GoogleMerchantCenterQueue;

use Shopware\Components\Logger;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Bundle\CookieBundle\CookieCollection;
use Shopware\Bundle\CookieBundle\Structs\CookieGroupStruct;
use Shopware\Bundle\CookieBundle\Structs\CookieStruct;
use Shopware\Components\Theme\LessDefinition;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php';
}


class ItswCar extends Plugin {
	/**
	 * @var \Shopware\Components\Logger|null
	 */
	public static ?Logger $logger;
	
	/**
	 * @var \Shopware\Components\Logger|null
	 */
	public static ?Logger $warningLogger;
	
	/**
	 * @var int
	 */
	public static int $logLevel;
	
	
	/**
	 * @param \Shopware\Components\Plugin\Context\InstallContext $context
	 */
	public function install(InstallContext $context): void {
		try {
			$this->createAttributes();
			$this->createSchemas();
		} catch (Exception $err) {}
		
		$context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
	}
	
	/**
	 * @param \Shopware\Components\Plugin\Context\UpdateContext $context
	 */
	public function update(UpdateContext $context): void {
		try {
			$this->createAttributes();
			$this->updateSchemas();
		} catch (Exception $err) {}
		
		$context->scheduleClearCache(UpdateContext::CACHE_LIST_ALL);
	}
	
	/**
	 * @param \Shopware\Components\Plugin\Context\ActivateContext $context
	 */
	public function activate(ActivateContext $context): void {
		try {
		$this->createAttributes();
		//$this->updateSchemas();
		} catch (Exception $err) {}
		
		$context->scheduleClearCache(ActivateContext::CACHE_LIST_ALL);
	}
	
	/**
	 * @param \Shopware\Components\Plugin\Context\DeactivateContext $context
	 */
	public function deactivate(DeactivateContext $context): void {
		$context->scheduleClearCache(DeactivateContext::CACHE_LIST_ALL);
	}
	
	/**
	 * @param \Shopware\Components\Plugin\Context\UninstallContext $context
	 */
	public function uninstall(UninstallContext $context): void {
		$context->scheduleClearCache(UninstallContext::CACHE_LIST_ALL);
	}
	
	/**
	 * @return string[]
	 */
	public static function getSubscribedEvents(): array {
		return [
			'Theme_Inheritance_Template_Directories_Collected' => 'onCollectTemplateDir',
			'Theme_Compiler_Collect_Plugin_Less' => 'onCollectPluginLess',
			'Theme_Compiler_Collect_Plugin_Javascript' => 'onCollectPluginJavascript',
			'Theme_Compiler_Collect_Plugin_Css' => 'onCollectPluginCss',
			'CookieCollector_Collect_Cookies' => 'addCookies'
		];
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $args
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function onCollectPluginJavascript(\Enlight_Event_EventArgs $args): ArrayCollection {
		return new ArrayCollection(
			[__DIR__ . '/Resources/frontend/vendors/select2-4.1.0-rc.0/dist/js/select2.full.min.js'],
			[__DIR__ . '/Resources/frontend/js/jquery.carfinder.js'],
			[__DIR__ . '/Resources/frontend/js/states.js'],
			[__DIR__ . '/Resources/frontend/js/main.js']
		);
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $args
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function onCollectPluginCss(\Enlight_Event_EventArgs $args): ArrayCollection {
		return new ArrayCollection(
			[__DIR__ . '/Resources/frontend/vendors/select2-4.0.13/dist/css/select2.css']
		);
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $args
	 * @return \Shopware\Components\Theme\LessDefinition
	 */
	public function onCollectPluginLess(\Enlight_Event_EventArgs $args): LessDefinition {
		return new LessDefinition(
			[],
			[__DIR__ . '/Resources/frontend/less/all.less']
		);
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $args
	 */
	public function onCollectTemplateDir(\Enlight_Event_EventArgs $args): void {
		$dirs = $args->getReturn();
		$dirs[] = __DIR__.'/Resources/views/';
		
		$args->setReturn($dirs);
	}
	
	/**
	 *
	 */
	protected function createAttributes(): void {
		$service = Shopware()->Container()->get('shopware_attribute.crud_service');
		
		$service->update('s_categories_attributes', 'ebay_category_id', 'float', [
			'label' => 'eBay Category ID',
			'supportText' => '',
			'helpText' => '',
			'translatable' => FALSE,
			'displayInBackend' => TRUE,
			'position' => 100,
			'custom' => TRUE
		]);
		
		$service->update('s_articles_attributes', 'afterbuy_id', 'float', [
			'label' => 'Afterbuy ID',
			'supportText' => '',
			'helpText' => '',
			'translatable' => FALSE,
			'displayInBackend' => TRUE,
			'position' => 100,
			'custom' => TRUE
		]);
		
		$service->update('s_articles_attributes', 'google_product_category_id', 'float', [
			'label' => 'Google Product Category',
			'supportText' => '',
			'helpText' => '',
			'translatable' => FALSE,
			'displayInBackend' => TRUE,
			'position' => 102,
			'custom' => TRUE
		]);
		
		$service->update('s_articles_attributes', 'afterbuy_link', 'string', [
			'label' => 'Afterbuy Link',
			'supportText' => '',
			'helpText' => '',
			'translatable' => FALSE,
			'displayInBackend' => TRUE,
			'position' => 103,
			'custom' => TRUE
		]);
		
		$service->update('s_articles_attributes', 'oe_numbers', 'string', [
			'label' => 'OE-Nummern',
			'supportText' => '',
			'helpText' => '',
			'translatable' => FALSE,
			'displayInBackend' => TRUE,
			'position' => 107,
			'custom' => TRUE
		]);
		
		$service->update('s_articles_attributes', 'fake_price', 'float', [
			'label' => 'Fake Preis',
			'supportText' => '',
			'helpText' => '',
			'translatable' => FALSE,
			'displayInBackend' => TRUE,
			'position' => 108,
			'custom' => FALSE
		]);
		
		$service->update('s_articles_attributes', 'google_produkt_id', 'string', [
			'label' => ' Google Merchant Center Produkt-ID',
			'supportText' => '',
			'helpText' => '',
			'translatable' => FALSE,
			'displayInBackend' => TRUE,
			'position' => 109,
			'custom' => FALSE
		]);
		
		$service->update('s_order_basket_attributes', 'tecdoc_id', 'float', [
			'label' => 'Tecdoc-ID',
			'supportText' => '',
			'helpText' => '',
			'translatable' => FALSE,
			'displayInBackend' => TRUE,
			'custom' => FALSE
		]);
		
		$service->update('s_order_basket_attributes', 'car_display', 'string', [
			'label' => 'Fahrzeug',
			'supportText' => '',
			'helpText' => '',
			'translatable' => FALSE,
			'displayInBackend' => TRUE,
			'custom' => FALSE
		]);
		
		$service->update('s_order_details_attributes', 'tecdoc_id', 'float', [
			'label' => 'Tecdoc-ID',
			'supportText' => '',
			'helpText' => '',
			'translatable' => FALSE,
			'displayInBackend' => TRUE,
			'custom' => FALSE
		]);
		
		$service->update('s_order_details_attributes', 'car_display', 'string', [
			'label' => 'Fahrzeug',
			'supportText' => '',
			'helpText' => '',
			'translatable' => FALSE,
			'displayInBackend' => TRUE,
			'custom' => FALSE
		]);
		
		$service->update('s_order_attributes', 'itsw_pay_until_date', 'string', [
			'label' => 'Zahldatum bei Vorkasse',
			'supportText' => '',
			'helpText' => '',
			'translatable' => FALSE,
			'displayInBackend' => TRUE,
			'custom' => FALSE
		]);
		
		if ($service->get('s_order_attributes', 'tecdoc_id')) {
			$service->delete('s_order_attributes', 'tecdoc_id');
		}
		
		if ($service->get('s_order_attributes', 'car_display')) {
			$service->delete('s_order_attributes', 'car_display');
		}
		
		if ($service->get('s_articles_attributes', 'position_1')) {
			$service->delete('s_articles_attributes', 'position_1');
		}
		
		if ($service->get('s_order_attributes', 'pay_until_date')) {
			$service->delete('s_order_attributes', 'pay_until_date');
		}
		
		if ($service->get('s_articles_attributes', 'position_2')) {
			$service->delete('s_articles_attributes', 'position_2');
		}
		
		if ($service->get('s_articles_attributes', 'position_3')) {
			$service->delete('s_articles_attributes', 'position_3');
		}
		
		if ($service->get('s_articles_attributes', 'position_4')) {
			$service->delete('s_articles_attributes', 'position_4');
		}
		
		if ($service->get('s_articles_attributes', 'position_aggregated')) {
			$service->delete('s_articles_attributes', 'position_aggregated');
		}
		
		$metaDataCache  = Shopware()->Models()->getConfiguration()->getMetadataCacheImpl();
		$metaDataCache->deleteAll();
		
		Shopware()->Models()->generateAttributeModels([
			's_categories_attributes',
			's_articles_attributes',
			's_order_basket_attributes',
			's_order_details_attributes',
			's_order_attributes'
		]);
		
		Shopware()->Models()->regenerateProxies();
	}
	
	/**
	 *
	 */
	public function updateSchemas(): void {
		$entityManager = Shopware()->Container()->get('models');
		$schemaTool = new SchemaTool($entityManager);
		$classes = [
			$entityManager->getClassMetadata(Manufacturer::class),
			$entityManager->getClassMetadata(Model::class),
			$entityManager->getClassMetadata(Type::class),
			$entityManager->getClassMetadata(EbayPlatform::class),
			$entityManager->getClassMetadata(KbaCodes::class),
			$entityManager->getClassMetadata(Car::class),
			$entityManager->getClassMetadata(ArticleCarLinks::class),
			$entityManager->getClassMetadata(GoogleMerchantCenterQueue::class),
		];
		
		$entityManager->getConfiguration()->getMetadataCacheImpl()->deleteAll();
		$entityManager->regenerateProxies();
		$schemaTool->updateSchema($classes, TRUE);
	}
	
	/**
	 *
	 */
	public function createSchemas(): void {
		$entityManager = Shopware()->Container()->get('models');
		$schemaTool = new SchemaTool($entityManager);
		$classes = [
			$entityManager->getClassMetadata(Manufacturer::class),
			$entityManager->getClassMetadata(Model::class),
			$entityManager->getClassMetadata(Type::class),
			$entityManager->getClassMetadata(EbayPlatform::class),
			$entityManager->getClassMetadata(KbaCodes::class),
			$entityManager->getClassMetadata(Car::class),
			$entityManager->getClassMetadata(ArticleCarLinks::class),
			$entityManager->getClassMetadata(GoogleMerchantCenterQueue::class),
		];
		
		$entityManager->getConfiguration()->getMetadataCacheImpl()->deleteAll();
		$schemaManager = $entityManager->getConnection()->getSchemaManager();
		foreach($classes as $schema) {
			if (!$schemaManager->tablesExist($schema->getTableName())) {
				try {
					$schemaTool->createSchema([$schema]);
				} catch (\Exception $exception) {
					return;
				}
			} else {
				$entityManager->regenerateProxies();
				$schemaTool->updateSchema([$schema], TRUE);
			}
		}
	}
	
	
	/**
	 * @return \Shopware\Bundle\CookieBundle\CookieCollection
	 */
	public function addCookies(): CookieCollection {
		$collection = new CookieCollection();
		
		$collection->add(new CookieStruct(
			'itswCache',
			'/^itsw-[a-z0-9-_]+-data$/',
			'ITSW Module - technische Daten',
			CookieGroupStruct::TECHNICAL
		));
		
		return $collection;
	}
}