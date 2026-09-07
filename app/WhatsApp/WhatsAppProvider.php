<?php

namespace App\WhatsApp;

/**
 * Contrato de provedor de WhatsApp. Permite trocar o provedor
 * (Cloud API, APIs terceiras) sem alterar o restante do sistema.
 */
interface WhatsAppProvider
{
    /**
     * Envia uma mensagem de texto simples.
     *
     * @return array{success:bool,message_id?:string,error?:string}
     */
    public function sendText(string $to, string $message): array;

    public function name(): string;
}
