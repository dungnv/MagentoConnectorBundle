<?php

namespace Pim\Bundle\MagentoConnectorBundle\Guesser;

use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClient;

/**
 * A magento guesser abstract class
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MagentoGuesser
{
    const MAGENTO_VERSION_1_8 = '1.8';
    const MAGENTO_VERSION_1_7 = '1.7';
    const MAGENTO_VERSION_1_6 = '1.6';

    /**
     * @var string
     */
    protected $version = null;

    /**
     * Get the Magento version for the given client
     * @param  MagentoSoapClient $client
     * @return float
     */
    protected function getMagentoVersion(MagentoSoapClient $client)
    {
        if (!$this->version) {
            try {
                $magentoVersion = $client->call('core_magento.info')['magento_version'];
            } catch (\SoapFault $e) {
                return '1.6';
            }

            $pattern = '/^(?P<version>[0-9]\.[0-9])/';

            if (preg_match($pattern, $magentoVersion, $matches)) {
                $this->version = $matches['version'];
            } else {
                $this->version = $magentoVersion;
            }
        }

        return $this->version;
    }
}
