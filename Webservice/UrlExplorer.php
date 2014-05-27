<?php

namespace Pim\Bundle\MagentoConnectorBundle\Webservice;

use Pim\Bundle\MagentoConnectorBundle\Validator\Exception\InvalidSoapUrlException;
use Pim\Bundle\MagentoConnectorBundle\Validator\Exception\NotReachableUrlException;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Service\ClientInterface;

/**
 * Allows to get the content of an url
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class UrlExplorer
{
    CONST TIMEOUT = 10;
    CONST CONNECT_TIMEOUT = 10;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var array
     */
    protected $resultCache;

    /**
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client      = $client;
        $this->resultCache = array();
    }

    /**
     * Reaches url and get his content
     *
     * @param MagentoSoapClientParametersRegistry $clientParameters
     *
     * @return string Xml content as string
     *
     * @throws NotReachableUrlException
     * @throws InvalidSoapUrlException
     */
    public function getUrlContent(MagentoSoapClientParametersRegistry $clientParameters)
    {
        try {
            $response = $this->connect($clientParameters);
        } catch (CurlException $e) {
            throw new NotReachableUrlException($e->getMessage());
        } catch (BadResponseException $e) {
            throw new InvalidSoapUrlException($e->getMessage());
        }

        if (false === $response->isContentType('text/xml')) {
            throw new InvalidSoapUrlException('Content type is not XML');
        }

        return $response->getBody(true);
    }

    /**
     * It connects to the url and give response
     *
     * @param  MagentoSoapClientParametersRegistry $clientParameters
     *
     * @return Guzzle\Http\Message\Response
     *
     * @throws \Exception
     */
    protected function connect($clientParameters)
    {
        $parametersHash = $clientParameters->getHash();

        if (!isset($this->resultCache[$parametersHash])) {
            $guzzleParams = array(
                'connect_timeout' => self::CONNECT_TIMEOUT,
                'timeout'         => self::TIMEOUT,
                'auth'            => array(
                    $clientParameters->getHttpLogin(),
                    $clientParameters->getHttpPassword()
                )
            );

            $request = $this->client->get($clientParameters->getSoapUrl(), array(), $guzzleParams);
            $request->getCurlOptions()->set(CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
            $request->getCurlOptions()->set(CURLOPT_TIMEOUT, self::TIMEOUT);

            try {
                $response = $this->client->send($request);
                $this->resultCache[$parametersHash] = $response;
            } catch(\Exception $e) {
                $this->resultCache[$parametersHash] = $e;
                throw $e;
            }
        } else {
            $response = $this->resultCache[$parametersHash];
            if ($response instanceof \Exception) {
                throw $response;
            }
        }

        return $response;
    }
}
