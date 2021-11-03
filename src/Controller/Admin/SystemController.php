<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Controller\Admin;

use DateTime;
use Evotodi\LogViewerBundle\Reader\LogReader;
use Evotodi\LogViewerBundle\Service\LogList;
use Locale;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class SystemController
 * @package App\Controller
 */
class SystemController extends AbstractController
{
    private LogList $logList;

    public function __construct(LogList $logList)
    {
        $this->logList = $logList;
    }

    /**
     * @Route("/admin/system")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function index()
    {
        $checks = $this->getLiipMonitorChecks();
        $status = $this->getMonitorCheckStatus($checks);
        return $this->render('system/index.html.twig', [
            'checks' => $this->getLiipMonitorChecks(),
            'logs' => $this->logList->getLogList(),
            'information' => $this->getSystemInformation(),
            'infos' => $this->getFurtherSystemInformation(),
            'status' => $status
        ]);
    }

    /**
     * @Route("/admin/system/log/{id}", requirements={"id"="\d+"})
     *
     * @param int id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logView($id, Request $request)
    {

        $logs = $this->logList->getLogList();
        $log = $logs[$id];
        $context = [];

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

        if (!file_exists($log->getPath())) {
            throw new FileNotFoundException(sprintf("Log file \"%s\" was not found!", $log['path']));
        }

        $reader = new LogReader($log);

        if (!is_null($log->getPattern())) {
            $reader->getParser()->registerPattern('NewPattern', $log->getPattern());
            $reader->setPattern('NewPattern');
        }

        $logs = [];
        foreach ($reader as $line) {
            try {
                $logs[] = [
                    'dateTime' => $line['date'],
                    'channel' => $line['channel'],
                    'level' => $line['level'],
                    'message' => $line['message'],
                ];
            } catch (\Exception $e) {
                continue;
            }

        }
        $logs = $this->filterLogEntryList($logs, $limit, $page, $level);
        return $this->render('system/logView.html.twig', [
            'logs' => $logs,
            'levels' => $log->getLevels()
        ]);
    }

    /**
     * @Route("/admin/system/info")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function info()
    {
        return $this->render('system/info.html.twig', [
            'info' => phpinfo()
        ]);
    }


    /**
     * @return null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getLiipMonitorChecks()
    {
        $url = $this->generateUrl('liip_monitor_run_all_checks', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $client = new \GuzzleHttp\Client();
        $response = $client->get($url);
        if ($response->getStatusCode() === 200) {
            return \GuzzleHttp\json_decode($response->getBody()->getContents())->checks;
        }
        return null;
    }

    /**
     * @return array
     */
    private function getSystemInformation(): array
    {
        $information = [];
        $information[] = [
            'value' => \json_decode(file_get_contents($this->getParameter('kernel.root_dir') . '/../composer.json'),true)['version'],
            'description' => 'Application version'
        ];

        if ($_ENV['SYMFONY_ENVIRONMENT']) {
            $information[] = [
                'value' => $_ENV['SYMFONY_ENVIRONMENT'],
                'description' => 'Application environment'
            ];
        }

        if ($_ENV['APP_ENV']) {
            $information[] = [
                'value' => $_ENV['APP_ENV'],
                'description' => 'Symfony environment'
            ];
        }

        return array_merge($information,[
            [
                'value' => phpversion(),
                'description' => 'PHP version'
            ],
            [
                'value' => \Symfony\Component\HttpKernel\Kernel::VERSION,
                'description' => 'Symfony version'
            ],
            [
                'value' => PHP_OS,
                'description' => 'Operating system'
            ]
        ]);
    }

    /**
     * @return array
     */
    private function getFurtherSystemInformation(): array
    {
        return [
            'Server IP' => $_SERVER['SERVER_ADDR'],
            'Server name' => gethostname(),
            'Server protocol' => $_SERVER['SERVER_PROTOCOL'],
            'Server software' => $_SERVER['SERVER_SOFTWARE'],
            'OS' => php_uname(),
            'Interface' => php_sapi_name(),
            'Intl locale' => Locale::getDefault(),
            'Timezone' => date_default_timezone_get(),
            'Date' => (new DateTime())->format('Y-m-d H:i:s')
        ];
    }

    /**
     * @param $logs
     * @param int $limit
     * @param int $page
     * @param null $level
     * @return array
     */
    private function filterLogEntryList($logs, int $limit = 100, int $page = 1, $level = null): array
    {
        $offset = ($page - 1) * $limit;

        // Default ordering by date
        $logs = array_reverse($logs);

        // Filter by level
        if ($level) {
            $logs = array_filter($logs, function ($log) use ($level) {
                return $log['level'] == $level;
            });
        }
        // Slice array
        $logs = array_slice($logs, $offset, $limit);

        return $logs;
    }

    /**
     * @param $checks
     * @return int
     */
    private function getMonitorCheckStatus($checks) {
        $status = 0;
        foreach ($checks as $check) {
            if (intval($check->status) > $status) {
                $status = intval($check->status);
            }
        }
        return $status;
    }
}
