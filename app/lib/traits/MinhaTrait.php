<?php

namespace App\Traits;

use TTransaction;
use TMessage;
use stdClass;

trait MinhaTrait
{
    public function onSalva($param)
    {
        try {
            TTransaction::open('sample'); // banco de dados fixo aqui, ou parametrizar depois

            $data = $this->form->getData(); // obtém os dados do formulário

            $class = $this->activeRecord ?? null;
            if (!$class) {
                throw new Exception("Classe de modelo não definida.");
            }

            $object = new $class(); // instancia dinamicamente
            $object->fromArray((array) $data);
            $object->store();

            // Limpa o formulário se id for nulo ou não estiver definido
            if (empty($object->id)) {
                $this->form->setData(new stdClass); // limpa o formulário
            } else {
                // Se o id estiver definido, atualiza os dados no formulário
                $this->form->setData($object);
            }
            

            TTransaction::close();

            new TMessage('info', 'Registro salvo com sucesso');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    //Limpa o formulário
    public function onLimpa($param)
    {
        $this->form->clear();
        $this->form->setData(new stdClass);
    }
}
