<?php

namespace Pim\Bundle\MagentoConnectorBundle\Processor;

use Pim\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\BatchBundle\Item\InvalidItemException;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\AbstractNormalizer;
use Pim\Bundle\MagentoConnectorBundle\Webservice\SoapCallException;

/**
 * Magento category processor
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CategoryProcessor extends AbstractProcessor
{
    /**
     * @var string
     */
    protected $rootCategoryMapping = '';

    /**
     * get rootCategoryMapping
     *
     * @return string rootCategoryMapping
     */
    public function getRootCategoryMapping()
    {
        return $this->rootCategoryMapping;
    }

    /**
     * Set rootCategoryMapping
     *
     * @param string $rootCategoryMapping rootCategoryMapping
     *
     * @return AbstractProcessor
     */
    public function setRootCategoryMapping($rootCategoryMapping)
    {
        $this->rootCategoryMapping = $rootCategoryMapping;

        return $this;
    }

    /**
     * Get computed storeView mapping (string to array)
     * @return array
     */
    protected function getComputedRootCategoryMapping()
    {
        return $this->getComputedMapping($this->rootCategoryMapping);
    }

    /**
     * Function called before all process
     */
    protected function beforeExecute()
    {
        parent::beforeExecute();

        $this->categoryNormalizer = $this->normalizerGuesser->getCategoryNormalizer($this->getClientParameters());

        $magentoCategories = $this->webservice->getCategoriesStatus();
        $magentoStoreViews = $this->webservice->getStoreViewsList();

        $this->globalContext = array(
            'magentoCategories'   => $magentoCategories,
            'magentoUrl'          => $this->soapUrl,
            'defaultLocale'       => $this->defaultLocale,
            'channel'             => $this->channel,
            'rootCategoryMapping' => $this->getComputedRootCategoryMapping(),
            'magentoStoreViews'   => $magentoStoreViews,
            'storeViewMapping'    => $this->getComputedStoreViewMapping(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function process($categories)
    {
        $this->beforeExecute();

        $normalizedCategories = array(
            'create'    => array(),
            'update'    => array(),
            'move'      => array(),
            'variation' => array()
        );

        $categories = is_array($categories) ? $categories : array($categories);

        foreach ($categories as $category) {
            if ($category->getParent()) {
                $normalizedCategory = $this->categoryNormalizer->normalize(
                    $category,
                    AbstractNormalizer::MAGENTO_FORMAT,
                    $this->globalContext
                );

                $normalizedCategories = array_merge_recursive($normalizedCategories, $normalizedCategory);
            }
        }

        return $normalizedCategories;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            array(
                'rootCategoryMapping' => array(
                    'type'    => 'textarea',
                    'options' => array(
                        'required' => false
                    )
                )
            )
        );
    }
}
