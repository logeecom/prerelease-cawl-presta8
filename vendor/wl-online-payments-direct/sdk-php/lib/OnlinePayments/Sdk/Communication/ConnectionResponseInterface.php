<?php

namespace CAWL\OnlinePayments\Sdk\Communication;

/**
 * Interface ConnectionResponseInterface
 *
 * @package OnlinePayments\Sdk\Communication
 * @internal
 */
interface ConnectionResponseInterface
{
    /**
     * @return int
     */
    public function getHttpStatusCode();
    /**
     * @return array
     */
    public function getHeaders();
    /**
     * @param string $name
     * @return mixed
     */
    public function getHeaderValue($name);
    /**
     * @return string
     */
    public function getBody();
}
