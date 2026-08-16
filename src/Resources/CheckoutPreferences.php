<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

final class CheckoutPreferences extends Resource
{
    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function get(): array
    {
        return $this->transport->get('manage/general/checkout-preferences/');
    }
}
