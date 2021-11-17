<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Controller\Admin;

use Kmi\SystemInformationBundle\Service\CheckService;
use Kmi\SystemInformationBundle\Service\InformationService;
use Kmi\SystemInformationBundle\Service\LogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

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
     * @param \Kmi\SystemInformationBundle\Service\CheckService $checkService
     * @param \Kmi\SystemInformationBundle\Service\LogService $logService
     * @param \Kmi\SystemInformationBundle\Service\InformationService $informationService
     */
    public function __construct(CheckService $checkService, LogService $logService, InformationService $informationService)
    {
        $this->checkService = $checkService;
        $this->logService = $logService;
        $this->informationService = $informationService;
    }

    /**
     * @Route("/admin/system")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\Cache\InvalidArgumentException
     */
    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        $checks = $this->checkService->getLiipMonitorChecks();
        $status = $this->checkService->getMonitorCheckStatus($checks);
        $logs = $this->logService->getLogList();

        return $this->render('@SystemInformationBundle/index.html.twig', [
            'checks' => $checks,
            'logs' => $logs,
            'logDir' => $this->getParameter('kernel.logs_dir'),
            'information' => $this->informationService->getSystemInformation(true),
            'infos' => $this->informationService->getFurtherSystemInformation(),
            'status' => $status
        ]);
    }

    /**
     * @Route("/admin/system/log/{id}", requirements={"id"="\d+"})
     *
     * @param int id
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logView($id, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $logs = $this->logService->logList->getLogList();
        $log = $logs[$id];
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

        $logs = $this->logService->getLogsById($id);
        $logs = $this->logService->filterLogEntryList($logs, $limit, $page, $level);
        return $this->render('@SystemInformationBundle/logView.html.twig', [
            'logs' => $logs['result'],
            'levels' => $log->getLevels(),
            'id' => $id,
            'resultCount' => $logs['count']
        ]);
    }

    /**
     * @Route("/admin/system/info")
     *
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
     * @Route("/admin/system/phpinfo")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function phpInfo(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('@SystemInformationBundle/phpInfo.html.twig', [
            'info' => phpinfo()
        ]);
    }
}
