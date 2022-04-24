<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Controller\Admin;

use Kmi\SystemInformationBundle\Service\CheckService;
use Kmi\SystemInformationBundle\Service\DependencyService;
use Kmi\SystemInformationBundle\Service\InformationService;
use Kmi\SystemInformationBundle\Service\LogService;
use Kmi\SystemInformationBundle\Service\SymfonyService;
use Kmi\SystemInformationBundle\Service\BundleService;
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
 * @package App\Controller
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
     * @param \Symfony\Component\HttpKernel\KernelInterface $kernel
     */
    public function __construct(CheckService $checkService, LogService $logService, InformationService $informationService, SymfonyService $symfonyService, BundleService $bundleService, DependencyService $dependencyService, KernelInterface $kernel)
    {
        $this->checkService = $checkService;
        $this->logService = $logService;
        $this->informationService = $informationService;
        $this->symfonyService = $symfonyService;
        $this->bundleService = $bundleService;
        $this->dependencyService = $dependencyService;
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
            'teaser' => $this->informationService->getSystemInformation(true),
            'logs' => $logs,
            'logDir' => $this->getParameter('kernel.logs_dir')
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
            $limit = intval($request->query->get('limit'));
        }

        $page = 1;
        if ($request->query->has('page')) {
            $page = intval($request->query->get('page'));
        }

        $level = null;
        if ($request->query->has('level')) {
            $level = $request->query->get('level');
        }

        $logs = $this->logService->getLog($id);
        $logs = $this->logService->filterLogEntryList($logs, $limit, $page, $level);
        return $this->render('@SystemInformationBundle/logView.html.twig', [
            'logs' => $logs['result'],
            'levels' => LogService::LOG_LEVEL,
            'id' => $id,
            'resultCount' => $logs['count'],
            'teaser' => $this->informationService->getSystemInformation(true)
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
            'teaser' => $this->informationService->getSystemInformation(true),
            'infos' => $this->informationService->getFurtherSystemInformation()
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
        if ($request->query->has('search')) {
            $search = strval($request->query->get('search'));
        }
        $search = $search ?: '';

        if ($request->query->has('updatable')) {
            $showOnlyUpdatable = boolval($request->query->get('updatable'));
        }
        $dependencies = $this->dependencyService->getDependencyInformation();
        $dependencies = $this->dependencyService->filterDependencies($dependencies, $search, $showOnlyUpdatable);

        return $this->render('@SystemInformationBundle/dependencies.html.twig', [
            'teaser' => $this->informationService->getSystemInformation(true),
            'dependencies' => $dependencies,
            'composerFilePath' => $this->getParameter('kernel.project_dir') . '/composer.json',
            'search' => $search,
            'showOnlyUpdatable' => $showOnlyUpdatable
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
            'teaser' => $this->informationService->getSystemInformation(true)
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
            'information' => $systemInformation
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function phpInfo(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('@SystemInformationBundle/phpInfo.html.twig', [
            'info' => phpinfo()
        ]);
    }

    /**
     * @return JsonResponse
     * @throws \Exception
     */
    public function clearCache(): JsonResponse
    {
        $kernel = $this->kernel;
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
            'command' => 'cache:clear'
        ));

        // Use the NullOutput class instead of BufferedOutput.
        $output = new NullOutput();

        return new JsonResponse($application->run($input, $output));
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
