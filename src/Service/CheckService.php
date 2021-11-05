<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use DateTime;
use Evotodi\LogViewerBundle\Reader\LogReader;
use Evotodi\LogViewerBundle\Service\LogList;
use Locale;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 *
 */
class CheckService {

    /**
     * @var Container
     */
    private $container;

    /**
     * Constructor
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLiipMonitorChecks()
    {
        $url = $this->container->get('router')->generate('liip_monitor_run_all_checks', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $client = new \GuzzleHttp\Client();
        $response = $client->get($url);
        if ($response->getStatusCode() === 200) {
            return \GuzzleHttp\json_decode($response->getBody()->getContents())->checks;
        }
        return null;
    }

    /**
     * @param $checks
     * @return int
     */
    public function getMonitorCheckStatus($checks)
    {
        $status = 0;
        foreach ($checks as $check) {
            if (intval($check->status) > $status) {
                $status = intval($check->status);
            }
        }
        return $status;
    }
}