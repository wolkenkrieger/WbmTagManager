<?php declare(strict_types=1);

/**
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 14.02.2022
 * Time: 07:58
 * File: CarMap.php
 */

use ItswCar\Models\Car;
use ItswCar\Traits\LoggingTrait;
use ItswCar\Helpers\SeoHelper;

class Shopware_Controllers_Frontend_CarMap extends Enlight_Controller_Action {
	use LoggingTrait;
	
	/** @var \Doctrine\ORM\EntityManager  */
	public Doctrine\ORM\EntityManager $entityManager;
	
	/** @var \ItswCar\Helpers\SeoHelper  */
	public SeoHelper $seoHelper;
	
	public function initController(Enlight_Controller_Request_RequestHttp $request, Enlight_Controller_Response_ResponseHttp $response): void {
		parent::initController($request, $response);
		
		$this->setContainer(Shopware()->Container());
		$this->entityManager = $this->get('models');
		$this->seoHelper = $this->get('itsw.helper.seo');
	}
	
	/**
	 * @return void
	 */
	public function preDispatch(): void {
		parent::preDispatch();
		$this->Response()->setHeader('X-Robots-Tag', 'noindex');
	}
	
	/**
	 * @return void
	 */
	public function indexAction(): void {
		$topBrandsViewData = $viewData = [];
		
		$data = $this->entityManager->getRepository(Car::class)->getDefaultQuery([
			'select' => [
				'manufacturers.display AS manufacturerDisplay',
				'manufacturers.topBrand',
				'cars.manufacturerId',
				'models.display AS modelDisplay',
				'cars.modelId'
			],
			'from' => [
				'cars' => Car::class
			],
			'joins' => [
				'manufacturers' => 'cars.manufacturer',
				'models' => 'cars.model'
			],
			'conditions' => [
				'cars.active' => 1,
				'manufacturers.active' => 1,
				'models.active' => 1
			],
			'groups' => [
				'cars.manufacturerId',
				'cars.modelId'
			],
			'orders' => [
				'manufacturerDisplay' => 'ASC',
				'modelDisplay' => 'ASC'
			]
		])
			->getResult();
		
		foreach($data as $datum) {
			if (!isset($models[$datum['manufacturerId']]) || count($models[$datum['manufacturerId']]) < 6) {
				$models[$datum['manufacturerId']][] = $datum['modelDisplay'];
			}
			
			$firstChar = mb_strtoupper(mb_substr($datum['manufacturerDisplay'], 0, 1, 'UTF-8'));
			
			if ($datum['topBrand'] === TRUE) {
				$topBrandsViewData[$datum['manufacturerDisplay']] = [
					'manufacturerId' => $datum['manufacturerId'],
					'models' => $models[$datum['manufacturerId']]
				];
			}
			
			$viewData[$firstChar][$datum['manufacturerDisplay']] = [
				'manufacturerId' => $datum['manufacturerId'],
				'models' => $models[$datum['manufacturerId']]
			];
			
		}
		
		$this->View()->assign('viewData', $viewData);
		$this->View()->assign('topBrandsViewData', $topBrandsViewData);
	}
	
	/**
	 * @return void
	 */
	public function modelsAction(): void {
		$manufacturerId = (int)$this->Request()->getParam('manufacturer', 0);
		
		if (!$manufacturerId) {
			$this->Request()->setParam('redirect', 0);
			$this->forward('index');
			return;
		}
		
		$models = $this->entityManager->getRepository(Car::class)->getDefaultQuery([
			'select' => [
				'cars.tecdocId',
				'cars.manufacturerId',
				'manufacturers.display AS manufacturerDisplay',
				'cars.modelId',
				'models.display AS modelDisplay',
				'MIN(cars.ps) as MIN_PS',
				'MAX(cars.ps) AS MAX_PS',
				'MIN(cars.kw) as MIN_KW',
				'MAX(cars.kw) AS MAX_KW',
				'MIN(cars.ccm) AS MIN_CCM',
				'MAX(cars.ccm) AS MAX_CCM',
				'MIN(cars.buildFrom) AS MIN_BUILD',
				'MAX(cars.buildTo) AS MAX_BUILD',
			],
			'from' => [
				'cars' => Car::class
			],
			'joins' => [
				'manufacturers' => 'cars.manufacturer',
				'models' => 'cars.model'
			],
			'conditions' => [
				'cars.active' => 1,
				'manufacturers.active' => 1,
				'models.active' => 1,
				'cars.manufacturerId' => $manufacturerId
			],
			'orders' => [
				'modelDisplay' => 'ASC'
			],
			'groups' => [
				'cars.manufacturerId',
				'cars.modelId'
			]
		])
			->getResult();
		
		$this->View()->assign('models', $models);
	}
	
	/**
	 * @return void
	 */
	public function typesAction(): void {
		$manufacturerId = (int)$this->Request()->getParam('manufacturer', 0);
		$modelId = (int)$this->Request()->getParam('model', 0);
		
		if (!$manufacturerId || !$modelId) {
			$this->Request()->setParam('redirect', 0);
			$this->forward('index');
			return;
		}
		
		$types = $this->entityManager->getRepository(Car::class)->getTypesByManufacturerIdAndModelIdQuery($manufacturerId, $modelId, [
			'select' => [
				'types.id AS typeId'
			],
			'conditions' => [
				'types.active' => 1
			],
			'orders' => [
				'types.display' => 'ASC'
			],
			'distinct' => TRUE
		])
			->getResult();
		
		if (!count($types)) {
			$this->Request()->setParam('redirect', 0);
			$this->forward('index');
			return;
		}
		
		$typeIds = [];
		
		foreach($types as $type) {
			$typeIds[] = $type['typeId'];
		}
		
		$cars = $this->entityManager->getRepository(Car::class)->getCarsForCarfinder([
			'select' => [
				'cars'
			],
			'conditions' => [
				'cars.manufacturerId' => $manufacturerId,
				'cars.modelId' => $modelId,
				'cars.typeId IN (' . implode(',', $typeIds) . ')',
				'cars.active' => 1
			],
			'orders' => [
				'cars.buildFrom' => 'ASC',
				'cars.buildTo' => 'ASC'
			]
		]);
		
		$controller = Shopware()->Front()->Router()->assemble([
			'controller' => 'cat',
			'module' => 'frontend',
			'action' => 'index',
			'sCategory' => 6,
			'rewriteUrl' => 1
		]);
		
		foreach ($cars as &$car) {
			$url = implode('/', [
				trim($this->seoHelper->getCarSeoUrl($manufacturerId, $modelId, (int)$car['tecdocId']), '/'),
				trim($this->seoHelper->extractPathFromUrl($controller), '/')
			]);
			
			$url = $this->seoHelper->completeUrl($url);
			
			$car['seoUrl'] = $url;
		}
		
		unset($car);
		
		$this->View()->assign('cars', $cars);
	}
	
}