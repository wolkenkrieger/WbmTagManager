<?php declare(strict_types=1);

/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   27.04.2021
 * Zeit:    15:11
 * Datei:   Categories.php
 */

class Shopware_Controllers_Widgets_Categories extends Enlight_Controller_Action {
	/**
	 * @var ItswCar\Components\Services\Services
	 */
	protected $service;
	protected $categories;
	
	/**
	 *
	 */
	public function init(): void {
		$this->service = Shopware()->Container()->get('itswcar.services');
		$this->setContainer($this->service->getContainer());
	}
	
	public function indexAction(): void {
		$mainCategories = Shopware()->Modules()->Categories()->sGetMainCategories();
		$this->View()->assign('sMainCategories', $mainCategories);
	}
}