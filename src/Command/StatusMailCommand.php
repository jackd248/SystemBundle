<?php

declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Command;

use Kmi\SystemInformationBundle\Service\InformationService;
use Kmi\SystemInformationBundle\Service\MailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsCommand(
    name: 'system:information:status:mail',
    description: 'Send status mail',
    hidden: false
)]
class StatusMailCommand extends Command
{
    /**
     * @var \Kmi\SystemInformationBundle\Service\MailService
     */
    private MailService $mailService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\InformationService
     */
    private InformationService $informationService;

    /**
     * @var \Symfony\Contracts\Cache\CacheInterface
     */
    protected CacheInterface $cachePool;

    /**
     * @param \Kmi\SystemInformationBundle\Service\MailService $mailService
     * @param \Kmi\SystemInformationBundle\Service\InformationService $informationService
     * @param \Symfony\Contracts\Cache\CacheInterface $cachePool
     */
    public function __construct(MailService $mailService, InformationService $informationService, CacheInterface $cachePool)
    {
        $this->mailService = $mailService;
        $this->informationService = $informationService;
        $this->cachePool = $cachePool;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('receiver', InputArgument::REQUIRED, 'Status mail receiver addresses, e.g. test@mail.com,test2@mail.com')
            ->addOption('force', 'f', InputOption::VALUE_OPTIONAL, 'Forcing the mail send process', 0)
        ;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = 0;
        $output->writeln([
            '',
            'SystemInformationBundle',
            '============',
        ]);

        /**
         * ToDo: Save result in cache to avoid to many status mails being send
         */
        $receiver = explode(',', $input->getArgument('receiver'));
        $teaser = $this->informationService->getSystemInformation(true);

        $result = $this->mailService->sendStatusMail($receiver, $teaser);
        $output->writeln([
            'Mail sent to ' . $input->getArgument('receiver'),
        ]);

        /**
         * ToDo: Save result in cache to avoid to many status mails being send
         * WARNING   [cache] Failed to save key "SystemInformationBundle-execute" of type array. ["key" => "SystemInformationBundle-execute","exception" => null]
         */
//        $cacheKey = SystemInformationBundle::CACHE_KEY . '-' . __CLASS__ . '-' . __FUNCTION__;
//        $force = $input->getOption('force');
//        if ($force) {
//            $this->cachePool->delete($cacheKey);
//        }
//        $cachedTeaser = $this->cachePool->get($cacheKey, function (ItemInterface $item) {
//            $item->expiresAfter(SystemInformationBundle::CACHE_LIFETIME_STATUS_MAIL);
//            return $this->informationService->getSystemInformation(true);;
//        });
//
//        /*
//         * Only sent mail if the content differs or the sending is forced
//         */
//        if ($teaser !== $cachedTeaser || $force) {
//            $result = $this->mailService->sendStatusMail($receiver, $teaser);
//            $output->writeln([
//                'Mail sent to ' . $input->getArgument('receiver'),
//            ]);
//        } else {
//            $output->writeln([
//                'Skipped mail',
//            ]);
//        }

        return (int)(!$result);
    }
}
