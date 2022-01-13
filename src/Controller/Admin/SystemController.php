<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Controller\Admin;

use Kmi\SystemInformationBundle\Service\CheckService;
use Kmi\SystemInformationBundle\Service\InformationService;
use Kmi\SystemInformationBundle\Service\LogService;
use Kmi\SystemInformationBundle\Service\SymfonyService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
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
     * @var KernelInterface
     */
    private KernelInterface $kernel;

    /**
     * @param \Kmi\SystemInformationBundle\Service\CheckService $checkService
     * @param \Kmi\SystemInformationBundle\Service\LogService $logService
     * @param \Kmi\SystemInformationBundle\Service\InformationService $informationService
     * @param \Kmi\SystemInformationBundle\Service\SymfonyService $symfonyService
     * @param \Symfony\Component\HttpKernel\KernelInterface $kernel
     */
    public function __construct(CheckService $checkService, LogService $logService, InformationService $informationService, SymfonyService $symfonyService, KernelInterface $kernel)
    {
        $this->checkService = $checkService;
        $this->logService = $logService;
        $this->informationService = $informationService;
        $this->symfonyService = $symfonyService;
        $this->kernel = $kernel;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Cache\InvalidArgumentException
     */
    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        $requirements = $this->symfonyService->checkRequirements(true);
        $checks = $this->checkService->getLiipMonitorChecks();
        $status = $this->checkService->getMonitorCheckStatus($checks);
        $logs = $this->logService->getLogs();

        return $this->render('@SystemInformationBundle/index.html.twig', [
            'checks' => $checks,
            'logs' => $logs,
            'logDir' => $this->getParameter('kernel.logs_dir'),
            'information' => $this->informationService->getSystemInformation(true),
            'infos' => $this->informationService->getFurtherSystemInformation(),
            'requirements' => $requirements,
            'status' => $status
        ]);
    }

    /**
     * @param string id
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
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
            'resultCount' => $logs['count']
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function info(): \Symfony\Component\HttpFoundation\Response
    {
        $systemInformation = $this->informationService->getSystemInformation();

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
}
