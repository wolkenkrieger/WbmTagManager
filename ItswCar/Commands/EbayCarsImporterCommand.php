<?php
/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   11.12.2020
 * Zeit:    18:08
 * Datei:   EbayCarsImporterCommand.php
 * @package ItswCar\Commands
 */

namespace ItswCar\Commands;

use Doctrine\Common\Collections\ArrayCollection;
use ItswCar\Models\Car;
use ItswCar\Models\EbayPlatform;
use ItswCar\Models\KbaCodes;
use ItswCar\Models\Manufacturer;
use ItswCar\Models\Model;
use ItswCar\Models\Type;
use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;

class EbayCarsImporterCommand extends ShopwareCommand {
	private $docPath;
	private $itswCarServices;
	private $entityManager;
	private $fileName = 'fahrzeugliste_de_20.07_q3.csv';
	
	/**
	 * EbayCarsImporterCommand constructor.
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
	protected function configure() {
		$this
			->setName('itsw:importer:cars')
			->setDescription('Imports cars from a eBay Fahrzeugverwendungsliste.')
			->addOption(
				'force-update',
				'u',
				InputOption::VALUE_REQUIRED,
				'force updating cars',
				1
			)
			->addOption(
				'file',
				'f',
				InputOption::VALUE_OPTIONAL,
				'car list (Fahrzeugverwendungsliste) IMPORTANT: no pathes',
				$this->fileName
			)
			->addOption(
				'newest',
				'',
				InputOption::VALUE_OPTIONAL,
				'scan for the newest list version',
				1
			)
			->setHelp('The <info>%command.name%</info> imports cars from a eBay Fahrzeugverwendungsliste');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->fileName = $input->getOption('file')?:$this->fileName;
		$forceUpdate = ((int)$input->getOption('force-update') === 1) || (strtolower($input->getOption('force-update')) === 'on');
		$forceNewest = ((int)$input->getOption('newest') === 1 || (strtolower($input->getOption('newest')) === 'on'));
		
		if ($forceNewest) {
			$this->getNewestListVersion();
		}
		
		$fileName = $this->docPath.$this->fileName;
		
		if (!file_exists($fileName)) {
			throw new FileNotFoundException(sprintf("%s not found", $fileName));
		}
		
		$_handle = file($fileName, FILE_SKIP_EMPTY_LINES);
		$handle = fopen($fileName, 'r');
		$csvHeader = fgetcsv($handle, 8192, ';', '"' );
		$csvCols = array_flip( $csvHeader );
		
		$rowCount = 0;
		$data = [];
		while( $csvRow = fgetcsv( $handle, 8192, ';', '"' ) ) {
			
			$data['tecdocId'] = (int)$csvRow[$this->itswCarServices->validate($csvCols, 'K-Type')];
			$data['manufacturer'] = trim($csvRow[$this->itswCarServices->validate($csvCols, 'Marke_Make_EN')]);
			$data['model'] = trim($csvRow[$this->itswCarServices->validate($csvCols, 'Modell_Model_EN')]);
			$data['type'] = trim($csvRow[$this->itswCarServices->validate($csvCols, 'Typ_Type_EN')]);
			$data['platform'] = trim($csvRow[$this->itswCarServices->validate($csvCols, 'Plattform_Platform_EN')]);
			
			$data['years'] = $this->splitYears($csvRow[$this->itswCarServices->validate($csvCols, 'Baujahr_ProductionPeriod_EN')]);
			$data['engine'] = $this->splitEngine($csvRow[$this->itswCarServices->validate($csvCols, 'Motor_Engine_EN')]);
			$data['codes'] = $this->splitCodes($csvRow[$this->itswCarServices->validate($csvCols, 'HSN_TSN_nur_zur_Hilfe')], $data['tecdocId']);
			
			$this->importData($data, $forceUpdate);
			$this->itswCarServices->showProgress(++$rowCount, count($_handle) - 1, $output);
			
		}
	}
	
	/**
	 * @param array $data
	 * @param bool  $forceUpdate
	 */
	private function importData(array $data, bool $forceUpdate) {
		$new = FALSE;
		if (!$manufacturer = $this->entityManager->getRepository(Manufacturer::class)
		->findOneByName($data['manufacturer'])) {
			$manufacturer = new Manufacturer();
			$manufacturer->setName($data['manufacturer']);
			$manufacturer->setDisplay($data['manufacturer']);
			$this->entityManager->persist($manufacturer);
		}
		
		if (!$model = $this->entityManager->getRepository(Model::class)
			->findOneByName($data['model'])) {
			$model = new Model();
			$model->setName($data['model']);
			$model->setDisplay($data['model']);
			$this->entityManager->persist($model);
		}
		
		if (!$type = $this->entityManager->getRepository(Type::class)
			->findOneByName($data['type'])) {
			$type = new Type();
			$type->setName($data['type']);
			$type->setDisplay($data['type']);
			$this->entityManager->persist($type);
		}
		
		if (!$platform = $this->entityManager->getRepository(EbayPlatform::class)
			->findOneByName($data['platform'])) {
			$platform = new EbayPlatform();
			$platform->setName($data['platform']);
			$platform->setDisplay($data['platform']);
			$this->entityManager->persist($platform);
		}
		
		$car = $this->entityManager->getRepository(Car::class)
		->findOneByTecdocId($data['tecdocId']);

		if (!$car) {
			$car = new Car();
			$new = TRUE;
		}
		
		if ($new || ($car && $forceUpdate)) {
			$new = FALSE;
			$this->cleanKbaCodes($data['tecdocId']);
			$car->setTecdocId($data['tecdocId']);
			$car->setManufacturer($manufacturer);
			$car->setModel($model);
			$car->setType($type);
			$car->setPlatform($platform);
			$car->setBuildFrom($data['years']['buildFrom']);
			$car->setBuildTo($data['years']['buildTo']);
			$car->setCcm($data['engine']['ccm']);
			$car->setKw($data['engine']['kw']);
			$car->setPs($data['engine']['ps']);
			$car->setCodes($data['codes']);
			
			$this->entityManager->persist($car);
		}
		
		$this->entityManager->flush();
	}
	
	/**
	 * @param $tecdocId
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 */
	private function cleanKbaCodes($tecdocId): void {
		$codes = $this->entityManager->getRepository(KbaCodes::class)
			->findByTecdocId($tecdocId);
		
		foreach ($codes as $code) {
			$this->entityManager->persist($code);
			$this->entityManager->remove($code);
			$this->entityManager->flush($code);
		}
	}
	
	/**
	 * @return \AppendIterator|\Iterator|\Symfony\Component\Finder\SplFileInfo[]|null
	 */
	private function getNewestListVersion() {
		$finder = Finder::create()
			->files()
			->name('fahrzeugliste_*.csv')
			->in($this->docPath)
			->sortByName();
		
		if ($finder->hasResults()) {
			foreach ($finder as $file) {
				$this->fileName = $file->getFileName();
			}
		}
	}
	
	/**
	 * @param $codes
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	private function splitCodes($codes, $tecdocId): ArrayCollection {
		if (!$codes || empty($codes)) {
			return new ArrayCollection([]);
		}
		
		$exploded = explode('<>', $codes);
		$codes = [];
		
		foreach($exploded as $fragment) {
			$tmp = explode('|', $fragment);
			$code = new KbaCodes();
			$codes[] = $code->fromArray([
				'tecdocId' => $tecdocId,
				'hsn' => $tmp[0],
				'tsn' => ($tmp[1]??'000')
			]);
		}
		
		return new ArrayCollection($codes);
	}
	
	/**
	 * @param $years
	 * @return array
	 */
	private function splitYears($years): array {
		if (!$years || empty($years)) {
			return [
				'buildFrom' => \DateTimeImmutable::createFromFormat(sprintf('%d-01-01 00:00:00', (int)date('Y'))),
				'buildTo' => \DateTimeImmutable::createFromFormat(sprintf('%d-12-31 00:00:00', (int)date('Y')))
			];
		}
		
		$exploded = explode('-', $years);
		$years = [];
	
		$buildFrom = \DateTimeImmutable::createFromFormat('Y/m', $exploded[0]);
		$buildTo = (isset($exploded[1]))?\DateTimeImmutable::createFromFormat('Y/m', $exploded[1]): \DateTimeImmutable::createFromFormat(sprintf('%d-12-31 00:00:00', (int)date('Y')));
		
		$years['buildFrom'] = $buildFrom;
		$years['buildTo'] = $buildTo;
		
		return $years;
	}
	
	/**
	 * @param $engine
	 * @return array
	 */
	private function splitEngine($engine): array {
		$_engine = [
			'ccm'   => 0,
			'kw'    => 0,
			'ps'    => 0
		];
		
		if (!$engine || empty($engine)) {
			return $_engine;
		}
		
		$exploded = explode(',', $engine);
		
		foreach($exploded as $fragment) {
			if (FALSE !== stripos($fragment, 'ccm')) {
				$_engine['ccm'] = (int)trim(stristr($fragment, 'ccm', TRUE));
			} else if (FALSE !== stripos($fragment, 'kw')) {
				$_engine['kw'] = (int)trim(stristr($fragment, 'kw', TRUE));
			} else if (FALSE !== stripos($fragment, 'ps')) {
				$_engine['ps'] = (int)trim(stristr($fragment, 'ps', TRUE));
			}
		}
		
		return $_engine;
	}
	
	/**
	 * @param string $string
	 * @return string
	 */
	private function cleanString(string $string): string {
		return strtolower(str_ireplace(' ', '_', trim($string)));
	}
}