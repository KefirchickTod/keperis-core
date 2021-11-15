<?php


namespace Keperis\Eloquent\Provide\Template;


interface ProvideTemplateInterface
{
    /**
     * Get resolve name for quick copy obj
     * @return string
     */
     public function getResolveName(): string;

    /**
     * Get table name
     * @return string
     */
    public function getOriginTableName();

    /**
     * Get template if invalid format throw exception
     * @param string $key
     * @return mixed
     */
    public function getTemplate(string $key);

    /**
     * Get all templates from class
     * @param string ...$keys
     * @return array
     */
    public function getTemplates(...$keys);
}