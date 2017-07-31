<?php
/**
 * PDO Database Migration Provider
 *
 * Registers the Migration services to the Dependency Injection Container
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.6
 */
namespace SlaxWeb\DatabasePDO\Service;

use Pimple\Container;
use SlaxWeb\DatabasePDO\Exception\MigrationException;

class MigrationProvider implements \Pimple\ServiceProviderInterface
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
        $container["loadMigrationClass.service"] = $container->protect(
            function(string $migration) use ($container) {
                $className = "\\{$container["migrationNamespace"]}{$name}";

                if (class_exists($className) === false) {
                    throw new Exception MigrationException(
                        "The migration class '{$className}' was not found. Please check "
                        . "the migration class."
                    );
                }

                return new $className(
                    $container["queryBuilder.service"],
                    $container["pdo.service"](),
                    $container["logger.service"]()
                );
            }
        )
    }
}
