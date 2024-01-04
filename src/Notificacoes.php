<?php
defined('MOODLE_INTERNAL') || die();

use core\message\message;
// use core_user;
use core_message\api;

class Notificacoes {
    /**
     * O objetivo desta classe  eh inserir as notificacoes/mensagens que serao enviadas aos usuarios
     * @param array $usuario eh o individuo que sera inscrito no Moodle.
     * @return array|boolean a funcao retorna o envio de uma email
     *  
     */
    public static function notificacao_inscricao($usuario, $moodle_curso) {
        /**
         * O objetivo desta funcao eh enviar um e-mail ao ministrante cuja conta foi criada no moodle
         * de modo a notifica-lo da criacao da sua conta no moodle.
         */
        Global $USER;
        $USER->firstname; 
        try {
            // verifica se ha um email do professor cadastrado no banco de dados
            if (empty($usuario->email)) {
                // mensagem de erro 
                throw new Exception("Atenção, o professor selecionado não possui um e-mail cadastrado no sistema, a notificação de inscrição não será enviada.");
            }
            $userfrom = core_user::get_noreply_user();
            $userto = $usuario->id;
            date_default_timezone_set('America/Sao_Paulo');
            $data = date('d/m/Y H:i:s', time());
            // Ler a configuração do campo block_extensao/email_body_new_user
            // Substituir os tokens: %profTit, %profAux, %curso e %turma
            // Dica str_replace, %firstname port por $usuario->firstname
            
            $msg = get_config('block_extensao', 'email_body_new_user');

            $msg = str_replace('%profTit', $USER->firstname, $msg); 
            $msg = str_replace('%curso', $moodle_curso->fullname, $msg); 
            $msg = str_replace('%profAux', $usuario->firstname, $msg);
            $msg = str_replace('%turma', $moodle_curso->shortname, $msg);
            $msg = str_replace('%data', $data, $msg);


            // Verifique se ja existe uma conversa entre os usuarios
            if (!api::get_conversation_between_users([$userfrom->id, $userto])) {
                $conversation = api::create_conversation(
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
                    [
                        $userfrom->id,
                        $userto
                    ]
                );
            }

            // Crie o objeto de mensagem
            $mensagem = new message();
            $mensagem->component = 'moodle';
            $mensagem->name = 'instantmessage';
            $mensagem->userfrom = $userfrom->id;
            $mensagem-> userto = $userto;
            $mensagem->subject = 'Nova mensagem';
            $mensagem->fullmessage = $msg;
            $mensagem->fullmessageformat = FORMAT_MARKDOWN;
            $mensagem->fullmessagehtml = $msg;
            $mensagem->smallmessage = $msg;
            $mensagem->notification = 0;
            $mensagem->contexturl = '';
            $mensagem->contexturlname = 'Nome do Contexto';

            // Obtem o email de configuracao padrao do Moodle
            $mensagem->replyto = get_config('moodle', 'replyto');
            $content = array('*' => array('header' => '', 'footer' => ''));
            $mensagem->set_additional_content('email', $content);
            $mensagem->courseid = 107;

            // Envie a mensagem
            $messageid = message_send($mensagem);

            if ($messageid) {
                // Mensagem enviada com sucesso
                return true;
            } else {
                // Erro ao enviar a mensagem
                return false;
            }
        } catch (Exception $e) {
            // Lida com excecoes
            return false;
        }
    }
}
