<?php
//Cria model para a tabela tbl_categoria_livros
//Criar um arquivo CategoriaLivros.php na pasta app/model/meusposts


use Adianti\Database\TRecord;

class DadosBiblia extends TRecord
{
    const TABLENAME  = 'tbl_dados_biblia';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max'; // {max, serial}

        public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('id_livro');
        parent::addAttribute('capitulo');
        parent::addAttribute('versiculos');
        parent::addAttribute('linhainicial');
    }


    public function get_livro()
    {
        return new LivroBiblia($this->id_livro);
    }


}

    