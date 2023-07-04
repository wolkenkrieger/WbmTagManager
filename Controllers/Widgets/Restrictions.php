<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   10.03.2021
 * Zeit:    13:14
 * Datei:   Restrictions.php
 * @package ItswCar\Controllers\Widgets
 */

use ItswCar\Helpers\SessionHelper;
use ItswCar\Models\ArticleCarLinks;
use ItswCar\Models\Car;
use ItswCar\Traits\LoggingTrait;
use Shopware\Components\Model\ModelManager;

class Shopware_Controllers_Widgets_Restrictions extends Enlight_Controller_Action {
	use LoggingTrait;
	
	/**
	 * @var \ItswCar\Helpers\SessionHelper
	 */
	protected SessionHelper $sessionHelper;
	/**
	 * @var \Shopware\Components\Model\ModelManager
	 */
	private ModelManager $entityManager;
	
	/**
	 *
	 */
	public function init(): void {
		$this->setContainer(Shopware()->Container());
		$this->sessionHelper = $this->container->get('itsw.helper.session');
		$this->entityManager = $this->container->get('models');
	}
	
	/**
	 *
	 */
	public function indexAction(): void {
		$sessionData = $this->sessionHelper->getSessionData();
		$tecdocId = $sessionData['car']??0;
		$articleDetailsId = (int)$this->Request()->getParam('id', 0);
		$viewData = [];
		
		$articleCarLinksQuery = $this->entityManager
			->getRepository(ArticleCarLinks::class)
			->getCarLinksQueryBuilder([
				'select' => [
					'articleCarLinks'
				],
				'conditions' => [
					'articleCarLinks.articleDetailsId' => $articleDetailsId
				]
			]);
		
		$articleCarLinks = $articleCarLinksQuery->getQuery()->getArrayResult();
		$fitsForCar = TRUE;
		
		if ($tecdocId && count($articleCarLinks)) {
			$foo = $articleCarLinksQuery
				->andWhere('articleCarLinks.tecdocId = :tecdocId')
				->setParameter('tecdocId', $tecdocId)
				->getQuery()
				->getArrayResult();
			
			$fitsForCar = (bool)count($foo);
		}
		
		foreach($articleCarLinks as $articleCarLink) {
			if ($car = $this->entityManager
					->getRepository(Car::class)
					->getCarsQuery([
						'select' => [
							'cars'
						],
						'conditions' => [
							'cars.tecdocId' => $articleCarLink['tecdocId'],
							'cars.active' => 1
						]
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
					'restriction' => $articleCarLink['restriction'],
					'title' => sprintf('%s %s %s', $car->getManufacturer()->getDisplay(), $car->getModel()->getDisplay(), $car->getType()->getDisplay())
				];
			}
		}
		
		usort($viewData, static fn($a, $b) => $a['title'] <=> $b['title']);
		
		$this->View()->assign('restrictionData', $viewData);
		$this->View()->assign('fitsForCar', $fitsForCar);
		
		$this->debug(__METHOD__, $this->View()->getAssign());
	}
}