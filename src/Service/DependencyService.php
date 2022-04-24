<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use Enlightn\SecurityChecker\AdvisoryAnalyzer;
use Enlightn\SecurityChecker\AdvisoryFetcher;
use Enlightn\SecurityChecker\AdvisoryParser;
use Enlightn\SecurityChecker\Composer;
use RuntimeException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 */
class DependencyService {

    /**
     * @var Container
     */
    private Container $container;

    /**
     * @var \Symfony\Contracts\Translation\TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     */
    public function __construct(Container $container, TranslatorInterface $translator)
    {
        $this->container = $container;
        $this->translator = $translator;
    }

    /**
     *
     */
    public function getDependencyInformation()
    {
        $composerFilePath = $this->container->getParameter('kernel.project_dir') . '/composer.lock';
        $composerLockFilePath = $this->container->getParameter('kernel.project_dir') . '/composer.lock';
        if (! is_file($composerLockFilePath)) {
            throw new RuntimeException("File not found at [$composerLockFilePath]");
        }

        if (! ($lockFileContent = file_get_contents($composerLockFilePath))) {
            throw new RuntimeException("Unable to read file");
        }

        $json = \json_decode($lockFileContent, true);

        if (is_null($json) || ! isset($json['packages'])) {
            throw new RuntimeException("Invalid composer file format");
        }
        return $json['packages'];
    }
}