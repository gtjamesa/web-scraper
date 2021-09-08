<?php

namespace JamesAusten\WebScraper\API;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use JamesAusten\WebScraper\Scraper\Scraper;
use Symfony\Component\DomCrawler\Crawler;

trait HandlesResults
{
    /**
     * Results to be returned
     *
     * @var array
     */
    private $results = [];

    /**
     * Total amount of raw results (before any grouping)
     *
     * @var int
     */
    private $count = 0;

    /**
     * Make API request and return a DOMCrawler instance
     *
     * @param null $apiUrl
     *
     * @return Crawler|array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request($apiUrl = null)
    {
        if ($apiUrl !== null) {
            $this->setApiUrl($apiUrl);
        }

        $this->makeApiRequest($this->method, $this->apiUrl);

        if ($this->shouldCache() && $this->cacheKey && Cache::has($this->cacheKey)) {
            return $this->getResults();
        }

        $this->setResults($this->parse());

        return $this->getResults();
    }

    /**
     * Make API request, setup DOM parser and return Response object
     *
     * @param  string       $method
     * @param string $uri
     * @param array  $options
     *
     * @return Response|array|\Psr\Http\Message\ResponseInterface
     */
    protected function makeApiRequest(string $method, $uri = '', array $options = [])
    {
        if ($this->shouldCache()) {
            if (!$this->cacheKey) {
                $this->setCacheKey($uri);
            }

            if ($this->cacheKey && Cache::has($this->cacheKey)) {
                $this->setResults(Cache::get($this->cacheKey));

                return [];
            }
        }

        // Attach CSRF token to headers
        if ($this->csrfTokenFilter['filter'] !== null && $this->csrfTokenFilter['type'] == Scraper::CSRF_TYPE_HEADER) {
            $options = array_merge($options, [
                'headers' => [
                    $this->csrfTokenFilter['name'] => $this->csrfToken,
                ],
            ]);
        }

        $this->response = $this->webScraper->request($method, $uri, $options);
        $this->parser = new Crawler((string)$this->response->getBody());

        return $this->response;
    }

    /**
     * Total amount of results
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @param int $count
     *
     * @return ScraperAPI
     */
    public function incCount(int $count): ScraperAPI
    {
        $this->count += $count;

        return $this;
    }

    /**
     * @param array $results
     *
     * @return ScraperAPI
     */
    public function setResults(array $results): ScraperAPI
    {
        $this->results = array_merge($this->results, $results);

        return $this;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        if ($this->shouldCache() && !$this->shouldPaginate()) {
            return \Cache::remember($this->cacheKey, now()->addMinutes($this->cacheMinutes), function () {
                return $this->results;
            });
        } else {
            return $this->results;
        }
    }
}
