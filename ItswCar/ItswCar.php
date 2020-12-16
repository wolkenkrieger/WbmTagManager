<?php
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


class ItswCar extends Plugin {
	/**
	 * @param \Shopware\Components\Plugin\Context\InstallContext $context
	 * @return array|void
	 * @throws \Exception
	 */
	public function install(InstallContext $context) {
		try {
			$this->createAttributes();
			$this->updateSchemas();
		} catch (Exception $err) {}
		
		$context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
	}
	
	/**
	 * @param \Shopware\Components\Plugin\Context\UpdateContext $context
	 * @return array|void
	 */
	public function update(UpdateContext $context) {
		try {
			$this->createAttributes();
			$this->updateSchemas();
		} catch (Exception $err) {}
		
		$context->scheduleClearCache(UpdateContext::CACHE_LIST_ALL);
	}
	
	/**
	 * @param \Shopware\Components\Plugin\Context\ActivateContext $context
	 */
	public function activate(ActivateContext $context) {
		try {
		$this->createAttributes();
		$this->updateSchemas();
		} catch (Exception $err) {}
		
		$context->scheduleClearCache(ActivateContext::CACHE_LIST_ALL);
	}
	
	/**
	 * @param \Shopware\Components\Plugin\Context\DeactivateContext $context
	 */
	public function deactivate(DeactivateContext $context) {
		$context->scheduleClearCache(DeactivateContext::CACHE_LIST_ALL);
	}
	
	/**
	 * @param \Shopware\Components\Plugin\Context\UninstallContext $context
	 */
	public function uninstall(UninstallContext $context) {
		$context->scheduleClearCache(UninstallContext::CACHE_LIST_ALL);
	}
	
	/**
	 * @return string[]
	 */
	public static function getSubscribedEvents() {
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
	public function onCollectPluginJavascript(\Enlight_Event_EventArgs $args) {
		return new ArrayCollection(
			[__DIR__ . '/Resources/frontend/vendors/select2-4.0.13/dist/js/select2.full.min.js'],
			[__DIR__ . '/Resources/frontend/js/jquery.carfinder.js.js'],
			[__DIR__ . '/Resources/frontend/js/states.js'],
			[__DIR__ . '/Resources/frontend/js/main.js']
		);
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $args
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function onCollectPluginCss(\Enlight_Event_EventArgs $args) {
		return new ArrayCollection(
			[__DIR__ . '/Resources/frontend/vendors/select2-4.0.13/dist/css/select2.min.css']
		);
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $args
	 * @return \Shopware\Components\Theme\LessDefinition
	 */
	public function onCollectPluginLess(\Enlight_Event_EventArgs $args) {
		return new \Shopware\Components\Theme\LessDefinition(
			[],
			[__DIR__ . '/Resources/frontend/less/all.less']
		);
	}
	
	/**
	 * @param \Enlight_Event_EventArgs $args
	 */
	public function onCollectTemplateDir(\Enlight_Event_EventArgs $args)
	{
		$dirs = $args->getReturn();
		$dirs[] = __DIR__.'/Resources/views/';
		
		$args->setReturn($dirs);
	}
	
	/**
	 *
	 */
	
	protected function createAttributes() {
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
		
		
		$metaDataCache  = Shopware()->Models()->getConfiguration()->getMetadataCacheImpl();
		$metaDataCache->deleteAll();
		
		Shopware()->Models()->generateAttributeModels([
			's_categories_attributes',
			's_articles_attributes'
		]);
	}
	
	/**
	 *
	 */
	public function updateSchemas() {
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
		];
		
		$schemaTool->updateSchema($classes, TRUE);
	}
	
	
	/**
	 * @return \Shopware\Bundle\CookieBundle\CookieCollection
	 */
	public function addCookies(): CookieCollection {
		$collection = new CookieCollection();
		
		$collection->add(new CookieStruct(
			'itswCache',
			'/^itsw_cache$/',
			'Modul "ITSW Car" - techn. Daten',
			CookieGroupStruct::TECHNICAL
		));
		
		return $collection;
	}
}