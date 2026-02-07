<?php

namespace App\Helpers;

class UpdateHelper
{
    private $language;
    private string $url = 'http://license.janickiy.com/';
    private string $currentVersion;

    public function __construct(string $language, string $currentVersion)
    {
        $this->language = $language;
        $this->currentVersion = $currentVersion;
    }

    /**
     * @return bool
     */
    public function checkNewVersion(): bool
    {
        return $this->checkVersion($this->getVersion(), $this->currentVersion);
    }

    /**
     * @return bool
     */
    public function checkUpgrade(): bool
    {
        return $this->checkVersion($this->getUpgradeVersion(), $this->currentVersion);
    }

    /**
     * @return string
     */
    public function getUrlInfo(): string
    {
        return $this->url . '?id=5&version=' . urlencode($this->currentVersion) . '&lang=' . $this->language . '&ip=' . $this->getIP();
    }

    /**
     * @param string $url
     * @param int $timeout
     * @return mixed|string
     */
    public function getDataContents(string $url, int $timeout = 10): mixed
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 0);
        curl_setopt($ch, CURLOPT_REFERER, isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $data = curl_exec($ch);

        curl_close($ch);

        preg_match('/\{([^\}])+\}/', $data, $out);

        return isset($out[0]) ? json_decode($out[0], true) : '';
    }

    /**
     * @return bool
     */
    public function checkTree(): bool
    {
        preg_match("/(\d+)\.(\d+)\.(\d+)/", $this->currentVersion, $out);

        if ($out[1] < $out[2]) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        $out = $this->getDataContents($this->getUrlInfo());

        return $out["version"] ?? '';
    }

    /**
     * @return string
     */
    public function getDownloadLink(): string
    {
        $out = $this->getDataContents($this->getUrlInfo());

        return $out['download'] ?? '';
    }

    /**
     * @return string
     */
    public function getUpdateLink(): string
    {
        $out = $this->getDataContents($this->getUrlInfo());

        return $out['update'] ?? '';
    }

    /**
     * @return string
     */
    public function getCreated(): string
    {
        $out = $this->getDataContents($this->getUrlInfo());

        return $out['created'] ?? '';
    }

    /**
     * @return string
     */
    public function getUpdate(): string
    {
        $out = $this->getDataContents($this->getUrlInfo());

        return $out['update'] ?? '';
    }

    /**
     * @return string
     */
    public function getUpgradeVersion(): string
    {
        $out = $this->getDataContents($this->getUrlInfo());

        return $out['upgrade_version'] ?? '';
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        $out = $this->getDataContents($this->getUrlInfo());

        return $out['message'] ?? '';
    }

    /**
     * @return string
     */
    public function getIP(): string
    {
        if (getenv("HTTP_CLIENT_IP") and strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
            $ip = getenv("HTTP_CLIENT_IP");
        elseif (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        elseif (getenv("REMOTE_ADDR") and strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
            $ip = getenv("REMOTE_ADDR");
        elseif (!empty($_SERVER['REMOTE_ADDR']) and strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = "unknown";

        return $ip;
    }

    /**
     * @param string $version
     * @param string $currentVersion
     * @return bool
     */
    private function checkVersion(string $version, string $currentVersion): bool
    {
        if ($version) {
            preg_match("/(\d+)\.(\d+)\.(\d+)/", $currentVersion, $out1);
            preg_match("/(\d+)\.(\d+)\.(\d+)/", $version, $out2);

            $v1 = ($out1[1] * 10000 + $out1[2] * 100 + $out1[3]);
            $v2 = ($out2[1] * 10000 + $out2[2] * 100 + $out2[3]);

            if ($v2 > $v1) return true;
        }

        return false;
    }
}
