@extends('impressao.layout-80mm', ['titulo' => 'Comprovante da venda #'.$venda->id])

@section('conteudo')
    <h1>{{ $empresa->nome_fantasia ?: $empresa->razao_social ?: 'Funerária Baldan' }}</h1>
    <p class="center muted">
        @if($empresa->cnpj) CNPJ {{ $empresa->cnpj }}<br>@endif
        {{ $empresa->municipio }} {{ $empresa->uf }}
    </p>
    <div class="linha"></div>
    <p class="center"><strong>COMPROVANTE DA VENDA</strong><br><span class="muted">Sem valor fiscal</span></p>
    <p>
        Venda #{{ $venda->id }}<br>
        {{ optional($venda->finalizada_em)->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}<br>
        {{ $venda->cliente?->nome ?: 'Consumidor' }}
        @if($documento)<br>Doc. {{ $documento }}@endif
    </p>
    <div class="linha"></div>
    <table>
        @foreach($venda->itens as $item)
            <tr>
                <td colspan="2">{{ $item->produto?->descricao ?: 'Item' }}</td>
            </tr>
            <tr>
                <td>{{ (int) $item->quantidade }} x {{ number_format((float) $item->preco_unitario, 2, ',', '.') }}</td>
                <td class="dir">{{ number_format((float) $item->total_linha, 2, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>
    <div class="linha"></div>
    <table>
        <tr><td>Subtotal</td><td class="dir">{{ number_format((float) $venda->subtotal, 2, ',', '.') }}</td></tr>
        @if((float) $venda->total < (float) $venda->subtotal)
            <tr><td>Desconto</td><td class="dir">-{{ number_format((float) $venda->subtotal - (float) $venda->total, 2, ',', '.') }}</td></tr>
        @endif
        <tr><td><strong>Total</strong></td><td class="dir"><strong>{{ number_format((float) $venda->total, 2, ',', '.') }}</strong></td></tr>
        <tr><td>Pagamento</td><td class="dir">{{ strtoupper(str_replace('_', ' ', $venda->forma_pagamento)) }}</td></tr>
        @if($venda->valor_recebido !== null)
            <tr><td>Recebido</td><td class="dir">{{ number_format((float) $venda->valor_recebido, 2, ',', '.') }}</td></tr>
            @php $troco = round((float) $venda->valor_recebido - (float) $venda->total, 2); @endphp
            @if($troco > 0)
                <tr><td>Troco</td><td class="dir">{{ number_format($troco, 2, ',', '.') }}</td></tr>
            @endif
        @endif
    </table>
    @if($venda->notaNfce?->status === 'autorizada')
        <div class="linha"></div>
        <p class="muted">
            NFC-e autorizada nº {{ $venda->notaNfce->numero }} série {{ $venda->notaNfce->serie }}<br>
            {{ $venda->notaNfce->chave }}
        </p>
    @endif
    <div class="linha"></div>
    <p class="center muted">Obrigado.</p>
@endsection
