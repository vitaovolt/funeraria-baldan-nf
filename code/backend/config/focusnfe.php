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
    'reforma_tributaria' => [
        'habilitar_ibs_cbs' => filter_var(env('FOCUSNFE_HABILITAR_IBS_CBS', true), FILTER_VALIDATE_BOOLEAN),
        'ibs' => [
            'aliquota' => (float) env('FOCUSNFE_IBS_ALIQUOTA', 0.1),
            'uf_aliquota' => (float) env('FOCUSNFE_IBS_UF_ALIQUOTA', 0),
            'municipio_aliquota' => (float) env('FOCUSNFE_IBS_MUNICIPIO_ALIQUOTA', 0),
        ],
        'cbs' => [
            'aliquota' => (float) env('FOCUSNFE_CBS_ALIQUOTA', 0.9),
        ],
        'ibs_cbs_situacao_tributaria' => env('FOCUSNFE_IBS_CBS_SITUACAO_TRIBUTARIA', '000'),
        'ibs_cbs_classificacao_tributaria' => env('FOCUSNFE_IBS_CBS_CLASSIFICACAO_TRIBUTARIA', '000001'),
    ],
];
