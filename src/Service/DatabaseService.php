<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use DateTime;
use Exception;
use Locale;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 */
class DatabaseService
{

    public const SIZE_THRESHOLD = 1.5;
    public const COUNT_THRESHOLD = 1.5;
    public const COLORS = [
        '#FF8A65',
        '#FF9800',
        '#FFC107',
        '#FFEB3B',
        '#CDDC39',
        '#8BC34A',
        '#4CAF50',
        '#009688',
        '#00BCD4',
        '#03A9F4',
        '#2196F3',
        '#3F51B5',
        '#673AB7',
        '#673AB7',
        '#9C27B0',
        '#E91E63',
        '#F44336'
    ];

    public const COLORS_SIZE = [
        '#004D40',
        '#006064',
        '#00695C',
        '#00838F',
        '#00796B',
        '#0097A7',
        '#00897B',
        '#00ACC1',
        '#009688',
        '#00BCD4',
        '#26A69A',
        '#4DB6AC',
        '#4DD0E1',
        '#80CBC4',
        '#80DEEA',
        '#B2DFDB',
        '#B2EBF2',
        '#E0F2F1',
        '#E0F7FA',
        '#004D40',
        '#006064',
        '#00695C',
        '#00838F',
        '#00796B',
        '#0097A7',
        '#00897B',
        '#00ACC1',
        '#009688',
        '#00BCD4',
        '#26A69A',
        '#4DB6AC',
        '#4DD0E1',
        '#80CBC4',
        '#80DEEA',
        '#B2DFDB',
        '#B2EBF2',
        '#E0F2F1',
        '#E0F7FA',
    ];
    public const COLOR_OTHERS = '#B0BEC5';

    /**
     * @var Container
     */
    private Container $container;

    /**
     * @var \Symfony\Contracts\Translation\TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var \Kmi\SystemInformationBundle\Service\CheckService
     */
    private CheckService $checkService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\LogService
     */
    private LogService $logService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\InformationService
     */
    private InformationService $informationService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\DependencyService
     */
    private DependencyService $dependencyService;

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     * @param \Kmi\SystemInformationBundle\Service\CheckService $checkService
     * @param \Kmi\SystemInformationBundle\Service\LogService $logService
     * @param \Kmi\SystemInformationBundle\Service\InformationService $informationService
     * @param \Kmi\SystemInformationBundle\Service\DependencyService $dependencyService
     * @param \Kmi\SystemInformationBundle\Service\MailService $mailService
     */
    public function __construct(Container $container, TranslatorInterface $translator, CheckService $checkService, LogService $logService, InformationService $informationService)
    {
        $this->container = $container;
        $this->translator = $translator;
        $this->informationService = $informationService;
    }

    /**
     * /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getConfig(): array
    {
        return [
            $this->informationService->getDatabasePlatform(),
            $this->informationService->getDatabaseVersion(),
            $this->informationService->getDatabaseHost(),
            $this->informationService->getDatabaseName(),
            $this->informationService->getDatabaseUser(),
            $this->informationService->getDatabasePort(),
            $this->informationService->getDatabaseCharacterSet(),
            $this->informationService->getDatabaseCollaction(),
            [
                'label' => $this->translator->trans('system.information.database.size', [], 'SystemInformationBundle'),
                'value' => $this->getTotal()['size']
            ],
            [
                'label' => $this->translator->trans('system.information.database.count', [], 'SystemInformationBundle'),
                'value' => $this->getTotal()['count']
            ]
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getTables(): array
    {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        $tables = null;
        try {
            $tables = $entityManager->getConnection()->fetchAllAssociative('
                SELECT 
                     table_name AS `table`, 
                     table_rows AS `count`,
                     round(((data_length + index_length) / 1024 / 1024), 2) `size`, 
                     table_collation AS `collation`
                FROM information_schema.TABLES 
                WHERE table_schema = "' . $entityManager->getConnection()->getDatabase() . '" 
                ORDER BY (data_length + index_length) DESC;
            ');
        } catch (Exception $e) {
        }
        return $tables;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getTotal(): array
    {
        $entityManager = $this->container->get('doctrine')->getManager();
        /* @var $entityManager \Doctrine\ORM\EntityManagerInterface */
        $total = null;
        try {
            $total = $entityManager->getConnection()->fetchAllAssociative('
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) `size`,
                    SUM(table_rows) `count` 
                FROM information_schema.tables 
                WHERE table_schema = "' . $entityManager->getConnection()->getDatabase() . '";
            ');
        } catch (Exception $e) {
        }
        return $total[0];
    }

    /**
     * @param $tables
     * @param $property
     * @param $max
     * @param $threshold
     * @return array
     */
    public function getRelevantTablesByProperty($tables, $property, $max, $threshold): array
    {
        $result = [];
        $others = 0.0;
        //usort($tables, fn($a, $b) => $a['size'] <=> $b['size']);

        $propertyThreshold = (float)$max / 100 * $threshold;
        foreach ($tables as $key => $table) {
            if ((float)$table[$property] > $propertyThreshold) {
                $result[] = [
                    'name' => $table['table'],
                    'value' => $table[$property],
                    'color' => self::COLORS_SIZE[$key],
                ];
            } else {
                $others += (float)$table[$property];
            }
        }

        if ($others) {
            $result[] = [
                'name' => 'Others',
                'value' => $others,
                'color' => self::COLOR_OTHERS,
            ];
        }
        return $result;
    }
}