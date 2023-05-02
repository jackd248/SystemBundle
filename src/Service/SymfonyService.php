<?php

declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use Kmi\SystemInformationBundle\SystemInformationBundle;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Requirements\ProjectRequirements;
use Symfony\Requirements\SymfonyRequirements;

class SymfonyService
{
    /**
     * @var Container
     */
    private ContainerInterface $container;

    /**
     * @var \Symfony\Contracts\Cache\CacheInterface
     */
    protected CacheInterface $cachePool;

    /**
     * @param \Psr\Container\ContainerInterface $container
     * @param \Symfony\Contracts\Cache\CacheInterface $cachePool
     */
    public function __construct(ContainerInterface $container, CacheInterface $cachePool)
    {
        $this->container = $container;
        $this->cachePool = $cachePool;
    }

    /**
     * @param bool $forceUpdate
     * @return array
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function checkRequirements(bool $forceUpdate = false)
    {
        $cacheKey = SystemInformationBundle::CACHE_KEY . '-' . __FUNCTION__;
        if ($forceUpdate) {
            $this->cachePool->delete($cacheKey);
        }

        return $this->cachePool->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(SystemInformationBundle::CACHE_LIFETIME);

            $symfonyRequirements = new SymfonyRequirements();
            $requirements = $symfonyRequirements->getRequirements();

            $projectRequirements = new ProjectRequirements($this->container->getParameter('kernel.project_dir'));
            $requirements = array_merge($requirements, $projectRequirements->getRequirements());

            $results = [
                'requirements' => [
                    'success' => [],
                    'error' => [],
                ],
                'recommendations' => [
                    'success' => [],
                    'error' => [],
                ],
            ];

            foreach ($requirements as $req) {
                $result = [];
                $result['help'] = $req->getHelpText();
                if ($req->isFulfilled()) {
                    $results['requirements']['success'][] = $result;
                } else {
                    $result['test'] = $req->getTestMessage();
                    $results['requirements']['error'][] = $result;
                }
            }

            foreach ($symfonyRequirements->getRecommendations() as $req) {
                $result = [];
                $result['help'] = $req->getHelpText();
                if ($req->isFulfilled()) {
                    $results['recommendations']['success'][] = $result;
                } else {
                    $result['test'] = $req->getTestMessage();
                    $results['recommendations']['warning'][] = $result;
                }
                $results['recommendations'][] = $result;
            }

            return $results;
        });
    }

    /**
     * @return array
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getRequirementsCount()
    {
        $requirements = $this->checkRequirements();
        return [
            'requirements' => count($requirements['requirements']['error']),
            'recommendations' => count($requirements['recommendations']['warning']),
        ];
    }
}
