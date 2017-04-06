<?php

namespace IdeasBucket\QueueBundle\Command;

use IdeasBucket\Common\Utils\StringHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class GenerateCommand
 *
 * @package IdeasBucket\QueueBundle\Command
 */
class GenerateCommand extends Command
{
    /**
     * @var FileLocator
     */
    private $fileLocator;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * GenerateCommand constructor.
     *
     * @param FileLocator     $fileLocator
     * @param KernelInterface $kernel
     */
    public function __construct(FileLocator $fileLocator, KernelInterface $kernel)
    {
        $this->fileLocator = $fileLocator;
        $this->kernel = $kernel;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('idb_queue:create_job')->setDescription('Creates the new job.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $msg = '<question>Please enter the bundle name where you want to define the entity (AppBundle):</question> ';
        $question = new Question($msg, 'AppBundle');
        $bundleName = $helper->ask($input, $output, $question);

        try {

            $namespace = $this->getNamespace($this->kernel->getBundle($bundleName));
            $path = $this->getOutputPath($this->fileLocator->locate('@' . $bundleName) . 'Job');

        } catch (\InvalidArgumentException $exception) {

            $output->writeln('');
            $output->write('No bundle exist by the name: ');
            $output->writeln('<info>'. $bundleName .'</info>');
            $output->write('Valid bundle name are: ');
            $output->writeln('<comment>'. implode(', ', array_keys($this->kernel->getBundles())) .'</comment>');
            $output->writeln('');

            return;

        } catch (IOException $exception) {

            $output->writeln('<comment>'. $exception->getMessage() .'</comment>');

            return;
        }

        $msg = '<question>Please enter name of the class name for the job: </question> ';
        $question = new Question($msg, null);
        $className = $helper->ask($input, $output, $question);

        if (empty($className)) {

            $output->writeln('<error>Class name cannot be empty.</error>');

            return;
        }

        if (!preg_match('/^[A-Za-z]+[A-Za-z0-9]+$/', $className)) {

            $output->writeln('<error>Invalid class name.</error>');

            return;
        }

        $suggestedServiceId = StringHelper::studlyCase($bundleName) . '.job.' . StringHelper::studlyCase($className);
        $suggestedServiceId = strtolower($suggestedServiceId);
        $msg = '<question>Please enter name of the service id for this job (' . $suggestedServiceId. '): </question> ';
        $question = new Question($msg, $suggestedServiceId);
        $serviceId = $helper->ask($input, $output, $question);

        $fileNameWithPath = $path . $className . '.php';

        if (file_exists($fileNameWithPath)) {

            $output->writeln('<info>File already exist in path '. $fileNameWithPath .'</info>');

            return;
        }

        file_put_contents($fileNameWithPath, $this->replacePlaceholders(
            $this->getTemplate('Class.txt'),
            $namespace,
            $className,
            $serviceId
        ));

        $serviceTemplate = $this->replacePlaceholders($this->getTemplate('Service.txt'), $namespace, $className, $serviceId);
        $output->writeln('');
        $output->writeln('');

        $message = 'However, we recommend to put all job related service in separate file.';
        $output->writeln('<comment>Please add this service to your services.yml file. '. $message .'</comment>');
        $output->writeln('');
        $output->writeln($serviceTemplate);
        $output->writeln('');
        $output->writeln('Files are generated in this location <comment>' . $path . '</comment>.');
        $output->writeln('');
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function getTemplate($type)
    {
        $ds = DIRECTORY_SEPARATOR;
        $templatePath = __DIR__ . $ds . 'Stubs' . $ds . 'Service' . $ds . $type;
        $template = file_get_contents($templatePath);

        return $template;
    }

    /**
     * @param $template
     * @param $ns
     * @param $class
     * @param $serviceId
     *
     * @return mixed
     */
    protected function replacePlaceholders($template, $ns, $class, $serviceId)
    {
        return str_replace(['{{namespace}}', '{{className}}', '{{serviceId}}'], [$ns, $class, $serviceId], $template);
    }

    /**
     * @throws IOException
     *
     * @return string
     */
    protected function getOutputPath($path)
    {
        if (!is_dir($path)) {

            (new Filesystem)->mkdir($path);
        }

        if (!is_writable($path)) {

            throw new IOException(sprintf('The directory "%s" is not writable.', $path), 0, null, $path);
        }

        return realpath($path) . DIRECTORY_SEPARATOR;
    }

    /**
     * @param object $bundle
     *
     * @return string
     */
    private function getNamespace($bundle)
    {
        $pieces = explode('\\', get_class($bundle));

        if (count($pieces) > 1) {

            unset($pieces[count($pieces) - 1]);
        }

        return implode('\\', $pieces) . '\\Job';
    }
}
