<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
        }

        @font-face {
            font-family: 'DejaVu Sans';
            src: url("{{ public_path('fonts/DejaVuSans.ttf') }}") format('truetype');
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
        }

        /* Har bir label alohida sahifa bo‘lsin */
        .label {
            /*width: 100%;*/
            /*height: 100%;*/
            padding: 1mm;
            box-sizing: border-box;
            text-align: center;

            page-break-inside: avoid;
            page-break-after: always; /* har product yangi sahifa */
        }

        /* Oxirgi sahifada bo‘sh page chiqmasin */
        .label:last-child {
            page-break-after: auto;
        }

        .product-name {
            max-width: 100%;
            margin: 0 auto 1mm auto;
            line-height: 1.1;
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            text-align: center;
            font-family: 'DejaVu Sans', sans-serif;
        }


        .barcode {
            display: block;
            width: 100%;
            text-align: center;
            margin: 0 auto;
        }

    </style>
</head>
<body>
{{-- DomPDF ba'zida birinchi bo‘sh sahifa chiqarib yubormasligi uchun hiyla --}}
<div style="display:none">&nbsp;</div>

@php
    $labelSize = $size ?? '30x20';

    // Sizing presets per label
    $presets = [
        '30x20' => [
            'name_font' => function (int $len): string {
                if ($len <= 18) { return '9px'; }
                if ($len <= 30) { return '8px'; }
                return '7px';
            },
            'name_max'   => 40,
            'barcode'    => ['scale' => 1.0, 'height' => 22],
            'code_font'  => '8px',
        ],
        '57x30' => [
            'name_font' => function (int $len): string {
                if ($len <= 30) { return '12px'; }
                if ($len <= 45) { return '10px'; }
                return '9px';
            },
            'name_max'   => 60,
            'barcode'    => ['scale' => 1.3, 'height' => 34],
            'code_font'  => '10px',
        ],
        '85x65' => [
            'name_font' => function (int $len): string {
                if ($len <= 40) { return '18px'; }
                if ($len <= 60) { return '16px'; }
                return '14px';
            },
            'name_max'   => 90,
            'barcode'    => ['scale' => 2.0, 'height' => 50],
            'code_font'  => '12px',
        ],
    ];

    $cfg = $presets[$labelSize] ?? $presets['30x20'];
@endphp

@foreach($products as $product)
    @php
        $name = (string) ($product->name ?? '');
        $len  = function_exists('mb_strlen') ? mb_strlen($name) : strlen($name);
        $max  = $cfg['name_max'];
        if ($len > $max) {
            $name = (function_exists('mb_substr') ? mb_substr($name, 0, $max) : substr($name, 0, $max)) . '…';
            $len  = function_exists('mb_strlen') ? mb_strlen($name) : strlen($name);
        }

        $nameFont   = ($cfg['name_font'])($len);
        $scale      = $cfg['barcode']['scale'];
        $barHeight  = $cfg['barcode']['height'];
        $codeFont   = $cfg['code_font'];
    @endphp

    <div class="label">
        <div class="product-name" style="font-size: {{ $nameFont }}">{{ $name }}</div>

        <div class="barcode">
            <div style="display:inline-block;">
                {!! DNS1D::getBarcodeHTML($product->barcode, 'EAN13', $scale, $barHeight) !!}
            </div>
        </div>

        <div class="product-name" style="font-size: {{ $codeFont }}">{{ $product->barcode }}</div>
    </div>
@endforeach
</body>
</html>
