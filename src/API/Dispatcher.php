<?php

namespace JamesAusten\WebScraper\API;

use JamesAusten\WebScraper\WebScraper;

class Dispatcher
{
    /**
     * Custom APIs configured to scrape a website's data
     *
     * @var ScraperAPI[]
     */
    protected $availableApis = [];

    /** @var WebScraper */
    private $webScraper;

    public function __construct($webScraper)
    {
        $this->webScraper = $webScraper;
    }

    /**
     * Add an API instance
     *
     * @param string|array $apis
     *
     * @return Dispatcher
     */
    public function addApi($apis): self
    {
        if (!is_array($apis)) {
            $apis = [$apis];
        }

        foreach ($apis as $api) {
            /** @var ScraperAPI $api */
            $api = new $api($this->webScraper);

            $this->availableApis[$api->getApiName()] = $api;
        }

        return $this;
    }

    /**
     * Magic method to access available APIs
     *
     * @param string $key
     *
     * @return ScraperAPI|null
     */
    public function __get(string $key)
    {
        if (array_key_exists($key, $this->availableApis)) {
            return $this->availableApis[$key];
        }

        $trace = debug_backtrace();
        trigger_error('No API configured for "' . $key . '"" in ' . $trace[0]['file'] . ' on line ' .
            $trace[0]['line'], E_USER_ERROR);

        return null;
    }
}
