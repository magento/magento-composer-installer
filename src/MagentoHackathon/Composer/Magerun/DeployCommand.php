<?php

namespace MagentoHackathon\Composer\Magerun;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends AbstractMagentoCommand
{
    private const SUCCESS_EXIT_CODE = 0;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
      $this
          ->setName('composer:magento:deploy')
          ->setDescription('Test command registered in a module')
      ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('it works, maybe');

        return self::SUCCESS_EXIT_CODE;
    }
}
