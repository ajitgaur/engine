<?php
/**
 * Stripe Customer
 */
namespace Minds\Core\Payments\Stripe\Customers;

use Minds\Traits\MagicAttributes;

class Customer
{
    use MagicAttributes;

    /** @var string $id */
    private $id;

    /** @var string $userGuid */
    private $userGuid;

    /** @var User $user */
    private $user;

    /** @var string $paymentSource */
    private $paymentSource;

    /**
     * Expose to the public apis
     * @param array $extend
     * @return array
     */
    public function export($extend = [])
    {
        return [
            'user_guid' => (string) $this->userGuid,
            'user' => $this->user ? $this->user->export() : null,
        ];
    }
}
