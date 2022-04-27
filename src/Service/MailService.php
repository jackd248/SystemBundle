<?php declare(strict_types=1);

namespace Kmi\SystemInformationBundle\Service;

use Composer\Semver\Semver;
use Kmi\SystemInformationBundle\SystemInformationBundle;
use RuntimeException;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @param \Symfony\Contracts\Cache\CacheInterface $cachePool
     */
    public function __construct(Container $container, CacheInterface $cachePool)
    {
        $this->container = $container;
        $this->cachePool = $cachePool;
    }

    /**
     * @param string $receiver
     * @return int
     */
    public function sendTestMail(string $receiver): int
    {
        // ToDo: Also use symfony mailer
        if (class_exists(\Swift_Mailer::class)) {
            $mailerConfiguration = self::getMailConfiguration();
            $transport = (new Swift_SmtpTransport($mailerConfiguration['host'], $mailerConfiguration['port']));
            $mailer = new Swift_Mailer($transport);

            $message = (new Swift_Message('Wonderful Subject'))
                ->setFrom(['test@mail.com' => 'SystemInformationBundle'])
                ->setTo([$receiver])
                ->setBody('Testmessage')
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