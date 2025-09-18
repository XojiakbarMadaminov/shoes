document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const input = document.querySelector('input[name="Search"]');
        if (input) {
            input.focus();
        }
    }, 200);
});

document.addEventListener('livewire:navigated', function() {
    setTimeout(() => {
        const input = document.querySelector('input[name="Search"]');
        if (input) {
            input.focus();
        }
    }, 100);
});

// Chek chiqarish
// public/js/pos.js  (unchanged parts –– faqat CSS o‘zgardi)
document.addEventListener('print-receipt', () => {
    const src = document.getElementById('receipt-content');
    if (!src) return;

    const w = window.open('', '_blank');
    w.document.write(`
        <html>
        <head>
            <title>Chek</title>
            <style>
                @page { size: 80mm auto; margin: 0 }
               body {
                    font-family: 'Courier New', monospace;
                    font-size: 12px;
                    margin: 0;
                    padding: 0;
                    background: #fff;
                }
                .receipt {
                    width: 76mm;
                    margin: 0 auto;
                    padding-left: 2mm;
                    padding-right: 2mm;
                    page-break-inside: avoid;
                }
                .center { text-align: center }
                .right { text-align: right }
                .bold { font-weight: 700 }
                .item-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-end;
                    margin: 1mm 0;
                    page-break-inside: avoid;
                }
                .item-name { flex: 1 }
                .item-total { text-align: right; min-width: 24mm }
                .line { border-bottom: 1px dashed #000; margin: 2mm 0 }
                .receipt img {
                    display: block;
                    margin: 0 auto 2mm auto;
                    max-width: 40mm;
                    max-height: 40mm;
                }
            </style>
        </head>
        <body>
            <div class="receipt">
                ${src.innerHTML}
            </div>
        </body>
        </html>
    `);
    w.document.close();
    setTimeout(() => { w.print(); w.close(); }, 200);
});


