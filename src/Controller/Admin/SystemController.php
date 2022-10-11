<?php

declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Controller\Admin;

use Kmi\SystemInformationBundle\Service\BundleService;
use Kmi\SystemInformationBundle\Service\CheckService;
use Kmi\SystemInformationBundle\Service\DatabaseService;
use Kmi\SystemInformationBundle\Service\DependencyService;
use Kmi\SystemInformationBundle\Service\InformationService;
use Kmi\SystemInformationBundle\Service\LogService;
use Kmi\SystemInformationBundle\Service\MailService;
use Kmi\SystemInformationBundle\Service\SymfonyService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class SystemController
 */
class SystemController extends AbstractController
{
    /**
     * @var \Kmi\SystemInformationBundle\Service\CheckService
     */
    private CheckService $checkService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\LogService
     */
    private LogService $logService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\InformationService
     */
    private InformationService $informationService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\SymfonyService
     */
    private SymfonyService $symfonyService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\BundleService
     */
    private BundleService $bundleService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\DependencyService
     */
    private DependencyService $dependencyService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\MailService
     */
    private MailService $mailService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\DatabaseService
     */
    private DatabaseService $databaseService;

    /**
     * @var KernelInterface
     */
    private KernelInterface $kernel;

    /**
     * @param \Kmi\SystemInformationBundle\Service\CheckService $checkService
     * @param \Kmi\SystemInformationBundle\Service\LogService $logService
     * @param \Kmi\SystemInformationBundle\Service\InformationService $informationService
     * @param \Kmi\SystemInformationBundle\Service\SymfonyService $symfonyService
     * @param \Kmi\SystemInformationBundle\Service\BundleService $bundleService
     * @param \Kmi\SystemInformationBundle\Service\DependencyService $dependencyService
     * @param \Kmi\SystemInformationBundle\Service\MailService $mailService
     * @param \Kmi\SystemInformationBundle\Service\DatabaseService $databaseService
     * @param \Symfony\Component\HttpKernel\KernelInterface $kernel
     */
    public function __construct(CheckService $checkService, LogService $logService, InformationService $informationService, SymfonyService $symfonyService, BundleService $bundleService, DependencyService $dependencyService, MailService $mailService, DatabaseService $databaseService, KernelInterface $kernel)
    {
        $this->checkService = $checkService;
        $this->logService = $logService;
        $this->informationService = $informationService;
        $this->symfonyService = $symfonyService;
        $this->bundleService = $bundleService;
        $this->dependencyService = $dependencyService;
        $this->mailService = $mailService;
        $this->databaseService = $databaseService;
        $this->kernel = $kernel;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        $checks = $this->checkService->getLiipMonitorChecks()->getResults();
        $status = $this->checkService->getMonitorCheckStatus($checks);

        return $this->render('@SystemInformationBundle/index.html.twig', [
            'bundleInfo' => $this->dependencyService->getSystemInformationBundleInfo(),
            'teaser' => $this->informationService->getSystemInformation(true),
            'checks' => $checks,
            'status' => $status,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception|\Psr\Cache\InvalidArgumentException
     */
    public function log(): \Symfony\Component\HttpFoundation\Response
    {
        $logs = $this->logService->getLogs();

        return $this->render('@SystemInformationBundle/log.html.twig', [
            'bundleInfo' => $this->dependencyService->getSystemInformationBundleInfo(),
            'teaser' => $this->informationService->getSystemInformation(true),
            'logs' => $logs,
            'logDir' => $this->getParameter('kernel.logs_dir'),
        ]);
    }

    /**
     * @param string id
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function logView(string $id, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $limit = 100;
        if ($request->query->has('limit')) {
            $limit = (int)($request->query->get('limit'));
        }

        $page = 1;
        if ($request->query->has('page')) {
            $page = (int)($request->query->get('page'));
        }

        $level = null;
        if ($request->query->has('level')) {
            $level = $request->query->get('level');
        }

        $channel = null;
        if ($request->query->has('channel')) {
            $channel = $request->query->get('channel');
        }

        $search = null;
        if ($request->query->has('search')) {
            $search = $request->query->get('search');
        }

        $logs = $this->logService->getLog($id);
        $logs = $this->logService->filterLogEntryList($logs, $limit, $page, $level, $channel, $search);
        return $this->render('@SystemInformationBundle/logView.html.twig', [
            'logs' => $logs['result'],
            'levels' => LogService::LOG_LEVEL,
            'channels' => $this->logService->getLogChannels($logs),
            'id' => $id,
            'resultCount' => $logs['count'],
            'bundleInfo' => $this->dependencyService->getSystemInformationBundleInfo(),
            'teaser' => $this->informationService->getSystemInformation(true),
            'search' => $search,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function requirements(): \Symfony\Component\HttpFoundation\Response
    {
        $requirements = $this->symfonyService->checkRequirements(true);

        return $this->render('@SystemInformationBundle/requirements.html.twig', [
            'bundleInfo' => $this->dependencyService->getSystemInformationBundleInfo(),
            'teaser' => $this->informationService->getSystemInformation(true),
            'requirements' => $requirements,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function information(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('@SystemInformationBundle/information.html.twig', [
            'bundleInfo' => $this->dependencyService->getSystemInformationBundleInfo(),
            'teaser' => $this->informationService->getSystemInformation(true),
            'infos' => $this->informationService->getFurtherSystemInformation(),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function dependencies(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $search = '';
        $showOnlyUpdatable = false;
        $showOnlyRequired = false;
        $forceUpdate = false;
        if ($request->query->has('search')) {
            $search = (string)($request->query->get('search'));
        }
        $search = $search ?: '';

        if ($request->query->has('updatable')) {
            $showOnlyUpdatable = (bool)($request->query->get('updatable'));
        }

        if ($request->query->has('required')) {
            $showOnlyRequired = (bool)($request->query->get('required'));
        }

        if ($request->query->has('force')) {
            $forceUpdate = (bool)($request->query->get('force'));
        }

        $dependencyInformation = $this->dependencyService->getDependencyInformation($forceUpdate);
        $metadata = $dependencyInformation['metadata'];
        $dependencies = $this->dependencyService->filterDependencies($dependencyInformation['dependencies'], $search, $showOnlyUpdatable, $showOnlyRequired);

        return $this->render('@SystemInformationBundle/dependencies.html.twig', [
            'bundleInfo' => $this->dependencyService->getSystemInformationBundleInfo(),
            'teaser' => $this->informationService->getSystemInformation(true),
            'dependencies' => $dependencies,
            'status' => $this->dependencyService->getDependencyApplicationStatus($dependencies),
            'composerFilePath' => $this->getParameter('kernel.project_dir') . '/composer.json',
            'search' => $search,
            'showOnlyUpdatable' => $showOnlyUpdatable,
            'showOnlyRequired' => $showOnlyRequired,
            'metadata' => $metadata,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function mail(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $teaser = $this->informationService->getSystemInformation(true);
        if ($request->query->has('receiver')) {
            $receiver = (string)($request->query->get('receiver'));
            $mailResult = $this->mailService->sendStatusMail([$receiver], $teaser);

            if ($mailResult) {
                $this->addFlash('success', "Mail successfully sent to $receiver");
            }
        }

        return $this->render('@SystemInformationBundle/mail.html.twig', [
            'bundleInfo' => $this->dependencyService->getSystemInformationBundleInfo(),
            'teaser' => $teaser,
            'config' => $this->informationService->getMailConfiguration(),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function database(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $teaser = $this->informationService->getSystemInformation(true);
        $tables = $this->databaseService->getTables();
        return $this->render('@SystemInformationBundle/database.html.twig', [
            'bundleInfo' => $this->dependencyService->getSystemInformationBundleInfo(),
            'teaser' => $teaser,
            'config' => $this->databaseService->getConfig(),
            'tables' => $tables,
            'relevantTablesBySize' =>
                $this->databaseService->getRelevantTablesByProperty(
                    $tables,
                    'size',
                    $this->databaseService->getTotal()['size'],
                    DatabaseService::SIZE_THRESHOLD
                ),
            'relevantTablesByCount' =>
                $this->databaseService->getRelevantTablesByProperty(
                    $tables,
                    'count',
                    $this->databaseService->getTotal()['count'],
                    DatabaseService::COUNT_THRESHOLD
                ),
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function additional(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('@SystemInformationBundle/additional.html.twig', [
            'bundleInfo' => $this->dependencyService->getSystemInformationBundleInfo(),
            'teaser' => $this->informationService->getSystemInformation(true),
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function info(): \Symfony\Component\HttpFoundation\Response
    {
        $systemInformation = $this->informationService->getSystemInformation(true);

        return $this->render('@SystemInformationBundle/info.html.twig', [
            'information' => $systemInformation,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function phpInfo(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('@SystemInformationBundle/phpInfo.html.twig', [
            'info' => \phpinfo(),
        ]);
    }

    /**
     * @return JsonResponse|RedirectResponse
     * @throws \Exception
     */
    public function clearCache(Request $request)
    {
        $kernel = $this->kernel;
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'cache:clear',
        ]);

        // Use the NullOutput class instead of BufferedOutput.
        $output = new NullOutput();

        $result = $application->run($input, $output);

        $this->addFlash('success', 'Cache cleared');

        if ($request->query->has('redirect')) {
            return $this->redirectToRoute($request->query->get('redirect'));
        }

        return new JsonResponse($result);
    }

    /**
     * @param string $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeLog(string $id): RedirectResponse
    {
        $this->logService->removeLogFile($id);
        return $this->redirectToRoute('kmi_system_information_log');
    }
}
