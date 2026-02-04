<?php
//Cria model para a tabela tbl_categoria_livros
//Criar um arquivo CategoriaLivros.php na pasta app/model/meusposts


use Adianti\Database\TRecord;

class LivroBiblia extends TRecord
{
    const TABLENAME  = 'tbl_livro_biblia';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max'; // {max, serial}

        public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('nome');
        parent::addAttribute('abreviatura');
        parent::addAttribute('testamento');
        parent::addAttribute('id_categoria');
    }

    public function get_categoria()
    {
        return new CategoriaLivros($this->id_livro);
    }



}

    