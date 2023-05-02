<?php

declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

class ConfigurationLoader
{
    /**
     * @var array|null
     */
    protected ?array $config;

    /**
     * @param array|null $config
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config;
    }

    private function verifyConfiguration()
    {
        // ToDo:
    }

    /**
     * @return array|null
     */
    public function getConfig()
    {
        return $this->config;
    }
}
