<?php
/**
 * Where Statement Predicate
 *
 * The Where Statement Predicate defines a Column, a value, and an comparison operator
 * for the SQL DML predicate.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\DatabasePDO\Query\Where;

class Predicate
{
    /**
     * Comparison operators
     */
    const OPR_EQUAL = "=";
    const OPR_DIFF = "<>";
    const OPR_GRTR = ">";
    const OPR_LESS = "<";
    const OPR_GRTREQ = ">=";
    const OPR_LESSEQ = "<=";
    const OPR_IN = "IN";
    const OPR_NOTIN = "NOT IN";
    const OPR_LIKE = "LIKE";
    const OPR_BTWN = "BETWEEN";
    const OPR_NULL = "IS NULL";
    const OPR_NOTNULL = "IS NOT NULL";

    /**
     * Column name
     *
     * @var string
     */
    protected $_col = "";

    /**
     * Value
     *
     * @var mixed
     */
    protected $_val = null;

    /**
     * Comparison operator
     *
     * @var string
     */
    protected $_opr = self::OPR_EQUAL;

    /**
     * Convert to string
     *
     * Convert the Predicate to string. It checks that the value and the comparison
     * operator are valid and an SQL DML can safely be constructed with those values.
     * If not, an exception is thrown.
     *
     * @return string
     */
    public function convert(): string
    {
        $predicate = "{$this->_col} {$this->_opr} ";
        switch ($this->_opr) {
            case self::OPR_NULL:
            case self::OPR_NOTNULL:
                if ($this->_val !== null || strtolower($this->_val) !== "null") {
                    // @todo: throw exception
                }
                $predicate = rtrim($predicate);
                break;

            case self::OPR_BTWN:
                if (is_array($this->_val) === false || count($this->_val) !== 2) {
                    // @todo: throw exception
                }
                $predicate .= $this->_prepArray($this->_val, " AND ");
                break;

            case self::OPR_IN:
            case self::OPR_NOTIN:
                // @todo: extend to allow another model to be the value(maybe a query as well?)
                if (is_array($this->_val) === false) {
                    // @todo: throw exception
                }
                $predicate .= $this->_prepArray($this->_val);
                break;

            default:
                $predicate .= is_string($this->_val) ? "'{$this->_val}'" : $this->_val;
        }

        return $predicate;
    }

    /**
     * Set column name
     *
     * Sets the column name and returns itself for method call linking.
     *
     * @param string $column Column name
     * @return self
     */
    public function setColumn(string $column): self
    {
        $this->_col = $column;
        return $this;
    }

    /**
     * Set value
     *
     * Sets the value for the predicate and returns itself for method call linking.
     * If value is NULL or string("NULL"), it automatically sets the comparisson
     * operator to self::OPR_NULL.
     *
     * @param mixed $value Value of the predicate
     * @return self
     */
    public function setValue($value): self
    {
        if ($value === null || strtolower($value) === "null") {
            $this->setOperator(self::OPR_NULL);
        }
        $this->_val = $value;
        return $this;
    }

    /**
     * Set comparison operator
     *
     * Sets the comparison operator for the predicate and returns itself for method
     * call linkint.
     *
     * @param string $operator Predicate comparison operator
     * @return self
     */
    public function setOperator(string $operator): self
    {
        $this->_opr = $operator;
        return $this;
    }

    /**
     * Prepare array
     *
     * Prepares the array by wrapping strings in single quotes, and implodes the
     * values with the delimiter that is passed in.
     *
     * @param array $values Array of values that need to be prepared and imploded
     * @param string $delim Array pieces delimiter, default string(",")
     * @return string
     */
    protected function _prepArray(array $values, string $delim = ","): string
    {
        array_walk($values, function(&$value) {
            $value = is_string($value) ? "'{$value}'" : $value;
        });
        return implode($delim, $values);
    }
}
