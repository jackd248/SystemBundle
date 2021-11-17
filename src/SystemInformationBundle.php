<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle;

use Kmi\SystemInformationBundle\DependencyInjection\SystemInformationBundleExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SystemInformationBundle extends Bundle
{
    const BUNDLE_CONFIG_NAME = 'kmi_system';
    const CACHE_KEY = 'SystemInformationBundle';
    const CACHE_LIFETIME = 300;

    /**
     * @inheritdoc
     */
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new SystemInformationBundleExtension();
        }

        return $this->extension;
    }
}