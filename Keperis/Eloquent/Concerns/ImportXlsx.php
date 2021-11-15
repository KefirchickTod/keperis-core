<?php


namespace Keperis\Eloquent\Concerns;


use Keperis\Xlsx\Import\Renderer\Render;

trait ImportXlsx
{
    public function convertImportType(string $key, $value)
    {

        if (!$this->schem) {
            throw new \RuntimeException("Cant convert import type without schem table undefined protected bc_table");
        }
        $key = str_replace('-', '_', $key);

        if (!$this->schem->isColumn($key)) {
            return $value;
        }
//        $callbackType = $this->schem->getColumnDataTypeCallback($key);
//        $value = call_user_func($callbackType, $value);
//        if (!$value) {
//            return '';
//        }
        $value = $this->converImpotTypeTel($key, $value);
        return $value;

    }

    protected function converImpotTypeTel(string $key, $value)
    {
        if (preg_match("~mobile~", $key) || preg_match("~phone~", $key)) {

            if ($value[0] !== '+' && strlen($value) > 10) {
                $value = "+" . $value;
            }
        }
        return $value;
    }

    public function mergeXlsxColumn($first, $second)
    {
        $mask = $this->getXlsxMargeColumn();
        $getvalue = function ($value, string $key) use ($mask) {
            if (empty($value)) {
                return $value;
            }

            if (array_key_exists($key, $mask) && (
                    $mask[$key]['type'] === 'multiple' || $mask[$key]['type'] === 'select')
            ) {
                $dictionaryValues = Render::searchValueInDictionary($value, $mask[$key]['values']);
                if ($mask[$key]['type'] === 'multiple' || $key === 'bc-user-companies-business-area' || $key === 'bc-user-companies-business-spher') {
                    return $dictionaryValues;
                }
                $value = $dictionaryValues[0] ?? $value;
            }


            return $value;
        };
        $prepare = function ($array) use ($mask, $getvalue) {
            $array_values = array_values($array);
            $array_keys = array_keys($array);
            $array_values = array_map($getvalue, $array_values, $array_keys);
            $result = array_map(function ($key) use ($mask) {
                if (!array_key_exists($key, $mask)) {
                    return $key;
                }
                return $mask[$key]['name'];
            }, $array_keys);
            $result = array_combine($result, $array_values);
            return $result;
        };


        [$first, $second] = [call_user_func($prepare, $first), call_user_func($prepare, $second)];

        return array_merge($second, $first);
    }

    /**
     * @return array
     * Get setting for create input feilds
     */
    public function getXlsxMargeColumn()
    {
        return $this->xlsxMargeColumn;
    }
}