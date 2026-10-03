<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Symfony\Component\Mime\Email;

class OrderStatusMail extends Mailable
{
    public function __construct(public array $details, public int $notificationId = 0) {}

    public function build(): self
    {
        $titles = [
            'placed' => 'Đã nhận đơn hàng', 'paid' => 'Đã nhận thanh toán',
            'dispatched' => 'Đơn hàng đang được giao', 'delivered' => 'Đơn hàng đã giao thành công',
        ];
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'soopi.local';

        return $this->subject('Soopi · '.($titles[$this->details['type']] ?? 'Cập nhật đơn hàng').' '.$this->details['order_number'])
            ->view('emails.orders.status')
            ->text('emails.orders.status-text')
            ->withSymfonyMessage(function (Email $message) use ($host) {
                // A stable ID also helps mail systems identify an ambiguous SMTP retry.
                $message->getHeaders()->addIdHeader('Message-ID', 'order-email-'.$this->notificationId.'@'.$host);
            });
    }
}
