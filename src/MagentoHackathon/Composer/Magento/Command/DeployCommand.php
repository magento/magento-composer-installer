<?php

/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Command;

use Composer\Command\BaseCommand;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\DeployManager;
use MagentoHackathon\Composer\Magento\Installer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Tiago Ribeiro <tiago.ribeiro@seegno.com>
 * @author Rui Marinho <rui.marinho@seegno.com>
 */
class DeployCommand extends BaseCommand
{
    private const SUCCESS_EXIT_CODE = 0;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('magento-module-deploy')
            ->setDescription('Deploy all Magento modules loaded via composer.json')
            ->setDefinition([
            // we dont need to define verbose, because composer already defined it internal
            //new InputOption('verbose', 'v', InputOption::VALUE_NONE, 'Show modified files for each directory that contains changes.'),
            ])
            ->setHelp(<<<EOT
This command deploys all magento Modules

EOT
        );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // init repos
        $composer = $this->getComposer();
        $installedRepo = $composer->getRepositoryManager()->getLocalRepository();
        $im = $composer->getInstallationManager();

        /** @var $moduleInstaller Installer */
        $moduleInstaller = $im->getInstaller("magento-module");
        $deployManager = new DeployManager($this->getIO());
        $extra = $composer->getPackage()->getExtra();
        $sortPriority = $extra['magento-deploy-sort-priority'] ?? [];
        $deployManager->setSortPriority( $sortPriority );
        $moduleInstaller->setDeployManager( $deployManager );

        foreach ($installedRepo->getPackages() as $package) {
            if ($input->getOption('verbose')) {
                $output->writeln($package->getName());
                $output->writeln($package->getType());
            }
            if ($package->getType() != "magento-module"){
                continue;
            }
            if ($input->getOption('verbose')) {
                $output->writeln("package {$package->getName()} recognized");
            }
            $strategy = $moduleInstaller->getDeployStrategy($package);

            if ($input->getOption('verbose')) {
                $output->writeln("used " . get_class($strategy) . " as deploy strategy");
            }
            $strategy->setMappings($moduleInstaller->getParser($package)->getMappings());
            $deployManagerEntry = new Entry();
            $deployManagerEntry->setPackageName($package->getName());
            $deployManagerEntry->setDeployStrategy($strategy);
            $deployManager->addPackage($deployManagerEntry);
        }
        $deployManager->doDeploy();

        return self::SUCCESS_EXIT_CODE;
    }
}
