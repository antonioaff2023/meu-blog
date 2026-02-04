<?php

namespace Adianti\Base;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Database\TTransaction;
use Adianti\Widget\Dialog\TToast;
use Adianti\Database\TRepository;
use Adianti\Database\TFilter;
use Adianti\Database\TCriteria;
use Adianti\Registry\TSession;
use Adianti\Control\TAction;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Widget\Form\TButton;
use stdClass;
use Adianti\Widget\Base\TScript;

use LogAcoes;


use Exception;

/**
 * Standard List Trait
 *
 * @version    8.1
 * @package    base
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license
 */
trait MeuTrait #depends:AdiantiStandardCollectionTrait
{

    use AdiantiStandardCollectionTrait;
    use AdiantiStandardListExportTrait;
    use AdiantiStandardListTrait;

    public function onSalva($param = null, $mensagem = 'Registro salvo com sucesso!')
    {
        try {
            if (empty($this->database)) {
                throw new Exception(AdiantiCoreTranslator::translate('^1 was not defined. You must call ^2 in ^3', AdiantiCoreTranslator::translate('Database'), 'setDatabase()', AdiantiCoreTranslator::translate('Constructor')));
            }

            if (empty($this->activeRecord)) {
                throw new Exception(AdiantiCoreTranslator::translate('^1 was not defined. You must call ^2 in ^3', 'Active Record', 'setActiveRecord()', AdiantiCoreTranslator::translate('Constructor')));
            }


            // open a transaction with database
            TTransaction::open($this->database);

            // get the form data
            $object = $this->form->getData($this->activeRecord);

            // validate data
            $this->form->validate();

            // stores the object
            $object->store();

            if (!empty($this->afterSaveCallback)) {
                $callback = $this->afterSaveCallback;
                $callback($object, $this->form->getData());
            }

            // fill the form with the active record data
            $this->form->setData($object);

            // close the transaction
            TTransaction::close();

            // shows the success message
            if (isset($this->useMessages) and $this->useMessages === false) {
                AdiantiCoreApplication::loadPageURL($this->afterSaveAction->serialize());
            } else {
                //new TMessage('info', AdiantiCoreTranslator::translate('Record saved'), $this->afterSaveAction);
                TToast::show('success',  $mensagem, 'bottom center', 'far:check-circle'); // notifica o usuário
            }

            return $object;
        } catch (Exception $e) // in case of exception
        {
            // get the form data
            $object = $this->form->getData();

            // fill the form with the active record data
            $this->form->setData($object);

            // shows the exception error message
            new TMessage('error', $e->getMessage());

            // undo all pending operations
            TTransaction::rollback();
        }
    }
}
