<?php
/**
 * PDO Database Library
 *
 * PDO Database Library for SlaxWeb Framework provides connection to a RDB with
 * the help of the PHP Data Objects, or PDO.
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
}
