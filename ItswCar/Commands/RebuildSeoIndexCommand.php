<?php declare(strict_types=1);
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   26.01.2021
 * Zeit:    14:55
 * Datei:   RebuildSeoIndexCommand.php
 * @package ItswCar\Commands
 */

namespace ItswCar\Commands;

use ItswCar\Components\Services\Services;
use Shopware\Commands\ShopwareCommand;
use Shopware\Components\ContainerAwareEventManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RebuildSeoIndexCommand extends ShopwareCommand {
	/**
	 * @var \Shopware_Components_SeoIndex
	 */
	protected $seoIndex;
	
	/**
	 * @var \sRewriteTable
	 */
	protected $rewriteTable;
	
	/**
	 * @var \sCategories
	 */
	protected $categories;
	
	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	protected $database;
	
	/**
	 * @var \Shopware_Components_Modules
	 */
	protected $modules;
	
	/**
	 * @var \Shopware\Components\Model\ModelManager
	 */
	protected $modelManager;
	
	/**
	 * @var ContainerAwareEventManager
	 */
	protected $events;
	
	/**
	 * ArticleCarLinksImporterCommand constructor.
	 * @param \ItswCar\Components\Services\Services $itswCarServices
	 */
	public function __construct(Services $itswCarServices) {
		$this->setContainer(Shopware()->Container());
		$this->modelManager = $this->container->get('models');
		$this->modules = $this->container->get('modules');
		$this->database = $this->container->get('dbal_connection');
		$this->seoIndex = $this->container->get('seoindex');
		$this->rewriteTable = $this->modules->RewriteTable();
		$this->events = $this->container->get('events');
		
		parent::__construct();
	}
	
	/**
	 *
	 */
	public function configure():void {
		$this
			->setName('itsw:seo:index:rebuild')
			->setDescription('Rebuilds the SEO index')
			->addArgument('shopId', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'The Id of the shop')
			->setHelp('The <info>%command.name%</info> rebuilds the SEO index');
	}
	
	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
	 * @throws \Enlight_Event_Exception
	 * @throws \SmartyException
	 * @throws \Zend_Db_Adapter_Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): void {
		$shops = $input->getArgument('shopId');
		
		if (empty($shops)) {
			/** @var \Doctrine\DBAL\Query\QueryBuilder $query */
			$query = $this->database->createQueryBuilder();
			$shops = $query->select('id')
				->from('s_core_shops', 'shops')
				->where('active', 1)
				->execute()
				->fetchAll(\PDO::FETCH_COLUMN);
		}
		
		$currentTime = new \DateTime();
		
		$this->rewriteTable->sCreateRewriteTableCleanup();
		$this->rewriteTableCleanup();
		
		foreach ($shops as $shopId) {
			$output->writeln('Rebuilding SEO index for shop ' . $shopId);
			
			/** @var \Shopware\Models\Shop\Repository $repository */
			$repository = $this->modelManager->getRepository(\Shopware\Models\Shop\Shop::class);
			$shop = $repository->getActiveById($shopId);
			
			if ($shop === null) {
				throw new \RuntimeException('No valid shop id passed');
			}
			
			$this->container->get('shopware.components.shop_registration_service')->registerShop($shop);
			$this->modules->Categories()->baseId = $shop->getCategory()->getId();
			[$cachedTime, $elementId, $shopId] = $this->seoIndex->getCachedTime();
			
			$this->seoIndex->setCachedTime($currentTime->format('Y-m-d h:m:i'), $elementId, $shopId);
			$this->rewriteTable->baseSetup();
			
			$limit = 10000;
			$lastId = null;
			$lastUpdateVal = '0000-00-00 00:00:00';
			
			do {
				$lastUpdateVal = $this->rewriteTable->sCreateRewriteTableArticles($lastUpdateVal, $limit);
				$lastId = $this->rewriteTable->getRewriteArticleslastId();
			} while ($lastId !== null);
			
			$this->seoIndex->setCachedTime($currentTime->format('Y-m-d h:m:i'), $elementId, $shopId);
			
			$context = $this->container->get('shopware_storefront.context_service')->createShopContext($shopId);
			
			$this->rewriteTable->sCreateRewriteTableCategories();
			$this->rewriteTable->sCreateRewriteTableCampaigns();
			$this->rewriteTable->sCreateRewriteTableContent();
			$this->rewriteTable->sCreateRewriteTableBlog(null, null, $context);
			$this->rewriteTable->createManufacturerUrls($context);
			$this->rewriteTable->sCreateRewriteTableStatic();
			
			$this->createRewriteTableTecdoc();
			
			$this->events->notify(
				'Shopware_Command_RebuildSeoIndexCommand_CreateRewriteTable',
				[
					'shopContext' => $context,
					'cachedTime' => $currentTime,
				]
			);
		}
		
		$output->writeln('The SEO index was rebuild successfully.');
	}
	
	/**
	 * @return int
	 * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
	 */
	protected function rewriteTableCleanup():int {
		return $this->database->delete('s_core_rewrite_urls',
			[
				'org_path LIKE "sViewport=cat&m=%"'
			]);
	}
	
	/**
	 * @param string $text
	 * @return string
	 */
	public function cleanStringForUrl($text = ''):string {
		$text = mb_strtolower(trim($text));
		$umlaute = [
			'/ß/',
			'/Ä/',
			'/Ö/',
			'/Ü/',
			'/ä/',
			'/ö/',
			'/ü/'
		];
		
		$umlautersetzungen = [
			'sz',
			'Ae',
			'Oe',
			'Ue',
			'ae',
			'oe',
			'ue'
		];
		
		$patterns = [
			'~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);~i',
			'/[^a-z0-9]+/i'
		];
		
		$replacements = [
			'$1',
			'-'
		];
		
		$text = preg_replace($umlaute, $umlautersetzungen, $text);
		$text = preg_replace($patterns, $replacements, htmlentities($text, ENT_QUOTES, 'UTF-8'));
		
		return trim($text,' -');
	}
	
	
	/**
	 * @param $car
	 * @return string
	 */
	protected function buildManufacturerPath($car):string {
		return $this->cleanStringForUrl($car->manufacturer_name);
	}
	
	/**
	 * @param $car
	 * @return string
	 */
	protected function buildModelPath($car):string {
		return implode('/', [
			$this->buildManufacturerPath($car),
			$this->cleanStringForUrl($car->model_name)
		]);
	}
	
	protected function buildCarPath($car):string {
		return implode('/', [
			$this->buildModelPath($car),
			$this->cleanStringForUrl(sprintf('%s-%d', $car->type_name, $car->tecdoc_id))
		]);
	}
	
	/**
	 * @param $car
	 */
	protected function renderAndWriteSeoUrl($car)	{
		/*
		$path = $this->cleanStringForUrl($car->manufacturer_name);
		$org_path = sprintf('sViewport=cat&m=%d', $car->manufacturer_id);
		$seoPath  = strtolower($this->rewriteTable->sCleanupPath($path)) . '/';
		$this->rewriteTable->sInsertUrl($org_path, $seoPath);
		
		$path = $this->cleanStringForUrl($car->model_name);
		$org_path = sprintf('sViewport=cat&mo=%d', $car->model_id);
		$seoPath  = strtolower($this->rewriteTable->sCleanupPath($path)) . '/';
		$this->rewriteTable->sInsertUrl($org_path, $seoPath);
		
		$path = $this->cleanStringForUrl(sprintf('%s-%d', $car->type_name, $car->tecdoc_id));
		$org_path = sprintf('sViewport=cat&car=%d', $car->tecdoc_id);
		$seoPath  = strtolower($this->rewriteTable->sCleanupPath($path)) . '/';
		$this->rewriteTable->sInsertUrl($org_path, $seoPath);
		*/
		/*
		$path = sprintf('%s/%s/%s-%d/', $this->cleanStringForUrl($car->manufacturer_name), $this->cleanStringForUrl($car->model_name), $this->cleanStringForUrl($car->type_name), $this->cleanStringForUrl($car->tecdoc_id));
		$org_path = sprintf('sViewport=cat&car=%d', $car->tecdoc_id);
		$seoPath  = strtolower($this->rewriteTable->sCleanupPath($path));
		$this->rewriteTable->sInsertUrl($org_path, $seoPath);
		*/
		
		$manufacturer = $this->cleanStringForUrl($car->manufacturer_name);
		$org_path = sprintf('sViewport=cat&m=%d', $car->manufacturer_id);
		$seoPath  = strtolower($this->rewriteTable->sCleanupPath(sprintf('%s/', $manufacturer)));
		$this->rewriteTable->sInsertUrl($org_path, $seoPath);
		
		$model = $this->cleanStringForUrl($car->model_name);
		$org_path = sprintf('sViewport=cat&m=%d&mo=%d', $car->manufacturer_id, $car->model_id);
		$seoPath  = strtolower($this->rewriteTable->sCleanupPath(sprintf('%s/%s/', $manufacturer, $model)));
		$this->rewriteTable->sInsertUrl($org_path, $seoPath);
		
		$_car = $this->cleanStringForUrl(sprintf('%s-%d', $car->type_name, $car->tecdoc_id));
		$org_path = sprintf('sViewport=cat&m=%d&mo=%d&car=%d', $car->manufacturer_id, $car->model_id, $car->tecdoc_id);
		$seoPath  = strtolower($this->rewriteTable->sCleanupPath(sprintf('%s/%s/%s/', $manufacturer, $model, $_car)));
		$this->rewriteTable->sInsertUrl($org_path, $seoPath);
		
	}
	
	/**
	 *
	 */
	protected function createRewriteTableTecdoc() {
		$query = $this->database->createQueryBuilder();
		$result = $query->select([
			'm.id as manufacturer_id',
			'm.name as manufacturer_name',
			'mo.id as model_id',
			'mo.name as model_name',
			't.name as type_name',
			'c.*'
		])
			->from('itsw_cars', 'c')
			->join('c', 'itsw_manufacturers', 'm', 'c.manufacturer_id = m.id')
			->join('c', 'itsw_models', 'mo', 'c.model_id = mo.id')
			->join('c','itsw_types', 't', 'c.type_id = t.id')
			->where('c.active = 1')
			->andWhere('m.active = 1')
			->andWhere('mo.active = 1')
			->andWhere('t.active = 1')
			//->groupBy('mo.id')
			->orderBy('m.name')
			->addOrderBy('mo.name')
			->execute();
		
		while ($car = $result->fetch(\PDO::FETCH_OBJ)) {
			$this->renderAndWriteSeoUrl($car);
		}
	}
}