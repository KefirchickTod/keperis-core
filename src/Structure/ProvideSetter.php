<?php


namespace src\Structure;



use Error;
use PDO;

class ProvideSetter
{

    /*
     * @var PDO and DB
     * */
    private $database;

    private $parser;

    /**
     * @var Structure
     */
    private $structure;


    private $key;

    function __construct(Structure $structure, string $key)
    {
        $this->database = db();
        $this->structure = $structure;
        $this->key = $key;
        $this->parser = new ProvideQueryGenerate($structure->classes($key), $structure->get[$key],
            isset($structure->setting[$key]) ? $structure->setting[$key] : []);

    }


    /**
     * @param $arrays
     * @param bool $special
     * @param bool $creatAsJoin
     * @return array
     */
    public function getValue($array = false): array
    {
        return $array === true ? $this->parser->getQuery(true) : $this->filter($this->parser->getQuery());
    }

    /**
     * @param $query
     * @return array
     */
    private function filter($query): array
    {





        $preparedQuery = $query;

        if (get('debug')) {
            echo '<br/>' . $preparedQuery . '<br/>';

            // var_dump($this->database->querySql($query)->fetchAll(PDO::FETCH_NAMED));
        }
        try {
         //   var_dump(['query_strart' => microtime(true)]);
            $forClean = $this->database->querySql($preparedQuery)->fetchAll(PDO::FETCH_ASSOC);

//            if(isset($query['LIMIT']) && $query['limit']){
//                $query['LIMIT'] = '';
//                $preparedQuery = implode(' ', $query);
//                $this->database->querySql($preparedQuery)->fetchAll();
//                $size = $this->database->querySql("SELECT FOUND_ROWS() as size;")->fetchAll(PDO::FETCH_ASSOC)[0]['size'];
//                var_dump($size);
//            }
        //    var_dump(['query_end'=> microtime(true)]);
        } catch (Error  $error) {
            error_log($error->getMessage() . '\n key = ' . $this->key . '\n user_id = 1264');
            var_dump($error->getMessage());
            return [new \RuntimeException("Error with get data")];
        }


        return $forClean;
    }

    /**
     * @param $input
     * @return array
     */
    private function array_unique_multidimensional($input): array
    {
        $serialized = array_map('serialize', $input);
        $unique = array_unique($serialized);
        return array_intersect_key($input, $unique);
    }

}

