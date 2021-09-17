<?php declare(strict_types=1);

use Shopware\Models\Category\Category;
use ItswCar\Traits\LoggingTrait;

/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   27.04.2021
 * Zeit:    15:11
 * Datei:   Categories.php
 */

class Shopware_Controllers_Widgets_Categories extends Enlight_Controller_Action {
	use LoggingTrait;
	
	protected $categories;
	private $seoHelper;
	
	/**
	 *
	 */
	public function init(): void {
		$this->setContainer(Shopware()->Container());
		$this->seoHelper = $this->container->get('itsw.helper.seo');
	}
	
	public function indexAction(): void {
		$return = [];
		$mainCategories = Shopware()->Modules()->Categories()->sGetMainCategories();
		
		foreach($mainCategories as $mainCategoryId => $mainCategory) {
			$mainCategory['link'] = $this->seoHelper->getCategorySeoUrl((int)$mainCategoryId);
			$return[$mainCategoryId] = $mainCategory;
			//$return[$mainCategoryId]['subcategories'] = $this->getCategoriesByParentId($mainCategoryId);
		}
		$this->View()->assign('sCategories', $return);
		
		$this->debug(__METHOD__, $this->View()->getAssign());
	}
	
	/**
	 * @param $id
	 * @return array
	 */
	private function getCategoriesByParentId($id): array {
		$baseUrl = Shopware()->Container()->get('config')->get('baseFile') . '?sViewport=cat&sCategory=';
		$blogBaseUrl = Shopware()->Container()->get('config')->get('baseFile') . '?sViewport=blog&sCategory=';
		$customerGroupId = (int) Shopware()->Modules()->System()->sUSERGROUPDATA['id'];
		
		$categories = Shopware()->Models()->getRepository(Category::class)
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
				//'link' => $category['category']['external'] ?: $url . $category['category']['id'],
				'link' => $category['category']['external'] ?: $this->seoHelper->getCategorySeoUrl((int)$category['category']['id']),
				'flag' => false,
			]);
		}
		
		return $resultCategories;
	}
}