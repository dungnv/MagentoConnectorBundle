<?php

namespace Pim\Bundle\MagentoConnectorBundle\Reader\MongoDBODM;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityRepository;
use Pim\Bundle\BaseConnectorBundle\Reader\Doctrine\ODMProductReader;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Manager\CompletenessManager;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\CatalogBundle\Repository\ProductRepositoryInterface;
use Pim\Bundle\TransformBundle\Converter\MetricConverter;

/**
 * Delta product reader for MongoDB
 *
 * @author Romain Monceau <romain@akeneo.com>
 */
class DeltaProductReader extends ODMProductReader
{
    /** @var EntityRepository */
    protected $deltaRepository;

    /**
     * @param ProductRepositoryInterface $repository
     * @param ChannelManager             $channelManager
     * @param CompletenessManager        $completenessManager
     * @param MetricConverter            $metricConverter
     * @param DocumentManager            $documentManager
     * @param boolean                    $missingCompleteness
     * @param EntityRepository           $deltaRepository
     */
    public function __construct(
        ProductRepositoryInterface $repository,
        ChannelManager $channelManager,
        CompletenessManager $completenessManager,
        MetricConverter $metricConverter,
        DocumentManager $documentManager,
        $missingCompleteness = true,
        EntityRepository $deltaRepository = null
    ) {
        parent::__construct(
            $repository,
            $channelManager,
            $completenessManager,
            $metricConverter,
            $documentManager,
            $missingCompleteness
        );

        $this->deltaRepository = $deltaRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $this->documentManager->clear();

        if (!$this->executed) {
            $this->executed = true;
            if (!is_object($this->channel)) {
                $this->channel = $this->channelManager->getChannelByCode($this->channel);
            }

            if ($this->missingCompleteness) {
                $this->completenessManager->generateMissingForChannel($this->channel);
            }

            $this->query = $this->repository
                ->buildByChannelAndCompleteness($this->channel)
                ->getQuery();

            $this->products = $this->getQuery()->execute();

            // MongoDB Cursor are not positioned on first element (whereas ArrayIterator is)
            // as long as getNext() hasn't be called
            $this->products->getNext();
        }

        $result = $this->products->current();

        if ($result) {
            while (!$this->needsUpdate($result)) {
                $result = $this->products->next();
                if (null === $result) {
                    return null;
                }
            }

            $this->metricConverter->convert($result, $this->channel);
            $this->stepExecution->incrementSummaryInfo('read');
            $this->products->next();
        }

        return $result;
    }

    /**
     * @param ProductInterface $product
     *
     * @return bool
     */
    protected function needsUpdate(ProductInterface $product)
    {
        var_dump($product->getId());
        var_dump($this->stepExecution->getJobExecution()->getJobInstance()->getCode());

        $delta = $this->deltaRepository->findOneBy(
            [
                'productId' => $product->getId(),
                'jobInstance' => $this->stepExecution->getJobExecution()->getJobInstance()
            ]
        );

        return null === $delta || $delta->getLastExport() < $product->getUpdated();
    }
}
