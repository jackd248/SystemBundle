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
     * @var \Kmi\SystemInformationBundle\Service\CheckService
     */
    private CheckService $checkService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\LogService
     */
    private LogService $logService;

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Kmi\SystemInformationBundle\Service\CheckService $checkService
     * @param \Kmi\SystemInformationBundle\Service\LogService $logService
     */
    public function __construct(Container $container, CheckService $checkService, LogService $logService)
    {
        $this->container = $container;
        $this->checkService = $checkService;
        $this->logService = $logService;
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSystemInformation(): array
    {
        $information = [];

        $checks = $this->checkService->getLiipMonitorChecks();

        if ($this->checkService->getMonitorCheckStatus($checks)) {
            $information[] = [
                'value' => $this->checkService->getMonitorCheckCount($checks) . ' checks',
                'description' => 'resulted in a warning or failure',
                'icon' => 'icon-alert-triangle',
                'class' => 'color-error'
            ];
        }

        if ($errorCount = $this->logService->getErrorCount()) {
            $information[] = [
                'value' => $errorCount . ' anomalies',
                'description' => 'in logs within last 24h',
                'icon' => 'icon-alert-circle',
                'class' => 'color-error'
            ];
        }

        $information[] = [
            'value' => \json_decode(file_get_contents($this->container->getParameter('kernel.root_dir') . '/../composer.json'), true)['version'],
            'description' => 'App version',
            'icon' => 'icon-command'
        ];

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

        if ($_ENV['SYMFONY_ENVIRONMENT']) {
            $information[] = [
                'value' => $_ENV['SYMFONY_ENVIRONMENT'],
                'description' => 'App environment',
                'icon' => 'icon-git-branch'
            ];
        }

        $information[] = [
            'value' => PHP_OS,
            'description' => 'Operating system',
            'icon' => 'icon-hard-drive'
        ];

        return array_splice($information, 0, 6);
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

    /**
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSystemStatus() {
        $countWarningsAndErrosInLogs = 0;
        $logList = $this->logService->getLogList();
        foreach ($logList as $log) {
            $countWarningsAndErrosInLogs += $log['warningCountByPeriod'] + $log['errorCountByPeriod'];
        }
        return $countWarningsAndErrosInLogs || $this->checkService->getMonitorCheckStatus($this->checkService->getLiipMonitorChecks());
    }
}