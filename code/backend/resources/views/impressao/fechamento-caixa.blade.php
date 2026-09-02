@extends('impressao.layout-80mm', ['titulo' => 'Fechamento de caixa'])

@section('conteudo')
    <h1>{{ $empresa->nome_fantasia ?: $empresa->razao_social ?: 'Funerária Baldan' }}</h1>
    <p class="center"><strong>FECHAMENTO DE CAIXA</strong></p>
    <p class="muted">
        {{ $resumo['preview'] ? 'Prévia' : 'Sessão fechada' }}<br>
        Aberto: {{ optional($caixa->aberto_em)->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
        @if($caixa->fechado_em)
            <br>Fechado: {{ $caixa->fechado_em->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
        @endif
    </p>
    <div class="linha"></div>
    <table>
        <tr><td>Fundo</td><td class="dir">{{ number_format($resumo['valor_abertura'], 2, ',', '.') }}</td></tr>
        <tr><td>Vendas</td><td class="dir">{{ number_format($resumo['total_vendas'], 2, ',', '.') }}</td></tr>
        <tr><td>Dinheiro</td><td class="dir">{{ number_format($resumo['totais_forma']['dinheiro'], 2, ',', '.') }}</td></tr>
        <tr><td>PIX</td><td class="dir">{{ number_format($resumo['totais_forma']['pix'], 2, ',', '.') }}</td></tr>
        <tr><td>Cartão crédito</td><td class="dir">{{ number_format($resumo['totais_forma']['cartao_credito'] ?? 0, 2, ',', '.') }}</td></tr>
        <tr><td>Cartão débito</td><td class="dir">{{ number_format($resumo['totais_forma']['cartao_debito'] ?? 0, 2, ',', '.') }}</td></tr>
        <tr><td>Suprimentos</td><td class="dir">{{ number_format($resumo['total_suprimentos'], 2, ',', '.') }}</td></tr>
        <tr><td>Sangrias</td><td class="dir">{{ number_format($resumo['total_sangrias'], 2, ',', '.') }}</td></tr>
        <tr><td><strong>Dinheiro esperado</strong></td><td class="dir"><strong>{{ number_format($resumo['total_dinheiro_esperado'], 2, ',', '.') }}</strong></td></tr>
    </table>
    <div class="linha"></div>
    <p class="center muted">Conferência do operador</p>
@endsection
