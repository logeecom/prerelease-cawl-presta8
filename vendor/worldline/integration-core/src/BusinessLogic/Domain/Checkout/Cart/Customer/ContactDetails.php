<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Customer;

/**
 * Class ContactDetails.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart
 * @internal
 */
class ContactDetails
{
    private string $email;
    private string $phone;
    public function __construct(string $email, string $phone = '')
    {
        $this->email = $email;
        $this->phone = $phone;
    }
    public function getEmail() : string
    {
        return $this->email;
    }
    public function getPhone() : string
    {
        return $this->phone;
    }
}
