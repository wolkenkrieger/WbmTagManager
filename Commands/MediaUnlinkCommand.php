<?php declare(strict_types=1);
/**
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 25.04.2022
 * Time: 10:59
 * File: MediaUnlinkCommand.php
 * @package ItswCar\Commands
 */

namespace ItswCar\Commands;

use Doctrine\ORM\Query\ResultSetMapping;
use Shopware\Bundle\MediaBundle\MediaService;
use Shopware\Commands\ShopwareCommand;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Shopware\Models\Article\Image;

class MediaUnlinkCommand extends ShopwareCommand {
	
	/** @var \Shopware\Components\Model\ModelManager  */
	private ModelManager $modelManager;
	
	/** @var \Shopware\Bundle\MediaBundle\MediaService  */
	private MediaService $mediaService;
	
	/** @var int  */
	private int $stack = 150;
	
	/** @var bool  */
	private bool $full = FALSE;
	
	/** @var \Symfony\Component\Console\Helper\ProgressBar  */
	private ProgressBar $progress;
	
	/**
	 * @param \Shopware\Components\Model\ModelManager   $modelManager
	 * @param \Shopware\Bundle\MediaBundle\MediaService $mediaService
	 */
	public function __construct(ModelManager $modelManager, MediaService $mediaService) {
		$this->modelManager = $modelManager;
		$this->mediaService = $mediaService;
		
		parent::__construct();
	}
	
	/**
	 * @return void
	 */
	public function configure(): void {
		$this
			->setName('itsw:media:unlink')
			->setDescription('Unlink non existent media from articles')
			->addOption('stack', 's', InputOption::VALUE_OPTIONAL, 'process amount per iteration', $this->stack)
			->addOption('full', 'f', InputOption::VALUE_OPTIONAL, 'run all 3 steps?', 0)
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
	 * @return void
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Doctrine\Persistence\Mapping\MappingException
	 */
	public function execute(InputInterface $input, OutputInterface $output): void {
		$memoryLimit = @ini_get('memory_limit');
		@ini_set('memory_limit', '-1');
		
		$this->stack = (int)$input->getOption('stack');
		$this->full = (boolean)$input->getOption('full');
		
		$this->progress = new ProgressBar($output);
		
		$this->stepOne($output);
		$this->stepTwo($output);
		
		if ($this->full) {
			$this->stepThree($output);
		}
		
	}
	
	/**
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return void
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Doctrine\Persistence\Mapping\MappingException
	 */
	protected function stepOne(OutputInterface $output) {
		$output->writeln('');
		$output->writeln('Step 1: Unlink non existent images from articles with at least 2 linked images');
		if ($articleImagesCount = $this->countArticleImages(1)) {
			$stackSize = max($this->stack, $articleImagesCount);
			$output->writeln('STACK: ' . $stackSize);
			$this->progress->start($articleImagesCount);
			
			$count = 0;
			foreach($this->buildQuery(1)->getQuery()->toIterable() as $articleImage) {
				$found = FALSE;
				$images = $this->modelManager->getRepository(Image::class)->findBy([
					'articleId' => $articleImage->getArticle()->getId()
				], [
					'position' => 'ASC'
				]);
				
				foreach($images as $image) {
					if (NULL === $image->getMedia()) {
						$this->modelManager->remove($image);
						$found = TRUE;
					} elseif ($found) {
						$image->setPosition($image->getPosition() - 1);
						$image->setMain($image->getMain() - 1);
					}
				}
				
				if (++$count >= $stackSize) {
					$this->modelManager->flush();
					$this->modelManager->clear();
					$count = 0;
				}
				
				$this->progress->advance();
			}
			
			$this->progress->finish();
		}
		
		$this->modelManager->flush();
		
		$output->writeln('');
	}
	
	/**
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return void
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Doctrine\Persistence\Mapping\MappingException
	 */
	protected function stepTwo(OutputInterface $output): void {
		$output->writeln('');
		$output->writeln('Step 2: Unlink non existent images from articles with only 1 linked image');
		if ($articleImagesCount = $this->countArticleImages(2)) {
			$stackSize = max($this->stack, $articleImagesCount);
			$output->writeln('STACK: ' . $stackSize);
			$this->progress->start($articleImagesCount);
			
			$count = 0;
			
			foreach($this->buildQuery(2)->getQuery()->toIterable() as $articleImage) {
				$this->modelManager->remove($articleImage);
				
				if (++$count >= $stackSize) {
					$this->modelManager->flush();
					$this->modelManager->clear();
					$count = 0;
				}
				
				$this->progress->advance();
			}
			
			$this->progress->finish();
		}
		
		$this->modelManager->flush();
		
		$output->writeln('');
	}
	
	/**
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Doctrine\Persistence\Mapping\MappingException
	 * @throws \Doctrine\ORM\ORMException
	 */
	protected function stepThree(OutputInterface $output): void {
		$output->writeln('');
		$output->writeln('Optional Step 3: Check all linked images for physical existence');
		
		if ($articleImagesCount = $this->countArticleImages(3)) {
			$stackSize = max($this->stack, $articleImagesCount);
			$output->writeln('STACK: ' . $stackSize);
			
			$this->progress->start($articleImagesCount);
			
			$toDelete = [];
			$count = 0;
			$hits = 0;
			
			foreach($this->buildQuery(3)->getQuery()->toIterable() as $articleImage) {
				$found = FALSE;
				$images = $this->modelManager->getRepository(Image::class)->findBy([
					'articleId' => $articleImage->getArticle()->getId()
				], [
					'position' => 'ASC'
				]);
				
				foreach($images as $image) {
					$media = $image->getMedia();
					if (!$this->mediaService->has($media->getPath())) {
						$this->modelManager->remove($media);
						$toDelete[] = $image;
						$found = TRUE;
						++$hits;
					} elseif ($found) {
						$image->setPosition($image->getPosition() - 1);
						$image->setMain($image->getMain() - 1);
					}
				}
				
				if (++$count >= $stackSize) {
					$this->modelManager->flush();
					$this->modelManager->clear();
					$count = 0;
				}
				
				$this->progress->advance();
			}
			
			$this->progress->finish();
			
			if ($hits) {
				$output->writeln(sprintf('%d images deleted .... now unlinking them', $hits));
				
				$articleImagesCount = count($toDelete);
				
				$this->progress->start($articleImagesCount);
				
				foreach($toDelete as $image) {
					$this->modelManager->remove($image);
					$this->modelManager->flush($image);
					
					$this->progress->advance();
				}
				
				$this->progress->finish();
			}
		}
		$this->modelManager->flush();
	}
	
	/**
	 * @param int $step
	 * @return int
	 * @throws \Doctrine\ORM\NoResultException
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	private function countArticleImages(int $step = 1): int {
		$rsm = new ResultSetMapping();
		$rsm->addScalarResult('imageCount','imageCount');
		
		if ($step === 1) {
			$sql = 'SELECT COUNT(*) AS imageCount FROM (SELECT i.articleID, m.id AS mediaID FROM s_articles_img AS i LEFT JOIN s_media m ON i.media_id = m.id GROUP BY i.articleID HAVING COUNT(i.articleID) > 1 AND mediaID IS NULL) AS x';
		} elseif ($step === 2) {
			$sql = 'SELECT COUNT(*) AS imageCount FROM (SELECT i.articleID, m.id AS mediaID FROM s_articles_img AS i LEFT JOIN s_media m ON i.media_id = m.id GROUP BY i.articleID HAVING COUNT(i.articleID) = 1 AND mediaID IS NULL) AS x';
		} else {
			$sql = 'SELECT COUNT(*) AS imageCount FROM (SELECT DISTINCT i.articleID FROM s_articles_img AS i) AS x';
		}
		
		$query = $this->modelManager->createNativeQuery($sql, $rsm);
		
		return (int)$query->getSingleScalarResult();
	}
	
	/**
	 * @param int $step
	 * @return \Shopware\Components\Model\QueryBuilder
	 */
	private function buildQuery(int $step = 1): QueryBuilder {
		$builder = $this->modelManager->createQueryBuilder();
		
		$query = $builder
			->select(['images',  'media'])
			->from(Image::class, 'images')
			->leftJoin('images.media', 'media')
			->groupBy('images.articleId')
		;
		
		if ($step === 1) {
			$query->having($builder->expr()->isNull('media.id'));
			$query->andHaving($builder->expr()->gt('COUNT(images.articleId)', 1));
			
		} elseif ($step === 2) {
			$query->having($builder->expr()->isNull('media.id'));
			$query->andHaving($builder->expr()->eq('COUNT(images.articleId)', 1));
		} else {
			$query->orderBy('images.articleId');
		}
		
		return $query;
	}
}