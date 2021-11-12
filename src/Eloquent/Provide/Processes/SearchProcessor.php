<?php


namespace src\Eloquent\Provide\Processes;


class SearchProcessor extends ProvideStructureProcessor implements ProcessorInterface
{

    private $search;

    public function __construct(string $search){
        $this->search = $search;
    }


    public function search(){
        return "WHERE SEARCH IS $this->search";
    }
}