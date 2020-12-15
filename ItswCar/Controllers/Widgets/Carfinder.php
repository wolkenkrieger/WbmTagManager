<?php
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
	public function getManufacturersAction(): void {
		$this->Request()->setHeader('Content-Type', 'application/json');
		try {
			$this->service->setNeverRender();
			$manufacturers = $this->service->getManufacturersForCarfinder();
			$rendered = $this->View()
				->loadTemplate('widgets/carfinder/render/manufacturers_select_default.tpl')
				->assign('manufacturers', $manufacturers)
				->render();
			
			$result = [
				'success' => TRUE,
				'data' => $rendered,
				'manufacturer' => 1,
				'model' => 2
			];
		} catch (\Exception $e) {
			$this->service->pluginLogger->addCritical($e->getMessage(), [
				'code' => $e->getCode(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString()
			]);
			
			$result = [
				'success' => FALSE,
				'data' => []
			];
		}
		
		$this->Response()->setBody(json_encode($result));
	}
	
	public function getModelsAction(): void {
		try {
			$this->service->setNeverRender();
			$manufacturerId = $this->Request()->getParam('manufacturer');
			$result = [
				'success' => TRUE,
				'data' => $this->service->getModelsForCarfinder($manufacturerId)
			];
		} catch (\Exception $e) {
			$this->service->pluginLogger->addCritical($e->getMessage(), [
				'code' => $e->getCode(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString()
			]);
			
			$result = [
				'success' => FALSE,
				'data' => []
			];
		}
		
		$this->Response()->setBody(json_encode($result));
	}
	
	/**
	 *
	 */
	public function getTypesAction(): void {
		try {
			$this->service->setNeverRender();
			$manufacturerId = $this->Request()->getParam('manufacturer');
			$modelId = $this->Request()->getParam('model');
			$result = [
				'success' => TRUE,
				'data' => $this->service->getTypesForCarfinder($manufacturerId, $modelId)
			];
		} catch (\Exception $e) {
			$this->service->pluginLogger->addCritical($e->getMessage(), [
				'code' => $e->getCode(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString()
			]);
			
			$result = [
				'success' => FALSE,
				'data' => []
			];
		}
		
		$this->Response()->setBody(json_encode($result));
	}
}