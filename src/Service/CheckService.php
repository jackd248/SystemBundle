<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use Kmi\SystemInformationBundle\SystemInformationBundle;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 *
 */
class CheckService {

    /**
     * @var Container
     */
    private Container $container;

    /**
     * @var \Symfony\Contracts\Cache\CacheInterface
     */
    protected CacheInterface $cachePool;

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Symfony\Contracts\Cache\CacheInterface $cachePool
     */
    public function __construct(Container $container, CacheInterface $cachePool)
    {
        $this->container = $container;
        $this->cachePool = $cachePool;
    }

    /**
     * @param false $forceUpdate
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getLiipMonitorChecks(bool $forceUpdate = false)
    {
        $cacheKey = SystemInformationBundle::CACHE_KEY . '-' . __FUNCTION__;
        if ($forceUpdate) {
            $this->cachePool->delete($cacheKey);
        }

        return $this->cachePool->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(SystemInformationBundle::CACHE_LIFETIME);

            $url = $this->container->get('router')->generate('liip_monitor_run_all_checks', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $client = new \GuzzleHttp\Client();
            $response = $client->get($url);
            if ($response->getStatusCode() === 200) {
                return \GuzzleHttp\json_decode($response->getBody()->getContents())->checks;
            }
            return null;
        });
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

    /**
     * @param $checks
     * @return int
     */
    public function getMonitorCheckCount($checks)
    {
        $count = 0;
        foreach ($checks as $check) {
            if (intval($check->status) > 0) {
                $count++;
            }
        }
        return $count;
    }
}