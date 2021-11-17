<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use Evotodi\LogViewerBundle\Reader\LogReader;
use Evotodi\LogViewerBundle\Service\LogList;
use Kmi\SystemInformationBundle\SystemInformationBundle;
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
     * @var \Evotodi\LogViewerBundle\Service\LogList
     */
    public LogList $logList;

    /**
     * @var \Symfony\Contracts\Cache\CacheInterface
     */
    protected CacheInterface $cachePool;

    /**
     * @param \Evotodi\LogViewerBundle\Service\LogList $logList
     * @param \Symfony\Contracts\Cache\CacheInterface $cachePool
     */
    public function __construct(LogList $logList, CacheInterface $cachePool)
    {
        $this->logList = $logList;
        $this->cachePool = $cachePool;
    }


    /**
     * @return array
     * @throws \Exception
     */
    public function getLogList(): array
    {
        $logs = [];
        foreach ($this->logList->getLogList() as $log) {
            $logs[] = [
                'log' => $log,
                'warningCountByPeriod' => $this->countLogTypeByPeriod($this->getLogsById($log->getId()), self::LOG_TYPE['WARNING']),
                'errorCountByPeriod' => $this->countLogTypeByPeriod($this->getLogsById($log->getId())),
                'size' => $this->formatBytes($log->getSize()),
                'readable' => str_ends_with($log->getName(), '.gz') ? 0 : 1
            ];
        }
        return $logs;
    }

    /**
     * @param $id
     * @return array
     */
    public function getLogsById($id): array
    {
        $logs = $this->logList->getLogList();
        $log = $logs[$id];

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
        // Default ordering by date
        $logs = array_reverse($logs);
        return $logs;
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
    public function getErrorCount(bool $forceUpdate = false) {

        $cacheKey = SystemInformationBundle::CACHE_KEY . '-' . __FUNCTION__;
        if ($forceUpdate) {
            $this->cachePool->delete($cacheKey);
        }

        return $this->cachePool->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(SystemInformationBundle::CACHE_LIFETIME);

            $countWarningsAndErrorsInLogs = 0;
            $logList = $this->getLogList();
            foreach ($logList as $log) {
                $countWarningsAndErrorsInLogs += $log['warningCountByPeriod'] + $log['errorCountByPeriod'];
            }
            return $countWarningsAndErrorsInLogs;
        });
    }

    /**
     * @param $logs
     * @param string[] $type
     * @param string $period
     * @return int
     * @throws \Exception
     */
    private function countLogTypeByPeriod($logs, $type = self::LOG_TYPE['ERROR'], $period = '-1 day'): int
    {
        $count = 0;
        foreach ($logs as $log) {
            if ($log['dateTime'] < new \DateTime($period)) {
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
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}