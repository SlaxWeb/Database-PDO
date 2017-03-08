<?php
/**
 * PDO Database Library Service Provider
 *
 * Registers the Library service to the DIC
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
namespace SlaxWeb\DatabasePDO\Service;

use Pimple\Container;

class Provider implements \Pimple\ServiceProviderInterface
{
    /**
     * Register Provider
     *
     * Called when the container is about to register this provider with the DIC.
     * It should define all the services, or call other methods that define the
     * services.
     *
     * @param \Pimple\Container $container Dependency Injection Container
     * @return void
     */
    public function register(Container $container)
    {
        $container["databaseLibrary.service"] = function(Container $container) {
            return new \SlaxWeb\DatabasePDO\Library($container["pdo.service"], $container["queryBuilder.service"]);
        };

        $container["pdo.service"] = function(Container $container) {
            $config = $container["config.service"]["database.connection"];
            $dsn = "{$config["driver"]}:dbname={$config["database"]};host={$config["hostname"]}";
            if (isset($config["port"]) === true && $config["port"] > 0) {
                $dsn .= ";port={$config["port"]}";
            }

            try {
                $pdo = new \PDO($dsn, $config["username"], $config["password"]);
            } catch (\PDOException $e) {
                $container["logger.service"]()->emergency(
                    "Connection to the database failed.",
                    [
                        "dsn"       =>  $dsn,
                        "username"  =>  $config["username"],
                        "exception" =>  $e->getMessage()
                    ]
                );
                // we have logged the error, time to rethrow it
                throw $e;
            }

            if (empty($config["timeout"]) === false) {
                $pdo->setAttribute(\PDO::ATTR_TIMEOUT, $config["timeout"]);
            }

            return $pdo;
        };

        $container["queryBuilder.service"] = function() {
            return new \SlaxWeb\DatabasePDO\Query\Builder;
        };
    }
}
