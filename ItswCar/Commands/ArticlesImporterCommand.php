<?php
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   08.12.2020
 * Zeit:    18:07
 * Datei:   ArticlesImporterCommand.php
 * @package ItswCar\Commands
 */

namespace ItswCar\Commands;

use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

use Shopware\Components\Api\Resource\Article as ArticleApi;
use Shopware\Models\Attribute\Category as CategoryAttributes;
use Shopware\Models\Tax\Tax;
use Shopware\Models\Article\Detail;
use Shopware\Models\Category\Category;

class ArticlesImporterCommand extends ShopwareCommand {
	private $docPath;
	private $entityManager;
	private $itswCarServices;
	private $mappingFileName = 'mapping_artikel_kategorie.csv';
	private $articleFileName = 'artikel_stammdaten.csv';
	private $ArticleApi;
	private $articleEbayCategoryMapping = [];
	private $rootCategoryId = 5;
	
	/**
	 * ArticlesImporterCommand constructor.
	 * @param \ItswCar\Components\Services\Services $itswCarServices
	 */
	public function __construct(\ItswCar\Components\Services\Services $itswCarServices) {
		$this->setContainer(Shopware()->Container());
		$this->itswCarServices = $itswCarServices;
		$this->entityManager = Shopware()->Models();
		$this->docPath = $this->getContainer()->get('itsw.helper.config')->getDocPath();
		
		parent::__construct();
	}
	/**
	 * @inheritDoc
	 */
	protected function configure()	{
		$this->ArticleApi = new ArticleApi();
		$this->ArticleApi->setManager($this->entityManager);
		
		$this
			->setName('itsw:importer:articles')
			->setDescription('Imports articles from a csv.')
			->addOption(
				'force-update',
				'u',
				InputOption::VALUE_REQUIRED,
				'force updating articles',
				1
			)
			->addOption(
				'file',
				'f',
				InputOption::VALUE_OPTIONAL,
				'articles csv file',
				$this->docPath . $this->articleFileName
			)
			->setHelp('The <info>%command.name%</info> imports articles from a csv file.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$environment = $input->getOption('env');
		$forceUpdate = ((int)$input->getOption('force-update') === 1) || (strtolower($input->getOption('force-update')) === 'on');
		$articlesFileName = $this->docPath . ($input->getOption('file')?:$this->articleFileName);
		$mappingFileName = $this->docPath.$this->mappingFileName;
		$rowCount = 0;
		
		if (!file_exists($articlesFileName)) {
			throw new FileNotFoundException(sprintf("%s not found", $articlesFileName));
		}
		
		if (!file_exists($mappingFileName)) {
			throw new FileNotFoundException(sprintf("%s not found", $mappingFileName));
		}
		
		// first step: read mappings
		$_handle = file($mappingFileName, FILE_SKIP_EMPTY_LINES);
		$handle = fopen($mappingFileName, 'r');
		$csvHeader = fgetcsv($handle, 8192, ';', '"' );
		$csvCols = array_flip( $csvHeader );
		
		$articleNumber = $this->itswCarServices->validate($csvCols, 'Artikelnummer');
		$ebayCategoryId = $this->itswCarServices->validate($csvCols, 'Ebay-Shopkat');
		
		$output->writeln(sprintf("Starting import with parameters >> article data file: %s | force update: %d", $articlesFileName, $forceUpdate));
		$output->writeln("Step 1: reading article <-> eBay category mapping");
		
		while ($csvRow = fgetcsv($handle, 8192, ';', '"')) {
			$this->articleEbayCategoryMapping[$csvRow[$articleNumber]] = $this->getCatergoryId((int)$csvRow[$ebayCategoryId]);
			$this->itswCarServices->showProgress(++$rowCount, count($_handle) - 1, $output);
		}
		
		$output->writeln(sprintf("Step 1 finished: %d unique mappings read", count($this->articleEbayCategoryMapping)));
		
		$handle = fopen( $articlesFileName, 'r' );
		$_handle = file($articlesFileName, FILE_SKIP_EMPTY_LINES);
		
		$csvHeader = fgetcsv($handle, 8192, ';', '"' );
		$csvCols = array_flip( $csvHeader );
		
		$output->writeln(("Step 2: reading article data"));
		
		$rowCount = 0;
		$updated = 0;
		$created = 0;
		while( $csvRow = fgetcsv( $handle, 8192, ';', '"' ) ) {
			if ($environment === 'dev' && $rowCount >= 1000) {
				break;
			}
			$this->itswCarServices->showProgress(++$rowCount, count($_handle) - 1, $output);
			$params = $this->buildParamsArray($csvRow, $csvCols);
			if ($articleVariant = $this->entityManager->getRepository(Detail::class)
			->findOneBy([
				'number' => $params['mainDetail']['number']
			])) {
				if ($forceUpdate) {
					try {
						$params['__options_images']	= ['replace' => TRUE];
						$product = $this->ArticleApi->update($articleVariant->getArticle()->getId(), $params);
						$updated++;
					} catch (\Exception $exception) {
						$output->writeln(sprintf("ERROR! article update >> message: %s", $exception->getMessage()));
					}
				}
			} else {
				try {
					$product = $this->ArticleApi->create($params);
					$created++;
				} catch (\Exception $exception) {
					$output->writeln(sprintf("ERROR! article create >> message: %s", $exception->getMessage()));
				}
			}
			
			$this->itswCarServices->showProgress($rowCount, count($_handle) - 1, $output);
		}
		
		return 0;
	}
	
	/**
	 * @param $ebayCategoryId
	 * @return int
	 */
	private function getCatergoryId($ebayCategoryId): int {
		$results = $this->entityManager->getRepository(CategoryAttributes::class)
			->findByEbayCategoryId($ebayCategoryId);
		
		foreach ($results as $result) {
			return $result->getCategoryId();
		}
		
		return $this->rootCategoryId;
	}
	
	/**
	 * @param string $name
	 * @return object|null
	 */
	private function getCategoryByName(string $name) {
		return $this->entityManager->getRepository(Category::class)
			->findOneBy([
				'name' => $name
			]);
	}
	
	/**
	 * @param $price
	 * @return float
	 */
	private function makePrice($price) {
		return (float)str_ireplace(',', '.', $price);
	}
	
	/**
	 * @param $articleNumber
	 * @return array[]
	 */
	private function buildCategoriesArray($articleNumber): array {
		if (isset($this->articleEbayCategoryMapping[$articleNumber])) {
			$id = $this->articleEbayCategoryMapping[$articleNumber];
		} else if ($category = $this->getCategoryByName('Sonstige')) {
			$id = $category->getId();
		} else {
			$id = $this->rootCategoryId;
		}

		return [
			[
				'id' => $id
			]
		];
	}
	
	/**
	 * @param array  $images
	 * @param string $name
	 * @return array
	 */
	private function buildImagesArray(array $images, string $name): array {
		$tmp = [];
		$position = 1;
		foreach (array_unique(array_filter($images)) as $image) {
			$tmp[] = [
				'link' => $image,
				'main' => ($position === 1)? 1 : 2,
				'position' => $position++,
				'description' => $name
			];
		}
		return $tmp;
	}
	
	/**
	 * @param string $name
	 * @return string
	 */
	private function cleanName(string $name): string {
		$pieces = explode('für', $name);
		
		return reset($pieces);
	}
	
	/**
	 * @param $tax
	 * @return int
	 */
	private function getTaxId($tax): int {
		if ($result = $this->entityManager->getRepository(Tax::class)
		->findOneBy([
			'tax' => $tax
		])) {
			return $result->getId();
		}
		return 1;
	}
	
	/**
	 * @param array $csvRow
	 * @param array $csvCols
	 * @return array
	 */
	private function buildParamsArray(array $csvRow, array $csvCols): array {
		$articleNumber = $csvRow[$this->itswCarServices->validate($csvCols, 'artikelnummer')];
		$name = $this->cleanName($csvRow[$this->itswCarServices->validate($csvCols, 'name')]);
		$description = $csvRow[$this->itswCarServices->validate($csvCols, 'kurzbeschreibung')];
		$descriptionLong = $csvRow[$this->itswCarServices->validate($csvCols, 'beschreibung')];
		$price = $this->makePrice($csvRow[$this->itswCarServices->validate($csvCols, 'vk')]);
		$supplierPrice = $this->makePrice($csvRow[$this->itswCarServices->validate($csvCols, 'ek')]);
		$inStock = (int)$csvRow[$this->itswCarServices->validate($csvCols, 'bestand')];
		$stockMin = $csvRow[$this->itswCarServices->validate($csvCols, 'mindestbestand')];
		$tax = (float)$csvRow[$this->itswCarServices->validate($csvCols, 'ust')];
		$ean = $csvRow[$this->itswCarServices->validate($csvCols, 'ean')];
		$weight = $csvRow[$this->itswCarServices->validate($csvCols, 'gewicht')];
		
		
		$images[] = $csvRow[$this->itswCarServices->validate($csvCols, 'bild')];
		$images[] = $csvRow[$this->itswCarServices->validate($csvCols, 'bildk')];
		$images[] = $csvRow[$this->itswCarServices->validate($csvCols, 'ownvar7')];
		$images[] = $csvRow[$this->itswCarServices->validate($csvCols, 'ownvar8')];
		$images[] = $csvRow[$this->itswCarServices->validate($csvCols, 'ownvar9')];
		$images[] = $csvRow[$this->itswCarServices->validate($csvCols, 'ownvar9')];
		
		$afterbyDeeplink = $csvRow[$this->itswCarServices->validate($csvCols, 'produktdeeplink')];
		$shippingTime = $csvRow[$this->itswCarServices->validate($csvCols, 'deliverytime')];
		$googleProductCategory = $csvRow[$this->itswCarServices->validate($csvCols, 'googleproductcategory')];
		$supplierNumber = $csvRow[$this->itswCarServices->validate($csvCols, 'ownvar2')];
		$keywords = $csvRow[$this->itswCarServices->validate($csvCols, 'searchtags')];
		$afterbuyId = $csvRow[$this->itswCarServices->validate($csvCols, 'id')];
		
		$active = TRUE;
		$supplierId = 1;
		$width = '0';
		$height = '0';
		$length = '0';
		
		return [
			'name'              => $name,
			'description'       => $description,
			'descriptionLong'   => $descriptionLong,
			'keywords'          => $keywords,
			'active'            => $active,
			'taxId'             => $this->getTaxId($tax),
			'supplierId'        => $supplierId,
			'lastStock'         => TRUE,
			'notification'      => TRUE,
			
			'categories'	    => $this->buildCategoriesArray($articleNumber),
			'images'		    => $this->buildImagesArray($images, $name),
			
			'mainDetail' => [
				'number'	    => $articleNumber,
				'weight'	    => $weight,
				'width'		    => $width,
				'len'		    => $length,
				'height'	    => $height,
				'inStock'	    => $inStock,
				'active'	    => $active,
				'lastStock'     => TRUE,
				'shippingTime'  => $shippingTime,
				'ean'           => $ean,
				
				'prices' => [
					[
						'customerGroupKey' => 'EK',
						'price'            => $price,
					]
				],
				
				'attribute'	=> [
					'afterbuyid'                => $afterbuyId,
					'afterbuylink'              => $afterbyDeeplink,
					'googleproductcategoryid'   => $googleProductCategory
				],
			]
		];
	}
}