<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   15.12.2020
 * Zeit:    11:39
 * Datei:   Carfinder.php
 * @package ItswCar\Controllers\Frontend
 */

class Shopware_Controllers_Widgets_Carfinder extends Enlight_Controller_Action {
	
	protected $service;
	
	
	public function init(): void {
		$this->service = Shopware()->Container()->get('itswcar.services');
		$this->setContainer($this->service->getContainer());
	}
	
	public function indexAction(): void {
		$sessionData = $this->service->getSessionData();
		$carSet = (bool)$sessionData['car'];
		$basePath = Shopware()->Shop()->getBasePath();
		
		$this->View()->assign('showSelect', !$carSet);
		$this->View()->assign('basePath', $basePath);
		
		if ($carSet) {
			$car = $this->service->getCarsForCarfinder([
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
	 *
	 */
	public function getManufacturerAction(): void {
		try {
			$this->Request()->setHeader('Content-Type', 'application/json');
			$this->service->setNeverRender();
			$manufacturers = $this->service->getManufacturersForCarfinder();
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
				->assign('session', $this->service->getSessionData())
				->render();
			
			$result = [
				'success' => TRUE,
				'data' => $rendered,
				'manufacturer' => NULL,
				'model' => NULL,
				'type' => NULL
			];
		} catch (\Exception $e) {
			$this->setLog($e);
			
			$result = [
				'success' => FALSE,
				'data' => NULL
			];
		}
		
		$this->Response()->setBody(json_encode($result));
	}
	
	/**
	 * @throws \JsonException
	 */
	public function getModelAction(): void {
		try {
			$this->Request()->setHeader('Content-Type', 'application/json');
			$this->service->setNeverRender();
			$manufacturerId = (int)$this->Request()->getParam('manufacturer');
			$models = $this->service->getModelsForCarfinder($manufacturerId);
			
			$rendered = $this->View()
				->loadTemplate('widgets/carfinder/render/models_select_default.tpl')
				->assign('models', $models)
				->assign('session', $this->service->getSessionData())
				->render();
			
			$result = [
				'success' => TRUE,
				'data' => $rendered,
				'manufacturer' => $manufacturerId,
				'model' => NULL,
				'type' => NULL
			];
		} catch (\Exception $e) {
			$this->setLog($e);
			
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
			$this->Request()->setHeader('Content-Type', 'application/json');
			$this->service->setNeverRender();
			$manufacturerId = (int)$this->Request()->getParam('manufacturer', NULL);
			$modelId = (int)$this->Request()->getParam('model', NULL);
			$typeId = (int)$this->Request()->getParam('type', NULL);
			$types = $this->service->getTypesForCarfinder($manufacturerId, $modelId, $typeId);
			$rendered = $this->View()
				->loadTemplate('widgets/carfinder/render/types_select_default.tpl')
				->assign('types', $types)
				->assign('session', $this->service->getSessionData())
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
			$this->setLog($e);
			
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
			$this->Request()->setHeader('Content-Type', 'application/json');
			$this->service->setNeverRender();
			$manufacturerId = (int)$this->Request()->getParam('manufacturer');
			
			if (!$manufacturerId) {
				$this->Request()->setParam('redirect', 0);
				$this->forward('unset-car');
				return;
				
			} else {
				$sessionData = $this->service->getSessionData();
				$carId = $typeId = $modelId = NULL;
				
				if (isset($sessionData['model']) && ((int)$sessionData['manufacturer'] === $manufacturerId)) {
					$modelId = $sessionData['model'];
					if (isset($sessionData['type'])) {
						$typeId = $sessionData['type'];
					}
				}
				
				$result = [
					'success' => TRUE,
					'data' => NULL,
					'manufacturer' => $manufacturerId,
					'model' => $modelId,
					'type' => $typeId,
					'car' => $carId
				];
			}
			
			$this->service->setSessionData($result);
		} catch (\Exception $e) {
			$this->setLog($e);
			
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
			$this->Request()->setHeader('Content-Type', 'application/json');
			$this->service->setNeverRender();
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
			
			$this->service->setSessionData($result);
		} catch (\Exception $e) {
			$this->setLog($e);
			
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
			$this->Request()->setHeader('Content-Type', 'application/json');
			$this->service->setNeverRender();
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
			$this->service->setSessionData($result);
		} catch (\Exception $e) {
			$this->setLog($e);
			
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
			$this->Request()->setHeader('Content-Type', 'application/json');
			$this->service->setNeverRender();
			$manufacturerId = (int)$this->Request()->getParam('manufacturer');
			$modelId = (int)$this->Request()->getParam('model');
			$typeId = (int)$this->Request()->getParam('type');
			$tecdocId = (int)$this->Request()->getParam('car');
			$hsn = $this->Request()->getParam('hsn', '');
			$tsn = $this->Request()->getParam('tsn', '');
			$cars = [];
			
			if ($hsn !== '' || $tsn !== '') {
				$codes = $this->service->getCodes([
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
					
					$cars = $this->service->getCarsForCarfinder([
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
				
				$cars = $this->service->getCarsForCarfinder([
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
				->assign('session', $this->service->getSessionData())
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
			$this->setLog($e);
			
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
				$ids = $this->service->getIdsByTecdocId($tecdocId);
				$manufacturerId = $ids['manufacturerId'];
				$modelId = $ids['modelId'];
				$typeId = $ids['typeId'];
			}
			
			$result = [
				'success' => TRUE,
				'manufacturer' => $manufacturerId,
				'model' => $modelId,
				'type' => $typeId,
				'car' => $tecdocId
			];
			
			$this->service->setSessionData($result);
			
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
			$this->setLog($e);
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
			
			$this->service->setSessionData($result);
			
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
				$this->Request()->setHeader('Content-Type', 'application/json');
				$this->service->setNeverRender();
				$this->Response()->setBody(json_encode($result, JSON_THROW_ON_ERROR));
			}
		} catch (\Exception $e) {
			$this->setLog($e);
		}
		
		return TRUE;
	}
	
	/**
	 * @param \Exception $e
	 */
	private function setLog(\Exception $e): void {
		$this->service->pluginLogger->addRecord($e->getMessage(), [
			'code' => $e->getCode(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => $e->getTraceAsString()
		]);
	}
}