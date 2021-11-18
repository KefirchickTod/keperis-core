<?php

namespace Keperis\Eloquent\Provide\Commands;

use Keperis\Eloquent\Provide\StructureCollection;

interface ProvideReceiverInterface
{

    /**
     * Check if call changer methods
     * @return bool
     */
    public function isChange() : bool;

    /** Get structure collection
     * @return StructureCollection
     */
    public function getStructure(): StructureCollection;

    /**
     * Change setting
     * @param callable $callback
     * @param string $key
     * @return ProvideReceiverInterface
     */
    public function change(string $key, callable $callback): ProvideReceiverInterface;

    /**
     * Call function change
     * @param callable $callback
     * @return ProvideReceiverInterface
     */
    public function changeGet(callable $callback): ProvideReceiverInterface;

    /**
     * Change where from setting
     * @param string $key
     * @param callable $callable
     * @return ProvideReceiverInterface
     */
    public function changeWhere(callable $callable): ProvideReceiverInterface;

    /**
     * Change array [setting] with all settings
     * @param callable $callable
     * @return ProvideReceiverInterface
     */
    public function changeSetting(callable $callable): ProvideReceiverInterface;

    /**
     * Change statement in setting array
     * @param string $key
     * @param callable $callable
     * @return ProvideReceiverInterface
     */
    public function changeInSetting(string $key, callable $callable): ProvideReceiverInterface;

    /**
     * Check if exists key in settings structure
     * @param string $key
     * @return bool
     */
    public function hasKeyInSetting(string $key): bool;

    /**
     * Return structure
     * @return array
     */
    public function get() : array;


}
