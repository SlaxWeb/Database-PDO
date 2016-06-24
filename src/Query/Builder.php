<?php
/**
 * Query Builder
 *
 * The Query Builder is used to do exactly what its name suggests. Build SQL queries
 * for execution.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\DatabasePDO\Query;

class Builder
{
    /**
     * Table
     *
     * @var string
     */
    protected $_table = "";

    /**
     * Parameters
     *
     * @var array
     */
    protected $_params = [];

    /**
     * SQL Object Delimiter
     *
     * @var string
     */
    protected $_delim = "";

    /**
     * Set DB Object Delimiter
     *
     * Sets the Database Object Delimiter character that will be used for creating
     * the query.
     *
     * @param string $delim Delimiter character
     * @return self
     */
    public function setDelim(string $delim): self
    {
        $this->_delim = $delim;
        return $this;
    }

    /**
     * Set table
     *
     * Sets the table name for the query. Before setting it wraps it in the delimiters.
     *
     * @param string $table Name of the table
     * @return self
     */
    public function table(string $table): self
    {
        $this->_table = $this->_delim . $table . $this->_delim;
        return $this;
    }

    /**
     * Get Bind Parameters
     *
     * Returns the parameters prepared for binding into the prepared statement.
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->_params;
    }

    /**
     * Get SELECT query
     *
     * Construct the query with all the information gathered and return it. The
     * Method retrieves a column list as an input parameter. All columns are wrapped
     * in the delimiters to prevent collision with reserved keywords. If the array
     * item key is not numeric, its string value is interpreted as an SQL function.
     *
     * @param array $cols Column definitions
     * @return string
     *
     * @todo: build complicated select statements when additional where predicates
     *        are defined, joins, group bys, etc.
     */
    public function select(array $cols): string
    {
        $query = "SELECT ";
        foreach ($cols as $key => $name) {
            $name = $this->_delim . $name . $name;
            if (is_string($key)) {
                $query .= "{$key}({$name}),";
            } else {
                $query .= "{$name},";
            }
        }
        $query .= " FROM {$this->_table}";

        return $query;
    }
}
