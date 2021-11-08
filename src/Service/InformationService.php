<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use DateTime;
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
    private $container;

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
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     * @param \Kmi\SystemInformationBundle\Service\CheckService $checkService
     * @param \Kmi\SystemInformationBundle\Service\LogService $logService
     */
    public function __construct(Container $container, TranslatorInterface $translator, CheckService $checkService, LogService $logService)
    {
        $this->container = $container;
        $this->translator = $translator;
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
                'value' => $this->checkService->getMonitorCheckCount($checks) . ' ' . $this->translator->trans('system.items.check.value', [], 'SystemInformationBundle'),
                'description' => $this->translator->trans('system.items.check.description', [], 'SystemInformationBundle'),
                'icon' => 'icon-alert-triangle',
                'class' => 'color-error'
            ];
        }

        if ($errorCount = $this->logService->getErrorCount()) {
            $information[] = [
                'value' => $errorCount . ' ' . $this->translator->trans('system.items.logs.value', [], 'SystemInformationBundle'),
                'description' => $this->translator->trans('system.items.logs.description', [], 'SystemInformationBundle'),
                'icon' => 'icon-alert-circle',
                'class' => 'color-error'
            ];
        }

        $information[] = [
            'value' => $this->getAppVersion(),
            'description' => $this->translator->trans('system.items.app_version.description', [], 'SystemInformationBundle'),
            'icon' => 'icon-command'
        ];

        $information[] = [
            'value' => phpversion(),
            'description' => $this->translator->trans('system.items.php.description', [], 'SystemInformationBundle'),
            'icon' => 'icon-php'
        ];
        $information[] = [
            'value' => \Symfony\Component\HttpKernel\Kernel::VERSION,
            'description' => $this->translator->trans('system.items.symfony.description', [], 'SystemInformationBundle'),
            'icon' => 'icon-symfony'
        ];

        if ($appEnv = $_ENV['APP_ENV']) {
            $information[] = [
                'value' => $appEnv,
                'description' => $this->translator->trans('system.items.app_env.description', [], 'SystemInformationBundle'),
                'icon' => 'icon-package'
            ];
        }

        if ($symfonyEnv = $_ENV['SYMFONY_ENVIRONMENT']) {
            $information[] = [
                'value' => $symfonyEnv,
                'description' => $this->translator->trans('system.items.symfony_env.description', [], 'SystemInformationBundle'),
                'icon' => 'icon-git-branch'
            ];
        }

        $information[] = [
            'value' => PHP_OS,
            'description' => $this->translator->trans('system.items.os.description', [], 'SystemInformationBundle'),
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
            $this->translator->trans('system.information.server.ip', [], 'SystemInformationBundle') => $_SERVER['SERVER_ADDR'],
            $this->translator->trans('system.information.server.name', [], 'SystemInformationBundle') => gethostname(),
            $this->translator->trans('system.information.server.protocol', [], 'SystemInformationBundle') => $_SERVER['SERVER_PROTOCOL'],
            $this->translator->trans('system.information.server.software', [], 'SystemInformationBundle') => $_SERVER['SERVER_SOFTWARE'],
            $this->translator->trans('system.information.server.operating', [], 'SystemInformationBundle') => PHP_OS,
            $this->translator->trans('system.information.server.os', [], 'SystemInformationBundle') => php_uname(),
            $this->translator->trans('system.information.php.version', [], 'SystemInformationBundle') => phpversion(),
            $this->translator->trans('system.information.php.interface', [], 'SystemInformationBundle') => php_sapi_name(),
            $this->translator->trans('system.information.php.locale', [], 'SystemInformationBundle') => Locale::getDefault(),
            $this->translator->trans('system.information.date.timezone', [], 'SystemInformationBundle') => date_default_timezone_get(),
            $this->translator->trans('system.information.date.now', [], 'SystemInformationBundle') => (new DateTime())->format('Y-m-d H:i:s'),
            $this->translator->trans('system.information.app.version', [], 'SystemInformationBundle') => $this->getAppVersion(),
            $this->translator->trans('system.information.app.environment', [], 'SystemInformationBundle') => $_ENV['SYMFONY_ENVIRONMENT'],
            $this->translator->trans('system.information.symfony.version', [], 'SystemInformationBundle') => \Symfony\Component\HttpKernel\Kernel::VERSION,
            $this->translator->trans('system.information.symfony.environment', [], 'SystemInformationBundle') => $_ENV['APP_ENV']
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

    /**
     * @return mixed|null
     */
    public function getAppVersion() {
        $composerFile = file_get_contents($this->container->getParameter('kernel.root_dir') . '/../composer.json');
        if ($composerFile) {
            $composerArray = \json_decode($composerFile, true);
            if (array_key_exists('version', $composerArray)) {
                return $composerArray['version'];
            }
        }
        return null;
    }
}