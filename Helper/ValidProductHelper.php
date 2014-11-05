<?php

namespace Pim\Bundle\MagentoConnectorBundle\Helper;

use Doctrine\Common\Collections\Collection;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\CatalogBundle\Entity\Channel;

/**
 * Valid product helper
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ValidProductHelper
{
    /**
     * Provides a method to find ready to export products from an array of products
     *
     * @param Channel                       $channel
     * @param ProductInterface[]|Collection $products
     *
     * @return \Pim\Bundle\CatalogBundle\Model\ProductInterface[]
     */
    public function getValidProducts(Channel $channel, $products)
    {
        $validProducts  = [];
        $rootCategoryId = $channel->getCategory()->getId();

        foreach ($products as $product) {
            $isProductComplete = $this->isProductComplete($product, $channel);

            $productCategories = $product->getCategories()->getIterator();
            if ($isProductComplete &&
                false !== $productCategories &&
                $this->isProductBelongToChannel($productCategories, $rootCategoryId)
            ) {
                $validProducts[] = $product;
            }
        }

        return $validProducts;
    }

    /**
     * Is the given product is complete for the given channel ?
     *
     * @param ProductInterface $product
     * @param Channel          $channel
     *
     * @return bool
     */
    protected function isProductComplete(ProductInterface $product, Channel $channel)
    {
        $isComplete = true;
        $completenesses = $product->getCompletenesses()->getIterator();
        while ((list($key, $completeness) = each($completenesses)) && $isComplete) {
            if ($completeness->getChannel()->getId() === $channel->getId() &&
                $completeness->getRatio() < 100
            ) {
                $isComplete = false;
            }
        }

        return $isComplete;
    }

    /**
     * Compute the belonging of a product to a channel with its categories
     *
     * @param \ArrayIterator|array $productCategories
     * @param int                  $rootCategoryId
     *
     * @return bool
     */
    protected function isProductBelongToChannel($productCategories, $rootCategoryId)
    {
        $isInChannel = false;
        while ((list($key, $category) = each($productCategories)) && !$isInChannel) {
            if ($category->getRoot() === $rootCategoryId) {
                $isInChannel = true;
            }
        }

        return $isInChannel;
    }
}
