<?php

namespace CAWL\OnlinePayments\Core\Infrastructure\Serializer;

use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
/**
 * Class Serializer
 *
 * @package OnlinePayments\Core\Infrastructure\Serializer
 * @internal
 */
abstract class Serializer
{
    /**
     * string CLASS_NAME Class name identifier.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Serializes data.
     *
     * @param mixed $data Data to be serialized.
     *
     * @return mixed String representation of the serialized data.
     */
    public static function serialize($data) : string
    {
        /** @var Serializer $instace */
        $instance = ServiceRegister::getService(self::CLASS_NAME);
        return $instance->doSerialize($data);
    }
    /**
     * Unserializes data.
     *
     * @param string $serialized Serialized data.
     *
     * @return mixed Unserialized data.
     */
    public static function unserialize(string $serialized)
    {
        /** @var Serializer $instace */
        $instance = ServiceRegister::getService(self::CLASS_NAME);
        return $instance->doUnserialize($serialized);
    }
    /**
     * Serializes data.
     *
     * @param mixed $data Data to be serialized.
     *
     * @return string String representation of the serialized data.
     */
    protected abstract function doSerialize($data) : string;
    /**
     * Unserializes data.
     *
     * @param string $serialized Serialized data.
     *
     * @return mixed Unserialized data.
     */
    protected abstract function doUnserialize(string $serialized);
}
