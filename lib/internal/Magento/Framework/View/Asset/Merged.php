<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\View\Asset;

/**
 * \Iterator that aggregates one or more assets and provides a single public file with equivalent behavior
 */
class Merged implements \Iterator
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var MergeStrategyInterface
     */
    protected $mergeStrategy;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $assetRepo;

    /**
     * @var MergeableInterface[]
     */
    protected $assets;

    /**
     * @var string
     */
    protected $contentType;

    /**
     * @var bool
     */
    protected $isInitialized = false;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param MergeStrategyInterface $mergeStrategy
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param MergeableInterface[] $assets
     * @throws \InvalidArgumentException
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        MergeStrategyInterface $mergeStrategy,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        array $assets
    ) {
        $this->logger = $logger;
        $this->mergeStrategy = $mergeStrategy;
        $this->assetRepo = $assetRepo;

        if (!$assets) {
            throw new \InvalidArgumentException('At least one asset has to be passed for merging.');
        }
        /** @var $asset MergeableInterface */
        foreach ($assets as $asset) {
            if (!($asset instanceof MergeableInterface)) {
                throw new \InvalidArgumentException(
                    'Asset has to implement \Magento\Framework\View\Asset\MergeableInterface.'
                );
            }
            if (!$this->contentType) {
                $this->contentType = $asset->getContentType();
            } elseif ($asset->getContentType() != $this->contentType) {
                throw new \InvalidArgumentException(
                    "Content type '{$asset->getContentType()}' cannot be merged with '{$this->contentType}'."
                );
            }
        }
        $this->assets = $assets;
    }

    /**
     * Attempt to merge assets, falling back to original non-merged ones, if merging fails
     *
     * @return void
     */
    protected function initialize()
    {
        if (!$this->isInitialized) {
            $this->isInitialized = true;
            try {
                $mergedAsset = $this->createMergedAsset($this->assets);
                $this->mergeStrategy->merge($this->assets, $mergedAsset);
                $this->assets = [$mergedAsset];
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }

    /**
     * Create an asset object for merged file
     *
     * @param array $assets
     * @return MergeableInterface
     */
    private function createMergedAsset(array $assets)
    {
        $paths = [];
        /** @var MergeableInterface $asset */
        foreach ($assets as $asset) {
            $paths[] = $asset->getPath();
        }
        $paths = array_unique($paths);
        $filePath = md5(implode('|', $paths)) . '.' . $this->contentType;
        return $this->assetRepo->createArbitrary($filePath, self::getRelativeDir());
    }

    /**
     * {@inheritdoc}
     *
     * @return AssetInterface
     */
    public function current()
    {
        $this->initialize();
        return current($this->assets);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        $this->initialize();
        return key($this->assets);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->initialize();
        next($this->assets);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->initialize();
        reset($this->assets);
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        $this->initialize();
        return (bool)current($this->assets);
    }

    /**
     * Returns directory for storing merged files relative to STATIC_VIEW
     *
     * @return string
     */
    public static function getRelativeDir()
    {
        return Minified::CACHE_VIEW_REL . '/merged';
    }
}
