<?php
defined('MOODLE_INTERNAL') || die();

use core\message\message;
use core_user;
use core_message\api;

class Notificacoes {
    /**
     * O objetivo desta classe  eh inserir as notificacoes/mensagens que serao enviadas aos usuarios
     * @param array $usuario eh o individuo que sera inscrito no Moodle.
     * @return array|boolean a funcao retorna o envio de uma email
     *  
     */
    public static function notificacao_inscricao($usuario) {
        /**
         * O objetivo desta funcao eh enviar um e-mail ao ministrante cuja conta foi criada no moodle
         * de modo a notifica-lo da criacao da sua conta no moodle.
         */
        try {
            $userfrom = core_user::get_noreply_user();
            $userto = $usuario->id;
            $msg = "Olá " . $usuario->firstname . ",\n\nA sua conta no Moodle foi criada com sucesso!";

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
            $mensagem->userto = $userto;
            $mensagem->subject = 'Nova mensagem';
            $mensagem->fullmessage = $msg;
            $mensagem->fullmessageformat = FORMAT_MARKDOWN;
            $mensagem->fullmessagehtml = $msg;
            $mensagem->smallmessage = $msg;
            $mensagem->notification = 0;
            $mensagem->contexturl = '';
            $mensagem->contexturlname = 'Nome do Contexto';
            $mensagem->replyto = "noreply@example.com";
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