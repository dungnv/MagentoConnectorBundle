<?php

namespace Pim\Bundle\MagentoConnectorBundle\Mapper;

/**
 * Defines the interface of a mapper.
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface MapperInterface
{
    /**
     * Get mapper identifier.
     *
     * @param string $rootIdentifier
     *
     * @return string
     */
    public function getIdentifier($rootIdentifier);

    /**
     * @return array
     */
    public function getMapping();

    /**
     * @param array $mapping
     */
    public function setMapping(array $mapping);

    /**
     * @return array
     */
    public function getAllTargets();

    /**
     * @return array
     */
    public function getAllSources();

    /**
     * @return integer
     */
    public function getPriority();

    /**
     * Is the mapper valid ?
     *
     * @return boolean
     */
    public function isValid();
}
