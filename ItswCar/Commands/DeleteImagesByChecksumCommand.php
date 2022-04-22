<?php declare(strict_types=1);
/**
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 21.04.2022
 * Time: 12:50
 * File: DeleteImagesByChecksum.php
 * @package ItswCar\Commands
 */

namespace ItswCar\Commands;

use Doctrine\ORM\QueryBuilder;
use Shopware\Bundle\MediaBundle\MediaService;
use Shopware\Commands\ShopwareCommand;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Media\Album;
use Shopware\Models\Media\Media;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class DeleteImagesByChecksumCommand extends ShopwareCommand {
	
	/** @var \Shopware\Components\Model\ModelManager  */
	private ModelManager $modelManager;
	
	/** @var \Shopware\Bundle\MediaBundle\MediaService  */
	private MediaService $mediaService;
	
	/** @var string  */
	private string $fileName = 'fallbackorigin.jpg';
	
	/** @var string  */
	private string $docPath;
	
	/** @var array */
	private array $collectionsToUse;
	
	/** @var array  */
	private array $collectionsToIgnore;
	
	/** @var int  */
	private int $stack = 150;
	
	/** @var int  */
	private int $offset;
	
	/** @var bool  */
	private bool $createThumbnails;
	
	/** @var \Doctrine\ORM\QueryBuilder  */
	private QueryBuilder $mediaQuery;
	
	/**
	 * @param \Shopware\Components\Model\ModelManager   $modelManager
	 * @param \Shopware\Bundle\MediaBundle\MediaService $mediaService
	 */
	public function __construct(ModelManager $modelManager, MediaService $mediaService) {
		$this->setContainer(Shopware()->Container());
		$this->modelManager = $modelManager;
		$this->mediaService = $mediaService;
		$this->docPath = $this->getContainer()->get('itsw.helper.config')->getDocPath();
		
		parent::__construct();
	}
	
	/**
	 * @return void
	 */
	protected function configure(): void {
		$this
			->setName('itsw:media:delete:bychecksum')
			->setDescription('Finds images by md5-checksum und deletes them. Can also run as stack-execution, for specific folders only and with excluded folders')
			->addOption('file','f',InputOption::VALUE_OPTIONAL,'image to compare (IMPORTANT: no pathes)', $this->fileName)
			->addOption('stack', 's', InputOption::VALUE_OPTIONAL, 'process amount per iteration', $this->stack)
			->addOption('offset', 'o', InputOption::VALUE_OPTIONAL, 'process amount per iteration', 0)
			->addOption('setCollection', 'c', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
				'only search medias for specified collection. Example: `itsw:media:deletefallback -c 12`')
			->addOption('ignoreCollection', 'i', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'ignores specified collection')
			->addOption('createThumbnails', 't', InputOption::VALUE_OPTIONAL, 'create album thumbnails', 0)
		;
	}
	
	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return void
	 */
	protected function initialize(InputInterface $input, OutputInterface $output): void {
		parent::initialize($input, $output);
	}
	
	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return int
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$memoryLimit = @ini_get('memory_limit');
		@ini_set('memory_limit', '-1');
		
		$this->collectionsToUse = $input->getOption('setCollection') ?? [];
		$this->collectionsToIgnore = $input->getOption('ignoreCollection') ?? [];
		$this->fileName = $input->getOption('file')?? $this->fileName;
		$this->offset = (int)$input->getOption('offset');
		$this->createThumbnails = (bool)$input->getOption('createThumbnails');
		
		$mediaCount = $this->countMedias($this->collectionsToUse, $this->collectionsToIgnore);
		$this->stack = $input->getOption('stack') ? (int)$input->getOption('stack') :  $mediaCount;
		
		$output->writeln('STACK: ' . $this->stack);
		$output->writeln('OFFSET: ' . $this->offset);
		$output->writeln('CREATE THUMBNAILS: ' . ($this->createThumbnails? 'TRUE' : 'FALSE'));
		$output->writeln('');
		
		$this->buildImageStack($output, $mediaCount);
		
		$output->writeln('');
		
		@ini_set('memory_limit', $memoryLimit);
		
		return 0;
	}
	
	
	/**
	 * @param array $useCollections
	 * @param array $ignoreCollections
	 * @return int
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	private function countMedias(array $useCollections = [], array $ignoreCollections = []): int {
		$this->mediaQuery = $this->createMediaQuery();
		$this->extendMediaQuery($useCollections, $ignoreCollections);
		
		return (int)$this->mediaQuery
			->select('COUNT(media.id)')
			->getQuery()
			->disableResultCache()
			->getSingleScalarResult();
	}
	
	/**
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	private function createMediaQuery(): QueryBuilder	{
		return $this->modelManager->createQueryBuilder()
			->select('media')
			->from(Media::class, 'media')
			->where('media.type = :type')
			->andWhere('media.albumId != :garbageId')
			->setParameter(':type', Media::TYPE_IMAGE)
			->setParameter(':garbageId', Album::ALBUM_GARBAGE)
			;
	}
	
	/**
	 * @param $stack
	 * @param $offset
	 * @param array $useCollections
	 * @param array $ignoreCollections
	 * @return float|int|mixed[]|string
	 */
	private function findByOffset($stack, $offset, array $useCollections = [], array $ignoreCollections = []) {
		$this->mediaQuery = $this->createMediaQuery();
		$this->extendMediaQuery($useCollections, $ignoreCollections);
		
		return $this->mediaQuery
			->setFirstResult($offset)
			->setMaxResults($stack)
			->getQuery()
			->disableResultCache()
			->getResult();
	}
	
	/**
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @param                                                   $mediaCount
	 * @return void
	 */
	private function buildImageStack(OutputInterface $output, $mediaCount): void {
		$progress = new ProgressBar($output, $mediaCount);
		$progress->start();
		$progress->setProgress($this->offset);
		$fallbackSize = filesize($this->docPath.$this->fileName);
		$fallbackHash = md5_file($this->docPath.$this->fileName);
		
		/** @var Album|null $album */
		$album = Shopware()->Container()->get(ModelManager::class)->getRepository(Album::class)->find(Album::ALBUM_GARBAGE);
		
		for ($i = $this->offset; $i <= $mediaCount + $this->stack; $i += $this->stack) {
			@gc_disable();
			$stackMedia = $this->findByOffset($this->stack, $i,	$this->collectionsToUse, $this->collectionsToIgnore);
			$this->handleImagesByStack($album, $fallbackHash, $fallbackSize, $output, $stackMedia, $progress);
			@gc_enable();
			@gc_collect_cycles();
		}
		
		$progress->finish();
	}
	
	/**
	 * @param \Shopware\Models\Media\Album|null                 $album
	 * @param string                                            $fallbackHash
	 * @param int                                               $fallbackSize
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @param                                                   $stackMedia
	 * @param \Symfony\Component\Console\Helper\ProgressBar     $progress
	 * @return void
	 */
	private function handleImagesByStack(?Album $album, string $fallbackHash, int $fallbackSize, OutputInterface $output, $stackMedia, ProgressBar $progress): void {
		foreach ($stackMedia as $media) {
			try {
				$mediaSize = filesize(Shopware()->DocPath() . $this->mediaService->encode($media->getPath()));
				
				if ($mediaSize === $fallbackSize) {
					$mediaHash = md5_file(Shopware()->DocPath() . $this->mediaService->encode($media->getPath()));
					
					if ($mediaHash === $fallbackHash) {
						if ($album) {
							$media->setAlbum($album);
							$media->setAlbumId(Album::ALBUM_GARBAGE);
							
							if ($this->createThumbnails) {
								$this->createThumbnailsForMovedMedia($media, $album);
							}
						}
						
						$this->modelManager->persist($media);
						$this->modelManager->flush();
					}
				}
				$progress->advance();
			} catch (Throwable $e) {
				$output->writeln($media->getPath() . ' => ' . $e->getMessage());
			}
		}
	}
	
	/**
	 * @param \Shopware\Models\Media\Media      $media
	 * @param \Shopware\Models\Media\Album|null $album
	 * @return void
	 */
	private function createThumbnailsForMovedMedia(Media $media, ?Album $album): void {
		$media->removeAlbumThumbnails($album->getSettings()->getThumbnailSize(), $media->getFileName());
		$media->createAlbumThumbnails($album);
	}
	
	/**
	 * @param array $useCollections
	 * @param array $ignoreCollections
	 * @return void
	 */
	private function extendMediaQuery(array $useCollections, array $ignoreCollections): void {
		if (!empty($useCollections)) {
			$and_cond = $this->mediaQuery->expr()->orX();
			foreach ($useCollections as $collection) {
				$and_cond->add($this->mediaQuery->expr()->eq('media.albumId', $this->mediaQuery->expr()->literal($collection)));
			}
			$this->mediaQuery->andWhere($and_cond);
		}
		if (!empty($ignoreCollections)) {
			$and_cond = $this->mediaQuery->expr()->orX();
			foreach ($ignoreCollections as $collection) {
				$and_cond->add($this->mediaQuery->expr()->neq('media.albumId', $this->mediaQuery->expr()->literal($collection)));
			}
			$this->mediaQuery->andWhere($and_cond);
		}
	}
}