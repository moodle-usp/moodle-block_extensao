<?php
defined('MOODLE_INTERNAL') || die();

use core\message\message;
// use core_user;
use core_message\api;

class Notificacoes {
    /**
     * O objetivo desta funcao eh enviar um e-mail ao ministrante cuja conta foi criada no moodle
     * de modo a notifica-lo da criacao da sua conta no moodle.
     */
    public static function notificacao_inscricao ($usuario, $moodle_curso) {
        global $USER;
        try {
            // Verifica se ha um email do professor cadastrado no banco de dados
            if (empty($usuario->email))
                throw new Exception(get_string('block_extensao', 'erro_profSemEmail'));
            
            // Montagem a mensagem
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

            // Captura a string do assunto
            $assunto = get_string('notificacao_inscricao_assunto', 'block_extensao');

            // Faz o envio da notificacao
            Notificacoes::enviar_notificacao_sistema($usuario->id, $assunto, $msg, $moodle_curso->id);

        } catch (Exception $e) {
            \core\notification::error(get_string('erro_padrao', 'block_extensao'));
            return false;
        }
    }


    /**
     * Metodo estatico para padronizar o envio de e-mails pelo e-mail de no-reply.
     */
    public static function enviar_notificacao_sistema (int $id_destinatario, string $assunto, string $msg, int $curso_id) {
        try {
            // O remetente eh o usuario no-reply padrao.
            $remetente = core_user::get_noreply_user();
            
            // Verifica se ja existe uma conversa entre os usuarios
            if (!api::get_conversation_between_users([$remetente->id, $id_destinatario])) {
                $conversa = api::create_conversation(
                    api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
                    [
                        $remetente->id,
                        $id_destinatario
                    ]
                );
            }
    
            // Cria objeto de mensagem
            $mensagem = new message();
            $mensagem->component         = 'moodle';
            $mensagem->name              = 'instantmessage';
            $mensagem->userfrom          = $remetente->id;
            $mensagem->userto            = $id_destinatario;
            $mensagem->subject           = $assunto;
            $mensagem->fullmessageformat = FORMAT_MARKDOWN;
            $mensagem->fullmessage       = $msg;
            $mensagem->fullmessagehtml   = $msg;
            $mensagem->smallmessage      = $msg;
            $mensagem->notification      = 0;
            $mensagem->contexturl        = '';
            $mensagem->contexturlname    = '';
            $mensagem->courseid          = $curso_id;
    
            // Obtem o e-mail de configuracao padrao do Moodle
            $mensagem->replyto = get_config('moodle', 'replyto');
            $content = array("*" => array('header'=>'', 'footer'=>''));
            $mensagem->set_additional_content('email', $content);
            
            // Envia a mensagem
            $message_id = message_send($mensagem);
            return $message_id;

        } catch (Exception $e) {
            // Lida com excecoes
            \core\notification::error(get_string('erro_padrao', 'block_extensao'));
            return false; 
        }
    }
}
