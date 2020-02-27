<?php

namespace JBroutier\Csv;

use JBroutier\Csv\Exception\IOException;
use JBroutier\Csv\Exception\SyntaxErrorException;

/**
 * Class Reader
 *
 * @package JBroutier\Csv
 */
class Reader
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
     * @var array|bool The header fields, or whether the header fields should be read from the file.
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
     * Reader constructor.
     *
     * @param string $filename The path to the file.
     *
     * @throws IOException If an IO error occurs.
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;

        if (false === ($this->handle = fopen($this->filename, 'r'))) {
            throw new IOException(sprintf('Unable to open the file "%s".', $this->filename));
        }

        if (false === flock($this->handle, LOCK_SH)) {
            throw new IOException(sprintf('Unable to acquire shared lock on file "%s".', $this->filename));
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
     * Returns the number of lines in the file, ignoring header fields and empty lines.
     *
     * @return int The number of lines in the file.
     */
    public function count(): int
    {
        $count = 0;

        $offset = ftell($this->handle);
        rewind($this->handle);

        while (!feof($this->handle)) {
            if (strlen(fgets($this->handle))) {
                $count++;
            }
        }

        fseek($this->handle, $offset);

        if (false !== $this->header) {
            $count--;
        }

        return $count;
    }

    /**
     * Reads data from the file.
     *
     * @param callable $callback The callback function to call each time a row is read from the file.
     *
     * The callback function should have the following signature:
     * function(array $row, int $rownum): void
     *                           
     * The first parameter of the callback function is an array containing fields data. The second parameter is an
     * integer representing the current row number.
     *
     * @throws IOException If an IO error occurs.
     * @throws SyntaxErrorException If the number of fields in a row does not match the number of header fields.
     */
    public function read(callable $callback)
    {
        $rownum = 0;

        rewind($this->handle);

        while (!feof($this->handle)) {
            if (is_null($row = fgetcsv($this->handle, 0, $this->delimiter, $this->enclosure, $this->escape))) {
                throw new IOException(sprintf('Unable to read from file "%s".', $this->filename));
            }

            if (false === $row) {
                continue;
            }

            $row = array_map([$this, 'convertEncoding'], $row);

            if (false !== $this->header && 0 === $rownum) {
                if (true === $this->header) {
                    $this->header = $row;
                }

                $rownum++;
                continue;
            }

            if (is_array($this->header)) {
                if (false === ($row = array_combine($this->header, $row))) {
                    throw new SyntaxErrorException(sprintf('%s columns were expected but %s were found on line %s.',
                        count($this->header), count($row), $rownum
                    ));
                }
            }

            call_user_func($callback, $row, $rownum);
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
     * @return Reader
     */
    public function setDelimiter(string $delimiter): Reader
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
     * @return Reader
     */
    public function setEnclosure(string $enclosure): Reader
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
     * @return Reader
     */
    public function setEscape(string $escape): Reader
    {
        $this->escape = $escape;

        return $this;
    }

    /**
     * Returns the header fields or whether the header fields should be read from the file.
     *
     * @return array|bool An array containing the header fields, true if the header fields should be read from the file,
     *                    or false if the header fields should be ignored.
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Sets the header fields or whether the header fields should be read from the file.
     *
     * @param array|bool $header An array containing the header fields, true if the header fields should be read from
     *                           the file, or false if the header fields should be ignored.
     *
     * @return Reader
     */
    public function setHeader($header): Reader
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
     * @return Reader
     */
    public function setSourceEncoding(?string $sourceEncoding): Reader
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
     * @return Reader
     */
    public function setTargetEncoding(string $targetEncoding): Reader
    {
        $this->targetEncoding = $targetEncoding;

        return $this;
    }
}
