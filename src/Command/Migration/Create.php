<?php
namespace SlaxWeb\DatabasePDO\Command\Migration;

use SlaxWeb\Bootstrap\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use SlaxWeb\DatabasePDO\Exception\MigrationException;
use SlaxWeb\DatabasePDO\Exception\MigrationExistsException;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

/**
 * Create Migration Command
 *
 * Migrations command to create an empty migration and add it to the list of migrations
 * with the Migration Manager.
 *
 * @package   SlaxWeb\DatabasePDO
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.6
 */
class Create extends Command
{
    /**
     * Application instance
     *
     * @var \SlaxWeb\Bootstrap\Appliation
     */
    protected $app = null;

    /**
     * Console intput
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input = null;

    /**
     * Console output
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output = null;

    /**
     * Initialize command
     *
     * Init the command and set required services to protected properties.
     *
     * @param \SlaxWeb\Bootstrap\Application $app Application instance
     * @return void
     */
    public function init(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Configure command
     *
     * Configure the command name and arguments.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName("migration:create")
            ->setDescription("Create an empty migration")
            ->addArgument(
                "name",
                InputArgument::REQUIRED,
                "Name of the migration to be added, may contain only word characters, "
                . "[a-zA-Z0-9_], and must begin with a letter"
            );
    }

    /**
     * Execute command
     *
     * Add a migration to the list with the Migration Manager.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input Console input
     * @param \Symfony\Component\Console\Output\OutputInterface $output Console output
     * @return void
     */
    protected function execute(Input $input, Output $output)
    {
        $this->input = $input;
        $this->output = $output;

        if ($this->output->isDebug()) {
            ini_set("display_errors", 1);
            error_reporting(E_ALL);
        }

        $name = $this->input->getArgument("name");

        if (preg_match("~^[a-zA-Z]\w*$~", $name) === 0) {
            $this->output->writeln("<error>The given name '{$name}' does not meet the requirements</>");
            $this->output->writeln(
                "<info>The name must satisfy the following regex: '^[a-zA-Z]\w*$'</>",
                Output::VERBOSITY_DEBUG
            );
            return;
        }

        try {
            $this->app["migration.service"]->create($name);
        } catch (MigrationException $e) {
            $this->output->writeln(
                "<error>An unexpexted error occured while trying to create the Migration</>"
            );
            return;
        } catch (MigrationExistsException $e) {
            $this->output->writeln("<error>Migration '{$name}' already exists.</>");
            return;
        }

        $this->output->writeln("<info>Migration successfuly created.</>");
        $this->output->writeln(
            "<comment>Migration file location: "
            . realpath($this->app["config.service"]["migration.repository"] . $name)
            . "</>",
            Output::VERBOSITY_VERBOSE
        );
    }
}
