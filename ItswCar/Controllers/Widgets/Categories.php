<?php declare(strict_types=1);

use Shopware\Models\Category\Category;

/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   27.04.2021
 * Zeit:    15:11
 * Datei:   Categories.php
 */

class Shopware_Controllers_Widgets_Categories extends Enlight_Controller_Action {
	/**
	 * @var ItswCar\Components\Services\Services
	 */
	protected $service;
	protected $categories;
	
	/**
	 *
	 */
	public function init(): void {
		$this->service = Shopware()->Container()->get('itswcar.services');
		$this->setContainer($this->service->getContainer());
	}
	
	public function indexAction(): void {
		$return = [];
		$mainCategories = Shopware()->Modules()->Categories()->sGetMainCategories();
		
		foreach($mainCategories as $mainCategoryId => $mainCategory) {
			$return[$mainCategoryId] = $mainCategory;
			//$return[$mainCategoryId]['subcategories'] = $this->getCategoriesByParentId($mainCategoryId);
		}
		$this->View()->assign('sCategories', $return);
	}
	
	/**
	 * @param $id
	 * @return array
	 */
	private function getCategoriesByParentId($id): array {
		$baseUrl = Shopware()->Container()->get('config')->get('baseFile') . '?sViewport=cat&sCategory=';
		$blogBaseUrl = Shopware()->Container()->get('config')->get('baseFile') . '?sViewport=blog&sCategory=';
		$customerGroupId = (int) Shopware()->Modules()->System()->sUSERGROUPDATA['id'];
		
		$categories = $this->service->getModelManager()->getRepository(Category::class)
			->getActiveByParentIdQuery($id, $customerGroupId)
			->getArrayResult();
		$resultCategories = [];
		foreach ($categories as $category) {
			$url = $category['category']['blog'] ? $blogBaseUrl : $baseUrl;
			$resultCategories[$category['category']['id']] = array_merge($category['category'], [
				'description' => $category['category']['name'],
				'childrenCount' => $category['childrenCount'],
				'articleCount' => $category['articleCount'],
				'hidetop' => $category['category']['hideTop'],
				'subcategories' => [],
				'link' => $category['category']['external'] ?: $url . $category['category']['id'],
				'flag' => false,
			]);
		}
		
		return $resultCategories;
	}
}