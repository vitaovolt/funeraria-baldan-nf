@extends('impressao.layout-80mm', ['titulo' => 'Notinha consignado #'.$consignado->id])

@section('conteudo')
    <h1>{{ $empresa->nome_fantasia ?: $empresa->razao_social ?: 'Funerária Baldan' }}</h1>
    <p class="center"><strong>NOTINHA DE CONSIGNADO</strong><br><span class="muted">Produto levado para prova — sem valor fiscal</span></p>
    <div class="linha"></div>
    <p>
        Consignado #{{ $consignado->id }}<br>
        {{ optional($consignado->created_at)->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}<br>
        Cliente: {{ $consignado->cliente?->nome }}<br>
        @if($consignado->cliente?->documento) Doc. {{ $consignado->cliente->documento }}@endif
    </p>
    <div class="linha"></div>
    <table>
        @foreach($consignado->itens as $item)
            <tr>
                <td>{{ $item->produto?->descricao ?: 'Item' }}</td>
                <td class="dir">{{ (int) $item->quantidade }} un.</td>
            </tr>
        @endforeach
    </table>
    <div class="linha"></div>
    <p class="muted">Devolver ou converter em venda no balcão. Estoque já baixado.</p>
    <div class="assinatura">Assinatura do cliente</div>
@endsection
