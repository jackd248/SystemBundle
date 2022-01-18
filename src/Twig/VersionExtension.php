<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Twig;

use Kmi\SystemInformationBundle\Service\InformationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class VersionExtension
 * @package Kmi\SystemInformationBundle\Twig
 */
class VersionExtension extends AbstractExtension
{
    /**
     * @var \Kmi\SystemInformationBundle\Service\InformationService
     */
    protected InformationService $informationService;

    /**
     * @param \Kmi\SystemInformationBundle\Service\InformationService $informationService
     */
    public function __construct(InformationService $informationService) {
        $this->informationService = $informationService;
    }

    /**
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('version', [$this, 'getComposerVersion']),
        ];
    }

    /**
     * @return mixed|null
     */
    public function getComposerVersion()
    {
        return $this->informationService->readAppVersion();
    }
}