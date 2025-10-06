<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
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

@foreach($products as $product)
    <div class="label">
        <div class="product-name"
             style="font-size: {{ strlen($product->name) > 40 ? '8px' : (strlen($product->name) > 20 ? '8px' : '10px') }}">
            {{ $product->name }}
        </div>


        <div class="barcode">
            <div style="display:inline-block;">
                {!! DNS1D::getBarcodeHTML($product->barcode, 'EAN13', 1.2, 28) !!}
            </div>
        </div>
        <div class="product-name" style="font-size: 10px">{{ $product->barcode }}</div>
    </div>
@endforeach
</body>
</html>
