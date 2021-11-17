<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use DateTime;
use Kmi\SystemInformationBundle\SystemInformationBundle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 *
 */
class LogService
{
    const LOG_TYPE = [
        'WARNING' => [
            'WARNING'
        ],
        'ERROR' => [
            'ERROR',
            'ALERT',
            'CRITICAL',
            'EMERGENCY'
        ]
    ];

    const LOG_LEVEL = [
        'DEBUG',
        'INFO',
        'NOTICE',
        'WARNING',
        'ERROR',
        'ALERT',
        'CRITICAL',
        'EMERGENCY'
    ];

    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var \Symfony\Contracts\Cache\CacheInterface
     */
    protected CacheInterface $cachePool;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param \Symfony\Contracts\Cache\CacheInterface $cachePool
     */
    public function __construct(ContainerInterface $container, CacheInterface $cachePool)
    {
        $this->container = $container;
        $this->cachePool = $cachePool;
    }


    /**
     * @return array
     * @throws \Exception
     */
    public function getLogs(): array
    {
        $files = [];
        $logDir = $this->container->getParameter('kernel.logs_dir');
        $fileList = array_diff(scandir($logDir), ['..', '.', '.DS_Store']);
        foreach ($fileList as $fileEntry) {
            $file['fileName'] = $fileEntry;
            $file['absolutePath'] = $logDir . '/' . $fileEntry;
            $file['fileSize'] = $this->formatBytes(filesize($file['absolutePath']));
            $file['changeDate'] = (new DateTime())->setTimestamp(filemtime($file['absolutePath']));
            $file['readable'] = str_ends_with($fileEntry, '.gz') ? 0 : 1;
            $file['warningCountByPeriod'] = $file['changeDate'] > new \DateTime('-1 day') ? $this->countLogTypeByPeriod($this->getLog($fileEntry), self::LOG_TYPE['WARNING']) : 0;
            $file['errorCountByPeriod'] = $file['changeDate'] > new \DateTime('-1 day') ? $this->countLogTypeByPeriod($this->getLog($fileEntry)) : 0;
            $files[$fileEntry] = $file;
        }

        usort($files, function($a, $b) {
            return $a['changeDate'] < $b['changeDate'];
        });
        return $files;
    }

    /**
     * @param $id
     * @return array
     */
    public function getLog($id): array
    {
        $logDir = $this->container->getParameter('kernel.logs_dir');
        $absolutePath = $logDir . '/' . $id;

        if (!file_exists($absolutePath)) {
            throw new FileNotFoundException();
        }

        $fn = fopen($absolutePath, "r");
        $lines = [];

        while (!feof($fn)) {
            $result = fgets($fn);

            if (!$result) {
                continue;
            }

            preg_match('/\[(?P<date>.*)\] (?P<channel>\w+).(?P<level>\w+): (?P<message>[^\[\{].*[\]\}])/', $result, $data);

            if (!isset($data['date'])) {
                continue;
            }
            $array = array(
                'date'    => DateTime::createFromFormat('Y-m-d H:i:s', $data['date']),
                'channel'  => $data['channel'],
                'level'   => $data['level'],
                'message' => $data['message']
            );

            $lines[] = $array;
        }
        fclose($fn);
        // Default ordering by date
        return array_reverse($lines);
    }

    /**
     * @param $logs
     * @param int $limit
     * @param int $page
     * @param null $level
     * @return array
     */
    public function filterLogEntryList($logs, int $limit = 100, int $page = 1, $level = null): array
    {
        $offset = ($page - 1) * $limit;

        // Filter upwards by level
        if ($level) {
            $logs = array_filter($logs, function ($log) use ($level) {
                return array_search($log['level'], self::LOG_LEVEL) >= array_search($level, self::LOG_LEVEL);
            });
        }
        $resultCount = count($logs);
        // Slice array
        $logs = array_slice($logs, $offset, $limit);

        return [
            'result' => $logs,
            'count' => $resultCount
        ];
    }

    /**
     * @param bool $forceUpdate
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getErrorCount(bool $forceUpdate = false)
    {

        $cacheKey = SystemInformationBundle::CACHE_KEY . '-' . __FUNCTION__;
        if ($forceUpdate) {
            $this->cachePool->delete($cacheKey);
        }

        return $this->cachePool->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(SystemInformationBundle::CACHE_LIFETIME);

            $countWarningsAndErrorsInLogs = 0;
            $logList = $this->getLogs();
            foreach ($logList as $log) {
                $countWarningsAndErrorsInLogs += $log['warningCountByPeriod'] + $log['errorCountByPeriod'];
            }
            return $countWarningsAndErrorsInLogs;
        });
    }

    /**
     * @param $logs
     * @param array|string[] $type
     * @param string $period
     * @return int
     * @throws \Exception
     */
    private function countLogTypeByPeriod($logs, array $type = self::LOG_TYPE['ERROR'], string $period = '-1 day'): int
    {
        $count = 0;
        foreach ($logs as $log) {
            if ($log['date'] < new \DateTime($period)) {
                break;
            }
            if (in_array($log['level'], $type)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * https://stackoverflow.com/a/2510459
     *
     * @param $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}