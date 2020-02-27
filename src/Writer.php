<?php

namespace JBroutier\Csv;

use JBroutier\Csv\Exception\IOException;
use JBroutier\Csv\Exception\LogicException;
use JBroutier\Csv\Exception\SyntaxErrorException;

/**
 * Class Writer
 *
 * @package JBroutier\Csv
 */
class Writer
{
    /**
     * @var string The field delimiter character.
     */
    protected $delimiter = ',';

    /**
     * @var string The field enclosure character.
     */
    protected $enclosure = '"';

    /**
     * @var string The escape character.
     */
    protected $escape = '\\';

    /**
     * @var string The path to the file.
     */
    protected $filename;

    /**
     * @var resource The file handle.
     */
    protected $handle;

    /**
     * @var array|bool The header fields, or whether the header fields should be ignored.
     */
    protected $header = false;

    /**
     * @var string|null The source encoding, or null to automatically detect the encoding.
     */
    protected $sourceEncoding = null;

    /**
     * @var string The target encoding.
     */
    protected $targetEncoding = 'UTF-8';

    /**
     * Writer constructor.
     *
     * @param string $filename The path to the file.
     * @param string $mode     The opening mode of the file. Defaults to 'w'.
     *
     * @throws IOException If an IO error occurs.
     */
    public function __construct(string $filename, string $mode = 'w')
    {
        $this->filename = $filename;

        if (false === ($this->handle = fopen($this->filename, $mode))) {
            throw new IOException(sprintf('Unable to open the file "%s".', $this->filename));
        }

        if (false === flock($this->handle, LOCK_EX)) {
            throw new IOException(sprintf('Unable to acquire exclusive lock on file "%s".', $this->filename));
        }
    }

    /**
     * Encodes a string with the proper character encoding.
     *
     * @param string $str The string being encoded.
     *
     * @return string The encoded string.
     */
    protected function convertEncoding(string $str): string
    {
        return mb_convert_encoding($str, $this->targetEncoding, $this->sourceEncoding ?? mb_internal_encoding());
    }

    /**
     * Writes data to the file.
     *
     * @param iterable $iterator An iterable containing the data of each row.
     * @param callable $callback The callback function to call at each iteration.
     *
     * The callback function should have the following signature:
     * function($item, int $rownum): array|bool
     *
     * The first parameter of the callback function is the current value of the iterable. The second parameter is an
     * integer representing the current position of the iterable. The callback function must return an array containing
     * fields data, or false to exit the loop.
     *
     * @throws IOException If an IO error occurs.
     * @throws LogicException If the callback function returns an unexpected value.
     * @throws SyntaxErrorException If the number of fields in a row does not match the number of header fields.
     */
    public function write(iterable $iterator, callable $callback): void
    {
        if (is_array($this->header)) {
            $header = array_map([$this, 'convertEncoding'], $this->header);

            if (false === fputcsv($this->handle, $header, $this->delimiter, $this->enclosure, $this->escape)) {
                throw new IOException(sprintf('Unable to write to file "%s".', $this->filename));
            }
        }

        $rownum = 1;

        foreach ($iterator as $item) {
            if (false === ($row = call_user_func($callback, $item, $rownum))) {
                break;
            }

            if (!is_array($row)) {
                throw new LogicException('Callback must return an array or false.');
            }

            $row = array_map([$this, 'convertEncoding'], $row);

            if (is_array($this->header)) {
                $header = array_map([$this, 'convertEncoding'], $this->header);

                if (false === ($row = array_combine($header, $row))) {
                    throw new SyntaxErrorException(sprintf('%s columns were expected but %s were found on line %s.',
                        count($this->header), count($row), $rownum
                    ));
                }
            }

            if (false === fputcsv($this->handle, $row, $this->delimiter, $this->enclosure, $this->escape)) {
                throw new IOException(sprintf('Unable to write line to file "%s".', $this->filename));
            }

            $rownum++;
        }

        if (false === flock($this->handle, LOCK_UN)) {
            throw new IOException(sprintf('Unable to release lock on file "%s".', $this->filename));
        }

        fclose($this->handle);
    }

    /**
     * Returns the field delimiter character.
     *
     * @return string The field delimiter character.
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * Sets the field delimiter character.
     *
     * @param string $delimiter The field delimiter character.
     *
     * @return Writer
     */
    public function setDelimiter(string $delimiter): Writer
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    /**
     * Returns the field enclosure character.
     *
     * @return string The field enclosure character.
     */
    public function getEnclosure(): string
    {
        return $this->enclosure;
    }

    /**
     * Sets the field enclosure character.
     *
     * @param string $enclosure The field enclosure character.
     *
     * @return Writer
     */
    public function setEnclosure(string $enclosure): Writer
    {
        $this->enclosure = $enclosure;

        return $this;
    }

    /**
     * Returns the escape character.
     *
     * @return string The escape character.
     */
    public function getEscape(): string
    {
        return $this->escape;
    }

    /**
     * Sets the escape character.
     *
     * @param string $escape The escape character.
     *
     * @return Writer
     */
    public function setEscape(string $escape): Writer
    {
        $this->escape = $escape;

        return $this;
    }

    /**
     * Returns the header fields or whether the header fields should be ignored.
     *
     * @return array|bool An array containing the header fields, or false if the header fields should be ignored.
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Sets the header fields or whether the header fields should be ignored.
     *
     * @param array|bool $header An array containing the header fields, or false if the header fields should be ignored.
     *
     * @return Writer
     */
    public function setHeader($header): Writer
    {
        $this->header = $header;

        return $this;
    }

    /**
     * Returns the source encoding.
     *
     * @return string|null The source encoding, or null if the encoding should be automatically detected.
     */
    public function getSourceEncoding(): ?string
    {
        return $this->sourceEncoding;
    }

    /**
     * Sets the source encoding.
     *
     * @param string|null $sourceEncoding The source encoding, or null if the encoding should be automatically detected.
     *
     * @return Writer
     */
    public function setSourceEncoding(?string $sourceEncoding): Writer
    {
        $this->sourceEncoding = $sourceEncoding;

        return $this;
    }

    /**
     * Returns the target encoding.
     *
     * @return string The target encoding.
     */
    public function getTargetEncoding(): string
    {
        return $this->targetEncoding;
    }

    /**
     * Sets the target encoding.
     *
     * @param string $targetEncoding The target encoding.
     *
     * @return Writer
     */
    public function setTargetEncoding(string $targetEncoding): Writer
    {
        $this->targetEncoding = $targetEncoding;

        return $this;
    }
}
