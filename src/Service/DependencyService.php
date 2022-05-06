<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use Composer\Semver\Semver;
use Kmi\SystemInformationBundle\SystemInformationBundle;
use RuntimeException;
use Sonata\AdminBundle\SonataAdminBundle;
use Sonata\AdminBundle\SonataConfiguration;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 */
class DependencyService
{

    /**
     * State constants
     * @var int
     */
    const STATE_UP_TO_DATE = 1;
    const STATE_PINNED_OUT_OF_DATE = 2;
    const STATE_OUT_OF_DATE = 3;
    const STATE_INSECURE = 4; // @ToDo

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
     * @var \Kmi\SystemInformationBundle\Service\ConfigurationLoader
     */
    private ConfigurationLoader $configurationLoader;

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     * @param \Symfony\Contracts\Cache\CacheInterface $cachePool
     * @param \Kmi\SystemInformationBundle\Service\ConfigurationLoader $configurationLoader
     */
    public function __construct(Container $container, TranslatorInterface $translator, CacheInterface $cachePool, ConfigurationLoader $configurationLoader)
    {
        $this->container = $container;
        $this->translator = $translator;
        $this->cachePool = $cachePool;
        $this->configurationLoader = $configurationLoader;
    }

    /**
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDependencyInformation(bool $forceUpdate = false): array
    {
        $cacheKey = SystemInformationBundle::CACHE_KEY . '-' . __FUNCTION__;
        if ($forceUpdate) {
            $this->cachePool->delete($cacheKey);
        }

        return $this->cachePool->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(SystemInformationBundle::CACHE_LIFETIME_DEPENDENCIES);

            $composerLockContent = $this->getComposerFileContent($this->container->getParameter('kernel.project_dir') . '/composer.lock');
            $composerContent = $this->mergeComposerData($composerLockContent['packages'], $this->checkForUpdates());
            return $this->addAdvancedInformation($composerContent);
        });
    }


    /**
     * @param array $dependencies
     * @param string|null $search
     * @param bool $showOnlyUpdatable
     * @param bool $showOnlyRequired
     * @return array
     */
    public function filterDependencies(array $dependencies, string $search = null, bool $showOnlyUpdatable = false, bool $showOnlyRequired = false): array
    {
        if (is_null($search) & !$showOnlyUpdatable) return $dependencies;

        $filteredDependencies = [];
        foreach ($dependencies as $dependencyName => $dependency) {
            $addDependency = true;
            if (!is_null($search) && $search != '') {
                if (strpos($dependencyName, $search) === false) {
                    $addDependency &= false;
                }
            }

            if ($showOnlyUpdatable) {
                if ($dependency['latest-status'] === 'up-to-date') {
                    $addDependency &= false;
                }
            }

            if ($showOnlyRequired) {
                if (!array_key_exists('requiredVersion', $dependency)) {
                    $addDependency &= false;
                }
            }

            if ($addDependency) {
                $filteredDependencies[$dependencyName] = $dependency;
            }
        }
        return $filteredDependencies;
    }


    /**
     * @param array $dependencies
     * @return array
     */
    public function getDependencyApplicationStatus(array $dependencies): array
    {
        $result = [
            'status' => self::STATE_UP_TO_DATE,
            'distribution' => [
                self::STATE_UP_TO_DATE => [],
                self::STATE_PINNED_OUT_OF_DATE => [],
                self::STATE_OUT_OF_DATE => [],
                self::STATE_INSECURE => [],
                'required' => []
            ]
        ];

        foreach ($dependencies as $dependency) {
            $result['distribution'][$dependency['status']][] = $dependency;
            if (array_key_exists('requiredVersion', $dependency)) {
                $result['distribution']['required'][] = $dependency;
            }
        }

        if (count($result['distribution'][self::STATE_PINNED_OUT_OF_DATE]) > 0) {
            $result['status'] = self::STATE_PINNED_OUT_OF_DATE;
        }

        if (count($result['distribution'][self::STATE_OUT_OF_DATE]) > 0) {
            $result['status'] = self::STATE_OUT_OF_DATE;
        }

        if (count($result['distribution'][self::STATE_INSECURE]) > 0) {
            $result['status'] = self::STATE_INSECURE;
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function getSystemInformationBundleInfo() {
        return $this->getComposerFileContent(dirname(__FILE__) . '/../../composer.json');
    }

    /**
     * @param string $filePath
     * @return mixed
     */
    protected function getComposerFileContent(string $filePath)
    {
        if (!is_file($filePath)) {
            throw new RuntimeException("File not found at [$filePath]");
        }

        if (!($fileContent = file_get_contents($filePath))) {
            throw new RuntimeException("Unable to read file");
        }

        $json = \json_decode($fileContent, true);

        if (is_null($json)) {
            throw new RuntimeException("Invalid composer file format");
        }

        return $json;
    }

    /**
     * @param array $composerContent
     * @return array
     */
    protected function addAdvancedInformation(array $composerContent): array
    {
        $composerFileContent = $this->getComposerFileContent($this->container->getParameter('kernel.project_dir') . '/composer.json');
        $requiredPackages = $composerFileContent['require'];
        foreach ($composerContent as &$package) {
            if (in_array($package['name'], array_keys($requiredPackages))) {
                $package['requiredVersion'] = $requiredPackages[$package['name']];
            }

            $package['status'] = $this->compareVersions($package['version'], $package['latest'], array_key_exists('requiredVersion', $package) ?? $package['requiredVersion']);
        }
        return $composerContent;
    }

    /**
     * @param bool $forceUpdate
     * @return mixed
     */
    protected function checkForUpdates(bool $forceUpdate = false)
    {
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
    }

    /**
     * @param array $composerLock
     * @param array $composerUpdate
     * @return array
     */
    protected function mergeComposerData(array $composerLock, array $composerUpdate): array
    {
        foreach ($composerLock as $key => $item) {
            if (array_key_exists($item['name'], $composerUpdate)) {
                $composerLock[$item['name']] = array_merge($item, $composerUpdate[$item['name']]);
                $composerLock[$item['name']]['version'] = ltrim($composerLock[$item['name']]['version'], 'v');
                $composerLock[$item['name']]['latest'] = ltrim($composerLock[$item['name']]['latest'], 'v');
                unset($composerLock[$key]);
            }
        }
        return $composerLock;
    }

    /**
     * Compare versions to check if they are:
     * 1 - Up to date
     * 2 - Pinned, out of date
     * 3 - Out of date
     *
     * @param $stable
     * @param $latest
     * @param $required
     * @return int
     */
    protected function compareVersions($stable, $latest, $required = null): int
    {
        $state = self::STATE_UP_TO_DATE;

        if (explode('.', $stable)[0] != explode('.', $latest)[0] ||
            (isset(explode('.', $stable)[1]) && isset(explode('.', $latest)[1]) && explode('.', $stable)[1] != explode('.', $latest)[1])) {
            $state = self::STATE_OUT_OF_DATE;
        } else if (isset(explode('.', $stable)[2]) && isset(explode('.', $latest)[2]) && explode('.', $stable)[2] != explode('.', $latest)[2]) {
            $state = self::STATE_PINNED_OUT_OF_DATE;
        }

        if ($state != self::STATE_UP_TO_DATE && $required != null) {

//            try {
//                if (!Semver::satisfies($latest, $required)) {
//                    $state = self::STATE_UP_TO_DATE;
//                }
//            } catch (\UnexpectedValueException $e) {
//            } catch (\Symfony\Component\ErrorHandler\Error\ClassNotFoundError $e) {
//
//            }
        }

        return $state;
    }
}