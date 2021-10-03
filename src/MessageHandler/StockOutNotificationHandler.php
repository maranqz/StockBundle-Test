<?php

namespace maranqz\StockBundle\MessageHandler;

use maranqz\StockBundle\Message\StockOutNotification;
use maranqz\StockBundle\Repository\StockRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class StockOutNotificationHandler
{
    /**
     * @var StockRepository
     */
    private $repository;
    /**
     * @var MailerInterface
     */
    private $mailer;
    /**
     * @var string
     */
    private $notificationSender;
    /**
     * @var string
     */
    private $notificationReceiver;

    public function __construct(StockRepository $repository, MailerInterface $mailer, string $notificationSender, string $notificationReceiver)
    {
        $this->repository = $repository;
        $this->mailer = $mailer;
        $this->notificationSender = $notificationSender;
        $this->notificationReceiver = $notificationReceiver;
    }

    public function __invoke(StockOutNotification $notification)
    {
        $stock = $this->repository->findByKey($notification->getSKU(), $notification->getBranch());

        if (is_null($stock) || $stock->getCount() > 0) {
            return;
        }

        $email = (new Email())
            ->from($this->notificationSender)
            ->to($this->notificationReceiver)
            ->subject(sprintf('Stock out in %s for %s', $stock->getBranch(), $stock->getSku()))
            ->text(sprintf('%s stocks need to be replenished in %s', $stock->getSku(), $stock->getBranch()));

        $this->mailer->send($email);
    }
}
