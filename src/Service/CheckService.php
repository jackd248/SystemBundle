<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use Liip\MonitorBundle\Helper\ArrayReporter;
use Liip\MonitorBundle\Helper\RunnerManager;

/**
 *
 */
class CheckService
{
    /**
     * @var \Liip\MonitorBundle\Helper\RunnerManager
     */
    protected RunnerManager $runnerManager;

    /**
     * @param \Liip\MonitorBundle\Helper\RunnerManager $runnerManager
     */
    public function __construct(RunnerManager $runnerManager)
    {
        $this->runnerManager = $runnerManager;
    }

    /**
     * @param false $forceUpdate
     * @return mixed
     */
    public function getLiipMonitorChecks(bool $forceUpdate = false)
    {
        $reporter = new ArrayReporter();
        $runner = $this->runnerManager->getRunner($this->runnerManager->getDefaultGroup());
        $runner->addReporter($reporter);
        $checks = $runner->getChecks();
        $runner->addChecks($checks);
        $runner->run();
        return $reporter;
    }

    /**
     * @param \Liip\MonitorBundle\Helper\ArrayReporter|null $checks
     * @return int
     */
    public function getMonitorCheckStatus(\Liip\MonitorBundle\Helper\ArrayReporter $checks = null): int
    {
        $status = 0;
        foreach ($checks->getResults() as $check) {
            if (intval($check['status']) > $status) {
                $status = intval($check['status']);
            }
        }
        return $status;
    }

    /**
     * @param \Liip\MonitorBundle\Helper\ArrayReporter|null $checks
     * @return int
     */
    public function getMonitorCheckCount(\Liip\MonitorBundle\Helper\ArrayReporter $checks = null): int
    {
        $count = 0;
        foreach ($checks->getResults() as $check) {
            if (intval($check['status']) > 0) {
                $count++;
            }
        }
        return $count;
    }
}