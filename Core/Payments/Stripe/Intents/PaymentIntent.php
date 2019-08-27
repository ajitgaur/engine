<?php
/**
 * Stripe Payment Intent
 */
namespace Minds\Core\Payments\Stripe\Intents;

use Minds\Traits\MagicAttributes;

class PaymentIntent extends Intent
{
    use MagicAttributes;

    /** @var int $amount */
    private $amount = 0;

    /** @var int $quantity */
    private $quantity = 1;

    /** @var string $currency */
    private $currency = 'usd';

    /** @var boolean $confirm */
    private $confirm = false;

    /** @var boolean $offSession */
    private $offSession = false;

    /** @var int $serviceFeePct */
    private $serviceFeePct = 0;

    /**
     * Return the service
     * @return int
     */
    public function getServiceFee(): int
    {
        return $this->amount * ($this->serviceFeePct / 100);
    }

    /**
     * Expose to the public apis
     * @param array $extend
     * @return array
     */
    public function export($extend = [])
    {
        return [
        ];
    }
}
