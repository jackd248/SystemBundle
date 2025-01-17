<?php

declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Twig;

use Kmi\SystemInformationBundle\Service\InformationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class VersionExtension
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
    public function __construct(InformationService $informationService)
    {
        $this->informationService = $informationService;
    }

    /**
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('version', [$this, 'getComposerVersion']),
        ];
    }

    /**
     * @return string|null
     */
    public function getComposerVersion(): ?string
    {
        return $this->informationService->readAppVersion();
    }
}
