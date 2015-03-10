<?php

namespace Pim\Bundle\MagentoConnectorBundle\Processor;

use Symfony\Component\Validator\Constraints as Assert;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\MagentoConnectorBundle\Guesser\WebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Guesser\NormalizerGuesser;
use Pim\Bundle\MagentoConnectorBundle\Manager\LocaleManager;
use Pim\Bundle\MagentoConnectorBundle\Merger\MagentoMappingMerger;
use Pim\Bundle\MagentoConnectorBundle\Manager\CurrencyManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\AttributeManager;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParametersRegistry;

/**
 * Abstract magento product processor.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @HasValidDefaultLocale(groups={"Execution"})
 * @HasValidCurrency(groups={"Execution"})
 */
abstract class AbstractProductProcessor extends AbstractProcessor
{
    /** @staticvar int */
    const MAGENTO_VISIBILITY_CATALOG_SEARCH = 4;

    /** @staticvar int */
    const MAGENTO_VISIBILITY_NONE = 1;

    /** @var \Pim\Bundle\MagentoConnectorBundle\Normalizer\ProductNormalizerInterface */
    protected $productNormalizer;

    /** @var ChannelManager */
    protected $channelManager;

    /** @var CurrencyManager */
    protected $currencyManager;

    /**
     * @var \Pim\Bundle\CatalogBundle\Entity\Currency
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $currency;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $channel;

    /** @var boolean */
    protected $enabled;

    /** @var integer */
    protected $visibility = self::MAGENTO_VISIBILITY_CATALOG_SEARCH;

    /** @var integer */
    protected $variantMemberVisibility = self::MAGENTO_VISIBILITY_NONE;

    /** @var string */
    protected $categoryMapping;

    /** @var MagentoMappingMerger */
    protected $categoryMappingMerger;

    /** @var AttributeManager */
    protected $attributeManager;

    /** @var string */
    protected $attributeCodeMapping;

    /** @var MagentoMappingMerger */
    protected $attributeMappingMerger;

    /** @var string */
    protected $smallImageAttribute;

    /** @var string */
    protected $baseImageAttribute;

    /** @var string */
    protected $thumbnailAttribute;

    /** @var boolean */
    protected $urlKey;

    /** @var  boolean */
    protected $skuFirst;

    /**
     * @param WebserviceGuesser                   $webserviceGuesser
     * @param NormalizerGuesser                   $normalizerGuesser
     * @param LocaleManager                       $localeManager
     * @param MagentoMappingMerger                $storeViewMappingMerger
     * @param CurrencyManager                     $currencyManager
     * @param ChannelManager                      $channelManager
     * @param MagentoMappingMerger                $categoryMappingMerger
     * @param MagentoMappingMerger                $attributeMappingMerger
     * @param MagentoSoapClientParametersRegistry $clientParametersRegistry,
     * @param AttributeManager                    $attributeManager
     */
    public function __construct(
        WebserviceGuesser $webserviceGuesser,
        NormalizerGuesser $normalizerGuesser,
        LocaleManager $localeManager,
        MagentoMappingMerger $storeViewMappingMerger,
        CurrencyManager $currencyManager,
        ChannelManager $channelManager,
        MagentoMappingMerger $categoryMappingMerger,
        MagentoMappingMerger $attributeMappingMerger,
        MagentoSoapClientParametersRegistry $clientParametersRegistry,
        AttributeManager $attributeManager
    ) {
        parent::__construct(
            $webserviceGuesser,
            $normalizerGuesser,
            $localeManager,
            $storeViewMappingMerger,
            $clientParametersRegistry
        );

        $this->currencyManager        = $currencyManager;
        $this->channelManager         = $channelManager;
        $this->categoryMappingMerger  = $categoryMappingMerger;
        $this->attributeManager       = $attributeManager;
        $this->attributeMappingMerger = $attributeMappingMerger;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     *
     * @return AbstractProductProcessor
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return AbstractProductProcessor
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return string
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param string $enabled
     *
     * @return AbstractProductProcessor
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param string $visibility
     *
     * @return AbstractProductProcessor
     */
    public function setVariantMemberVisibility($visibility)
    {
        $this->variantMemberVisibility = $visibility;

        return $this;
    }

    /**
     * @return string
     */
    public function getVariantMemberVisibility()
    {
        return $this->variantMemberVisibility;
    }

    /**
     * @param string $visibility
     *
     * @return AbstractProductProcessor
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @return string
     */
    public function getSmallImageAttribute()
    {
        return $this->smallImageAttribute;
    }

    /**
     * @param string $smallImageAttribute
     *
     * @return AbstractProductProcessor
     */
    public function setSmallImageAttribute($smallImageAttribute)
    {
        $this->smallImageAttribute = $smallImageAttribute;

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseImageAttribute()
    {
        return $this->baseImageAttribute;
    }

    /**
     * @param string $baseImageAttribute
     *
     * @return AbstractProductProcessor
     */
    public function setBaseImageAttribute($baseImageAttribute)
    {
        $this->baseImageAttribute = $baseImageAttribute;

        return $this;
    }

    /**
     * @return string
     */
    public function getThumbnailAttribute()
    {
        return $this->thumbnailAttribute;
    }

    /**
     * @param string $thumbnailAttribute
     *
     * @return AbstractProductProcessor
     */
    public function setThumbnailAttribute($thumbnailAttribute)
    {
        $this->thumbnailAttribute = $thumbnailAttribute;

        return $this;
    }

    /**
     * Get category mapping from merger.
     *
     * @return string JSON
     */
    public function getCategoryMapping()
    {
        $mapping = null;

        if ($this->categoryMappingMerger->getMapping() !== null) {
            $mapping = json_encode($this->categoryMappingMerger->getMapping()->toArray());
        }

        return $mapping;
    }

    /**
     * Set category mapping ni parameters AND in database.
     *
     * @param string $categoryMapping JSON
     *
     * @return AbstractProductProcessor
     */
    public function setCategoryMapping($categoryMapping)
    {
        $decodedCategoryMapping = json_decode($categoryMapping, true);

        if (!is_array($decodedCategoryMapping)) {
            $decodedCategoryMapping = [$decodedCategoryMapping];
        }

        $this->categoryMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
        $this->categoryMappingMerger->setMapping($decodedCategoryMapping);
        $this->categoryMapping = $this->getCategoryMapping();

        return $this;
    }

    /**
     * Get attribute code mapping from merger.
     *
     * @return string JSON
     */
    public function getAttributeCodeMapping()
    {
        $mapping = null;

        if ($this->attributeMappingMerger->getMapping() !== null) {
            $mapping = json_encode($this->attributeMappingMerger->getMapping()->toArray());
        }

        return $mapping;
    }

    /**
     * Set attribute code mapping in parameters AND in database.
     *
     * @param string $attributeCodeMapping JSON
     *
     * @return AbstractProductProcessor
     */
    public function setAttributeCodeMapping($attributeCodeMapping)
    {
        $decodedAttributeCodeMapping = json_decode($attributeCodeMapping, true);

        if (!is_array($decodedAttributeCodeMapping)) {
            $decodedAttributeCodeMapping = [$decodedAttributeCodeMapping];
        }

        $this->attributeMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
        $this->attributeMappingMerger->setMapping($decodedAttributeCodeMapping);
        $this->attributeCodeMapping = $this->getAttributeCodeMapping();

        return $this;
    }

    /**
     * @return boolean
     */
    public function isUrlKey()
    {
        return $this->urlKey;
    }

    /**
     * @param boolean $urlKey
     *
     * @return AbstractProductProcessor
     */
    public function setUrlKey($urlKey)
    {
        $this->urlKey = $urlKey;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSkuFirst()
    {
        return $this->skuFirst;
    }

    /**
     * @param boolean $skuFirst
     *
     * @return AbstractProductProcessor
     */
    public function setSkuFirst($skuFirst)
    {
        $this->skuFirst = $skuFirst;

        return $this;
    }

    /**
     * Function called before all process.
     */
    protected function beforeExecute()
    {
        parent::beforeExecute();

        $this->productNormalizer = $this->normalizerGuesser->getProductNormalizer(
            $this->getClientParameters(),
            $this->enabled,
            $this->visibility,
            $this->variantMemberVisibility,
            $this->currency
        );

        $magentoStoreViews        = $this->webservice->getStoreViewsList();
        $magentoAttributes        = $this->webservice->getAllAttributes();
        $magentoAttributesOptions = $this->webservice->getAllAttributesOptions();

        $this->globalContext = array_merge(
            $this->globalContext,
            [
                'channel'                  => $this->channel,
                'website'                  => $this->website,
                'magentoAttributes'        => $magentoAttributes,
                'magentoAttributesOptions' => $magentoAttributesOptions,
                'magentoStoreViews'        => $magentoStoreViews,
                'categoryMapping'          => $this->categoryMappingMerger->getMapping(),
                'attributeCodeMapping'     => $this->attributeMappingMerger->getMapping(),
                'smallImageAttribute'      => $this->smallImageAttribute,
                'baseImageAttribute'       => $this->baseImageAttribute,
                'thumbnailAttribute'       => $this->thumbnailAttribute,
                'urlKey'                   => $this->urlKey,
                'skuFirst'                 => $this->skuFirst,
            ]
        );
    }

    /**
     * Called after the configuration is set.
     */
    protected function afterConfigurationSet()
    {
        parent::afterConfigurationSet();

        $this->categoryMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
        $this->attributeMappingMerger->setParameters($this->getClientParameters(), $this->getDefaultStoreView());
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array_merge(
            parent::getConfigurationFields(),
            [
                'channel' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => $this->channelManager->getChannelChoices(),
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.channel.help',
                        'label'    => 'pim_magento_connector.export.channel.label',
                    ],
                ],
                'enabled' => [
                    'type'    => 'switch',
                    'options' => [
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.enabled.help',
                        'label'    => 'pim_magento_connector.export.enabled.label',
                    ],
                ],
                'visibility' => [
                    'type'    => 'text',
                    'options' => [
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.visibility.help',
                        'label'    => 'pim_magento_connector.export.visibility.label',
                    ],
                ],
                'variantMemberVisibility' => [
                    'type'    => 'text',
                    'options' => [
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.variant_member_visibility.help',
                        'label'    => 'pim_magento_connector.export.variant_member_visibility.label',
                    ],
                ],
                'currency' => [
                    'type'    => 'choice',
                    'options' => [
                        'choices'  => $this->currencyManager->getCurrencyChoices(),
                        'required' => true,
                        'help'     => 'pim_magento_connector.export.currency.help',
                        'label'    => 'pim_magento_connector.export.currency.label',
                        'attr' => [
                            'class' => 'select2',
                        ],
                    ],
                ],
                'smallImageAttribute' => [
                    'type' => 'choice',
                    'options' => [
                        'choices' => $this->attributeManager->getImageAttributeChoice(),
                        'help'    => 'pim_magento_connector.export.smallImageAttribute.help',
                        'label'   => 'pim_magento_connector.export.smallImageAttribute.label',
                        'attr' => [
                            'class' => 'select2',
                        ],
                    ],
                ],
                'baseImageAttribute' => [
                    'type' => 'choice',
                    'options' => [
                        'choices' => $this->attributeManager->getImageAttributeChoice(),
                        'help'    => 'pim_magento_connector.export.baseImageAttribute.help',
                        'label'   => 'pim_magento_connector.export.baseImageAttribute.label',
                        'attr' => [
                            'class' => 'select2',
                        ],
                    ],
                ],
                'thumbnailAttribute' => [
                    'type' => 'choice',
                    'options' => [
                        'choices' => $this->attributeManager->getImageAttributeChoice(),
                        'help'    => 'pim_magento_connector.export.thumbnailAttribute.help',
                        'label'   => 'pim_magento_connector.export.thumbnailAttribute.label',
                        'attr' => [
                            'class' => 'select2',
                        ],
                    ],
                ],
                'urlKey' => [
                    'type'    => 'checkbox',
                    'options' => [
                        'help'  => 'pim_magento_connector.export.urlKey.help',
                        'label' => 'pim_magento_connector.export.urlKey.label',
                    ],
                ],
                'skuFirst' => [
                    'type'    => 'checkbox',
                    'options' => [
                        'help'  => 'pim_magento_connector.export.skuFirst.help',
                        'label' => 'pim_magento_connector.export.skuFirst.label',
                    ],
                ],
            ],
            $this->categoryMappingMerger->getConfigurationField(),
            $this->attributeMappingMerger->getConfigurationField()
        );
    }
}
