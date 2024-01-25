<?php

declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Twig;

use Kmi\SystemInformationBundle\Service\CheckService;
use Kmi\SystemInformationBundle\Service\LogService;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class SystemController
 */
class SystemExtension extends AbstractExtension
{
    const CACHE_KEY = 'SystemInformationBundle_SystemExtension';
    const CACHE_LIFETIME = 300;

    /**
     * @var Container
     */
    private Container $container;

    /**
     * @var \Symfony\Contracts\Cache\CacheInterface
     */
    protected CacheInterface $cachePool;

    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @var \Kmi\SystemInformationBundle\Service\LogService
     */
    protected LogService $logService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\CheckService
     */
    protected CheckService $checkService;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param \Symfony\Contracts\Cache\CacheInterface $cachePool
     * @param \Kmi\SystemInformationBundle\Service\LogService $logService
     * @param \Kmi\SystemInformationBundle\Service\CheckService $checkService
     */
    public function __construct(ContainerInterface $container, CacheInterface $cachePool, LogService $logService, CheckService $checkService)
    {
        $this->container = $container;
        $this->cachePool = $cachePool;
        $this->logService = $logService;
        $this->checkService = $checkService;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('system_information', [$this, 'getSystemInformationStatus']),
        ];
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSystemInformationStatus()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $path = $request->getRequestUri();
        $url = $this->container->get('router')->generate('kmi_system_information_overview');
        $isActive = (strpos($path, $url) !== false);

        $status = $this->cachePool->get(self::CACHE_KEY, function (ItemInterface $item) {
            $item->expiresAfter(3600);

            $result = [];
            $result['logStatus'] = $this->logService->getErrorCount();
            $result['checkStatus'] = $this->checkService->getMonitorCheckStatus($this->checkService->getLiipMonitorChecks());
            return $result;
        });

        $checkStatus = $status['checkStatus'] ? '<i class="icon-sib-alert-triangle" style="margin-right: 5px;"></i>' : '';
        $logStatus = $status['logStatus'] ? '<i class="icon-sib-alert-circle" style="margin-right: 5px;"></i>' : '';

        return '<ul class="sidebar-menu" data-widget="tree"><li class="' . ($isActive ? 'active ': '') . 'first"><a href="' . $url . '"><i class="fa fa-cogs" aria-hidden="true"></i>System<div style="float:right; margin-right: 5px; opacity: .4;">' . $logStatus . $checkStatus . '</div></a></li>';
    }
}
