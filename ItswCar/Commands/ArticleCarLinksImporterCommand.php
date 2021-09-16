<?php
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   12.12.2020
 * Zeit:    15:43
 * Datei:   ArticleCarLinksImporterCommand.php
 * @package ItswCar\Commands
 */

namespace ItswCar\Commands;


use ItswCar\Components\Services\Services;
use ItswCar\Models\ArticleCarLinks;
use ItswCar\Models\Car;
use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class ArticleCarLinksImporterCommand extends ShopwareCommand {
	private $entityManager;
	private $docPath;
	private $environment;
	private $itswCarServices;
	private $fileName = 'partsfitment_export.csv';
	
	/**
	 * ArticleCarLinksImporterCommand constructor.
	 * @param \ItswCar\Components\Services\Services $itswCarServices
	 */
	public function __construct(Services $itswCarServices) {
		$this->setContainer(Shopware()->Container());
		$this->itswCarServices = $itswCarServices;
		$this->entityManager = Shopware()->Models();
		$this->docPath = $this->getContainer()->get('itsw.helper.config')->getDocPath();
		
		parent::__construct();
	}
	/**
	 *
	 */
	public function configure() {
		$this
			->setName('itsw:importer:links')
			->setDescription('Imports linkage between articles and cars from a csv.')
			->addOption(
				'force-update',
				'u',
				InputOption::VALUE_REQUIRED,
				'force updating links',
				1
			)
			->addOption(
				'file',
				'f',
				InputOption::VALUE_OPTIONAL,
				'links csv file',
				$this->fileName
			)
			->setHelp('The <info>%command.name%</info> imports linkage between articles and cars from a csv file.');
	}
	
	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return int|null
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	public function execute(InputInterface $input, OutputInterface $output): ?int {
		$environment = $input->getOption('env');
		$forceUpdate = ((int)$input->getOption('force-update') === 1) || (strtolower($input->getOption('force-update')) === 'on');
		$fileName = $this->docPath.($input->getOption('file')?:$this->fileName);
		
		if (!file_exists($fileName)) {
			throw new FileNotFoundException(sprintf("%s not found", $fileName));
		}
		
		$_handle = file($fileName, FILE_SKIP_EMPTY_LINES);
		$handle = fopen($fileName, 'r');
		$csvHeader = fgetcsv($handle, 8192, '|', '"' );
		$csvCols = array_flip( $csvHeader );
		$rowCount = 0;
		
		while ($csvRow = fgetcsv($handle, 8192, '|', '"')) {
			$afterbuyId = $csvRow[$this->itswCarServices->validate($csvCols, 'ProductId')];
			$tecdocId = (int)$csvRow[$this->itswCarServices->validate($csvCols, 'KTypNr')];
			$restriction = $csvRow[$this->itswCarServices->validate($csvCols, 'FitmentComments')]??'';
			
			if ($detail = $this->entityManager->getRepository(ArticleCarLinks::class)
				->getDetailsByAttributesQuery([
					'afterbuyId' => $afterbuyId
				])
				->getOneOrNullResult()) {
				
				$articleCarLinks = $this->entityManager->getRepository(ArticleCarLinks::class)
				->findByTecdocId($tecdocId);
				
				foreach($articleCarLinks as $articleCarLink) {
					$this->entityManager->persist($articleCarLink);
					$this->entityManager->remove($articleCarLink);
				}
				$this->entityManager->flush();
				
				if ($car = $this->entityManager->getRepository(Car::class)
				->findOneBy([
					'tecdocId' => $tecdocId
					])) {
					$articleCarLink = new ArticleCarLinks();
					$articleCarLink->setTecdocId($car->getTecdocId());
					$articleCarLink->setArticleDetailsId($detail->getId());
					$articleCarLink->setRestriction($restriction);
					$this->entityManager->persist($articleCarLink);
					$this->entityManager->flush();
				}
			}
			
			$this->itswCarServices->showProgress(++$rowCount, count($_handle) - 1, $output);
		}
		
		return 0;
	}
	
}