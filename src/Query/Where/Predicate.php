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
    protected $col = "";

    /**
     * Value
     *
     * @var mixed
     */
    protected $val = null;

    /**
     * Comparison operator
     *
     * @var string
     */
    protected $opr = self::OPR_EQUAL;

    /**
     * Parameters
     *
     * @var array
     */
    protected $params = [];

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
        $predicate = "{$this->col} {$this->opr} ";
        switch ($this->opr) {
            case self::OPR_NULL:
            case self::OPR_NOTNULL:
                if ($this->val !== null || strtolower($this->val) !== "null") {
                    // @todo: throw exception
                }
                $predicate = rtrim($predicate);
                break;

            case self::OPR_BTWN:
                if (is_array($this->val) === false || count($this->val) !== 2) {
                    // @todo: throw exception
                }
                $predicate .= implode(" AND ", $this->val);
                break;

            case self::OPR_IN:
            case self::OPR_NOTIN:
                if (is_string($this->val)) {
                    $predicate .= "({$this->val})";
                    break;
                }
                $predicate .= "(" . implode(",", $this->val) . ")";
                break;

            default:
                $predicate .= $this->val;
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
        $this->col = $column;
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
     * @param bool $prep Prepare value, default bool(true)
     * @param array $params Predefined parameters, default []
     * @return self
     */
    public function setValue($value, bool $prep = true, array $params = []): self
    {
        if ($value === null || (is_string($value) && strtolower($value) === "null")) {
            $this->setOperator(self::OPR_NULL);
            return $this;
        }
        $this->val = $prep ? $this->_prepValues($value) : $value;
        if ($params !== []) {
            $this->params = $params;
        }
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
        $this->opr = $operator;
        return $this;
    }

    /**
     * Get parameters
     *
     * Returns the list of parameters for this predicate.
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Prepare values
     *
     * Prepare the values by replacing the actual value with the question mark placeholder
     * and add the value to the 'params' array.
     *
     * @param mixed $value Value to be prepared
     * @return mixed
     */
    public function _prepValues($value)
    {
        if (is_array($value)) {
            foreach ($value as &$param) {
                $this->params[] = $param;
                $param = "?";
            }
            unset($param);
            return $value;
        }
        if (in_array(is_string($value) ? strtolower($value) : $value, [null, "null"]) === false) {
            $this->params[] = $value;
            $value = "?";
        }
        return $value;
    }
}
