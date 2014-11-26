<?php

namespace Pim\Bundle\MagentoConnectorBundle\Validator\Constraints;

use Pim\Bundle\MagentoConnectorBundle\Entity\MagentoConfiguration;
use Pim\Bundle\MagentoConnectorBundle\Factory\MagentoSoapClientFactory;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClient;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Service\ClientInterface;

/**
 * This validator allows to validate if Magento is reachable with parameters of MagentoConfiguration entity
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MagentoReachableValidator extends ConstraintValidator
{
    /** @staticvar int */
    const TIMEOUT = 10;

    /** @staticvar int */
    const CONNECT_TIMEOUT = 10;

    /** @staticvar string */
    const EXCEPTION_MESSAGE_PARAM = '%EXCEPTION%';

    /** @staticvar string */
    const SOAP_URL_MESSAGE_PARAM = '%SOAP_URL%';

    /** @staticvar string */
    const HTTP_LOGIN_MESSAGE_PARAM = '%HTTP_LOGIN%';

    /** @staticvar string */
    const HTTP_PASSWD_MESSAGE_PARAM = '%HTTP_PASSWD%';

    /** @staticvar string */
    const SOAP_USERNAME_MESSAGE_PARAM = '%USERNAME%';

    /** @staticvar string */
    const SOAP_API_KEY_MESSAGE_PARAM = '%API_KEY%';

    /** @staticvar int */
    const ACCESS_DENIED_CODE = 2;


    /** @var ClientInterface Guzzle HTTP client */
    protected $guzzleClient;

    /** @var MagentoSoapClientFactory Factory to create Soap clients */
    protected $soapClientFactory;

    /**
     * Constructor
     *
     * @param ClientInterface          $guzzleClient
     * @param MagentoSoapClientFactory $soapClientFactory
     */
    public function __construct(ClientInterface $guzzleClient, MagentoSoapClientFactory $soapClientFactory)
    {
        $this->guzzleClient      = $guzzleClient;
        $this->soapClientFactory = $soapClientFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($configuration, Constraint $constraint)
    {
        if ($this->checkHttp($configuration, $constraint)) {
            $this->checkSoap($configuration, $constraint);
        }
    }

    /**
     * Allows to check connection to the given soap URL
     *
     * @param MagentoConfiguration $configuration
     * @param Constraint           $constraint
     *
     * @return bool
     */
    protected function checkHttp(MagentoConfiguration $configuration, Constraint $constraint)
    {
        try {
            $response  = $this->connectHttpClient($configuration);
            $isConnected = true;
        } catch (CurlException $e) {
            // When you can not access to anything and it returns a 404
            $this->context->addViolationAt(
                'MagentoConfiguration',
                $constraint->messageNotReachableUrl,
                [
                    static::EXCEPTION_MESSAGE_PARAM   => $e->getMessage(),
                    static::SOAP_URL_MESSAGE_PARAM    => $configuration->getSoapUrl(),
                    static::HTTP_LOGIN_MESSAGE_PARAM  => $configuration->getHttpLogin(),
                    static::HTTP_PASSWD_MESSAGE_PARAM => $configuration->getHttpPassword()
                ]
            );
            $isConnected = false;
        } catch (BadResponseException $e) {
            // When you can access to a web site but it returns a 404
            $this->context->addViolationAt(
                'MagentoConfiguration',
                $constraint->messageInvalidSoapUrl,
                [
                    static::EXCEPTION_MESSAGE_PARAM => $e->getMessage(),
                    static::SOAP_URL_MESSAGE_PARAM  => $configuration->getSoapUrl()
                ]
            );
            $isConnected = false;
        }

        if ($isConnected && false === $response->isContentType('text/xml')) {
            // When the response is not XML
            $this->context->addViolationAt(
                'MagentoConfiguration',
                $constraint->messageXmlNotValid,
                [static::SOAP_URL_MESSAGE_PARAM => $configuration->getSoapUrl()]
            );
            $isConnected = false;
        }

        return $isConnected;
    }

    /**
     * It connects to the url and give response
     *
     * @param MagentoConfiguration $configuration
     *
     * @return array|\Guzzle\Http\Message\Response
     */
    protected function connectHttpClient(MagentoConfiguration $configuration)
    {
        $guzzleParams = [
            'connect_timeout' => static::CONNECT_TIMEOUT,
            'timeout'         => static::TIMEOUT,
            'auth'            => [
                $configuration->getHttpLogin(),
                $configuration->getHttpPassword()
            ]
        ];

        $request = $this->guzzleClient->get($configuration->getSoapUrl(), [], $guzzleParams);
        $request->getCurlOptions()->set(CURLOPT_CONNECTTIMEOUT, static::CONNECT_TIMEOUT);
        $request->getCurlOptions()->set(CURLOPT_TIMEOUT, static::TIMEOUT);

        $response = $this->guzzleClient->send($request);

        return $response;
    }

    /**
     * Allows to check soap connection, login and api method
     *
     * @param MagentoConfiguration $configuration
     * @param Constraint           $constraint
     */
    protected function checkSoap(MagentoConfiguration $configuration, Constraint $constraint)
    {
        $magentoSoapClient = $this->soapClientFactory->createMagentoSoapClient($configuration);
        if (null !== $magentoSoapClient) {
            $this->loginSoapClient($magentoSoapClient, $constraint, $configuration);
        }
    }

    /**
     * Login Magento Soap client to verify if username and api key are rights and returns session token
     *
     * @param MagentoSoapClientInterface $soapClient
     * @param Constraint                 $constraint
     * @param MagentoConfiguration       $configuration
     *
     * @return string|null Session token
     */
    protected function loginSoapClient(
        MagentoSoapClientInterface $soapClient,
        Constraint $constraint,
        MagentoConfiguration $configuration
    ) {
        try {
            $session = $soapClient->login($configuration->getSoapUsername(), $configuration->getSoapApiKey());
        } catch (\SoapFault $e) {
            if (static::ACCESS_DENIED_CODE === $e->faultcode) {
                $this->context->addViolationAt(
                    'MagentoConfiguration',
                    $constraint->messageAccessDenied,
                    [
                        static::EXCEPTION_MESSAGE_PARAM     => $e->getMessage(),
                        static::SOAP_USERNAME_MESSAGE_PARAM => $configuration->getSoapUsername(),
                        static::SOAP_API_KEY_MESSAGE_PARAM  => $configuration->getSoapApiKey()
                    ]
                );
            } else {
                $this->addUndefinedViolation($e, $constraint, $configuration);
            }
            $session = null;
        }

        return $session;
    }

    /**
     * Add a violation on MagentoConfiguration with the UndefinedSoapException message
     *
     * @param \Exception           $exception
     * @param Constraint           $constraint
     * @param MagentoConfiguration $configuration
     */
    protected function addUndefinedViolation(
        \Exception $exception,
        Constraint $constraint,
        MagentoConfiguration $configuration
    ) {
        $this->context->addViolationAt(
            'MagentoConfiguration',
            $constraint->messageUndefinedSoapException,
            [
                static::EXCEPTION_MESSAGE_PARAM     => $exception->getMessage(),
                static::SOAP_USERNAME_MESSAGE_PARAM => $configuration->getSoapUsername(),
                static::SOAP_API_KEY_MESSAGE_PARAM  => $configuration->getSoapApiKey(),
                static::SOAP_URL_MESSAGE_PARAM      => $configuration->getSoapUrl(),
                static::HTTP_LOGIN_MESSAGE_PARAM    => $configuration->getHttpLogin(),
                static::HTTP_PASSWD_MESSAGE_PARAM   => $configuration->getHttpPassword()
            ]
        );
    }
}
