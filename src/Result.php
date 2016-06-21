<?php
/**
 * Database Result Class
 *
 * The result class holds the result of an executed statement, which is easy to
 * navigate and retrieve data from. It also provides functionality to easily update
 * the programatically changed data in a result set.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\DatabasePDO;

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
     * @return void
     */
    public function __construct(array $rawData)
    {
        $this->_rawData = $rawData;
        $this->_rows = count($this->_rawData);
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
        return isset($this->_rawData[++$this->_currRow]);
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
        return isset($this->_rawData[--$this->_currRow]);
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
     * Get Row
     *
     * Returns the row object to the caller. If the row does not exists, an exception
     * is thrown.
     *
     * @return \stdClass
     *
     * @exceptions \SlaxWeb\Database\Exception\ResultRowNotFoundException
     */
    public function get(): \stdClass
    {
        if (isset($this->_rawData[$this->_currRow]) === false) {
            // @todo: throw exception
        }

        return $this->_rawData[$this->_currRow];
    }
}
