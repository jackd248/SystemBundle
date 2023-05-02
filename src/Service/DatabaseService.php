<?php

declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use Exception;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Contracts\Translation\TranslatorInterface;

class DatabaseService
{
    public const SIZE_THRESHOLD = 1.5;
    public const COUNT_THRESHOLD = 1.5;

    public const COLOR_START = '1A2B4F';
    public const COLOR_END = 'D8DFF0';

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
    public function getConfig()
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
                'value' => $this->getTotal()['size'],
            ],
            [
                'label' => $this->translator->trans('system.information.database.count', [], 'SystemInformationBundle'),
                'value' => $this->getTotal()['count'],
            ],
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getTables()
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
    public function getRelevantTablesByProperty($tables, $property, $max, $threshold)
    {
        $result = [];
        $others = 0.0;
        usort($tables, fn($a, $b) => $b[$property] <=> $a[$property]);

        $propertyThreshold = (float)$max / 100 * $threshold;
        foreach ($tables as $key => $table) {
            if ((float)$table[$property] > $propertyThreshold) {
                $result[] = [
                    'name' => $table['table'],
                    'value' => $table[$property],
                ];
            } else {
                $others += (float)$table[$property];
            }
        }

        if ($others) {
            $result[] = [
                'name' => 'Others',
                'value' => $others,
            ];
        }

        $colors = $this->generateGradients('0x' . $this::COLOR_START,'0x' . $this::COLOR_END, count($result));

        foreach ($result as $key => &$item) {
            $item['color'] = $colors[$key];
        }
        return $result;
    }

    /**
     * @param $pBegin
     * @param $pEnd
     * @param $pStep
     * @param $pMax
     * @return float
     */
    private function generateGradientsInterpolate($pBegin, $pEnd, $pStep, $pMax)
    {
        if ($pBegin < $pEnd) {
            return (($pEnd - $pBegin) * ($pStep / $pMax)) + $pBegin;
        }

        return (($pBegin - $pEnd) * (1 - ($pStep / $pMax))) + $pEnd;
    }

    /**
     * @param $theColorBegin
     * @param $theColorEnd
     * @param $theNumSteps
     * @return array
     */
    public function generateGradients($theColorBegin=0x000000,$theColorEnd=0xffffff,$theNumSteps=10)
    {
        //transform to hex, and get rid of # if exists
        $theColorBegin = hexdec(str_replace('#','',$theColorBegin));
        $theColorEnd = hexdec(str_replace('#','',$theColorEnd));

        //failsafe color codes
        $theColorBegin = (($theColorBegin >= 0x000000) && ($theColorBegin <= 0xffffff)) ? $theColorBegin : 0x000000;
        $theColorEnd = (($theColorEnd >= 0x000000) && ($theColorEnd <= 0xffffff)) ? $theColorEnd : 0xffffff;
        $theNumSteps = (($theNumSteps > 0) && ($theNumSteps < 256)) ? $theNumSteps : 16;

        $theR0 = ($theColorBegin & 0xff0000) >> 16;
        $theG0 = ($theColorBegin & 0x00ff00) >> 8;
        $theB0 = ($theColorBegin & 0x0000ff) >> 0;

        $theR1 = ($theColorEnd & 0xff0000) >> 16;
        $theG1 = ($theColorEnd & 0x00ff00) >> 8;
        $theB1 = ($theColorEnd & 0x0000ff) >> 0;

        $result = array();

        for ($i = 0; $i <= $theNumSteps; $i++) {
            $theR = $this->generateGradientsInterpolate($theR0, $theR1, $i, $theNumSteps);
            $theG = $this->generateGradientsInterpolate($theG0, $theG1, $i, $theNumSteps);
            $theB = $this->generateGradientsInterpolate($theB0, $theB1, $i, $theNumSteps);
            $theVal = ((($theR << 8) | $theG) << 8) | $theB;
            $result[] = sprintf("#%06X",$theVal);//strtoupper(str_pad(dechex($theVal),6,'0'))
        }
        return $result;
    }
}
