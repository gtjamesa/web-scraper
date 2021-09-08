<?php

namespace JamesAusten\WebScraper\API;

trait CachesResults
{
    private $shouldCache = false;

    protected $cacheKey = null;

    protected $cacheMinutes = 60;

    /**
     * @param int $minutes
     *
     * @return ScraperAPI
     */
    public function cache($minutes = 60): ScraperAPI
    {
        $this->shouldCache = true;
        $this->cacheMinutes = $minutes;

        return $this;
    }

    /**
     * @return bool
     */
    public function shouldCache(): bool
    {
        return $this->shouldCache;
    }

    /**
     * @return null
     */
    public function getCacheKey()
    {
        return $this->cacheKey;
    }

    /**
     * Set a CacheKey for this API request
     *
     * @param null $uri
     */
    protected function setCacheKey($uri = null)
    {
        $hash = ($uri) ? md5($uri) : md5($this->getApiUrl());

        $this->cacheKey = 'ScraperAPI.' . $this->getApiName() . '.' . $hash;

        if (count($this->urlParams)) {
            $this->cacheKey .= '.' . md5(http_build_query($this->urlParams));
        }

        if ($this->shouldPaginate()) {
            $this->cacheKey .= '.' . $this->totalPages;
        }
    }
}
