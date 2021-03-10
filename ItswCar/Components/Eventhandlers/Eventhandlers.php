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
use ItswCar\Models\ArticleCarLinks;
use ItswCar\Models\Car;

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
	 * @param \Enlight_Controller_ActionEventArgs $actionEventArgs
	 */
	public function onPostDispatchSecureFrontendDetail(\Enlight_Controller_ActionEventArgs $actionEventArgs): void {
		$subject = $actionEventArgs->getSubject();
		$sArticle = $subject->View()->getAssign('sArticle');
		$articleCarLinks = $this->service->getModelManager()
			->getRepository(ArticleCarLinks::class)
			->getCarLinksQuery([
				'articleCarLinks.articleDetailsId' => $sArticle['articleDetailsID'],
				'articleCarLinks.active' => 1
			])
			->getArrayResult();
		
		foreach($articleCarLinks as $articleCarLink) {
			$car = $this->service->getModelManager()
				->getRepository(Car::class)
				->getCarsQuery([
					'cars.tecdocId' => $articleCarLink['tecdocId']
				])
				->getResult();
		}
		$subject->View()->assign('sArticle', $sArticle);
	}
}