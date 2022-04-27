<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Command;

use Kmi\SystemInformationBundle\Service\MailService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusMailCommand extends Command
{
    protected static $defaultName = 'system:information:status:mail';

    /**
     * @var \Kmi\SystemInformationBundle\Service\MailService
     */
    private MailService $mailService;

    /**
     * @param \Kmi\SystemInformationBundle\Service\MailService $mailService
     */
    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('receiver', InputArgument::OPTIONAL, 'Status mail receiver address')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            '',
            'SystemInformationBundle',
            '============',
            'Sending a status mail',
        ]);

        $result = $this->mailService->sendStatusMail($input->getArgument('receiver'));
        return intval(!$result);
    }
}