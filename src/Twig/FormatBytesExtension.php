<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class FormatBytesExtension
 * @package Kmi\SystemInformationBundle\Twig
 */
class FormatBytesExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('format_bytes', [$this, 'formatBytes']),
        ];
    }

    /**
     * https://stackoverflow.com/a/2510459
     *
     * @param $bytes
     * @param int $precision
     * @return string
     */
    public function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        // $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}