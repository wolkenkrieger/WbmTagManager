<?php
declare(strict_types=1);
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
				->loadTemplate('widgets/carfinder/render/manufacturers_select_default.tpl')
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
				'type' => $typeId
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
				'type' => $typeId
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
			];
			$this->service->setSessionData($result);
			
			$result['url'] = $this->service->getUrl([
				'controller' => 'cat',
				'module' => 'frontend',
				'action' => 'index',
				'sCategory' => $this->service->getRootCategoryId(),
				'rewriteUrl' => 1
			]);
			
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
			$this->service->setNoRender();
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
			
			$cars = $this->service->getCarsForCarfinder($manufacturerId, $modelId, $typeId);
			
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