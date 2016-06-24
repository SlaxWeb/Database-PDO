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
use PDOStatement;
use SlaxWeb\Database\Error;
use SlaxWeb\DatabasePDO\Query\Builder;
use SlaxWeb\Database\Exception\NoErrorException;
use SlaxWeb\DatabasePDO\Exception\QueryException;
use SlaxWeb\DatabasePDO\Exception\NoDataException;
use SlaxWeb\Database\Interfaces\Result as ResultInterface;

class Library implements \SlaxWeb\Database\Interfaces\Library
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
     * Query Builder
     *
     * @var \SlaxWeb\DatabasePDO\Query\Builder
     */
    protected $_qBuilder = null;

    /**
     * Last Executed Statement
     *
     * @var \PDOStatement
     */
    protected $_stmnt = null;

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
     * @param \SlaxWeb\DatabasePDO\Query\Builder $queryBuilder Query Builder instance
     * @return void
     */
    public function __construct(PDO $pdo, Builder $queryBuilder)
    {
        $this->_pdo = $pdo;
        $this->_qBuilder = $queryBuilder;
        $this->_qBuilder->setDelim($this->_delim);
    }

    /**
     * Execute Query
     *
     * Executes the received query and binds the received parameters into the query
     * to decrease the chance of an SQL injection. Returns bool(true) if query was
     * successfuly executed, and bool(false) if it was not. If the query yielded
     * a result set, a Result object will be populated.
     *
     * @param string $query The Query to be executed
     * @param array $data Data to be bound into the Query, default []
     * @return bool
     */
    public function execute(string $query, array $data = []): bool
    {
        if (($this->_stmnt = $this->_pdo->prepare($query)) === false) {
            $this->_error = new Error($this->_pdo->errorInfo()[2]);
            return false;
        }
        if ($this->_stmnt->execute(array_values($data)) === false) {
            $this->_error = new Error($this->_stmnt->errorInfo()[2]);
            return false;
        }
        return true;
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
        $query = "INSERT INTO {$table} ({$this->_delim}"
            . implode("{$this->_delim},{$this->_delim}", array_keys($data))
            . "{$this->_delim}) VALUES ("
            . rtrim(str_repeat("?,", count($data)), ",")
            . ");";
        return $this->execute($query, $data);
    }

    /**
     * Select query
     *
     * Run a select query against the database and return the result set if it was
     * successful. Throw an exception on error. The input array defines a list of
     * columns that need to get selected from the database. If a SQL function should
     * be executed on a column, like i.e., MAX, then the key of that value should
     * be the name of that function. If the key is numeric, the column is used normally
     * in the query.
     *
     * @param string $table Table on which the select statement is to be executed
     * @param array $cols Array of columns for the SELECT statement
     * @return \SlaxWeb\DatabasePDO\Result
     *
     * @exceptions \SlaxWeb\DatabasePDO\Exception\QueryException
     *             \SlaxWeb\DatabasePDO\Exception\NoDataException
     */
    public function select(string $table, array $cols): ResultInterface
    {
        $query = $this->_qBuilder
            ->table($table)
            ->select($cols);
        if ($this->execute($query, $this->_qBuilder->getParams()) === false) {
            throw new QueryException("Query execution resulted in an error");
        }
        return $this->fetch();
    }

    /**
     * Fetch Results
     *
     * It fetches the results from the last executed statement, creates the Result
     * object and returns it. If an statement has not yet been executed or did not
     * yield a valid result set, an exception is thrown.
     *
     * @return \SlaxWeb\DatabasePDO\Result
     *
     * @exceptions \SlaxWeb\DatabasePDO\Exception\NoDataException
     */
    public function fetch(): ResultInterface
    {
        if (!($this->_stmnt instanceof PDOStatement)) {
            throw new NoDataException("No statement has yet been executed. Unable to fetch data.");
        }
        if (is_array(($result = $this->_stmnt->fetchAll(PDO::FETCH_OBJ))) === false) {
            throw new NoDataException("Statement did not yield a valid result set.");
        }

        return new Result($result);
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
