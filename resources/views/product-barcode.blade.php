<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: 57mm 20mm;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 1px;
            font-family: sans-serif;
        }

        .label {
            /*width: 100%;*/
            /*height: 100%;*/
            padding: 1mm;
            box-sizing: border-box;
            text-align: center;
        }

        .product-name {
            font-size: 14px;
            margin-bottom: 1mm;
            word-wrap: break-word;
        }
        .product-barcode {
            font-size: 20px;
            margin-top: 1mm;
            margin-bottom: 1mm;
            text-align: center;
            margin-left: 10px;
        }

    </style>
</head>
<body>
@foreach($products as $product)
    <div class="label">
        <div class="product-name">{{ $product->name }}</div>
        <div class="product-barcode"> {!! DNS1D::getBarcodeHTML($product->barcode, 'EAN13', 1.0, 24) !!}</div>
    </div>
@endforeach
</body>
</html>
