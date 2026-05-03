<?php

namespace App\Helpers;

class UpdateHelper
{
    private $language;
    private string $url = 'http://license.janickiy.com/';
    private string $currentVersion;
    private bool $updateInfoLoaded = false;

    /** @var array<string, mixed> */
    private array $updateInfo = [];

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

        if (!is_string($data) || $data === '') {
            return '';
        }

        preg_match('/\{([^\}])+\}/', $data, $out);

        if (!isset($out[0])) {
            return '';
        }

        $decoded = json_decode($out[0], true);

        return is_array($decoded) ? $decoded : '';
    }

    /**
     * @return bool
     */
    public function checkTree(): bool
    {
        if (!preg_match("/^(\d+)\.(\d+)\.(\d+)$/", $this->currentVersion, $out)) {
            return false;
        }

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
        $out = $this->getUpdateInfo();

        return $out["version"] ?? '';
    }

    /**
     * @return string
     */
    public function getDownloadLink(): string
    {
        $out = $this->getUpdateInfo();

        return $out['download'] ?? '';
    }

    /**
     * @return string
     */
    public function getUpdateLink(): string
    {
        $out = $this->getUpdateInfo();

        return $out['update'] ?? '';
    }

    /**
     * @return string
     */
    public function getCreated(): string
    {
        $out = $this->getUpdateInfo();

        return $out['created'] ?? '';
    }

    /**
     * @return string
     */
    public function getUpdate(): string
    {
        $out = $this->getUpdateInfo();

        return $out['update'] ?? '';
    }

    /**
     * @return string
     */
    public function getUpgradeVersion(): string
    {
        $out = $this->getUpdateInfo();

        return $out['upgrade_version'] ?? '';
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        $out = $this->getUpdateInfo();

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
        foreach ([$version, $currentVersion] as $value) {
            if (!preg_match("/^\d+\.\d+\.\d+$/", $value)) {
                return false;
            }
        }

        return version_compare($version, $currentVersion, '>');
    }

    /**
     * Load update metadata once per request.
     *
     * @return array<string, mixed>
     */
    private function getUpdateInfo(): array
    {
        if ($this->updateInfoLoaded) {
            return $this->updateInfo;
        }

        $this->updateInfoLoaded = true;
        $data = $this->getDataContents($this->getUrlInfo());
        $this->updateInfo = is_array($data) ? $data : [];

        return $this->updateInfo;
    }
}
