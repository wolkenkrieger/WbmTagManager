<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   10.03.2021
 * Zeit:    13:14
 * Datei:   Restrictions.php
 * @package ItswCar\Controllers\Widgets
 */

use ItswCar\Models\ArticleCarLinks;
use ItswCar\Models\Car;

class Shopware_Controllers_Widgets_Restrictions extends Enlight_Controller_Action {
	protected $service;
	
	public function init(): void {
		$this->service = Shopware()->Container()->get('itswcar.services');
		$this->setContainer($this->service->getContainer());
	}
	
	public function indexAction(): void {
		$sessionData = $this->service->getSessionData();
		$tecdocId = $sessionData['car']??0;
		$articleDetailsId = (int)$this->Request()->getParam('id', 0);
		$viewData = [];
		
		$articleCarLinks = $this->service->getModelManager()
			->getRepository(ArticleCarLinks::class)
			->getCarLinksQuery([
				'articleCarLinks.articleDetailsId' => $articleDetailsId,
				($tecdocId? 'articleCarLinks.tecdocId = ' . $tecdocId : NULL),
				'articleCarLinks.active' => 1
			])
			->getArrayResult();
		
		foreach($articleCarLinks as $articleCarLink) {
			if ($car = $this->service->getModelManager()
					->getRepository(Car::class)
					->getCarsQuery([
						'cars.tecdocId' => $articleCarLink['tecdocId'],
						'cars.active' => 1
					])
					->getOneOrNullResult()) {
				$codes = [];
				
				foreach($car->getCodes() as $kbaCodes) {
					$codes[] = [
						'hsn' => $kbaCodes->getHsn(),
						'tsn' => $kbaCodes->getTsn()
					];
				}
				
				$viewData[] = [
					'manufacturer' => $car->getManufacturer()->getDisplay(),
					'model' => $car->getModel()->getDisplay(),
					'type' => $car->getType()->getDisplay(),
					'platform' => $car->getPlatform()->getDisplay(),
					'codes' => $codes,
					'ccm' => $car->ccm,
					'kw' => $car->kw,
					'ps' => $car->ps,
					'buildFrom' => $car->getBuildFrom()->format('m/Y'),
					'buildTo' => $car->getBuildTo()?$car->getBuildTo()->format('m/Y') : '---',
					'restriction' => $articleCarLink['restriction']
				];
			}
		}
		
		$this->View()->assign('restrictionData', $viewData);
	}
}