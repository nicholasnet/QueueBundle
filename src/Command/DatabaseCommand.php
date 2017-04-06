<?php

namespace IdeasBucket\QueueBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class DatabaseCommand
 *
 * @package IdeasBucket\QueueBundle\Command
 */
class DatabaseCommand extends Command
{
    /**
     * The cache directory path.
     *
     * @var string
     */
    private $cacheDirectoryPath;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * DatabaseCommand constructor.
     *
     * @param string $cacheDirectoryPath
     * @param KernelInterface $kernel
     */
    public function __construct($cacheDirectoryPath, KernelInterface $kernel)
    {
        $this->cacheDirectoryPath = $cacheDirectoryPath . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR . 'output';
        $this->kernel = $kernel;

        parent::__construct();
    }

    /**
     * Configure the command.
     */
    protected function configure()
    {
        $this->setName('idb_queue:database')
             ->setDescription('Create a required entity and repository for storing jobs in database.')
             ->setHelp('Create a required entity and repository for storing jobs in database');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $msg = '<question>Please select the type of database that you want to use. (0):</question> ';
        $question = new ChoiceQuestion($msg, ['orm', 'mongo'], 0);
        $question->setErrorMessage('Mapping %s is invalid valid choices are 0 and 1.');
        $flavor = $helper->ask($input, $output, $question);

        $msg = '<question>Please enter the bundle name where you want to define the entity (AppBundle):</question> ';
        $question = new Question($msg, 'AppBundle');
        $bundleName = $helper->ask($input, $output, $question);

        try {

            $bundle = $this->kernel->getBundle($bundleName);
            $namespace = $this->getNamespace($bundle, $flavor);

        } catch (\InvalidArgumentException $exception) {

            $output->writeln('');
            $output->write('No bundle exist by the name: ');
            $output->writeln('<info>'. $bundleName .'</info>');
            $output->write('Valid bundle name are: ');
            $output->writeln('<comment>'. implode(', ', array_keys($this->kernel->getBundles())) .'</comment>');
            $output->writeln('');

            return;
        }

        $question = new Question('<question>Please enter the class name for the entity (Queue):</question> ', 'Queue');
        $entityName = $helper->ask($input, $output, $question);

        $target = ($flavor === 'orm') ? 'table' : 'document';
        $msg = '<question>Please enter the '. $target .' name where you want to store the jobs (queues):</question> ';
        $question = new Question($msg, 'queues');
        $tableName = $helper->ask($input, $output, $question);

        $outputPath = $this->getOutputPath();
        $types = [$entityName => 'Entity.txt', $entityName.'Repository' => 'Repository.txt'];

        foreach ($types as $key => $type) {

            $content = $this->replacePlaceholders($this->getTemplate($flavor, $type), $namespace, $bundleName, $entityName, $tableName);
            file_put_contents($outputPath . $key . '.php', $content);
        }

        $serviceTemplate = $this->replacePlaceholders($this->getTemplate($flavor, 'Service.txt'), $namespace, $bundleName, $entityName, $tableName);

        $output->writeln('');
        $output->writeln('');
        $output->writeln('<comment>Please add this service to your services.yml file</comment>');
        $output->writeln('');
        $output->writeln($serviceTemplate);
        $output->writeln('');
        $output->writeln('Update application config.yml file for queue database repository to app.repository.queue');
        $output->writeln('Files are generated in this location <comment>' . $outputPath . '</comment> Please move them to appropriate location.');
        $output->writeln('<comment>Please see documentation for more information.</comment>');
        $output->writeln('');
    }

    /**
     * @param string $flavor
     * @param string $type
     *
     * @return string
     */
    protected function getTemplate($flavor, $type)
    {
        $ds = DIRECTORY_SEPARATOR;
        $templatePath = __DIR__ . $ds . 'Stubs' . $ds . ucfirst($flavor) . $ds . $type;
        $template = file_get_contents($templatePath);

        return $template;
    }

    /**
     * @param $template
     * @param $ns
     * @param $entity
     * @param $bundleName
     * @param $table
     *
     * @return string
     */
    protected function replacePlaceholders($template, $ns, $bundleName, $entity, $table)
    {
        return str_replace(['{{namespace}}', '{{className}}', '{{tableName}}', '{{bundleName}}'], [$ns, $entity, $table, $bundleName], $template);
    }

    /**
     * @throws IOException
     *
     * @return string
     */
    protected function getOutputPath()
    {
        $outputPath = $this->cacheDirectoryPath;

        if (!is_dir($outputPath)) {

            (new Filesystem)->mkdir($outputPath);
        }

        if (!is_writable($outputPath)) {

            throw new IOException(sprintf('The directory "%s" is not writable.', $outputPath), 0, null, $outputPath);
        }

        return realpath($outputPath) . DIRECTORY_SEPARATOR;
    }

    /**
     * @param object $bundle
     * @param string $flavor
     *
     * @return string
     */
    private function getNamespace($bundle, $flavor)
    {
        $pieces = explode('\\', get_class($bundle));

        if (count($pieces) > 1) {

            unset($pieces[count($pieces) - 1]);
        }

        $case = ($flavor === 'orm') ? 'Entity' : 'Document';

        return implode('\\', $pieces) . '\\' . $case;
    }
}
