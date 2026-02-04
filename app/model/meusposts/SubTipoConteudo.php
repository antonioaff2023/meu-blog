<?php
//Cria model para a tabela tbl_categoria_livros
//Criar um arquivo CategoriaLivros.php na pasta app/model/meusposts


use Adianti\Database\TRecord;

class SubTipoConteudo extends TRecord
{
    const TABLENAME  = 'tbl_subtipo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max'; // {max, serial}

        public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('id_tipo');
        parent::addAttribute('descricao');
    }

    public function get_tipo()
    {
        return new TipoConteudo($this->id_tipo);
    }

}

    