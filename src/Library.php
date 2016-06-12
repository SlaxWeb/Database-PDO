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

use PDO;
use SlaxWeb\Database\Error;
use SlaxWeb\Database\Exception\NoErrorException;

class Library implements \SlaxWeb\Database\LibraryInterface
{
    /**
     * SQL Object Delimiter
     *
     * Defaults to "\"" for all major RDBs, except for MYSQL.
     *
     * @var string
     */
    protected $_delim = "\"";

    /**
     * PDO instance
     *
     * @var \PDO
     */
    protected $_pdo = null;

    /**
     * Database Error Object
     *
     * @var \SlaxWeb\Database\Error
     */
    protected $_error = null;

    /**
     * Class constrcutor
     *
     * Initiates the class and assigns the dependencies to local properties for
     * later use.
     *
     * @param \PDO $pdo PDO instance
     * @return void
     */
    public function __construct(PDO $pdo)
    {
        $this->_pdo = $pdo;
    }

    /**
     * Insert row
     *
     * Inserts a row into the database with the provided data. Returns bool(true)
     * on success and bool(false) on failure. On failure it instantiates the error
     * object, and sets it to a local property for later retrieval.
     *
     * @param string $table Table to which the data is to be inserted
     * @param array $data Data to be inserted
     * @return bool
     */
    public function insert(string $table, array $data): bool
    {
        $table = "{$this->_delim}{$table}{$this->_delim}";
        $query = "INSERT INTO {$table} (\""
            . implode("\",\"", array_keys($data))
            . "\") VALUES ("
            . str_repeat("?,", count($data))
            . ");";
        if (($statement = $this->_pdo->prepare($query)) === false) {
            $this->_error = new Error($this->_pdo->errorInfo()[2]);
            return false;
        }
        if ($statement->execute(array_values($data)) === false) {
            $this->_error = new Error($statement->errorInfo()[2]);
            return false;
        }
        return true;
    }

    /**
     * Get last error
     *
     * Retrieves the error of the last executed query. If there was no error, an
     * exception must be thrown.
     *
     * @return \SlaxWeb\Database\Error
     *
     * @exceptions \SlaxWeb\Database\Exception\NoErrorException
     */
    public function lastError(): Error
    {
        if ($this->_error === null) {
            throw new NoErrorException;
        }

        return $this->_error;
    }

    /**
     * Set error
     *
     * Sets the error based on PDOs error info.
     *
     * @return void
     */
    protected function _setError()
    {
        $this->_error = new Error($this->_pdo->errorInfo()[2]);
    }
}
