<?php

namespace Pim\Bundle\MagentoConnectorBundle\Reader\ORM;

use Pim\Bundle\BaseConnectorBundle\Reader\ORM\EntityReader;
use Pim\Bundle\CatalogBundle\Entity\Attribute;
use Doctrine\ORM\EntityManager;
use Pim\Bundle\MagentoConnectorBundle\Merger\MappingMerger;
use Pim\Bundle\ConnectorMappingBundle\Mapper\MappingCollection;

/**
 * ORM reader for product
 *
 * @author    Julien SAnchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AttributeReader extends EntityReader
{
    const IMAGE_ATTRIBUTE_TYPE = 'pim_catalog_image';

    /**
     * @var MappingMerger
     */
    protected $attributeMappingMerger;

    /**
     * @var string
     */
    protected $attributeMapping = '';

    /**
     * Set attribute mapping
     * @param string $attributeMapping
     *
     * @return AttributeProcessor
     */
    public function setAttributeMapping($attributeMapping)
    {
        $this->attributeMappingMerger->setMapping(json_decode($attributeMapping, true));

        return $this;
    }

    /**
     * Get attribute mapping
     * @return string
     */
    public function getAttributeMapping()
    {
        return json_encode($this->attributeMappingMerger->getMapping()->toArray());
    }

    /**
     * @param EntityManager $em                     The entity manager
     * @param string        $className              The entity class name used
     * @param MappingMerger $attributeMappingMerger Attribute mapping merger
     */
    public function __construct(EntityManager $em, $className, MappingMerger $attributeMappingMerger)
    {
        parent::__construct($em, $className);

        $this->attributeMappingMerger = $attributeMappingMerger;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $attribute = parent::read();

        $attributeMapping = $this->attributeMappingMerger->getMapping();

        while ($attribute !== null && $this->isAttriguteIgnored($attribute, $attributeMapping)) {
            $attribute = parent::read();
        }

        return $attribute;
    }

    /**
     * Is the given attribute ignored ?
     * @param Attribute $attribute
     *
     * @return boolean
     */
    protected function isAttriguteIgnored(Attribute $attribute, MappingCollection $attributeMapping)
    {
        return in_array(strtolower($attributeMapping->getTarget($attribute->getCode())), $this->getIgnoredAttributes()) ||
            $attribute->getAttributeType() == self::IMAGE_ATTRIBUTE_TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        if (!$this->query) {
            $this->query = $this->em
                ->getRepository($this->className)
                ->createQueryBuilder('c')
                ->join('c.families', 'PimCatalogBundle:Family')
                ->getQuery();
        }

        return $this->query;
    }

    /**
     * Get all ignored attributes
     * @return array
     */
    protected function getIgnoredAttributes()
    {
        return array(
            'sku',
            'name',
            'description',
            'collection'
        );
    }

    /**
     * Called after the configuration is set
     */
    protected function afterConfigurationSet()
    {
        parent::afterConfigurationSet();

        $this->attributeMappingMerger->setParameters($this->getClientParameters());
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            $this->attributeMappingMerger->getConfigurationField()
        );
    }
}
