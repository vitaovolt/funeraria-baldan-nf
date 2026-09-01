<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo ?? 'Impressão' }}</title>
    <style>
        @page { size: 80mm auto; margin: 3mm; }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            width: 80mm;
            font-family: "Courier New", Courier, monospace;
            font-size: 12px;
            line-height: 1.25;
            color: #000;
            background: #fff;
        }
        .cupom { width: 74mm; margin: 0 auto; }
        h1 { font-size: 13px; margin: 0 0 6px; text-align: center; text-transform: uppercase; }
        .center { text-align: center; }
        .muted { font-size: 11px; }
        .linha { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 1px 0; }
        .dir { text-align: right; }
        .assinatura { margin-top: 18px; border-top: 1px solid #000; padding-top: 4px; text-align: center; }
        @media print {
            body { width: 80mm; }
            button { display: none !important; }
        }
    </style>
</head>
<body>
<div class="cupom">
    @yield('conteudo')
</div>
<script>
    window.addEventListener('load', function () {
        window.focus();
        window.print();
    });
</script>
</body>
</html>
