<?php

declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use Sonata\AdminBundle\SonataConfiguration;
use Swift_Image;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Cache\CacheInterface;

class MailService
{
    /**
     * @var Container
     */
    private Container $container;

    /**
     * @var \Symfony\Contracts\Cache\CacheInterface
     */
    protected CacheInterface $cachePool;

    /**
     * @var \Kmi\SystemInformationBundle\Service\InformationService
     */
    private InformationService $informationService;

    /**
     * @var \Kmi\SystemInformationBundle\Service\DependencyService
     */
    private DependencyService $dependencyService;

    /**
     * @var \Symfony\Component\HttpKernel\Config\FileLocator
     */
    private FileLocator $fileLocator;

    /**
     * @var \Sonata\AdminBundle\SonataConfiguration
     */
    private SonataConfiguration $sonataConfiguration;

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Symfony\Contracts\Cache\CacheInterface $cachePool
     * @param \Kmi\SystemInformationBundle\Service\InformationService $informationService
     * @param \Kmi\SystemInformationBundle\Service\DependencyService $dependencyService
     * @param \Symfony\Component\HttpKernel\Config\FileLocator $fileLocator
     * @param \Sonata\AdminBundle\SonataConfiguration $sonataConfiguration
     */
    public function __construct(Container $container, CacheInterface $cachePool, InformationService $informationService, DependencyService $dependencyService, FileLocator $fileLocator, SonataConfiguration $sonataConfiguration)
    {
        $this->container = $container;
        $this->cachePool = $cachePool;
        $this->informationService = $informationService;
        $this->dependencyService = $dependencyService;
        $this->fileLocator = $fileLocator;
        $this->sonataConfiguration = $sonataConfiguration;
    }

    /**
     * @param array $receiver
     * @param array|null $teaser
     * @return int
     * @throws \Exception
     */
    public function sendStatusMail(array $receiver, array $teaser = null): int
    {
        $projectName = $this->sonataConfiguration->getTitle();
        $projectLogo = $this->sonataConfiguration->getLogo();
        $mailerConfiguration = $this->informationService->getMailConfiguration();

        if (class_exists(\Swift_Mailer::class)) {
            $transport = (new Swift_SmtpTransport($mailerConfiguration['host'], $mailerConfiguration['port']));
            $mailer = new Swift_Mailer($transport);

            $sender = $this->getSenderMail();

            $message = (new Swift_Message("[$projectName] SystemInformationBundle Status Update"))
                ->setFrom([$sender => 'SystemInformationBundle'])
                ->setTo($receiver);
            $message
                ->setBody(
                    $this->container->get('twig')->render(
                        '@SystemInformationBundle/mail/status.html.twig',
                        [
                            'teaser' => $teaser,
                            'project' => $projectName,
                            'projectLogo' => $projectLogo,
                            'bundleInfo' => $this->dependencyService->getSystemInformationBundleInfo(),
                            'logo' => $message->embed(Swift_Image::fromPath($this->fileLocator->locate('@SystemInformationBundle/Resources/public/images/settings.svg'))),
                        ]
                    ),
                    'text/html'
                );
            return $mailer->send($message);
        }
        if (interface_exists(\Symfony\Component\Mailer\MailerInterface::class)) {
            $mailer = new Mailer(Transport::fromDsn($_ENV['MAILER_DSN']));

            $email = (new Email())
                ->from($this->getSenderMail())
                ->subject("[$projectName] SystemInformationBundle Status Update")
                ->html(
                    $this->container->get('twig')->render(
                        '@SystemInformationBundle/mail/status.html.twig',
                        [
                            'teaser' => $teaser,
                            'project' => $projectName,
                            'projectLogo' => $projectLogo,
                            'bundleInfo' => $this->dependencyService->getSystemInformationBundleInfo(),
                            'logo' => '',
                        ]
                    ),
                )
                ->embedFromPath($this->fileLocator->locate('@SystemInformationBundle/Resources/public/images/settings.svg'), 'logo')
            ;

            foreach ($receiver as $to) {
                $email->addTo($to);
            }

            $mailer->send($email);
            return 1;
        }
        return 0;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function getSenderMail(): mixed
    {
        if (!array_key_exists('SYSTEM_INFORMATION_BUNDLE_SENDER_MAIL', $_ENV)) {
            throw new \Exception('Missing environment variable "SYSTEM_INFORMATION_BUNDLE_SENDER_MAIL" for system_information_bundle sender mail address');
        }
        return $_ENV['SYSTEM_INFORMATION_BUNDLE_SENDER_MAIL'];
    }
}
