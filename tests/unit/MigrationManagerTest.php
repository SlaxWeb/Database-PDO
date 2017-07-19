<?php
namespace SlaxWeb\DatabasePDO\Test\Unit;

use Exception;
use Mockery as m;
use SlaxWeb\DatabasePDO\Migration\Manager;
use SlaxWeb\DatabasePDO\Exception\MigrationException;
use SlaxWeb\DatabasePDO\Exception\MigrationRepositoryException;

class MigrationManagerTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $repository = __DIR__ . "/../_data/migration_repository/";

    public function testExistingRepositoryCheck()
    {
        mkdir($this->repository, 0755, true);
        try {
            $migrationManager = new Manager($this->repository);
        } catch (MigrationRepositoryException $e) {
            throw new Exception(
                "No exception was expected with an existing repository directory"
            );
        }
    }

    public function testRepositoryCreation()
    {
        if (file_exists($this->repository)) {
            $this->recurRmDir($this->repository);
        }

        try {
            $migrationManager = new Manager($this->repository);
        } catch (MigrationRepositoryException $e) {
            throw new Exception(
                "No exception was expected when creating a test repository directory"
            );
        }

        $this->assertTrue(file_exists($this->repository));
    }

    public function testRepositoryWritabilityCheck()
    {
        if (file_exists($this->repository) === false) {
            mkdir($this->repository, 0000, true);
        } else {
            chmod($this->repository, 0000);
        }

        $exception = false;
        try {
            $migrationManager = new Manager($this->repository);
        } catch (MigrationRepositoryException $e) {
            $exception = true;
            $this->assertEquals(
                "Received migration repository is not writable!",
                $e->getMessage()
            );
        }

        $this->assertTrue($exception);
        chmod($this->repository, 0755);
    }

    public function testMigrationsFilesCreated()
    {
        if (file_exists("{$this->repository}.migrations.json")) {
            unlink("{$this->repository}.migrations.json");
        }
        if (file_exists("{$this->repository}.executed.json")) {
            unlink("{$this->repository}.executed.json");
        }

        $migrationManager = new Manager($this->repository);

        $migrations = json_decode(file_get_contents("{$this->repository}.migrations.json"), true);
        $executed = json_decode(file_get_contents("{$this->repository}.executed.json"), true);
        $this->assertInternalType("array", $migrations);
        $this->assertInternalType("array", $executed);
    }

    public function testMigrationFilesCorruptionHandling()
    {
        mkdir($this->repository, 0755, true);
        file_put_contents("{$this->repository}.migrations.json", "corrupted");
        file_put_contents("{$this->repository}.executed.json", "corrupted");

        $exception = false;
        try {
            $migrationManager = new Manager($this->repository);
        } catch (MigrationRepositoryException $e) {
            $exception = true;
            $this->assertEquals(
                "Migration file is corrupted!",
                $e->getMessage()
            );
        }
        $this->assertTrue($exception);
    }

    public function testMigrationCreation()
    {
        mkdir($this->repository, 0755, true);

        $migrationManager = new Manager($this->repository);
        $migrationManager->create("TestMigration");
        unset($migrationManager);

        $migrations = json_decode(file_get_contents("{$this->repository}.migrations.json"), true);

        $this->assertTrue(
            file_exists("{$this->repository}TestMigration.php"),
            "Migration class file was not created"
        );
        $this->assertContains(
            "TestMigration",
            $migrations,
            "Migration was not added to the migrations status file."
        );
    }

    public function testInvalidMigrationName()
    {
        $exception = false;
        $migrationManager = new Manager($this->repository);
        try {
            $migrationManager->create("!TestMigration");
        } catch (MigrationException $e) {
            $exception = true;
        }
        unset($migrationManager);

        $this->assertTrue($exception, "Expected exception on invalid migration name was not thrown");
    }

    protected function _before()
    {
    }

    protected function _after()
    {
        m::close();
        if (file_exists($this->repository)) {
            $this->recurRmDir($this->repository);
        }
    }

    protected function recurRmDir(string $dir)
    {
        foreach (scandir($dir) as $file) {
            if ($file === "." || $file === "..") {
                continue;
            }
            if (is_dir($file)) {
                $this->recurRmDir($file);
            } else {
                unlink($dir . $file);
            }
        }
        rmdir($dir);
    }
}
