<?php

namespace JamesAusten\WebScraper\Cookie;

use Crypt;
use GuzzleHttp\Cookie\CookieJar;

class CookieHandler
{
    /** @var CookieJar */
    protected $cookieJar;

    protected $filePath;

    protected $url;

    protected $username;

    /** @var array */
    protected $config;

    public function __construct(CookieJar $cookieJar, $url = null, $username = null)
    {
        $this->cookieJar = $cookieJar;
        $this->url = $url;
        $this->username = $username;

        $this->config = config('web-scraper.cookies');

        if (!is_null($url)) {
            $this->setFilePath();
        }
    }

    /**
     * Serialise and write CookieJar to disk
     */
    public function write()
    {
        $jarData = serialize($this->cookieJar);

        if ($this->config['encrypt']) {
            $jarData = Crypt::encrypt($jarData);
        }

        // TODO: This should use either 'league/flysystem' or 'symfony/filesystem'
        $f = fopen($this->filePath, 'w+');
        @fwrite($f, $jarData);
        @fclose($f);
    }

    /**
     * Read and unserialise CookieJar from disk
     *
     * @return CookieJar
     */
    public function read()
    {
        if (file_exists($this->filePath)) {
            $jarData = file_get_contents($this->filePath);

            if ($this->config['encrypt']) {
                $jarData = Crypt::decrypt($jarData);
            }

            $this->cookieJar = unserialize($jarData);
        }

        return $this->cookieJar;
    }

    /**
     * Set the Cookie filePath
     *
     * @return mixed
     */
    private function setFilePath()
    {
        $directory = $this->config['directory'];

        $this->filePath = $directory . DIRECTORY_SEPARATOR . $this->formatFileName();

        // Ensure cookie directory exists
        if (!file_exists($directory)) {
            mkdir($directory);
        }
    }

    /**
     * Extract domain from URL and format the configured filename
     *
     * @return mixed
     */
    private function formatFileName()
    {
        $fileName = $this->config['fileName'];

        $domain = parse_url($this->url, PHP_URL_HOST);

        $username = ($this->username) ?: 'unauthenticated';

        $fileName = str_replace('{domain}', basename($domain), $fileName);
        $fileName = str_replace('{username}', basename($username), $fileName);

        return $fileName;
    }

    /**
     * @return CookieJar
     */
    public function getCookieJar()
    {
        return $this->cookieJar;
    }

    /**
     * @param CookieJar $cookieJar
     */
    public function setCookieJar($cookieJar)
    {
        $this->cookieJar = $cookieJar;
    }

    /**
     * @param mixed $url
     *
     * @return CookieHandler
     */
    public function setUrl($url)
    {
        $this->url = $url;
        $this->setFilePath();
        $this->read();
        return $this;
    }

    /**
     * @param null $username
     *
     * @return CookieHandler
     */
    public function setUsername($username)
    {
        $this->username = $username;
        $this->setFilePath();
        $this->read();
        return $this;
    }
}
