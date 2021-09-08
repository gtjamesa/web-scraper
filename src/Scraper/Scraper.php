<?php

namespace JamesAusten\WebScraper\Scraper;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Response;
use JamesAusten\WebScraper\Cookie\CookieHandler;
use JamesAusten\WebScraper\WebScraper;
use Symfony\Component\DomCrawler\Crawler;

class Scraper
{
    /** @var Client */
    protected $client;

    /** @var Crawler */
    protected $parser;

    /** @var CookieHandler */
    protected $cookieHandler;

    /** @var string */
    protected $userAgent;

    /** @var Response */
    protected $response;

    /** @var array */
    protected $config;

    /** @var array */
    protected $csrfTokenFilter = [
        'filter' => null,
        'attr'   => null,
        'name'   => null,
    ];

    /** @var string */
    protected $csrfToken;

    /** @var bool */
    protected $isLoggedIn = false;

    /** @var WebScraper */
    private $webScraper;

    const CSRF_TYPE_FORM_PARAM = 0;
    const CSRF_TYPE_HEADER = 1;

    public function __construct($webScraper)
    {
        $this->webScraper = $webScraper;

        // TODO: URL and username needs to be passed into the CookieHandler
        $this->cookieHandler = new CookieHandler(new CookieJar());

        $this->client = new Client([
            'cookies' => true,
        ]);

        $this->parser = new Crawler();

        $this->loadConfig();
    }

    /**
     * Make a GuzzleHTTP request
     *
     * @param string $method
     * @param string $uri
     * @param array  $options
     *
     * @return Response|mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(string $method, string $uri = '', array $options = [])
    {
        $this->cookieHandler->setUrl($uri);

        $options = array_merge_recursive([
            'cookies' => $this->cookieHandler->getCookieJar(),
            'headers' => [
                'User-Agent' => $this->userAgent,
            ],
        ], $options);

        $this->response = $this->client->request($method, $uri, $options);

        // Write cookies to disk
        $this->cookieHandler->write();

        // Load HTML into DOM parser
        $this->parser = new Crawler((string)$this->response->getBody());

        return $this->response;
    }

    protected function loadConfig()
    {
        $this->config = config('web-scraper');
        $this->userAgent = $this->config['userAgent'];
    }

    /**
     * @param string $userAgent
     *
     * @return Scraper
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return Crawler
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * @return array
     */
    public function getCsrfTokenFilter(): array
    {
        return $this->csrfTokenFilter;
    }

    /**
     * @param string|array $csrfTokenFilter
     *
     * @return Scraper
     */
    public function setCsrfTokenFilter($csrfTokenFilter)
    {
        if (is_string($csrfTokenFilter)) {
            $this->csrfTokenFilter['filter'] = $csrfTokenFilter;
        } else {
            $this->csrfTokenFilter = array_merge($this->csrfTokenFilter, $csrfTokenFilter);
        }

        return $this;
    }

    /**
     * Get CSRF Token filtered from the DOM
     *
     * @param null         $csrfTokenFilter
     *
     * @param Crawler|null $parser
     *
     * @return \DOMElement|string
     */
    public function getCsrfToken($csrfTokenFilter = null, Crawler $parser = null)
    {
        if ($csrfTokenFilter !== null) {
            $this->setCsrfTokenFilter($csrfTokenFilter);
        }

        if ($parser === null) {
            $parser = $this->parser;
        }

        /** @var \DOMElement $node */
        $node = $parser->filter($this->csrfTokenFilter['filter'])->getNode(0);

        if ($this->csrfTokenFilter['attr'] && $node) {
            $this->csrfToken = $node->getAttribute($this->csrfTokenFilter['attr']);

            return $this->csrfToken;
        }

        return $node;
    }

    /**
     * Login to a website
     *
     * This method will request a page containing the login form, fetch the CSRF token and POST the login data
     *
     * @param string $loginPageUri
     * @param array  $postData
     * @param array  $options
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function login(string $loginPageUri, array $postData, array $options = [])
    {
        $this->checkIfLoggedIn();

        if ($this->isLoggedIn) {
            return;
        }

        // Fetch `client` options for GuzzleHttp
        if ((array_key_exists('client', $options))) {
            $clientOpts = $options['client'];
            unset($options['client']);
        } else {
            $clientOpts = [];
        }

        // Default options for `login`
        $options = array_merge([
            'postUri' => $loginPageUri,
        ], $options);

        $this->request('GET', $loginPageUri);

        if ($this->csrfTokenFilter['filter']) {
            $this->getCsrfToken();
        }

        // Add CSRF token into `postData` if it has already been fetched
        $postData = $this->addCsrfTokenToFormData($postData);

        $clientOpts = array_merge([
            'form_params'     => $postData,
            'allow_redirects' => true,
        ], $clientOpts);

        $response = $this->request('POST', $options['postUri'], $clientOpts);

        // TODO: This should be set depending on the response from the POST/redirect
        $this->isLoggedIn = true;
    }

    /**
     * Fetches the `authTest.url` URL to test whether the user is authenticated or not, by checking for the
     * existence of `authTest.see` on the page
     *
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkIfLoggedIn()
    {
        $options = $this->webScraper->getOptions();

        if (!$options['authTest']['url']) {
            $this->setIsLoggedIn(false);

            return false;
        }

        $response = $this->request('GET', $options['authTest']['url'], [
            'allow_redirects' => true,
        ]);

        $body = (string)$response->getBody();

        preg_match('~' . $options['authTest']['see'] . '~', $body, $matches);

        $loggedIn = (count($matches) > 0);

        $this->setIsLoggedIn($loggedIn);

        return $loggedIn;
    }

    /**
     * Is the user logged into the application
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->isLoggedIn;
    }

    /**
     * Automatically append CSRF token to POST form data
     *
     * @param array $postData
     *
     * @return mixed
     */
    private function addCsrfTokenToFormData(array $postData)
    {
        if ($this->csrfToken) {
            if ($this->csrfTokenFilter['name']) {
                $postData[$this->csrfTokenFilter['name']] = $this->csrfToken;
            } elseif (preg_match('~\[name="(.+?)"\]~', $this->csrfTokenFilter['filter'], $matches)) {
                if (count($matches) == 2) {
                    $postData[$matches[1]] = $this->csrfToken;
                }
            }
        }

        return $postData;
    }

    /**
     * @param bool $isLoggedIn
     *
     * @return Scraper
     */
    public function setIsLoggedIn(bool $isLoggedIn): self
    {
        $this->isLoggedIn = $isLoggedIn;

        return $this;
    }
}
