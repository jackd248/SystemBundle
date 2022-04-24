<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use Kmi\SystemInformationBundle\SystemInformationBundle;
use RuntimeException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 */
class DependencyService
{

    /**
     * @var Container
     */
    private Container $container;

    /**
     * @var \Symfony\Contracts\Cache\CacheInterface
     */
    protected CacheInterface $cachePool;

    /**
     * @var \Symfony\Contracts\Translation\TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     */
    public function __construct(Container $container, TranslatorInterface $translator, CacheInterface $cachePool)
    {
        $this->container = $container;
        $this->translator = $translator;
        $this->cachePool = $cachePool;
    }

    /**
     *
     */
    public function getDependencyInformation()
    {
        $composerFilePath = $this->container->getParameter('kernel.project_dir') . '/composer.json';
        $composerLockFilePath = $this->container->getParameter('kernel.project_dir') . '/composer.lock';
        if (!is_file($composerLockFilePath)) {
            throw new RuntimeException("File not found at [$composerLockFilePath]");
        }

        if (!($lockFileContent = file_get_contents($composerLockFilePath))) {
            throw new RuntimeException("Unable to read file");
        }

        $json = \json_decode($lockFileContent, true);

        if (is_null($json) || !isset($json['packages'])) {
            throw new RuntimeException("Invalid composer file format");
        }

        return $this->mergeComposerData($json['packages'], $this->checkForUpdates());
    }

    /**
     * @param bool $forceUpdate
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function checkForUpdates(bool $forceUpdate = false)
    {
        $cacheKey = SystemInformationBundle::CACHE_KEY . '-' . __FUNCTION__;
        if ($forceUpdate) {
            $this->cachePool->delete($cacheKey);
        }

        return $this->cachePool->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(SystemInformationBundle::CACHE_LIFETIME);


            $result = null;
            $process = new Process(['composer', 'show', '--latest', '--minor-only', '--format', 'json', '-d', $this->container->getParameter('kernel.project_dir')]);

            try {
                $process->mustRun();

                $result = \json_decode($process->getOutput())->installed;
                $result = json_decode(json_encode($result), true);

                foreach ($result as $key => $value) {
                    $result[$value['name']] = $value;
                    unset($result[$key]);
                }
            } catch (ProcessFailedException $exception) {
                echo $exception->getMessage();
            }
            return $result;
        });
    }

    /**
     * @param array $composerLock
     * @param array $composerUpdate
     * @return array
     */
    protected function mergeComposerData(array $composerLock, array $composerUpdate): array
    {
        foreach ($composerLock as &$item) {
            if (array_key_exists($item['name'], $composerUpdate)) {
                $item = array_merge($item, $composerUpdate[$item['name']]);
            }
        }
        return $composerLock;
    }
}