<?php

declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use DateTime;
use Exception;
use Locale;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class InformationService
{
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
     * @var \Kmi\SystemInformationBundle\Service\DependencyService
     */
    private DependencyService $dependencyService;

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     * @param \Kmi\SystemInformationBundle\Service\CheckService $checkService
     * @param \Kmi\SystemInformationBundle\Service\LogService $logService
     * @param \Kmi\SystemInformationBundle\Service\SymfonyService $symfonyService
     * @param \Kmi\SystemInformationBundle\Service\DependencyService $dependencyService
     */
    public function __construct(Container $container, TranslatorInterface $translator, CheckService $checkService, LogService $logService, SymfonyService $symfonyService, DependencyService $dependencyService)
    {
        $this->container = $container;
        $this->translator = $translator;
        $this->checkService = $checkService;
        $this->logService = $logService;
        $this->symfonyService = $symfonyService;
        $this->dependencyService = $dependencyService;
    }

    /**
     * @param bool $forceUpdate
     * @return array
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getSystemInformation(bool $forceUpdate = false): array
    {
        $information = [];

        $checks = $this->checkService->getLiipMonitorChecks($forceUpdate)->getResults();
        $dependencies = $this->dependencyService->getDependencyInformation()['dependencies'];
        $dependencyStatus = $this->dependencyService->getDependencyApplicationStatus($dependencies);

        if ($this->checkService->getMonitorCheckStatus($checks)) {
            $information['checks'] = [
                'value' => $this->checkService->getMonitorCheckCount($checks) . ' ' . $this->translator->trans('system.items.check.value', [], 'SystemInformationBundle'),
                'description' => $this->translator->trans('system.items.check.description', [], 'SystemInformationBundle'),
                'icon' => 'icon-sib-monitor',
                'class' => 'color-error',
                'route' => $this->container->get('router')->generate('kmi_system_information_monitoring', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        if ($errorCount = $this->logService->getErrorCount($forceUpdate)) {
            $information['logs'] = [
                'value' => $errorCount . ' ' . $this->translator->trans('system.items.logs.value', [], 'SystemInformationBundle'),
                'description' => $this->translator->trans('system.items.logs.description', [], 'SystemInformationBundle'),
                'icon' => 'icon-sib-info',
                'class' => 'color-error',
                'route' => $this->container->get('router')->generate('kmi_system_information_log', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        if ($dependencyStatus['status'] !== DependencyService::STATE_UP_TO_DATE && $dependencyStatus['status'] !== DependencyService::STATE_INSECURE) {
            $count = count($dependencyStatus['distribution'][DependencyService::STATE_PINNED_OUT_OF_DATE]) + count($dependencyStatus['distribution'][DependencyService::STATE_OUT_OF_DATE]);
            $information['dependency'] = [
                'value' => $count . ' ' . $this->translator->trans('system.items.dependency.value', [], 'SystemInformationBundle'),
                'description' => $this->translator->trans('system.items.dependency.description', [], 'SystemInformationBundle'),
                'icon' => 'icon-sib-code',
                'class' => 'color-warning',
                'route' => $this->container->get('router')->generate('kmi_system_information_dependencies', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        if ($dependencyStatus['status'] == DependencyService::STATE_INSECURE) {
            $count = count($dependencyStatus['distribution'][DependencyService::STATE_INSECURE]);
            $information['dependency'] = [
                'value' => $count . ' ' . $this->translator->trans('system.items.dependency.value', [], 'SystemInformationBundle'),
                'description' => $this->translator->trans('system.items.dependency.description', [], 'SystemInformationBundle'),
                'icon' => 'icon-sib-code',
                'class' => 'color-error',
                'route' => $this->container->get('router')->generate('kmi_system_information_dependencies', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        if ($this->symfonyService->getRequirementsCount()['requirements'] || $this->symfonyService->getRequirementsCount()['recommendations']) {
            if ($requirementsCount = $this->symfonyService->getRequirementsCount()['requirements']) {
                $information['requirements'] = [
                    'value' => $requirementsCount . ' ' . $this->translator->trans('system.items.requirements.value', [], 'SystemInformationBundle'),
                    'description' => $this->translator->trans('system.items.requirements.description', [], 'SystemInformationBundle'),
                    'icon' => 'icon-sib-package',
                    'class' => 'color-error',
                    'route' => $this->container->get('router')->generate('kmi_system_information_requirements', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ];
            }
            if ($recommendationCount = $this->symfonyService->getRequirementsCount()['recommendations']) {
                $information['requirements'] = [
                    'value' => $recommendationCount . ' ' . $this->translator->trans('system.items.requirements.value', [], 'SystemInformationBundle'),
                    'description' => $this->translator->trans('system.items.requirements.description', [], 'SystemInformationBundle'),
                    'icon' => 'icon-sib-package',
                    'class' => 'color-warning',
                    'route' => $this->container->get('router')->generate('kmi_system_information_requirements', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ];
            }
        }

        $information['appVersion'] = [
            'value' => $this->getAppVersion()['value'],
            'description' => $this->translator->trans('system.items.app_version.description', [], 'SystemInformationBundle'),
            'icon' => 'icon-sib-command',
            'route' => $this->container->get('router')->generate('kmi_system_information_information', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        $information['phpVersion'] = [
            'value' => $this->getPhpVersion()['value'],
            'description' => $this->translator->trans('system.items.php.description', [], 'SystemInformationBundle'),
            'icon' => 'icon-sib-php',
            'route' => $this->container->get('router')->generate('kmi_system_information_information', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
        $information['symfonyVersion'] = [
            'value' => $this->getSymfonyVersion()['value'],
            'description' => $this->translator->trans('system.items.symfony.description', [], 'SystemInformationBundle'),
            'icon' => 'icon-sib-symfony',
            'route' => $this->container->get('router')->generate('kmi_system_information_information'),
        ];

        if ($appEnv = $this->getAppEnvironment()['value']) {
            $information['appEnvironment'] = [
                'value' => $appEnv,
                'description' => $this->translator->trans('system.items.app_env.description', [], 'SystemInformationBundle'),
                'icon' => 'icon-sib-package',
                'route' => $this->container->get('router')->generate('kmi_system_information_information', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        if ($symfonyEnv = $this->getSymfonyEnvironment()['value']) {
            $information['symfonyEnvironment'] = [
                'value' => $symfonyEnv,
                'description' => $this->translator->trans('system.items.symfony_env.description', [], 'SystemInformationBundle'),
                'icon' => 'icon-sib-git-branch',
                'route' => $this->container->get('router')->generate('kmi_system_information_information', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        $information['os'] = [
            'value' => $this->getServerOperating()['value'],
            'description' => $this->translator->trans('system.items.os.description', [], 'SystemInformationBundle'),
            'icon' => 'icon-sib-hard-drive',
            'route' => $this->container->get('router')->generate('kmi_system_information_information', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        return array_splice($information, 0, 6);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getFurtherSystemInformation(): array
    {
        return [
            $this->translator->trans('system.information.server.label', [], 'SystemInformationBundle') => [
                $this->getServerIp(),
                $this->getServerName(),
                $this->getServerProtocol(),
                $this->getServerWeb(),
                $this->getServerOperating(),
                $this->getServerDistribution(),
                $this->getServerDescription(),
            ],
            $this->translator->trans('system.information.php.label', [], 'SystemInformationBundle') => [
                $this->getPhpVersion(),
                $this->getPhpInterface(),
                $this->getPhpLocale(),
                $this->getPhpMemoryLimit(),
                $this->getPhpMaxExecutionTime(),
            ],
            $this->translator->trans('system.information.date.label', [], 'SystemInformationBundle') => [
                $this->getDateTimezone(),
                $this->getDateNow(),
            ],
            $this->translator->trans('system.information.app.label', [], 'SystemInformationBundle') => [
                $this->getAppVersion(),
                $this->getAppEnvironment(),
            ],
            $this->translator->trans('system.information.symfony.label', [], 'SystemInformationBundle') => [
                $this->getSymfonyVersion(),
                $this->getSymfonyEnvironment(),
            ],
            $this->translator->trans('system.information.mail.label', [], 'SystemInformationBundle') => [
                $this->getMailService(),
                $this->getMailScheme(),
                $this->getMailHost(),
                $this->getMailPort(),
            ],
            $this->translator->trans('system.information.database.label', [], 'SystemInformationBundle') => [
                $this->getDatabasePlatform(),
                $this->getDatabaseVersion(),
                $this->getDatabaseHost(),
                $this->getDatabaseName(),
                $this->getDatabaseUser(),
                $this->getDatabasePort(),
                $this->getDatabaseCharacterSet(),
                $this->getDatabaseCollaction(),
            ],
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
    public function readAppVersion()
    {
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
    public function getServerIp(): array
    {
        return [
            'label' => $this->translator->trans('system.information.server.ip', [], 'SystemInformationBundle'),
            'value' => $_SERVER['SERVER_ADDR'],
        ];
    }

    /**
     * @return array
     */
    public function getServerName(): array
    {
        return [
            'label' => $this->translator->trans('system.information.server.name', [], 'SystemInformationBundle'),
            'value' => gethostname(),
        ];
    }

    /**
     * @return array
     */
    public function getServerProtocol(): array
    {
        return [
            'label' => $this->translator->trans('system.information.server.protocol', [], 'SystemInformationBundle'),
            'value' => $_SERVER['SERVER_PROTOCOL'],
        ];
    }

    /**
     * @return array
     */
    public function getServerWeb(): array
    {
        return [
            'label' => $this->translator->trans('system.information.server.web', [], 'SystemInformationBundle'),
            'value' => $_SERVER['SERVER_SOFTWARE'],
        ];
    }

    /**
     * @return array
     */
    public function getServerOperating(): array
    {
        return [
            'label' => $this->translator->trans('system.information.server.operating', [], 'SystemInformationBundle'),
            'value' => PHP_OS,
        ];
    }

    /**
     * @return array
     */
    public function getServerDistribution(): array
    {
        return [
            'label' => $this->translator->trans('system.information.server.distribution', [], 'SystemInformationBundle'),
            'value' => $this->getOSInformation()['pretty_name'],
        ];
    }

    /**
     * @return array
     */
    public function getServerDescription(): array
    {
        return [
            'label' => $this->translator->trans('system.information.server.description', [], 'SystemInformationBundle'),
            'value' => php_uname(),
        ];
    }

    /**
     * @return array
     */
    public function getPhpVersion(): array
    {
        return [
            'label' => $this->translator->trans('system.information.php.version', [], 'SystemInformationBundle'),
            'value' => phpversion(),
        ];
    }

    /**
     * @return array
     */
    public function getPhpInterface(): array
    {
        return [
            'label' => $this->translator->trans('system.information.php.interface', [], 'SystemInformationBundle'),
            'value' => php_sapi_name(),
        ];
    }

    /**
     * @return array
     */
    public function getPhpLocale(): array
    {
        return [
            'label' => $this->translator->trans('system.information.php.locale', [], 'SystemInformationBundle'),
            'value' => Locale::getDefault(),
        ];
    }

    /**
     * @return array
     */
    public function getPhpMemoryLimit(): array
    {
        return [
            'label' => $this->translator->trans('system.information.php.memory_limit', [], 'SystemInformationBundle'),
            'value' => ini_get('memory_limit'),
        ];
    }

    /**
     * @return array
     */
    public function getPhpMaxExecutionTime(): array
    {
        return [
            'label' => $this->translator->trans('system.information.php.execution_time', [], 'SystemInformationBundle'),
            'value' => ini_get('max_execution_time'),
        ];
    }

    /**
     * @return array
     */
    public function getDateTimezone(): array
    {
        return [
            'label' => $this->translator->trans('system.information.date.timezone', [], 'SystemInformationBundle'),
            'value' => date_default_timezone_get(),
        ];
    }

    /**
     * @return array
     */
    public function getDateNow(): array
    {
        return [
            'label' => $this->translator->trans('system.information.date.now', [], 'SystemInformationBundle'),
            'value' => (new DateTime())->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array
     */
    public function getAppVersion(): array
    {
        return [
            'label' => $this->translator->trans('system.information.app.version', [], 'SystemInformationBundle'),
            'value' => $this->readAppVersion(),
        ];
    }

    /**
     * @return array
     */
    public function getAppEnvironment(): array
    {
        return [
            'label' => $this->translator->trans('system.information.app.environment', [], 'SystemInformationBundle'),
            'value' => $_ENV['SYMFONY_ENVIRONMENT'],
        ];
    }

    /**
     * @return array
     */
    public function getSymfonyVersion(): array
    {
        return [
            'label' => $this->translator->trans('system.information.symfony.version', [], 'SystemInformationBundle'),
            'value' => \Symfony\Component\HttpKernel\Kernel::VERSION,
        ];
    }

    /**
     * @return array
     */
    public function getSymfonyEnvironment(): array
    {
        return [
            'label' => $this->translator->trans('system.information.symfony.environment', [], 'SystemInformationBundle'),
            'value' => $_ENV['APP_ENV'],
        ];
    }

    /**
     * @return array
     */
    public function getMailScheme(): array
    {
        return [
            'label' => $this->translator->trans('system.information.mail.scheme', [], 'SystemInformationBundle'),
            'value' => $this->getMailConfiguration()['scheme'],
        ];
    }

    /**
     * @return array
     */
    public function getMailHost(): array
    {
        return [
            'label' => $this->translator->trans('system.information.mail.host', [], 'SystemInformationBundle'),
            'value' => $this->getMailConfiguration()['host'],
        ];
    }

    /**
     * @return array
     */
    public function getMailPort(): array
    {
        return [
            'label' => $this->translator->trans('system.information.mail.port', [], 'SystemInformationBundle'),
            'value' => $this->getMailConfiguration()['port'],
        ];
    }

    /**
     * @return array
     */
    public function getMailService(): array
    {
        return [
            'label' => $this->translator->trans('system.information.mail.service', [], 'SystemInformationBundle'),
            'value' => $this->getMailConfiguration()['service'],
        ];
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDatabasePlatform(): array
    {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        return [
            'label' => $this->translator->trans('system.information.database.platform', [], 'SystemInformationBundle'),
            'value' => $entityManager->getConnection()->getDatabasePlatform()->getName(),
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getDatabaseVersion(): array
    {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        $databaseVersion = null;
        try {
            $databaseVersion = $entityManager->getConnection()->fetchOne('SELECT @@version;');
        } catch (Exception $e) {
        }
        return [
            'label' => $this->translator->trans('system.information.database.version', [], 'SystemInformationBundle'),
            'value' => $databaseVersion,
        ];
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDatabaseHost(): array
    {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        return [
            'label' => $this->translator->trans('system.information.database.host', [], 'SystemInformationBundle'),
            'value' => $entityManager->getConnection()->getParams()['host'],
        ];
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDatabaseName(): array
    {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        return [
            'label' => $this->translator->trans('system.information.database.name', [], 'SystemInformationBundle'),
            'value' => $entityManager->getConnection()->getDatabase(),
        ];
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDatabaseUser(): array
    {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        return [
            'label' => $this->translator->trans('system.information.database.user', [], 'SystemInformationBundle'),
            'value' => $entityManager->getConnection()->getParams()['user'],
        ];
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDatabasePort(): array
    {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        return [
            'label' => $this->translator->trans('system.information.database.port', [], 'SystemInformationBundle'),
            'value' => $entityManager->getConnection()->getParams()['port'],
        ];
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDatabaseCharacterSet(): array
    {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        $characterSet = null;
        try {
            $characterSet = $entityManager->getConnection()->fetchOne('SELECT @@character_set_database;');
        } catch (Exception $e) {
        }
        return [
            'label' => $this->translator->trans('system.information.database.character_set', [], 'SystemInformationBundle'),
            'value' => $characterSet,
        ];
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getDatabaseCollaction(): array
    {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        $collation = null;
        try {
            $collation = $entityManager->getConnection()->fetchOne('SELECT @@collation_database;');
        } catch (Exception $e) {
        }
        return [
            'label' => $this->translator->trans('system.information.database.collation', [], 'SystemInformationBundle'),
            'value' => $collation,
        ];
    }

    /**
     * @return array|false|int|string|null
     */
    public function getMailConfiguration()
    {
        $configuration = null;
        // Swiftmailer
        if (array_key_exists('MAILER_URL', $_ENV) && class_exists(\Swift_Mailer::class)) {
            $configuration = parse_url($_ENV['MAILER_URL']);
            $configuration['service'] = 'SwiftMailer';
        }
        // Symfony Mailer
        if (array_key_exists('MAILER_DSN', $_ENV) && interface_exists(\Symfony\Component\Mailer\MailerInterface::class)) {
            $configuration = parse_url($_ENV['MAILER_DSN']);
            $configuration['service'] = 'SymfonyMailer';
        }
        return $configuration;
    }

    /**
     * https://stackoverflow.com/a/42397673
     * @return array|false|null
     */
    private function getOSInformation()
    {
        if (false == function_exists('shell_exec') || false == is_readable('/etc/os-release')) {
            return null;
        }

        $os         = shell_exec('cat /etc/os-release');
        $listIds    = preg_match_all('/.*=/', $os, $matchListIds);
        $listIds    = $matchListIds[0];

        $listVal    = preg_match_all('/=.*/', $os, $matchListVal);
        $listVal    = $matchListVal[0];

        array_walk($listIds, function (&$v, $k) {
            $v = strtolower(str_replace('=', '', $v));
        });

        array_walk($listVal, function (&$v, $k) {
            $v = preg_replace('/=|"/', '', $v);
        });

        return array_combine($listIds, $listVal);
    }
}
