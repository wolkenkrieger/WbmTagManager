<?php declare(strict_types=1);

use ItswCar\Components\Services\Services;
use ItswCar\Models\Car;
use ItswCar\Traits\LoggingTrait;

/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   15.12.2020
 * Zeit:    11:39
 * Datei:   Carfinder.php
 * @package ItswCar\Controllers\Frontend
 */

class Shopware_Controllers_Widgets_Carfinder extends Enlight_Controller_Action {
	use LoggingTrait;
	
	protected Services $service;
	protected $sessionHelper;
	protected $configHelper;
	protected $entityManager;
	
	/**
	 * @throws \Exception
	 */
	public function init(): void {
		$this->setContainer(Shopware()->Container());
		
		$this->service = $this->get('itswcar.services');
		$this->sessionHelper = $this->get('itsw.helper.session');
		$this->configHelper = $this->get('itsw.helper.config');
		$this->entityManager = $this->get('models');
	}
	
	public function indexAction(): void {
		$sessionData = $this->sessionHelper->getSessionData();
		$carSet = (bool)$sessionData['car'];
		$basePath = $this->configHelper->getBasePath();
		
		$this->View()->assign('showSelect', !$carSet);
		$this->View()->assign('basePath', $basePath);
		
		if ($carSet) {
			$car = $this->entityManager->getRepository(Car::class)->getCarsForCarfinder([
				'select' => [
					'cars'
				],
				'conditions' => [
					'cars.tecdocId' => $sessionData['car']
				]
			]);
			
			$this->View()->assign('car', reset($car));
		}
	}
	
	/**
	 * @throws \JsonException
	 */
	public function getManufacturerAction(): void {
		try {
			$this->Front()->Plugins()->ViewRenderer()->setNoRender();
			$this->View()->setTemplate();
			$manufacturers = $this->entityManager->getRepository(Car::class)->getManufacturersForCarfinder();
			$topBrands = [];
			$allBrands = [];
			
			foreach($manufacturers as $manufacturer) {
				if ($manufacturer['topBrand']) {
					$topBrands[] = $manufacturer;
				} else {
					$allBrands[] = $manufacturer;
				}
			}
			
			$rendered = $this->View()
				->loadTemplate('widgets/carfinder/render/manufacturers_select_default.tpl')
				->assign('topBrands', $topBrands)
				->assign('allBrands', $allBrands)
				->assign('session', $this->sessionHelper->getSessionData())
				->render();
			
			$result = [
				'success' => TRUE,
				'data' => $rendered,
				'manufacturer' => NULL,
				'model' => NULL,
				'type' => NULL
			];
		} catch (\Exception $e) {
			$this->error($e);
			
			$result = [
				'success' => FALSE,
				'data' => NULL
			];
		}
		
		$this->Response()->setBody(json_encode($result, JSON_THROW_ON_ERROR));
	}
	
	/**
	 * @throws \JsonException
	 */
	public function getModelAction(): void {
		try {
			$this->Front()->Plugins()->ViewRenderer()->setNoRender();
			$this->View()->setTemplate();
			$manufacturerId = (int)$this->Request()->getParam('manufacturer');
			$models = $this->entityManager->getRepository(Car::class)->getModelsForCarfinder($manufacturerId);
			
			$rendered = $this->View()
				->loadTemplate('widgets/carfinder/render/models_select_default.tpl')
				->assign('models', $models)
				->assign('session', $this->sessionHelper->getSessionData())
				->render();
			
			$result = [
				'success' => TRUE,
				'data' => $rendered,
				'manufacturer' => $manufacturerId,
				'model' => NULL,
				'type' => NULL
			];
		} catch (\Exception $e) {
			$this->error($e);
			
			$result = [
				'success' => FALSE,
				'data' => NULL
			];
		}
		
		$this->Response()->setBody(json_encode($result, JSON_THROW_ON_ERROR));
	}
	
	/**
	 * @throws \JsonException
	 */
	public function getTypeAction(): void {
		try {
			$this->Front()->Plugins()->ViewRenderer()->setNoRender();
			$this->View()->setTemplate();
			$manufacturerId = (int)$this->Request()->getParam('manufacturer', NULL);
			$modelId = (int)$this->Request()->getParam('model', NULL);
			$typeId = (int)$this->Request()->getParam('type', NULL);
			$types = $this->entityManager->getRepository(Car::class)->getTypesForCarfinder($manufacturerId, $modelId, $typeId);
			$rendered = $this->View()
				->loadTemplate('widgets/carfinder/render/types_select_default.tpl')
				->assign('types', $types)
				->assign('session', $this->sessionHelper->getSessionData())
				->render();
			
			$result = [
				'success' => TRUE,
				'data' => $rendered,
				'manufacturer' => $manufacturerId,
				'model' => $modelId,
				'type' => $typeId,
				'car' => NULL
			];
			
		} catch (\Exception $e) {
			$this->error($e);
			
			$result = [
				'success' => FALSE,
				'data' => NULL
			];
		}
		
		$this->Response()->setBody(json_encode($result, JSON_THROW_ON_ERROR));
	}
	
	/**
	 * @throws \JsonException
	 */
	public function setManufacturerAction(): void {
		try {
			$this->Front()->Plugins()->ViewRenderer()->setNoRender();
			$this->View()->setTemplate();
			$manufacturerId = (int)$this->Request()->getParam('manufacturer');
			
			if (!$manufacturerId) {
				$this->Request()->setParam('redirect', 0);
				$this->forward('unset-car');
				return;
				
			} else {
				$sessionData = $this->sessionHelper->getSessionData();
				
				if ($manufacturerId !== $sessionData['manufacturer']) {
					$sessionData = array_merge($sessionData, [
						'manufacturer'  => $manufacturerId,
						'model'         => NULL,
						'type'          => NULL,
						'car'           => NULL
					]);
				}
				
				$result = array_merge($sessionData, [
					'success' => TRUE,
					'data' => NULL
				]);
			}
			
			$this->sessionHelper->setSessionData($sessionData);
			
		} catch (\Exception $e) {
			$this->error($e);
			
			$result = [
				'success' => FALSE,
				'data' => NULL
			];
		}
		
		$this->Response()->setBody(json_encode($result, JSON_THROW_ON_ERROR));
	}
	
	/**
	 * @throws \JsonException
	 */
	public function setModelAction(): void {
		try {
			$this->Front()->Plugins()->ViewRenderer()->setNoRender();
			$this->View()->setTemplate();
			$manufacturerId = (int)$this->Request()->getParam('manufacturer');
			$modelId = (int)$this->Request()->getParam('model');
			$typeId = (int)$this->Request()->getParam('type');
			
			if (!$manufacturerId) {
				$this->Request()->setParam('redirect', 0);
				$this->forward('unset-car');
				return;
			}
			if (!$modelId) {
				$this->Request()->setParam('redirect', 0);
				$this->forward('unset-car');
				return;
			}
			if (!$typeId) {
				$this->Request()->setParam('redirect', 0);
				$this->forward('unset-car');
				return;
			}
			
			$result = [
				'success' => TRUE,
				'data' => NULL,
				'manufacturer' => $manufacturerId,
				'model' => $modelId,
				'type' => $typeId,
				'car' => NULL
			];
			
			$this->sessionHelper->setSessionData([
				'manufacturer' => $manufacturerId,
				'model' => $modelId,
				'type' => $typeId,
				'car' => NULL]);
		} catch (\Exception $e) {
			$this->error($e);
			
			$result = [
				'success' => FALSE,
				'data' => NULL
			];
		}
		
		$this->Response()->setBody(json_encode($result, JSON_THROW_ON_ERROR));
	}
	
	/**
	 * @throws \JsonException
	 */
	public function setTypeAction(): void {
		try {
			$this->Front()->Plugins()->ViewRenderer()->setNoRender();
			$this->View()->setTemplate();
			$manufacturerId = (int)$this->Request()->getParam('manufacturer');
			$modelId = (int)$this->Request()->getParam('model');
			$typeId = (int)$this->Request()->getParam('type');
			$tecdocId = (int)$this->Request()->getParam('car');
			
			if (!$manufacturerId) {
				$this->Request()->setParam('redirect', 0);
				$this->forward('unset-car');
				return;
			}
			if (!$modelId) {
				$this->Request()->setParam('redirect', 0);
				$this->forward('unset-car');
				return;
			}
			if (!$typeId) {
				$this->Request()->setParam('redirect', 0);
				$this->forward('unset-car');
				return;
			}
			if (!$tecdocId) {
				$this->Request()->setParam('redirect', 0);
				$this->forward('unset-car');
				return;
			}
			
			$result = [
				'success' => TRUE,
				'data' => NULL,
				'manufacturer' => $manufacturerId,
				'model' => $modelId,
				'type' => $typeId,
				'car' => $tecdocId
			];
			$this->sessionHelper->setSessionData([
				'manufacturer' => $manufacturerId,
				'model' => $modelId,
				'type' => $typeId,
				'car' => $tecdocId]);
		} catch (\Exception $e) {
			$this->error($e);
			
			$result = [
				'success' => FALSE,
				'data' => NULL
			];
		}
		
		$this->Response()->setBody(json_encode($result, JSON_THROW_ON_ERROR));
	}
	
	/**
	 * @throws \JsonException
	 */
	public function getCarsAction(): void {
		try {
			$this->Front()->Plugins()->ViewRenderer()->setNoRender();
			$this->View()->setTemplate();
			$manufacturerId = (int)$this->Request()->getParam('manufacturer');
			$modelId = (int)$this->Request()->getParam('model');
			$typeId = (int)$this->Request()->getParam('type');
			$tecdocId = (int)$this->Request()->getParam('car');
			$hsn = $this->Request()->getParam('hsn', '');
			$tsn = $this->Request()->getParam('tsn', '');
			$cars = [];
			
			if ($hsn !== '' || $tsn !== '') {
				$codes = $this->entityManager->getRepository(Car::class)->getCodes([
					'select' => [
						'kba_codes.tecdocId'
					],
					'conditions' => [
						'kba_codes.hsn' => strtoupper($hsn),
						'kba_codes.tsn' => strtoupper($tsn),
						'kba_codes.active' => 1
					]
				]);
				
				if (count($codes)) {
					$tecdocIds = [];
					foreach ($codes as $code) {
						$tecdocIds[] = $code['tecdocId'];
					}
					
					$cars = $this->entityManager->getRepository(Car::class)->getCarsForCarfinder([
						'select' => [
							'cars'
						],
						'conditions' => [
							'cars.tecdocId' => $tecdocIds,
						'cars.active' => 1
						],
						'orders' => [
							'cars.buildFrom' => 'ASC',
							'cars.buildTo' => 'ASC'
						]
					]);
				}
			} else {
				if (!$manufacturerId) {
					$this->Request()->setParam('redirect', 0);
					$this->forward('unset-car');
					return;
				}
				if (!$modelId) {
					$this->Request()->setParam('redirect', 0);
					$this->forward('unset-car');
					return;
				}
				if (!$typeId) {
					$this->Request()->setParam('redirect', 0);
					$this->forward('unset-car');
					return;
				}
				if (!$tecdocId) {
					$this->Request()->setParam('redirect', 0);
					$this->forward('unset-car');
					return;
				}
				
				$cars = $this->entityManager->getRepository(Car::class)->getCarsForCarfinder([
					'select' => [
						'cars'
					],
					'conditions' => [
						'cars.manufacturerId' => $manufacturerId,
						'cars.modelId' => $modelId,
						'cars.typeId' => $typeId,
						'cars.tecdocId' => $tecdocId,
						'cars.active' => 1
					],
					'orders' => [
						'cars.buildFrom' => 'ASC',
						'cars.buildTo' => 'ASC'
					]
				]);
			}
			
			$rendered = $this->View()
				->loadTemplate('widgets/carfinder/render/get_cars_modal.tpl')
				->assign('cars', $cars)
				->assign('session', $this->sessionHelper->getSessionData())
				->render();
			
			$result = [
				'success' => TRUE,
				'data' => $rendered,
				'manufacturer' => $manufacturerId,
				'model' => $modelId,
				'type' => $typeId,
				'car' => NULL
			];
		} catch (\Exception $e) {
			$this->error($e);
			
			$result = [
				'success' => FALSE,
				'data' => NULL
			];
		}
		
		$this->Response()->setBody(json_encode($result, JSON_THROW_ON_ERROR));
	}
	
	/**
	 * @return bool
	 */
	public function setCarAction(): bool {
		try {
			$tecdocId = (int)$this->Request()->getParam('car');
			$manufacturerId = (int)$this->Request()->getParam('manufacturer');
			$modelId = (int)$this->Request()->getParam('model');
			$typeId = (int)$this->Request()->getParam('type');
			
			if (!$tecdocId) {
				throw new \RuntimeException('id');
			}
			
			if (!$manufacturerId || !$modelId || $typeId) {
				$ids = $this->entityManager->getRepository(Car::class)->getIdsByTecdocId($tecdocId);
				$manufacturerId = $ids['manufacturerId'];
				$modelId = $ids['modelId'];
				$typeId = $ids['typeId'];
			}
			
			$this->sessionHelper->setSessionData([
				'manufacturer' => $manufacturerId,
				'model' => $modelId,
				'type' => $typeId,
				'car' => $tecdocId]);
			
			$url = $this->service->getUrl([
				'controller' => 'cat',
				'module' => 'frontend',
				'action' => 'index',
				//'sCategory' => $this->service->getRootCategoryId(),
				'sCategory' => 6,
				'rewriteUrl' => 1
			]);
			
			$this->redirect($url);
			
		} catch (\Exception $e) {
			$this->error($e);
		}
		
		return TRUE;
	}
	
	/**
	 * @return bool
	 */
	public function unsetCarAction(): bool {
		try{
			$withRedirect = (int)$this->Request()->getParam('redirect', 1);
			$result = [
				'success' => TRUE,
				'manufacturer' => NULL,
				'model' => NULL,
				'type' => NULL,
				'car' => NULL
			];
			
			$this->sessionHelper->setSessionData([
				'manufacturer' => NULL,
				'model' => NULL,
				'type' => NULL,
				'car' => NULL]);
			
			if ($withRedirect) {
				$url = $this->service->getUrl([
					'controller' => 'cat',
					'module' => 'frontend',
					'action' => 'index',
					//'sCategory' => $this->service->getRootCategoryId(),
					'sCategory' => 6,
					'rewriteUrl' => 1
				]);
				
				$this->redirect($url);
			} else {
				$this->Front()->Plugins()->ViewRenderer()->setNoRender();
				$this->View()->setTemplate();
				$this->Response()->setBody(json_encode($result, JSON_THROW_ON_ERROR));
			}
		} catch (\Exception $e) {
			$this->error($e);
		}
		
		return TRUE;
	}
}