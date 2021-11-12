<?php

namespace Keperis\Eloquent\Provide\Commands;

use Keperis\Eloquent\Provide\StructureCollection;

class ProvideReceiver implements ProvideReceiverInterface
{

    /**
     * @var StructureCollection
     */
    private $structure;

    public function __construct($structure)
    {
        if (!$structure instanceof StructureCollection) {
            $structure = new StructureCollection('receiver', $structure);
        }
        $this->structure = $structure;
    }


    /**
     * @return StructureCollection
     */
    public final function getStructure(): StructureCollection
    {
        return $this->structure;
    }

    /**
     * Change setting
     * @param callable $callback
     * @param string $key
     * @return ProvideReceiverInterface
     */
    public function change(string $key, callable $callback): ProvideReceiverInterface
    {

        $data = call_user_func($callback, $this->structure->get($key, []));

        $this->structure->set($key, $data);

        return $this;

    }

    /**
     * Call function change
     * @param callable $callback
     * @return ProvideReceiverInterface
     */
    public function changeGet(callable $callback): ProvideReceiverInterface
    {
        return $this->change('get', $callback);
    }

    /**
     * Change where from setting
     * @param string $key
     * @param callable $callable
     * @return ProvideReceiverInterface
     */
    public function changeWhere(callable $callable): ProvideReceiverInterface
    {
        return $this->changeInSetting('where', $callable);
    }

    /**
     * Change array [setting] with all settings
     * @param callable $callable
     * @return ProvideReceiverInterface
     */
    public function changeSetting(callable $callable): ProvideReceiverInterface
    {
        return $this->change('setting', $callable);
    }

    /**
     * Change statement in setting array
     * @param string $key
     * @param callable $callable
     * @return ProvideReceiverInterface
     */
    public function changeInSetting(string $key, callable $callable): ProvideReceiverInterface
    {
        return $this->changeSetting(function ($setting) use ($key, $callable) {
            $data = $setting[$key] ?? [];

            $setting[$key] = call_user_func($callable, $data);
            return $setting;

        });

    }


    /**
     * Check if exists key in settings structure
     * @param string $key
     * @return bool
     */
    public function hasKeyInSetting(string $key): bool
    {
        return $this->structure->hasSetting($key);
    }

    /**
     * Return structure
     * @return array
     */
    public function get(): array
    {
        return (array)$this->structure->toArray();
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getInSetting(string $key)
    {
        return $this->structure->get('setting')[$key];
    }
}
