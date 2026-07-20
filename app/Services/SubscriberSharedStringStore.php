<?php

namespace App\Services;

use RuntimeException;
use XMLReader;
use ZipArchive;

final class SubscriberSharedStringStore
{
    private const INDEX_RECORD_SIZE = 8;

    private const MAX_MEMORY_STRINGS = 200000;

    private ?string $indexFile = null;

    private ?string $dataFile = null;

    /** @var array<int, string>|null */
    private ?array $strings = null;

    /** @var resource|null */
    private $indexHandle = null;

    /** @var resource|null */
    private $dataHandle = null;

    private function __construct(bool $useMemory)
    {
        if ($useMemory) {
            $this->strings = [];

            return;
        }

        $this->indexFile = (string) tempnam(sys_get_temp_dir(), 'xlsx_sst_index');
        $this->dataFile = (string) tempnam(sys_get_temp_dir(), 'xlsx_sst_data');
        $this->indexHandle = fopen($this->indexFile, 'w+b');
        $this->dataHandle = fopen($this->dataFile, 'w+b');

        if ($this->indexHandle === false || $this->dataHandle === false) {
            $this->close();
            throw new RuntimeException('Failed to create shared string temporary files.');
        }
    }

    /**
     * Build a memory- or disk-backed shared-string lookup for an XLSX file.
     */
    public static function create(string $file): self
    {
        $store = null;
        $reader = new XMLReader;
        $zip = new ZipArchive;

        if ($zip->open($file) !== true) {
            return new self(true);
        }

        $hasSharedStrings = $zip->locateName('xl/sharedStrings.xml') !== false;
        $zip->close();

        if (! $hasSharedStrings || ! @$reader->open('zip://'.$file.'#xl/sharedStrings.xml')) {
            return new self(true);
        }

        try {
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'sst' && $store === null) {
                    $uniqueCount = (int) ($reader->getAttribute('uniqueCount') ?: $reader->getAttribute('count'));
                    $store = new self($uniqueCount <= self::MAX_MEMORY_STRINGS);
                }

                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'si') {
                    continue;
                }

                $store ??= new self(true);
                $store->append($store->readSharedString($reader->readOuterXml()));
            }
        } finally {
            $reader->close();
        }

        return $store ?? new self(true);
    }

    /**
     * Return a shared string by its zero-based index.
     */
    public function get(int $index): string
    {
        if ($index < 0) {
            return '';
        }

        if ($this->strings !== null) {
            return $this->strings[$index] ?? '';
        }

        if (! is_resource($this->indexHandle) || ! is_resource($this->dataHandle)) {
            return '';
        }

        if (fseek($this->indexHandle, $index * self::INDEX_RECORD_SIZE) !== 0) {
            return '';
        }

        $offsetBytes = fread($this->indexHandle, self::INDEX_RECORD_SIZE);

        if ($offsetBytes === false || strlen($offsetBytes) !== self::INDEX_RECORD_SIZE) {
            return '';
        }

        $parts = unpack('Vlow/Vhigh', $offsetBytes);

        if ($parts === false) {
            return '';
        }

        $offset = (int) $parts['low'] + ((int) $parts['high'] * 4294967296);

        if (fseek($this->dataHandle, $offset) !== 0) {
            return '';
        }

        $lengthBytes = fread($this->dataHandle, 4);

        if ($lengthBytes === false || strlen($lengthBytes) !== 4) {
            return '';
        }

        $lengthData = unpack('Vlength', $lengthBytes);
        $length = $lengthData === false ? 0 : (int) $lengthData['length'];

        return $length === 0 ? '' : (string) fread($this->dataHandle, $length);
    }

    /**
     * Close handles and remove temporary files.
     */
    public function close(): void
    {
        if (is_resource($this->indexHandle)) {
            fclose($this->indexHandle);
        }

        if (is_resource($this->dataHandle)) {
            fclose($this->dataHandle);
        }

        $this->indexHandle = null;
        $this->dataHandle = null;

        if ($this->indexFile !== null) {
            @unlink($this->indexFile);
            $this->indexFile = null;
        }

        if ($this->dataFile !== null) {
            @unlink($this->dataFile);
            $this->dataFile = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    private function append(string $value): void
    {
        if ($this->strings !== null) {
            $this->strings[] = $value;

            return;
        }

        if (! is_resource($this->indexHandle) || ! is_resource($this->dataHandle)) {
            return;
        }

        $offset = ftell($this->dataHandle);

        if ($offset === false) {
            return;
        }

        $low = $offset & 0xFFFFFFFF;
        $high = intdiv($offset, 4294967296);

        fwrite($this->indexHandle, pack('V2', $low, $high));
        fwrite($this->dataHandle, pack('V', strlen($value)).$value);
    }

    private function readSharedString(string $xml): string
    {
        $element = simplexml_load_string($xml);

        if ($element === false) {
            return '';
        }

        $value = '';

        foreach ($element->xpath('.//*[local-name()="t"]') ?: [] as $textNode) {
            $value .= (string) $textNode;
        }

        return $value;
    }
}
