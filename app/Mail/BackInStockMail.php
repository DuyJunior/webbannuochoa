<?php

namespace App\Mail;

use App\Models\Perfume;
use App\Models\Product;
use Illuminate\Mail\Mailable;

class BackInStockMail extends Mailable
{
    public function __construct(public Perfume|Product $perfume, public array $availableVolumes = []) {}

    public function build(): self
    {
        return $this->subject('Mùi hương bạn chờ đã có hàng')
            ->view('emails.back-in-stock');
    }
}
