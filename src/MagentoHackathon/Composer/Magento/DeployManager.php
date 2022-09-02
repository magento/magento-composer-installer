<?php
/**
 *
 *
 *
 *
 */

namespace MagentoHackathon\Composer\Magento;


use Composer\IO\IOInterface;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\Deploystrategy\Copy;

class DeployManager
{
    /**
     * @var Entry[]
     */
    protected $packages = [];

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * an array with package names as key and priorities as value
     *
     * @var array
     */
    protected $sortPriority = [];

    /**
     * High priority
     *
     * An array of packages that must have high priority for deployment
     * For packages that need to be deployed before all other packages
     *
     * @var array
     */
    private $highPriority = [
        'magento/magento2-base' => 10
    ];

    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    public function addPackage(Entry $package)
    {
        $this->packages[] = $package;
    }

    public function setSortPriority($priorities)
    {
        $this->sortPriority = $priorities;
    }

    /**
     * Uses the sortPriority Array to sort the packages.
     *
     * Highest priority first.
     * Copy gets per default higher priority then others
     *
     * @return array
     */
    protected function sortPackages()
    {
        usort(
            $this->packages,
            function ($a, $b) {
                $aPriority = $this->getPackagePriority($a);
                $bPriority = $this->getPackagePriority($b);
                if ($aPriority == $bPriority) {
                    return 0;
                }
                return ($aPriority > $bPriority) ? -1 : 1;
            }
        );

        return $this->packages;
    }

    public function doDeploy()
    {
        $this->sortPackages();

        /** @var Entry $package */
        foreach ($this->packages as $package) {
            if ($this->io->isDebug()) {
                $this->io->write('start magento deploy for ' . $package->getPackageName());
            }
            $package->getDeployStrategy()->deploy();
        }
    }

    /**
     * Determine the priority in which the package should be deployed
     *
     * @param Entry $package
     * @return int
     */
    private function getPackagePriority(Entry $package)
    {
        $result = 100;
        $maxPriority = max(array_merge($this->sortPriority, [100, 101]));

        if (isset($this->highPriority[$package->getPackageName()])) {
            $packagePriority = $this->highPriority[$package->getPackageName()];
            $result = intval($maxPriority) + intval($packagePriority);
        } elseif (isset($this->sortPriority[$package->getPackageName()])) {
            $result = $this->sortPriority[$package->getPackageName()];
        } elseif ($package->getDeployStrategy() instanceof Copy) {
            $result = 101;
        }

        return $result;
    }

}
