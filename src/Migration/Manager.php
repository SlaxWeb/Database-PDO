<?php
/**
 * Migration Manager
 *
 * Migration manager controls adding, removing, editing, and executing database
 * migrations.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.6
 */
namespace SlaxWeb\DatabasePDO\Migration;

use SlaxWeb\DatabasePDO\Migration\BaseMigration;
use SlaxWeb\DatabasePDO\Exception\MigrationException;
use \SlaxWeb\DatabasePDO\Exception\MigrationExistsException;
use SlaxWeb\DatabasePDO\Exception\MigrationRepositoryException;

class Manager
{
    /**
     * Migration repository directory
     *
     * @var string
     */
    protected $repository = "";

    /**
     * Migration class loader
     *
     * @var callable
     */
    protected $loader = null;

    /**
     * Migrations
     *
     * @param array
     */
    protected $migrations = [];

    /**
     * Executed migrations
     *
     * @param array
     */
    protected $executed = [];

    /**
     * Class constructor
     *
     * Copies the class dependencies into internal properties, and checks if the
     * retrieved repository directory is writable.
     *
     * @param string $repository Migration repository directory - must be writable.
     * @param Callable $loader Migration class loader
     */
    public function __construct(string $repository, callable $loader)
    {
        $this->checkRepository($repository);
        $this->repository = rtrim($repository, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->loadStatus("migrations");
        $this->loadStatus("executed");

        $this->loader = $loader;
    }

    /**
     * Destructor
     *
     * Writes the status files back to the filesystem when the class destructs.
     */
    public function __destruct()
    {
        file_put_contents(
            "{$this->repository}.migrations.json",
            json_encode($this->migrations)
        );
        file_put_contents(
            "{$this->repository}.executed.json",
            json_encode($this->executed)
        );
    }

    /**
     * Create migration
     *
     * Copies the migration class file template into the repository, and adds it
     * to the migrations status file. It takes the name of the migration as input,
     * this name will be used for the migration class and file name. Returns a bool
     * status if migration file has been created.
     *
     * @param string $name Name of the migration
     * @return void
     *
     * @throws \SlaxWeb\DatabasePDO\Exception\MigrationException
     *         \SlaxWeb\DatabasePDO\Exception\MigrationExistsException
     */
    public function create(string $name)
    {
        if (preg_match("~^[a-zA-Z]\w*$~", $name) !== 1) {
            throw new MigrationException(
                "Can not create migration. '{$name}' is not a valid class name!"
            );
        }

        $migrationPath = "{$this->repository}{$name}.php";
        if (file_exists($migrationPath)) {
            throw new MigrationExistsException(
                "Migration '{$name}' already exists. Unable to create."
            );
        }

        $migration = file_get_contents(__DIR__ . "/Template/MigrationClass.php");
        $migration = str_replace("MigrationClass", $name, $migration);
        if (file_put_contents($migrationPath, $migration) === false) {
            throw new MigrationException(
                "An unexpected error occured when attempting to create the migration file."
            );
        }

        $this->migrations[time()] = $name;
    }

    /**
     * Run migrations
     *
     * Executes all non-executed migrations. Input parameter may also take an array
     * of migration names, and then only those migrations will be executed, if they
     * have not been executed before. The second parameter may be set to bool, in
     * that case, the received migrations will be executed regardless if they have
     * already been executed before or not. The second parameter has the effect
     * only if specific migrations are defined in the array.
     *
     * Returns an array of migrations that failed when executing.
     *
     * @param array $migrations Array of migrations to be executed. Default empty array
     * @param bool $force Execute specified migrations regardless if they have already been
     *                    executed, default false
     * @return array
     */
    public function run(array $migrations = [], $force = false): array
    {
        $failed = [];

        if (empty($migrations)) {
            // migrations not passed, ensure we do not force
            $force = false;

            $migrations = $this->migrations;
        }

        foreach ($migrations as $migration) {
            if (array_key_exists($migration, $this->executed) && $force === false) {
                continue;
            }

            if ($this->loadMigration($migration)->execute() === false) {
                $failed[] = $migration;
                continue;
            }

            $this->executed[$migration] = [
                "time" => time()
            ];
        }

        return $failed;
    }

    /**
     * Revert migrations
     *
     * Reverts the already executed migrations. The first input parameter must be
     * an array of migration names to be reverted. The revert process calls the
     * 'down' method of the Migration class.
     *
     * Returns an array of migrations that failed when executing.
     *
     * @param array $migrations Array of migrations to be executed.
     * @return array
     */
    public function revert(array $migrations): array
    {
        $failed = [];

        foreach ($migrations as $migration) {
            if (array_key_exists($migration, $this->executed) === false) {
                $failed[] = $migration;
                continue;
            }

            if ($this->loadMigration($migration)->execute(BaseMigration::TEAR_DOWN) === false) {
                $failed[] = $migration;
                continue;
            }

            unset($this->executed[$migration]);
        }

        return $failed;
    }

    /**
     * Remove migration
     *
     * Removes the migration from the filesystem and the status files. If bool(true)
     * is used as the second parameter, the migration will first be reverted, and
     * then removed, if it was executed before.
     *
     * @param string $migration Migration name
     * @param bool $revert Revert the migration before removing, default bool(false)
     * @return void
     */
    public function remove(string $name, bool $revert = false)
    {
        if (isset($this->executed[$name])) {
            if ($revert) {
                $this->revert([$name]);
            }

            unset($this->executed[$name]);
        }

        unlink("{$this->repository}{$name}.php");
        if (($key = array_search($name, $this->migrations)) !== false) {
            unset($this->migrations[$key]);
        }
    }

    /**
     * Check repository
     *
     * Checks the migration repository derectory, if it exists and is writable.
     * If it does not exist, it will be created. Throws an exception if it can not
     * be created or is not writable.
     *
     * @param string $repository Migration repository directory
     * @return void
     *
     * @throws \SlaxWeb\DatabasePDO\Exception\MigrationRepositoryException
     */
    protected function checkRepository(string $repository)
    {
        if (file_exists($repository) === false) {
            if (mkdir($repository, 0755, true) === false) {
                throw new MigrationRepositoryException(
                    "Unable to create migration repository exception due to an error."
                );
            }
            return;
        }

        if (is_dir($repository) === false) {
            throw new MigrationRepositoryException(
                "Received migration repository is not a directory!"
            );
        }

        if (is_writable($repository) === false) {
            throw new MigrationRepositoryException(
                "Received migration repository is not writable!"
            );
        }
    }

    /**
     * Load migration status file
     *
     * Loads the migration status file, or creates a new one if it does not exist.
     *
     * @param string $name Name of the migration file
     * @return void
     *
     * @throws \SlaxWeb\DatabasePDO\Exception\MigrationRepositoryException
     */
    protected function loadStatus(string $name)
    {
        if (file_exists("{$this->repository}.{$name}.json")) {
            $this->{$name} = json_decode(
                file_get_contents("{$this->repository}.{$name}.json"),
                true
            );
            if (is_array($this->{$name}) === false) {
                throw new MigrationRepositoryException(
                    "Migration file is corrupted!"
                );
            }
            ksort($this->{$name});
            return;
        }
        // status file does not exist, create an empty one
        $this->{$name} = [];
        file_put_contents("{$this->repository}.{$name}.json", json_encode($this->{$name}));
    }

    /**
     * Load migration
     *
     * Loads the migration class file, and instantiates it. Returns the object of
     * the migration to the caller.
     *
     * @param string $name Name of the migration
     * @return \SlaxWeb\DatabasePDO\Migration\BaseMigration
     *
     * @throws \SlaxWeb\DatabasePDO\Exception\MigrationException
     */
    protected function loadMigration(string $name): BaseMigration
    {
        $migration = ($this->loader)($name);

        if (!$migration instanceof BaseMigration) {
            throw new MigrationException(
                "The loader did not return an expected instance of the BaseMigration "
                . "class."
            );
        }

        return $migration;
    }
}
