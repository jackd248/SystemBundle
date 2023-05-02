<?php

declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use Liip\MonitorBundle\Helper\ArrayReporter;
use Liip\MonitorBundle\Helper\RunnerManager;

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
     * @param array $checks
     * @return int
     */
    public function getMonitorCheckStatus(array $checks = [])
    {
        $status = 0;
        foreach ($checks as $check) {
            if ((int)($check['status']) > $status) {
                $status = (int)($check['status']);
            }
        }
        return $status;
    }

    /**
     * @param array|null $checks
     * @return int
     */
    public function getMonitorCheckCount(array $checks = [])
    {
        $count = 0;
        foreach ($checks as $check) {
            if ((int)($check['status']) > 0) {
                $count++;
            }
        }
        return $count;
    }
}
