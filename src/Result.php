<?php
/**
 * Database Result Class
 *
 * The result class holds the result of an executed statement, which is easy to
 * navigate and retrieve data from.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\DatabasePDO;

use Slaxweb\Database\Exception\RowNotFoundException;
use Slaxweb\Database\Exception\ColumnNotFoundException;
use SlaxWeb\Database\Interfaces\Result as ResultInterface;

class Result implements ResultInterface
{
    /**
     * Raw result data
     *
     * @var array
     */
    protected $_rawData = [];

    /**
     * Number of rows
     *
     * @var int
     */
    protected $_rows = 0;

    /**
     * Current row pointer index
     *
     * @var int
     */
    protected $_currRow = -1;

    /**
     * Class constructor
     *
     * Save the raw result data into the protected property '$_rawData'.
     *
     * @param array $rawData Raw data array
     */
    public function __construct(array $rawData)
    {
        $this->_rawData = $rawData;
        $this->_rows = count($this->_rawData);
    }

    /**
     * Magic Get Method
     *
     * Retrieves the result row column value and returns it to the caller. If the
     * internal index is not pointing to a valid row, or the requested column is
     * not part of the result set, an exception is thrown.
     *
     * @param string $name Name of the column
     * @return mixed
     *
     * @exceptions \SlaxWeb\Database\Exception\RowNotFoundException
     *             \SlaxWeb\Database\Exception\ColumnNotFoundException
     */
    public function __get(string $name)
    {
        if (isset($this->_rawData[$this->_currRow]) === false) {
            throw new RowNotFoundException("The requested row does not exist in the current result set.");
        }
        if (isset($this->_rawData[$this->_currRow]->{$name}) === false) {
            throw new ColumnNotFoundException("The requested column does not exist in the current result set.");
        }
        return $this->_rawData[$this->_currRow]->{$name};
    }

    /**
     * Next row
     *
     * Move the internal pointer to the next row of the result array. If there is
     * no row found under the next index, bool(false) is returned, otherwise bool(true)
     * is returned.
     *
     * @return bool
     */
    public function next(): bool
    {
        if (isset($this->_rawData[$this->_currRow + 1])) {
            $this->_currRow++;
            return true;
        }
        return false;
    }

    /**
     * Previous row
     *
     * Move the internal pointer to the previous row of the result array. If there
     * is no row found under the previous index, bool(false) is returned, otherwise
     * bool(true) is returned.
     *
     * @return bool
     */
    public function prev(): bool
    {
        if (isset($this->_rawData[$this->_currRow - 1])) {
            $this->_currRow--;
            return true;
        }
        return false;
    }

    /**
     * Jump to row
     *
     * Move the internal pointer to the passed in row of the result array. If there
     * is no row found under the passed in row, bool(false) is returned, otherwise
     * bool(true) is returned.
     *
     * @param int $row Row number
     * @return bool
     */
    public function row(int $row): bool
    {
        if (isset($this->_rawData[--$row])) {
            $this->_currRow = $row;
            return true;
        }
        return false;
    }

    /**
     * Get row count
     *
     * Get the row count of the result set.
     *
     * @return int
     */
    public function rowCount(): int
    {
        return $this->_rows;
    }

    /**
     * Get result set
     *
     * Returns the raw result set array to the caller.
     *
     * @return array
     */
    public function getResults(): array
    {
        return $this->_rawData;
    }

    /**
     * Get Row
     *
     * Returns the row object to the caller. If the row does not exists, an exception
     * is thrown.
     *
     * @return \stdClass
     *
     * @exceptions \SlaxWeb\Database\Exception\RowNotFoundException
     */
    public function get(): \stdClass
    {
        if (isset($this->_rawData[$this->_currRow]) === false) {
            throw new RowNotFoundException("The requested row does not exist in the current result set.");
        }

        return $this->_rawData[$this->_currRow];
    }
}
