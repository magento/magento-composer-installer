<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Deploystrategy;

/**
 * Symlink deploy strategy
 */
class Copy extends DeploystrategyAbstract
{
    /**
     * copy files
     *
     * @param string $source
     * @param string $dest
     * @return bool
     * @throws \ErrorException
     */
    public function createDelegate($source, $dest)
    {
        list($mapSource, $mapDest) = $this->getCurrentMapping();
        $mapSource = $this->removeTrailingSlash($mapSource);
        $mapDest = $this->removeTrailingSlash($mapDest);
        $cleanDest = $this->removeTrailingSlash($dest);

        $sourcePath = $this->getSourceDir() . DIRECTORY_SEPARATOR
            . ltrim($this->removeTrailingSlash($source), DIRECTORY_SEPARATOR);
        $destPath = $this->getDestDir() . DIRECTORY_SEPARATOR
            . ltrim($this->removeTrailingSlash($dest), DIRECTORY_SEPARATOR);


        // Create all directories up to one below the target if they don't exist
        $destDir = dirname($destPath);
        if (!file_exists($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Handle source to dir copy,
        // e.g. Namespace_Module.csv => app/locale/de_DE/
        // Namespace/ModuleDir => Namespace/
        // Namespace/ModuleDir => Namespace/, but Namespace/ModuleDir may exist
        // Namespace/ModuleDir => Namespace/ModuleDir, but ModuleDir may exist

        // first iteration through, we need to update the mappings to correctly handle mismatch globs
        if ($mapSource == $this->removeTrailingSlash($source) && $mapDest == $this->removeTrailingSlash($dest)) {
            if (basename($sourcePath) !== basename($destPath)) {
                $this->setCurrentMapping([$mapSource, $mapDest . DIRECTORY_SEPARATOR . basename($source)]);
                $cleanDest = $cleanDest . DIRECTORY_SEPARATOR . basename($source);
            }
        }

        if (file_exists($destPath) && is_dir($destPath)) {
            $mapSource = rtrim($mapSource, '*');
            $mapSourceLen = empty($mapSource) ? 0 : strlen($mapSource);
            if (
                strcmp(
                    substr(ltrim($cleanDest, DIRECTORY_SEPARATOR), strlen($mapDest)),
                    substr(ltrim($source, DIRECTORY_SEPARATOR), $mapSourceLen)
                ) === 0
            ) {
                // copy each child of $sourcePath into $destPath
                foreach (new \DirectoryIterator($sourcePath) as $item) {
                    $item = (string) $item;
                    if (!strcmp($item, '.') || !strcmp($item, '..')) {
                        continue;
                    }
                    $childSource = $this->removeTrailingSlash($source) . DIRECTORY_SEPARATOR . $item;
                    $this->create($childSource, substr($destPath, strlen($this->getDestDir()) + 1));
                }
                return true;
            } else {
                $destPath = $this->removeTrailingSlash($destPath) . DIRECTORY_SEPARATOR . basename($source);
                return $this->create($source, substr($destPath, strlen($this->getDestDir()) + 1));
            }
        }

        // From now on $destPath can't be a directory, that case is already handled

        // If file exists and force is not specified, throw exception unless FORCE is set
        if (file_exists($destPath)) {
            if ($this->isForced()) {
                unlink($destPath);
            } else {
                throw new \ErrorException("Target $dest already exists (set extra.magento-force to override)");
            }
        }

        // File to file
        if (!is_dir($sourcePath)) {
            if (is_dir($destPath)) {
                $destPath .= DIRECTORY_SEPARATOR . basename($sourcePath);
            }
            return copy($sourcePath, $destPath);
        }

        // Copy dir to dir
        // First create destination folder if it doesn't exist
        if (file_exists($destPath)) {
            $destPath .= DIRECTORY_SEPARATOR . basename($sourcePath);
        }
        mkdir($destPath, 0755, true);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $subDestPath = $destPath . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!file_exists($subDestPath)) {
                    mkdir($subDestPath, 0755, true);
                }
            } else {
                copy($item, $subDestPath);
            }
            if (!is_readable($subDestPath)) {
                throw new \ErrorException("Could not create $subDestPath");
            }
        }

        return true;
    }
}
