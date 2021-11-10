<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Twig;

use Kmi\SystemInformationBundle\Service\InformationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class EnvironmentExtension
 * @package Kmi\SystemInformationBundle\Twig
 */
class EnvironmentExtension extends AbstractExtension
{

    /**
     * Constants
     */
    const ENV_DEV = ['dev', 'development'];
    const ENV_STAGE = ['stage', 'staging'];
    const ENV_STANDBY = ['standby'];
    const ENV_PROD = ['prod', 'production'];

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
    public function getFunctions(): array
    {
        return [
            new TwigFunction('environment', [$this, 'getEnvironmentTemplate']),
        ];
    }

    /**
     * @return string
     */
    public function getEnvironmentTemplate(): string
    {
        $environment = $_ENV['SYMFONY_ENVIRONMENT'] ?? $_ENV['APP_ENV'];

        if (in_array(mb_strtolower($environment), self::ENV_PROD)) return '';

        $color = '#F39C12';
        if (in_array(mb_strtolower($environment), self::ENV_DEV)) {
            $color = '#F75C4C';
        } elseif (in_array(mb_strtolower($environment), self::ENV_STANDBY)) {
            $color = '#22A7F0';
        }

        return '
            <li class="app-environment app-environment-' . $environment . '" style=" line-height: 20px;position: relative;display: block;padding: 15px;color: white; background-color:' . $color . '" title="This is the environment indicator and will not be displayed in production context">
                ' . $environment . '
            </li>
            ';
    }
}