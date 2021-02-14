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
		$this->View()->assign('showSelect', !$carSet);
		
		if ($carSet) {
			$car = $this->service->getCarsForCarfinder([
				'cars.tecdocId' => $sessionData['car']
			], []);
			
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
			$rendered = $this->View()
				->loadTemplate('/widgets/carfinder/render/manufacturers_select_default.tpl')
				->assign('manufacturers', $manufacturers)
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
		
		$this->Response()->setBody(json_encode($result));
	}
	
	/**
	 *
	 */
	public function getTypeAction(): void {
		try {
			$this->Request()->setHeader('Content-Type', 'application/json');
			$this->service->setNeverRender();
			$manufacturerId = (int)$this->Request()->getParam('manufacturer');
			$modelId = (int)$this->Request()->getParam('model');
			$types = $this->service->getTypesForCarfinder($manufacturerId, $modelId);
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
				'type' => NULL,
				'car' => NULL
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
	 *
	 */
	public function setManufacturerAction(): void {
		try {
			$this->Request()->setHeader('Content-Type', 'application/json');
			$this->service->setNeverRender();
			$manufacturerId = (int)$this->Request()->getParam('manufacturer');
			
			if (!$manufacturerId) {
				throw new \RuntimeException('manufacturerId');
			}
			
			$modelId = $typeId = NULL;
			$sessionData = $this->service->getSessionData();
			if (isset($sessionData['model']) && ((int)$sessionData['manufacturer'] === $manufacturerId)) {
				$modelId = (int)$sessionData['model'];
			}
			
			if ($modelId && isset($sessionData['type'])) {
				$typeId = (int)$sessionData['type'];
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
		
		$this->Response()->setBody(json_encode($result));
	}
	
	/**
	 *
	 */
	public function setModelAction(): void {
		try {
			$this->Request()->setHeader('Content-Type', 'application/json');
			$this->service->setNeverRender();
			$manufacturerId = (int)$this->Request()->getParam('manufacturer');
			$modelId = (int)$this->Request()->getParam('model');
			
			if (!$manufacturerId) {
				throw new \RuntimeException('manufacturerId');
			}
			if (!$modelId) {
				throw new \RuntimeException('modelId');
			}
			
			$typeId = NULL;
			$sessionData = $this->service->getSessionData();
			if (isset($sessionData['type']) && ((int)$sessionData['model'] === $modelId)) {
				$typeId = (int)$sessionData['type'];
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
		
		$this->Response()->setBody(json_encode($result));
	}
	
	/**
	 *
	 */
	public function setTypeAction(): void {
		try {
			$this->Request()->setHeader('Content-Type', 'application/json');
			$this->service->setNeverRender();
			$manufacturerId = (int)$this->Request()->getParam('manufacturer');
			$modelId = (int)$this->Request()->getParam('model');
			$typeId = (int)$this->Request()->getParam('type');
			if (!$manufacturerId) {
				throw new \RuntimeException('manufacturerId');
			}
			if (!$modelId) {
				throw new \RuntimeException('modelId');
			}
			if (!$typeId) {
				throw new \RuntimeException('typeId');
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
		
		$this->Response()->setBody(json_encode($result));
	}
	
	/**
	 *
	 */
	public function getCarsAction(): void {
		try {
			$this->Request()->setHeader('Content-Type', 'application/json');
			$this->service->setNeverRender();
			$manufacturerId = (int)$this->Request()->getParam('manufacturer');
			$modelId = (int)$this->Request()->getParam('model');
			$typeId = (int)$this->Request()->getParam('type');
			
			if (!$manufacturerId) {
				throw new \RuntimeException('manufacturerId');
			}
			if (!$modelId) {
				throw new \RuntimeException('modelId');
			}
			if (!$typeId) {
				throw new \RuntimeException('typeId');
			}
			
			$cars = $this->service->getCarsForCarfinder([
				'cars.manufacturerId' => $manufacturerId,
				'cars.modelId' => $modelId,
				'cars.typeId' => $typeId,
				'cars.active' => 1
			], [
				'cars.buildFrom' => 'ASC',
				'cars.buildTo' => 'ASC'
			]);
			
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
		
		$this->Response()->setBody(json_encode($result));
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
		try {
			$result = [
				'success' => TRUE,
				'manufacturer' => NULL,
				'model' => NULL,
				'type' => NULL,
				'car' => NULL
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
	 * @param \Exception $e
	 */
	private function setLog(\Exception $e): void {
		$this->service->pluginLogger->addCritical($e->getMessage(), [
			'code' => $e->getCode(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => $e->getTraceAsString()
		]);
	}
}