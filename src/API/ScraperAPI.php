<?php

namespace JamesAusten\WebScraper\API;

use GuzzleHttp\Psr7\Response;
use JamesAusten\WebScraper\WebScraper;
use Symfony\Component\DomCrawler\Crawler;

abstract class ScraperAPI
{
    use HandlesResults;
    use CachesResults;
    use PaginatesResults;

    protected $apiName;

    protected $apiUrl;

    protected $requiresAuth = false;

    protected $method = 'GET';

    protected $page = 1;

    protected $urlParams = [];

    protected $csrfTokenFilter = [
        'filter' => null,
        'attr'   => null,
        'name'   => null,
    ];

    /** @var Response */
    protected $response;

    /** @var Crawler */
    protected $parser;

    /** @var string */
    protected $csrfToken;

    /** @var WebScraper */
    private $webScraper;

    public function __construct($webScraper)
    {
        $this->webScraper = $webScraper;
    }

    /**
     * Parse HTML response
     *
     * @return Crawler|array
     */
    public function parse()
    {
        return $this->parser;
    }

    /**
     * @return mixed
     */
    public function getApiName()
    {
        return $this->apiName;
    }

    /**
     * @return mixed
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * @return bool
     */
    public function requiresAuth()
    {
        return $this->requiresAuth;
    }

    /**
     * Set API URL
     *
     * @param mixed $apiUrl
     *
     * @return ScraperAPI
     */
    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $this->attachUrlParams($apiUrl);

        return $this;
    }

    /**
     * Update URL Params on a per-request basis
     *
     * @param array $params
     *
     * @return ScraperAPI
     */
    public function params(array $params): self
    {
        $this->urlParams = array_merge($this->urlParams, $params);

        return $this;
    }

    /**
     * Attach configured URL params to URL
     *
     * @param string $apiUri
     *
     * @return string
     */
    protected function attachUrlParams(string $apiUri): string
    {
        if (!count($this->urlParams)) {
            return $apiUri;
        }

        if ($this->shouldPaginate()) {
            $this->urlParams['page'] = $this->page;
        }

        return $apiUri . '?' . http_build_query($this->urlParams);
    }

    /**
     * Load URL and apply a filter to retrieve CSRF token
     *
     * @param string $uri
     * @param array|null $filter
     *
     * @return \DOMElement|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    protected function getCsrfToken(string $uri, ?array $filter = null)
    {
        if ($filter === null) {
            if ($this->csrfTokenFilter['filter'] === null) {
                $this->csrfTokenFilter = $this->webScraper->getScraper()->getCsrfTokenFilter();
            }

            $filter = $this->csrfTokenFilter;
        }

        if ($filter['filter'] === null) {
            throw new \Exception('CSRF token filter not set');
        }

        $response = $this->webScraper->request('GET', $uri);
        $parser = new Crawler((string)$response->getBody());

        $this->csrfToken = $this->webScraper->getScraper()->getCsrfToken($filter, $parser);

        return $this->csrfToken;
    }
}
