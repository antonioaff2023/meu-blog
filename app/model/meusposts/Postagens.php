<?php
//Cria model para a tabela tbl_postagem


use Adianti\Database\TRecord;

class Postagens extends TRecord
{
    const TABLENAME  = 'tbl_postagem';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max'; // {max, serial}

        public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('id_tipo');
        parent::addAttribute('id_subtipo');
        parent::addAttribute('id_tema');
        parent::addAttribute('id_livrobiblia');
        parent::addAttribute('id_serie');
        parent::addAttribute('passagem');
        parent::addAttribute('titulo');
        parent::addAttribute('subtitulo');
        parent::addAttribute('data_postagem');
        parent::addAttribute('conteudo');
        parent::addAttribute('tags');
    }

        public function get_tipo()
    {
        return new TipoConteudo($this->id_tipo);
    }
        public function get_subtipo()
    {
        return new SubTipoConteudo($this->id_subtipo);
    }

        public function get_tema()
    {
        return new TemaConteudo($this->id_tema);
    }

        public function get_categoria()
    {
        return new CategoriaLivros($this->id_categoria);
    }

        public function get_livrobiblia()
    {
        return new LivroBiblia($this->id_livrobiblia);
    }
        public function get_serie()
    {
        return new Series($this->id_serie);
    }

}

    