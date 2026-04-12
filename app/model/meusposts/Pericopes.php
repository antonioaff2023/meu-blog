<?php


use Adianti\Database\TRecord;

class Pericopes extends TRecord
{
    const TABLENAME  = 'titulo_xxi';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max'; // {max, serial}

        public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('titulo');
        parent::addAttribute('referencia');
    }


}

    