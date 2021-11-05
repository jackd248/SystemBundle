<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use DateTime;
use Locale;
use Symfony\Component\DependencyInjection\Container;

/**
 *
 */
class InformationService {

    /**
     * @var Container
     */
    private $container;

    /**
     * Constructor
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getSystemInformation(): array
    {
        $information = [];
        $information[] = [
            'value' => \json_decode(file_get_contents($this->container->getParameter('kernel.root_dir') . '/../composer.json'), true)['version'],
            'description' => 'App version',
            'icon' => 'icon-command'
        ];

        if ($_ENV['SYMFONY_ENVIRONMENT']) {
            $information[] = [
                'value' => $_ENV['SYMFONY_ENVIRONMENT'],
                'description' => 'App environment',
                'icon' => 'icon-git-branch'
            ];
        }

        $information[] = [
            'value' => phpversion(),
            'description' => 'PHP version',
            'icon' => 'icon-php'
        ];
        $information[] = [
            'value' => \Symfony\Component\HttpKernel\Kernel::VERSION,
            'description' => 'Symfony version',
            'icon' => 'icon-symfony'
        ];

        if ($_ENV['APP_ENV']) {
            $information[] = [
                'value' => $_ENV['APP_ENV'],
                'description' => 'Symfony environment',
                'icon' => 'icon-package'
            ];
        }

        return array_merge($information, [
            [
                'value' => PHP_OS,
                'description' => 'Operating system',
                'icon' => 'icon-hard-drive'
            ]
        ]);
    }



    /**
     * @return array
     */
    public function getFurtherSystemInformation(): array
    {
        return [
            'Server IP' => $_SERVER['SERVER_ADDR'],
            'Server name' => gethostname(),
            'Server protocol' => $_SERVER['SERVER_PROTOCOL'],
            'Server software' => $_SERVER['SERVER_SOFTWARE'],
            'Operating system' => PHP_OS,
            'OS' => php_uname(),
            'PHP' => phpversion(),
            'Interface' => php_sapi_name(),
            'Intl locale' => Locale::getDefault(),
            'Timezone' => date_default_timezone_get(),
            'Date' => (new DateTime())->format('Y-m-d H:i:s'),
            'App version' => \json_decode(file_get_contents($this->container->getParameter('kernel.root_dir') . '/../composer.json'), true)['version'],
            'App environment' => $_ENV['SYMFONY_ENVIRONMENT'],
            'Symfony version' => \Symfony\Component\HttpKernel\Kernel::VERSION,
            'Symfony environment' => $_ENV['APP_ENV']
        ];
    }
}