<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento;

class MapParser extends PathTranslationParser {

    protected $_mappings = [];

    function __construct($mappings, $translations = [], $pathSuffix = '')
    {
        parent::__construct($translations, $pathSuffix);

        $this->setMappings($mappings);
    }

    public function setMappings($mappings)
    {
        $this->_mappings = $this->translatePathMappings($mappings);
    }

    public function getMappings()
    {
        return $this->_mappings;
    }

}
