<?php

namespace SURFnet\SslLabs;

use GuzzleHttp\Client as HttpClient;
use SURFnet\SslLabs\Dto\Endpoint;
use SURFnet\SslLabs\Dto\EndpointDetails;
use SURFnet\SslLabs\Dto\Host;
use SURFnet\SslLabs\Dto\StatusCodes;
use Symfony\Component\Serializer\Serializer;
use SURFnet\SslLabs\Dto\Info;

class Client
{
    const ALL_ON = 'on';
    const ALL_DONE = 'done';

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * Client constructor.
     * @param HttpClient $httpClient
     * @param Serializer $serializer
     */
    public function __construct(
        HttpClient $httpClient,
        Serializer $serializer
    ) {
        $this->httpClient = $httpClient;
        $this->serializer = $serializer;
    }

    /**
     * @return Info
     */
    public function info()
    {
        $response = $this->httpClient->get('info');

        return $this->serializer->deserialize(
            $response->getBody(true),
            Info::CLASS_NAME,
            'json'
        );
    }

    /**
     * @param $host
     * @param bool|false $publish
     * @param null $startNew
     * @param bool|false $fromCache
     * @param null $maxAge
     * @param null $all
     * @param bool|false $ignoreMismatch
     * @return Host
     */
    public function analyze(
        $host,
        $publish = null,
        $startNew = null,
        $fromCache = null,
        $maxAge = null,
        $all = null,
        $ignoreMismatch = null
    ) {
        $arguments = array(
            'host'              => $host,
            'publish'           => $this->encodeBooleanValue($publish),
            'startNew'          => $this->encodeBooleanValue($startNew),
            'fromCache'         => $this->encodeBooleanValue($fromCache),
            'maxAge'            => $maxAge,
            'all'               => $all,
            'ignoreMismatch'    => $this->encodeBooleanValue($ignoreMismatch),
        );
        $arguments = array_filter($arguments);
        $arguments = array_map(function($val) { return urlencode($val); }, $arguments);
        $path = 'analyze?' . http_build_query($arguments);

        $response = $this->httpClient->get($path);

        return $this->mapJsonToHost($response->getBody(true));
    }

    public function getEndpointData($host, $s, $fromCache = null)
    {
        $arguments = array(
            'host'              => $host,
            's'                 => $s,
            'fromCache'         => $this->encodeBooleanValue($fromCache),
        );
        $arguments = array_filter($arguments);
        $arguments = array_map(function($val) { return urlencode($val); }, $arguments);
        $path = 'getEndpointData?' . http_build_query($arguments);

        $response = $this->httpClient->get($path);

        return $this->serializer->deserialize(
            $response->getBody(true),
            Endpoint::CLASS_NAME,
            'json'
        );
    }

    public function getStatusCodes()
    {
        $response = $this->httpClient->get('getStatusCodes');

        return $this->serializer->deserialize(
            $response->getBody(true),
            StatusCodes::CLASS_NAME,
            'json'
        );
    }

    private function encodeBooleanValue($value)
    {
        if (is_null($value)) {
            return null;
        }

        if ($value) {
            return 'on';
        }

        return 'off';
    }

    /**
     * @param $response
     * @return Host
     */
    private function mapJsonToHost($json) {
        /** @var Host $host */
        $host = $this->serializer->deserialize(
          $json,
          Host::CLASS_NAME,
          'json'
        );

        if (empty($host->endpoints)) {
            $host->endpoints = array();
            return $host;
        }

        $endpointDtos = array();
        foreach ($host->endpoints as $endpoint) {
            $endpointDto = new Endpoint();
            foreach ($endpoint as $key => $value) {
                $endpointDto->$key = $value;
            }
            $endpointDtos[] = $endpointDto;
        }
        $host->endpoints = $endpointDtos;

        return $host;
    }
}
