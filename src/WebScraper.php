<?php

namespace JamesAusten\WebScraper;

use JamesAusten\WebScraper\API\Dispatcher;
use JamesAusten\WebScraper\Scraper\Scraper;

class WebScraper
{
    /** @var Scraper */
    protected $scraper;

    /** @var Dispatcher */
    protected $api;

    /** @var array */
    protected $options = [
        'authTest' => [
            'url' => null,
            'see' => null,
        ],
    ];

    private $guzzleMethods = ['request', 'get', 'post'];

    private $isLaravel = false;

    public function __construct($options = [])
    {
        $this->setOptions($options);
        $this->scraper = new Scraper($this);
        $this->api = new Dispatcher($this);
    }

    /**
     * Proxy a request to the Scraper instance
     *
     * @param string $method
     * @param string $uri
     * @param array  $options
     *
     * @return \GuzzleHttp\Psr7\Response|mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(string $method, string $uri = '', array $options = [])
    {
        return $this->scraper->request($method, $uri, $options);
    }

    public function api()
    {
        return $this->api;
    }

    /**
     * @return Scraper
     */
    public function getScraper()
    {
        return $this->scraper;
    }

    /**
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function getParser()
    {
        return $this->scraper->getParser();
    }

    public function laravel()
    {
        $this->isLaravel = true;

        $this->scraper->setCsrfTokenFilter([
            'filter' => '[name="_token"]',
            'attr'   => 'value',
        ]);

        return $this;
    }

    /**
     * @param array $options
     *
     * @return WebScraper
     */
    public function setOptions($options)
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

//    public function __get($name)
//    {
//        if (in_array($name, $this->guzzleMethods)) {
//            $this->getScraper()->getClient()->request()
//        }
//    }
}
