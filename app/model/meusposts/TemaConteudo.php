<?php
//Cria model para a tabela tbl_categoria_livros
//Criar um arquivo CategoriaLivros.php na pasta app/model/meusposts


use Adianti\Database\TRecord;

class TemaConteudo extends TRecord
{
    const TABLENAME  = 'tbl_tema';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max'; // {max, serial}

        public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('descricao');
    }


}

    