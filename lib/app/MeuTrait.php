<?php

namespace app;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Database\TTransaction;
use Adianti\Widget\Dialog\TToast;
use Adianti\Base\AdiantiStandardCollectionTrait;
use Adianti\Base\AdiantiStandardListExportTrait;
use Adianti\Base\AdiantiStandardListTrait;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Widget\Container\TVBox;
use DateTime;
use Postagens;
use IntlDateFormatter;


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

    public function onDivideTela($esquerda, $direita, $tam_esquerda = '50%', $tam_direita = '50%')
    {
        $div_principal = new TElement('div');
        $div_principal->style = 'width: 100%; margin: auto; display: flex; gap: 20px;';

        $div_esquerda = new TElement('div');
        $div_esquerda->style = "width: $tam_esquerda; margin: auto;";

        $div_direita = new TElement('div');
        $div_direita->style = "width: $tam_direita; margin: auto;";

        $div_esquerda->add($esquerda);
        $div_direita->add($direita);
        $div_principal->add($div_esquerda);
        $div_principal->add($div_direita);

        return $div_principal;
    }

    public static function UltimosSermoes($codigo = null)
    {
        $html = "<style>
                    * { margin: 0; padding: 0; }
                    h3 {
                        font-size: clamp(.8em, 2.5vw, 1.2em);
                        margin-bottom: 5px;
                        color: #ffffff;
                    }

                    .texto-devocional {
                        font-size: clamp(.8em, 2.5vw, 1em);
                        margin-bottom: 10px;
                        color: #ffffff;
                        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
                    }

                    p {
                        margin: 0 0 0 0;
                        padding: 0;

                    }

                    @media (max-width: 768px) {
                        h3 {
                            font-size: clamp(.8em, 2.5vw, 1em);
                        }
                        .texto-devocional {
                            font-size: 0.7em;
                        }
                    }

                    @media (max-width: 480px) {
                        h3 {
                            font-size: 0.9em;
                        }
                        .texto-devocional {
                            font-size: 0.5em;
                        }
                        p {
                            font-size: 0.85em;
                        }

                    }
                </style>";


        if ($codigo == null) {
            $codigo =  1; // Sermões
        }

        $acao = ($codigo == 1) ? 'Pregado' : 'Ministrado';

        TTransaction::open('sample');
        //Lista os últimos 3 sermões
        $sermoes = Postagens::where('id_tipo', '=', $codigo)
            ->where('data_postagem', '<', date('Y-m-d'))
            ->orderBy('data_postagem', 'desc')
            ->take(4)
            ->load();
        TTransaction::close();

        foreach ($sermoes as $sermao) {

            $postagem = (new DateTime($sermao->data_postagem))->format('d/m/Y');
            // Converte a data em extenso
            $formatter = new IntlDateFormatter('pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, null, null, "EEEE, dd 'de' MMMM 'de' yyyy");
            $data_extenso = $formatter->format(new DateTime($sermao->data_postagem));

            $sermao->passagem = $sermao->passagem;
            //Título em caixa alta
            $titulo = strtoupper($sermao->titulo);

            $html .= <<<HTML
                <div class="postagem">
                    <h3>
                        <a style="font-size: clamp(.8em, 2.5vw, 1.0em);" href="/index.php?class=InicioPublicoView&method=onGeraPDF&id={$sermao->id}"
                        generator="adianti">{$titulo}
                        </a>
                    </h3>
                    <span style="font-size: clamp(.8em, 2.5vw, 1em);"><p><strong>Texto:</strong> {$sermao->passagem}</p>
                    <p class='data-postagem'>{$acao} - {$data_extenso}</p></span>
                    <hr style="height: 2px; background: black; margin: 10px 0; width: 50%;">
                    
                     
                </div>
            HTML;
        }

        return $html;
    }

    public function onGeraPDF($param)
    {

        try {


            TTransaction::open('sample');
            $GerandoPDF = Postagens::find($param['id']);
            TTransaction::close();

            // processa um template de página em HTML
            $html = <<<EOF
                    <body class="corpo-sermao">
                        <div class="sermao-container">
                            <div class="sermao-titulo">
                                <h2>{$GerandoPDF->titulo}</h2>
                            </div>

                            <hr>
                            <div class="sermao-texto">
                                {$GerandoPDF->conteudo}
                            </div>
                        </div>
                    </body>

            EOF;



            // converte o modelo HTML em PDF
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();



            // Exclui os arquivos PDF antigos
            $files = glob('app/output/*.pdf');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            $current_datetime = date('Ymd_His');

            //Caso $GerandoPDF->passagem tenha caracteres inválidos para nome de arquivo, substitui por "." mantendo apenas letras, números, hífens, espaços e sublinhados
            $safe_passagem = preg_replace('/[^a-zA-Z0-9\s\-_]/', '.', $GerandoPDF->passagem);
            $safe_titulo = preg_replace('/[^a-zA-Z0-9\s\-_]/', '.', $GerandoPDF->titulo);
            // Se o título for muito longo, limita a 50 caracteres
            if (strlen($safe_titulo) > 50) {
                $safe_titulo = substr($safe_titulo, 0, 50);
            }


            $file = 'app/output/' . $safe_passagem . ' - ' . $safe_titulo . '.pdf';

            // Exclui arquivo se já existir com o mesmo nome
            if (file_exists($file)) {
                unlink($file);
            }


            // gravar e abrir arquivo
            file_put_contents($file, $dompdf->output());


            $file_url = $file . '?v=' . time();

            $mostra = $this->retornaModal($file_url);

            $container = new TVBox;
            $container->style = 'width: 100%';
            $container->add($mostra);
            parent::add($container);
        } catch (Exception $e) {
            new TMessage('error', 'Erro ao gerar PDF: ' . $e->getMessage());
        }
    }

    public function retornaModal($file)
    {

        // processa um template de página em HTML
        $mostra = new THtmlRenderer('app/resources/modal_pdf.html');

        $replaces = [];
        $replaces['file'] = $file;
        $mostra->enableSection('main', $replaces);


        return $mostra;
    }

    //Limpa o formulário
    public function onLimpa($param)
    {
        $this->form->clear();
        $this->form->setData(new stdClass);
    }

    protected function chamarPerplexity(string $prompt): string
    {
        $apiKey = $_ENV['PERPLEXITY_API_KEY']; // ideal: pegar de config/env

        $payload = [
            'model' => 'sonar-pro',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Você é um assistente para elaboração de sermões e estudos bíblicos reformados, pré-milenistas, pós-tribulacionistas, com fidelidade exegética.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        $ch = curl_init('https://api.perplexity.ai/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $res = curl_exec($ch);
        if ($res === false) {
            $err = curl_error($ch);
            unset($ch);
            throw new Exception('Erro na requisição à IA: ' . $err);
        }

        unset($ch);

        $json = json_decode($res, true);

        // Se veio erro estruturado da API
        if (isset($json['error'])) {
            $msg = $json['error']['message'] ?? json_encode($json['error']);
            throw new Exception('Erro da API da IA: ' . $msg);
        }

        // Tenta formato OpenAI-compatível (choices[0].message.content)
        if (isset($json['choices'][0]['message']['content'])) {
            return trim($json['choices'][0]['message']['content']);
        }

        // Tenta formato alternativo output[0].content[0].text (se em algum momento a API usar isso)
        if (isset($json['output'][0]['content'][0]['text'])) {
            return trim($json['output'][0]['content'][0]['text']);
        }

        // Último recurso: dump parcial pro log e mensagem genérica
        file_put_contents(__DIR__ . '/log_perplexity_erro.json', $res . PHP_EOL, FILE_APPEND);
        throw new Exception('Resposta inesperada da IA. Verifique o log_perplexity_erro.json para detalhes.');
    }

    protected  function chamarClaude(string $prompt): string
    {
        $apiKey = $_ENV['ANTHROPIC_API_KEY'];

        $payload = [
            'model'      => 'claude-sonnet-4-6',
            'max_tokens' => 8192,
            'system'     => 'Você é um assistente para elaboração de sermões e estudos bíblicos reformados, pré-milenistas, pós-tribulacionistas, com fidelidade exegética.',
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $res = curl_exec($ch);
        if ($res === false) {
            $err = curl_error($ch);
            unset($ch);
            throw new Exception('Erro na requisição à IA: ' . $err);
        }

        unset($ch);

        $json = json_decode($res, true);

        // Erro estruturado da API
        if (isset($json['error'])) {
            $msg = $json['error']['message'] ?? json_encode($json['error']);
            throw new Exception('Erro da API da IA: ' . $msg);
        }

        // Formato Claude: content[0].text
        if (isset($json['content'][0]['text'])) {
            return trim($json['content'][0]['text']);
        }

        // Último recurso: log e exceção
        file_put_contents(__DIR__ . '/log_claude_erro.json', $res . PHP_EOL, FILE_APPEND);
        throw new Exception('Resposta inesperada da IA. Verifique o log_claude_erro.json para detalhes.');
    }
}
