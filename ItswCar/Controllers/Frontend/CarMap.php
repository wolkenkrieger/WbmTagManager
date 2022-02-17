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
		
		/*echo "<pre>";
		var_dump($data);die;
		*/
		
		foreach($data as $datum) {
			if ($datum['topBrand'] === TRUE) {
				$topBrandsViewData[$datum['manufacturerDisplay']][$datum['modelDisplay']] = [
					'manufacturerId' => $datum['manufacturerId'],
					'modelId' => $datum['modelId']
				];
			} else {
				$viewData[$datum['manufacturerDisplay']][$datum['modelDisplay']] = [
					'manufacturerId' => $datum['manufacturerId'],
					'modelId' => $datum['modelId']
				];
			}
		}
		
		$this->View()->assign('viewData', $viewData);
		$this->View()->assign('topBrandsViewData', $topBrandsViewData);
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
				//'cars.tecdocId' => $tecdocId,
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