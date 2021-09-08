<?php

namespace JamesAusten\WebScraper\API;

use Illuminate\Support\Facades\Cache;
use Closure;

trait PaginatesResults
{
    private $shouldPaginate = false;

    private $totalPages = null;

    /** @var Closure */
    protected $callback;

    /**
     * Paginate results
     *
     * @param string $pages
     * @param null   $apiUrl
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function paginate($pages = 'all', $apiUrl = null)
    {
        $this->shouldPaginate = true;

        $lastCount = 0;
        $this->totalPages = $pages;
        $pages = ($pages === 'all') ? 9999 : (int)$pages;

        for ($page = 1; $page <= $pages; $page++) {
            $this->page = $page;
            $lastCount = $this->getCount();

            // Make API request
            $this->request($apiUrl);

            if ($this->shouldCache() && $this->cacheKey && Cache::has($this->cacheKey)) {
                $this->shouldPaginate = false;
                return $this->getResults();
            }

            // Call the callback closure if specified
            if ($this->callback) {
                $callback = $this->callback;
                $callback($this->page);
            }

            // No results were found on this iteration, stop here
            if ($this->getCount() === $lastCount) {
                break;
            }
        }

        $this->shouldPaginate = false;

        return $this->getResults();
    }

    /**
     * Call the callback at the end of every page
     *
     * The current page is passed into the callback as a parameter
     *
     * @param Closure $callback
     *
     * @return $this
     */
    public function paginateCallback(Closure $callback)
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldPaginate(): bool
    {
        return $this->shouldPaginate;
    }
}
