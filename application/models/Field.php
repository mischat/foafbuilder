<?php

abstract class Field{
    protected $name;
    protected $type;//resource or literal.  TODO: Should be an enumerator really.
    protected $label;
    protected $data;//whether we keep the field even if empty (i.e. render an empty box);

    public function Field(){	
    }

    public function getName(){
        return $this->name;
    }
    public function setName($name){
        $this->name = $name;
    }

    public function getType(){
        return $this->type;
    }
    public function setType($type){
        $this->type = $type;
    }

    public function getLabel(){
        return $this->label;
    }
    public function setLabel($label){
        $this->label = $label;
    }

    public function setData($data){
        $this->data = $data;
    }
    public function getData(){
        return $this->data;
    }

    //Function to be overwritten	
    public function saveToModel(&$foafData, $value, $index){

    }

    //some sort of save thing ought to go here
}

/* vi:set expandtab sts=4 sw=4: */
