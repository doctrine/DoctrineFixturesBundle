<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\DoctrineFixturesBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\DoctrineBundle\Command\DoctrineCommand;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Generates a new fixtures class.
 *
 * @author Ryan Weaver <ryan@knplabs.com>
 * @author Fabien Potencier <fabien@symfony.com>
 */
class GenerateFixturesCommand extends DoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName('doctrine:fixtures:generate')
            ->setDescription('Generates a new fixtures file.')
            ->addOption('bundle', null, InputOption::VALUE_REQUIRED, 'The name of the bundle into which the fixture class will go.')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'The name for the fixture class (e.g. "LoadProducts").')
            ->addOption('container-aware', null, InputOption::VALUE_NONE, 'Whether or not the fixture needs access to the container')
            ->setHelp(<<<EOT
The <info>doctrine:fixtures:generate</info> command generates a new fixture class:

  <info>php app/console doctrine:fixtures:generate</info>

By default, a wizard asks you about the fixture class that you'd like to create.
You can also pass in the options manually:

  <info>php app/console doctrine:fixtures:generate --bundle=AcmeStoreBundle --name=LoadProducts</info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        $bundleName = $dialog->ask($output, '<comment>What bundle should the fixture be placed into?</comment> ', $input->getOption('bundle'));
        $bundle = $this->getKernel()->getBundle($bundleName);

        $name = $dialog->ask($output, '<comment>What should the class be named (e.g. LoadProducts)?</comment> ', $input->getOption('name'));
        $containerAware = $dialog->askConfirmation($output, '<comment>Do you need access to the service container? (y/n)</comment> ', $input->getOption('container-aware'));

        $target = $bundle->getPath().'/DataFixtures/ORM'.'/'.$name.'.php';
        if (file_exists($target)) {
            throw new InvalidArgumentException(sprintf('File already exists at "%s"', $target));
        }

        self::renderFile(
            'fixturesClass.twig',
            $target,
            array(
                'bundleNamespace' => $bundle->getNamespace(),
                'className'       => $name,
                'containerAware'  => $containerAware,
            )
        );

        $output->writeln(sprintf('New fixture class written to <info>%s</info>', $target));
    }

    /**
     * @return \Symfony\Component\Console\Helper\DialogHelper
     */
    private function getDialogHelper()
    {
        return $this->getHelperSet()->get('dialog');
    }

    /**
     * @return \Symfony\Component\HttpKernel\KernelInterface
     */
    private function getKernel()
    {
        return $this->getApplication()->getKernel();
    }

    protected function renderFile($template, $target, $parameters)
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        $skeletonDir = __DIR__.'/../Resources/skeleton';

        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem($skeletonDir), array(
            'debug'            => true,
            'cache'            => false,
            'strict_variables' => true,
            'autoescape'       => false,
        ));

        file_put_contents($target, $twig->render($template, $parameters));
    }
}
