<?php

/**
 * Parametros da sessao. Ajuste o dominio para o subdominio de producao.
 */

return [
    'name' => 'ltsaas_session',
    'lifetime' => 0, // 0 = ate fechar o navegador; mantido por cookie httponly.
    'domain' => '',
];
