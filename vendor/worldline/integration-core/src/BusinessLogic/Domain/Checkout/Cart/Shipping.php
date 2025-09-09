<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Customer\ContactDetails;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\TaxableAmount;
/**
 * Class Shipping.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart
 * @internal
 */
class Shipping
{
    private TaxableAmount $cost;
    private ?Address $address;
    private ?ContactDetails $contactDetails;
    public function __construct(TaxableAmount $cost, ?Address $address = null, ?ContactDetails $contactDetails = null)
    {
        $this->cost = $cost;
        $this->address = $address;
        $this->contactDetails = $contactDetails;
    }
    public function getCost() : TaxableAmount
    {
        return $this->cost;
    }
    public function getAddress() : ?Address
    {
        return $this->address;
    }
    public function getContactDetails() : ?ContactDetails
    {
        return $this->contactDetails;
    }
    public function setAddress(Address $address) : void
    {
        $this->address = $address;
    }
    public function setContactDetails(ContactDetails $contactDetails) : void
    {
        $this->contactDetails = $contactDetails;
    }
}
