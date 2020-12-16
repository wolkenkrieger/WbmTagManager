<?php
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   08.12.2020
 * Zeit:    10:20
 * Datei:   CategoriesImporterCommand.php
 * @package ItswCar\Commands
 */

namespace ItswCar\Commands;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Shopware\Commands\ShopwareCommand;
use Shopware\Models\Category\Category;
use Shopware\Models\Attribute\Category as CategoryAttribute;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class CategoriesImporterCommand extends ShopwareCommand {
	private $docPath;
	private $itswCarServices;
	private $entityManager;
	private $fileName = 'kategorien_ebay_shop.csv';
	
	/**
	 * CategoriesImporterCommand constructor.
	 * @param \ItswCar\Components\Services\Services $itswCarServices
	 */
	public function __construct(\ItswCar\Components\Services\Services $itswCarServices) {
		$this->itswCarServices = $itswCarServices;
		
		$this->setContainer($itswCarServices->getContainer());
		$this->entityManager = $itswCarServices->getModelManager();
		$this->environment = $itswCarServices->getEnvironment();
		$this->docPath = $itswCarServices->getDocPath();
		
		parent::__construct();
	}
	
	/**
	 * @inheritDoc
	 */
	protected function configure()	{
		$this
			->setName('itsw:importer:categories')
			->setDescription('Imports categories from a csv.')
			->addOption(
				'file',
				'f',
				InputOption::VALUE_OPTIONAL,
				'csv file'
			)
			->setHelp('The <info>%command.name%</info> imports categories from a csv file. Standard file name is ' . $this->docPath . $this->fileName);
	}
	
	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return int|void|null
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$fileName = $input->getOption('file')?:$this->docPath.$this->fileName;
		$rootId = 5;
		$level1Count = 0;
		$level2Count = 0;
		
		if (!file_exists($fileName)) {
			throw new FileNotFoundException(sprintf("%s not found", $fileName));
		}
		$_handle=file($fileName);
		$handle = fopen( $fileName, 'r' );
		$csvHeader = fgetcsv($handle, 8192, ';', '"' );
		$csvCols = array_flip( $csvHeader );
		
		$catIdCol	    = $csvCols['KategorieID'];
		$catLevel1Col	= $csvCols['Kategorieebene 1'];
		$catLevel2Col	= $csvCols['Kategorieebene 2'];
		
		$categories = [];
		
		while( $csvRow = fgetcsv( $handle, 8192, ';', '"' ) ) {
			$catId = (int)$csvRow[$catIdCol];
			$catLevel1 = $csvRow[$catLevel1Col];
			$catLevel2 = $csvRow[$catLevel2Col];
			
			$tmp = explode('*', $catLevel1);
			$catLevel1 = reset($tmp);
			
			$categories[$catLevel1]['ebayCategoryId'] = $catId;
			
			if ($catLevel2) {
				$categories[$catLevel1]['children'][] = [
					'name' => $catLevel2,
					'ebayCategoryId' => $catId
				];
			} else {
				$categories[$catLevel1]['children'][] = [
					'name' => '',
					'ebayCategoryId' => 0
				];
			}
		}
		
		foreach($categories as $name => $values) {
			try {
				$level1 = $this->findOrCreate($name, $rootId, $values['ebayCategoryId']);
				if ($level1) {
					$level1Count++;
					$this->itswCarServices->showProgress($level1Count + $level2Count, count($_handle) - 1, $output);
					foreach ($values['children'] as $child) {
						try {
							if ($child['name'] && $level2 = $this->findOrCreate($child['name'], $level1->getId(), $child['ebayCategoryId'])) {
								$level2Count++;
								$this->itswCarServices->showProgress($level1Count + $level2Count, count($_handle) - 1, $output);
							}
						} catch (OptimisticLockException | ORMException $e) {
							$output->writeln(sprintf("could not find or create category [name: %s, parentId: %d, ebayCategoryId: %d] >> %s", $child['name'], $level1->getId(), $child['ebayCategoryId'], $e->getMessage()));
						}
					}
				}
			} catch (OptimisticLockException | ORMException $e) {
				$output->writeln(sprintf("could not find or create category [name: %s, parentId: %d, ebayCategoryId: %d] >> %s", $name, $rootId, $values['ebayCategoryId'], $e->getMessage()));
			}
		}
		return 0;
	}
	
	/**
	 * @param $name
	 * @param $parentId
	 * @param $ebayCategoryId
	 * @return object|\Shopware\Models\Category\Category|null
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	private function findOrCreate($name, $parentId, $ebayCategoryId) {
		$parentCategory = $this->entityManager->getRepository(Category::class)
			->find($parentId);
		
		if ($parentCategory) {
			$category = $this->entityManager->getRepository(Category::class)
				->findOneBy([
					'name' => $name,
					'parentId' => $parentId
				]);
			
			if (!$category) {
				$attribute = new CategoryAttribute();
				$attribute->setEbayCategoryId($ebayCategoryId);
				
				$category = new Category();
				$category
					->setActive(TRUE)
					->setName($name)
					->setParent($parentCategory)
					->setAttribute($attribute);
				$this->entityManager->persist($category);
			} else {
				$this->entityManager->persist($category);
				$category->getAttribute()->setEbayCategoryId($ebayCategoryId);
			}
			$this->entityManager->flush();
			
			return $category;
		}
		
		return NULL;
	}
}