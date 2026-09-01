<?php

$ambiente = env('FOCUSNFE_AMBIENTE');
if ($ambiente === null || $ambiente === '') {
    $ambiente = 'homologacao';
}

return [
    'driver' => env('FOCUSNFE_DRIVER', 'focus'),
    'ambiente' => $ambiente,
    'base_url' => $ambiente === 'producao'
        ? 'https://api.focusnfe.com.br'
        : 'https://homologacao.focusnfe.com.br',
    'token_homologacao' => env('FOCUSNFE_TOKEN_HOMOLOGACAO', ''),
    'token_producao' => env('FOCUSNFE_TOKEN_PRODUCAO', ''),
    'timeout' => (int) env('FOCUSNFE_TIMEOUT', 30),
    'retry' => (int) env('FOCUSNFE_RETRY_ATTEMPTS', 3),
    'cfop_padrao' => env('FOCUSNFE_CFOP_PADRAO', '5102'),
    'csosn_padrao' => env('FOCUSNFE_CSOSN_PADRAO', '102'),
];
