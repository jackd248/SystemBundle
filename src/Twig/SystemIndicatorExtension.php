<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Twig;

use Kmi\SystemInformationBundle\Service\CheckService;
use Kmi\SystemInformationBundle\Service\InformationService;
use Kmi\SystemInformationBundle\Service\LogService;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class SystemController
 * @package App\Controller
 */
class SystemIndicatorExtension extends AbstractExtension
{
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
     * @var \Kmi\SystemInformationBundle\Service\InformationService
     */
    protected InformationService $informationService;

    /**
     * @param \Kmi\SystemInformationBundle\Service\LogService $logService
     * @param \Kmi\SystemInformationBundle\Service\CheckService $checkService
     * @param \Kmi\SystemInformationBundle\Service\InformationService $informationService
     */
    public function __construct(LogService $logService, CheckService $checkService, InformationService $informationService)
    {
        $this->logService = $logService;
        $this->checkService = $checkService;
        $this->informationService = $informationService;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('system_information_indicator', [$this, 'getSystemInformationIndicator'], ['needs_environment' => true]),
        ];
    }

    /**
     * @param \Twig\Environment $twig
     * @return string|null
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getSystemInformationIndicator(Environment $twig)
    {

        try {
            return $twig->render('@SystemInformationBundle/twig/systemIndicator.html.twig');
        } catch (LoaderError | RuntimeError $e) {
        } catch (SyntaxError $e) {
        }
        return null;
    }
}
