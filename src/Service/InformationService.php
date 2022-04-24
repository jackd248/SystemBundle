<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use DateTime;
use Exception;
use Locale;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 */
class InformationService {

    /**
     * @var Container
     */
    private Container $container;

    /**
     * @var \Symfony\Contracts\Translation\TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var \Kmi\SystemInformationBundle\Service\CheckService
     */
    private CheckService $checkService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\LogService
     */
    private LogService $logService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\SymfonyService
     */
    private SymfonyService $symfonyService;

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     * @param \Kmi\SystemInformationBundle\Service\CheckService $checkService
     * @param \Kmi\SystemInformationBundle\Service\LogService $logService
     * @param \Kmi\SystemInformationBundle\Service\SymfonyService $symfonyService
     */
    public function __construct(Container $container, TranslatorInterface $translator, CheckService $checkService, LogService $logService, SymfonyService $symfonyService)
    {
        $this->container = $container;
        $this->translator = $translator;
        $this->checkService = $checkService;
        $this->logService = $logService;
        $this->symfonyService = $symfonyService;
    }

    /**
     * @param bool $forceUpdate
     * @return array
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getSystemInformation(bool $forceUpdate = false): array
    {
        $information = [];

        $checks = $this->checkService->getLiipMonitorChecks($forceUpdate);

        if ($this->checkService->getMonitorCheckStatus($checks)) {
            $information['checks'] = [
                'value' => $this->checkService->getMonitorCheckCount($checks) . ' ' . $this->translator->trans('system.items.check.value', [], 'SystemInformationBundle'),
                'description' => $this->translator->trans('system.items.check.description', [], 'SystemInformationBundle'),
                'icon' => 'icon-monitor',
                'class' => 'bg-color-error'
            ];
        }

        if ($errorCount = $this->logService->getErrorCount($forceUpdate)) {
            $information['logs'] = [
                'value' => $errorCount . ' ' . $this->translator->trans('system.items.logs.value', [], 'SystemInformationBundle'),
                'description' => $this->translator->trans('system.items.logs.description', [], 'SystemInformationBundle'),
                'icon' => 'icon-info',
                'class' => 'bg-color-error'
            ];
        }

        if ($this->symfonyService->getRequirementsCount()['requirements'] || $this->symfonyService->getRequirementsCount()['recommendations']) {
            if ($requirementsCount = $this->symfonyService->getRequirementsCount()['requirements']) {
                $information['requirements'] = [
                    'value' => $requirementsCount . ' ' . $this->translator->trans('system.items.requirements.value', [], 'SystemInformationBundle'),
                    'description' => $this->translator->trans('system.items.requirements.description', [], 'SystemInformationBundle'),
                    'icon' => 'icon-package',
                    'class' => 'bg-color-error'
                ];
            }
            if ($recommendationCount = $this->symfonyService->getRequirementsCount()['recommendations']) {
                $information['requirements'] = [
                    'value' => $recommendationCount . ' ' . $this->translator->trans('system.items.requirements.value', [], 'SystemInformationBundle'),
                    'description' => $this->translator->trans('system.items.requirements.description', [], 'SystemInformationBundle'),
                    'icon' => 'icon-package',
                    'class' => 'bg-color-warning'
                ];
            }
        }

        $information['appVersion'] = [
            'value' => $this->getAppVersion()['value'],
            'description' => $this->translator->trans('system.items.app_version.description', [], 'SystemInformationBundle'),
            'icon' => 'icon-command'
        ];

        $information['phpVersion'] = [
            'value' => $this->getPhpVersion()['value'],
            'description' => $this->translator->trans('system.items.php.description', [], 'SystemInformationBundle'),
            'icon' => 'icon-php'
        ];
        $information['symfonyVersion'] = [
            'value' => $this->getSymfonyVersion()['value'],
            'description' => $this->translator->trans('system.items.symfony.description', [], 'SystemInformationBundle'),
            'icon' => 'icon-symfony'
        ];

        if ($appEnv = $this->getAppEnvironment()['value']) {
            $information['appEnvironment'] = [
                'value' => $appEnv,
                'description' => $this->translator->trans('system.items.app_env.description', [], 'SystemInformationBundle'),
                'icon' => 'icon-package'
            ];
        }

        if ($symfonyEnv = $this->getSymfonyEnvironment()['value']) {
            $information['symfonyEnvironment'] = [
                'value' => $symfonyEnv,
                'description' => $this->translator->trans('system.items.symfony_env.description', [], 'SystemInformationBundle'),
                'icon' => 'icon-git-branch'
            ];
        }

        $information['os'] = [
            'value' => $this->getServerOperating()['value'],
            'description' => $this->translator->trans('system.items.os.description', [], 'SystemInformationBundle'),
            'icon' => 'icon-hard-drive'
        ];

        return array_splice($information, 0, 6);
    }


    /**
     * @return array
     * @throws \Exception
     */
    public function getFurtherSystemInformation(): array
    {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */

        $databaseVersion = null;
        try {
            $databaseVersion = $entityManager->getConnection()->fetchOne('SELECT @@version;');
        } catch (Exception $e) {};

        return [
            $this->translator->trans('system.information.server.label', [], 'SystemInformationBundle') => [
                $this->getServerIp(),
                $this->getServerName(),
                $this->getServerProtocol(),
                $this->getServerWeb(),
                $this->getServerOperating(),
                $this->getServerDistribution(),
                $this->getServerDescription()
            ],
            $this->translator->trans('system.information.php.label', [], 'SystemInformationBundle') => [
                $this->getPhpVersion(),
                $this->getPhpInterface(),
                $this->getPhpLocale()
            ],
            $this->translator->trans('system.information.date.label', [], 'SystemInformationBundle') => [
                $this->getDateTimezone(),
                $this->getDateNow()
            ],
            $this->translator->trans('system.information.app.label', [], 'SystemInformationBundle') => [
                $this->getAppVersion(),
                $this->getAppEnvironment()
            ],
            $this->translator->trans('system.information.symfony.label', [], 'SystemInformationBundle') => [
                $this->getSymfonyVersion(),
                $this->getSymfonyEnvironment()
            ],
            $this->translator->trans('system.information.database.label', [], 'SystemInformationBundle') => [
                $this->getDatabasePlatform(),
                $this->getDatabaseVersion(),
                $this->getDatabaseHost(),
                $this->getDatabaseName(),
                $this->getDatabaseUser(),
                $this->getDatabasePort()
            ]
        ];
    }

    /**
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSystemStatus(): bool
    {
        $countWarningsAndErrorsInLogs = 0;
        $logList = $this->logService->getLogs();
        foreach ($logList as $log) {
            $countWarningsAndErrorsInLogs += $log['warningCountByPeriod'] + $log['errorCountByPeriod'];
        }
        return $countWarningsAndErrorsInLogs || $this->checkService->getMonitorCheckStatus($this->checkService->getLiipMonitorChecks());
    }

    /**
     * @return mixed|null
     */
    public function readAppVersion() {
        $composerFile = file_get_contents($this->container->getParameter('kernel.project_dir') . '/composer.json');
        if ($composerFile) {
            $composerArray = \json_decode($composerFile, true);
            if ($composerArray) {
                if (array_key_exists('version', $composerArray)) {
                    return $composerArray['version'];
                }
            }
        }
        return null;
    }

    /**
     * @return array
     */
    public function getServerIp(): array {
        return [
            'label' => $this->translator->trans('system.information.server.ip', [], 'SystemInformationBundle'),
            'value' => $_SERVER['SERVER_ADDR']
        ];
    }

    /**
     * @return array
     */
    public function getServerName(): array {
        return [
            'label' => $this->translator->trans('system.information.server.name', [], 'SystemInformationBundle'),
            'value' => gethostname()
        ];
    }

    /**
     * @return array
     */
    public function getServerProtocol(): array {
        return [
            'label' => $this->translator->trans('system.information.server.protocol', [], 'SystemInformationBundle'),
            'value' => $_SERVER['SERVER_PROTOCOL']
        ];
    }

    /**
     * @return array
     */
    public function getServerWeb(): array {
        return [
            'label' => $this->translator->trans('system.information.server.web', [], 'SystemInformationBundle'),
            'value' => $_SERVER['SERVER_SOFTWARE']
        ];
    }

    /**
     * @return array
     */
    public function getServerOperating(): array {
        return [
            'label' => $this->translator->trans('system.information.server.operating', [], 'SystemInformationBundle'),
            'value' => PHP_OS
        ];
    }

    /**
     * @return array
     */
    public function getServerDistribution(): array {
        return [
            'label' => $this->translator->trans('system.information.server.distribution', [], 'SystemInformationBundle'),
            'value' => $this->getOSInformation()['pretty_name']
        ];
    }

    /**
     * @return array
     */
    public function getServerDescription(): array {
        return [
            'label' => $this->translator->trans('system.information.server.description', [], 'SystemInformationBundle'),
            'value' => php_uname()
        ];
    }

    /**
     * @return array
     */
    public function getPhpVersion(): array {
        return [
            'label' => $this->translator->trans('system.information.php.version', [], 'SystemInformationBundle'),
            'value' => phpversion()
        ];
    }

    /**
     * @return array
     */
    public function getPhpInterface(): array {
        return [
            'label' => $this->translator->trans('system.information.php.interface', [], 'SystemInformationBundle'),
            'value' => php_sapi_name()
        ];
    }

    /**
     * @return array
     */
    public function getPhpLocale(): array {
        return [
            'label' => $this->translator->trans('system.information.php.locale', [], 'SystemInformationBundle'),
            'value' => Locale::getDefault()
        ];
    }

    /**
     * @return array
     */
    public function getDateTimezone(): array {
        return [
            'label' => $this->translator->trans('system.information.date.timezone', [], 'SystemInformationBundle'),
            'value' => date_default_timezone_get(),
        ];
    }

    /**
     * @return array
     */
    public function getDateNow(): array {
        return [
            'label' => $this->translator->trans('system.information.date.now', [], 'SystemInformationBundle'),
            'value' => (new DateTime())->format('Y-m-d H:i:s')
        ];
    }

    /**
     * @return array
     */
    public function getAppVersion(): array {
        return [
            'label' => $this->translator->trans('system.information.app.version', [], 'SystemInformationBundle'),
            'value' => $this->readAppVersion()
        ];
    }

    /**
     * @return array
     */
    public function getAppEnvironment(): array {
        return [
            'label' => $this->translator->trans('system.information.app.environment', [], 'SystemInformationBundle'),
            'value' => $_ENV['SYMFONY_ENVIRONMENT']
        ];
    }

    /**
     * @return array
     */
    public function getSymfonyVersion(): array {
        return [
            'label' => $this->translator->trans('system.information.symfony.version', [], 'SystemInformationBundle'),
            'value' => \Symfony\Component\HttpKernel\Kernel::VERSION
        ];
    }

    /**
     * @return array
     */
    public function getSymfonyEnvironment(): array {
        return [
            'label' => $this->translator->trans('system.information.symfony.environment', [], 'SystemInformationBundle'),
            'value' => $_ENV['APP_ENV']
        ];
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDatabasePlatform(): array {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        return [
            'label' => $this->translator->trans('system.information.database.platform', [], 'SystemInformationBundle'),
            'value' => $entityManager->getConnection()->getDatabasePlatform()->getName()
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getDatabaseVersion(): array {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        $databaseVersion = null;
        try {
            $databaseVersion = $entityManager->getConnection()->fetchOne('SELECT @@version;');
        } catch (Exception $e) {};
        return [
            'label' => $this->translator->trans('system.information.database.version', [], 'SystemInformationBundle'),
            'value' => $databaseVersion
        ];
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDatabaseHost(): array {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        return [
            'label' => $this->translator->trans('system.information.database.host', [], 'SystemInformationBundle'),
            'value' => $entityManager->getConnection()->getParams()['host']
        ];
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDatabaseName(): array {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        return [
            'label' => $this->translator->trans('system.information.database.name', [], 'SystemInformationBundle'),
            'value' => $entityManager->getConnection()->getDatabase()
        ];
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDatabaseUser(): array {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        return [
            'label' => $this->translator->trans('system.information.database.platform', [], 'SystemInformationBundle'),
            'value' => $entityManager->getConnection()->getParams()['user']
        ];
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDatabasePort(): array {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        return [
            'label' => $this->translator->trans('system.information.database.platform', [], 'SystemInformationBundle'),
            'value' => $entityManager->getConnection()->getParams()['port']
        ];
    }



    /**
     * https://stackoverflow.com/a/42397673
     * @return array|false|null
     */
    private function getOSInformation()
    {
        if (false == function_exists("shell_exec") || false == is_readable("/etc/os-release")) {
            return null;
        }

        $os         = shell_exec('cat /etc/os-release');
        $listIds    = preg_match_all('/.*=/', $os, $matchListIds);
        $listIds    = $matchListIds[0];

        $listVal    = preg_match_all('/=.*/', $os, $matchListVal);
        $listVal    = $matchListVal[0];

        array_walk($listIds, function(&$v, $k){
            $v = strtolower(str_replace('=', '', $v));
        });

        array_walk($listVal, function(&$v, $k){
            $v = preg_replace('/=|"/', '', $v);
        });

        return array_combine($listIds, $listVal);
    }
}