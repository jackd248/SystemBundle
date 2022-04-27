<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use Swift_Image;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Contracts\Cache\CacheInterface;

/**
 *
 */
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
     * @var \Symfony\Component\HttpKernel\Config\FileLocator
     */
    private FileLocator $fileLocator;

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Symfony\Contracts\Cache\CacheInterface $cachePool
     * @param \Kmi\SystemInformationBundle\Service\InformationService $informationService
     */
    public function __construct(Container $container, CacheInterface $cachePool, InformationService $informationService, FileLocator $fileLocator)
    {
        $this->container = $container;
        $this->cachePool = $cachePool;
        $this->informationService = $informationService;
        $this->fileLocator = $fileLocator;
    }

    /**
     * @param string $receiver
     * @return int
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function sendTestMail(string $receiver): int
    {
        // ToDo: Also use symfony mailer
        if (class_exists(\Swift_Mailer::class)) {
            $mailerConfiguration = self::getMailConfiguration();
            $transport = (new Swift_SmtpTransport($mailerConfiguration['host'], $mailerConfiguration['port']));
            $mailer = new Swift_Mailer($transport);

            $projectName = 'DWI DB';
            $path = 'https://test.de';
            $sender = 'test@mail.com';

            $message = (new Swift_Message("[$projectName] SystemInformationBundle"))
                ->setFrom([$sender => 'SystemInformationBundle'])
                ->setTo([$receiver]);
            $message
                ->setBody(
                    $this->container->get('twig')->render(
                        '@SystemInformationBundle/mail/status.html.twig',
                        array(
                            'teaser' => $this->informationService->getSystemInformation(true),
                            'project' => $projectName,
                            'path' => $path,
                            'logo' => $message->embed(Swift_Image::fromPath($this->fileLocator->locate('@SystemInformationBundle/Resources/public/images/settings.svg')))
                        )
                    ),
                    'text/html'
                )
            ;
            return $mailer->send($message);
        }
        return 0;
    }

    /**
     * @return array|false|int|string|null
     */
    public function getMailConfiguration() {
        $configuration = null;
        // Swiftmailer
        if (array_key_exists('MAILER_URL', $_ENV) && class_exists(\Swift_Mailer::class)) {
            $configuration = parse_url($_ENV['MAILER_URL']);
            $configuration['service'] = 'SwiftMailer';
        }
        // Symfony Mailer
        if (array_key_exists('MAILER_DSN', $_ENV) && class_exists(\Symfony\Component\Mailer\MailerInterface::class)) {
            $configuration = parse_url($_ENV['MAILER_DSN']);
            $configuration['service'] = 'SymfonyMailer';
        }
        return $configuration;
    }

}