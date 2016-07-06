<?php
/**
 * No Join Condition Exception
 *
 * Thrown when a join is added but a condition for it does not exist.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\DatabasePDO\Exception;

class NoJoinConditionException extends \Exception
{
}
