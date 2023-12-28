@extends('templates.main')

@section('title_page')
    PCBC
@endsection

@section('breadcrumb_title')
    cashier / pcbc / create
@endsection

@section('content')
    @include('cashier.pcbc.form')
@endsection

@section('styles')
    {{--  --}}
@endsection

@section('scripts')
    <script>
        function calculateAmount(input, element, kopur) {
            var output_element = document.getElementById(element);
            var value = input.value;
            var amount = value * kopur;
                        output_element.value = amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        // output_element.value = amount.toLocaleString();

            calculateTotalKertas();
            calculateTotalCoin();
            calculateTotal();
            calculateVariance();
            
        }

        function calculateTotalKertas() {
            var total = 0;
            var kertas = document.getElementsByClassName('kertas');
            for (var i = 0; i < kertas.length; i++) {
                var value = kertas[i].value;
                if (value != '') {
                    total += parseInt(value.replace(/\,/g, ''));
                }
            }
            document.getElementById('fisik_kertas').value = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            // document.getElementById('fisik_kertas').value = total.toLocaleString();
        }

        function calculateTotalCoin() {
            var total = 0;
            var kertas = document.getElementsByClassName('coins');
            for (var i = 0; i < kertas.length; i++) {
                var value = kertas[i].value;
                if (value != '') {
                    total += parseInt(value.replace(/\,/g, ''));
                }
            }
            document.getElementById('fisik_coin').value = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            // document.getElementById('fisik_coin').value = total.toLocaleString();
        }

        // function that calculates the total amount of class coins plus class kertas
        function calculateTotal() {
            var total = 0;
            var kertas = document.getElementsByClassName('kertas');
            for (var i = 0; i < kertas.length; i++) {
                var value = kertas[i].value;
                if (value != '') {
                    total += parseInt(value.replace(/\,/g, ''));
                }
            }
            var coins = document.getElementsByClassName('coins');
            for (var i = 0; i < coins.length; i++) {
                var value = coins[i].value;
                if (value != '') {
                    total += parseInt(value.replace(/\,/g, ''));
                }
            }
            document.getElementById('fisik_total').value = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function calculateVariance() {
            var app_balance = document.getElementById('app_balance').value.replace(/\,/g, '');
            var fisik_total = document.getElementById('fisik_total').value.replace(/\,/g, '');
            var variance = app_balance - fisik_total;
            document.getElementById('variance').value = variance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function calculateVarianceAplikasi() {
            var app_balance = document.getElementById('app_balance').value.replace(/\,/g, '');
            var sap_balance = document.getElementById('sap_balance').value.replace(/\,/g, '');
            var variance_aplikasi = app_balance - sap_balance;
            document.getElementById('variance_aplikasi').value = variance_aplikasi.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        window.onload = function() {
            calculateAmount(document.querySelector('[name="seratus_ribu"]'), 'seratus_ribu_amount', 100000);
            calculateAmount(document.querySelector('[name="lima_puluh_ribu"]'), 'lima_puluh_ribu_amount', 50000);
            calculateAmount(document.querySelector('[name="dua_puluh_ribu"]'), 'dua_puluh_ribu_amount', 20000);
            calculateAmount(document.querySelector('[name="sepuluh_ribu"]'), 'sepuluh_ribu_amount', 10000);
            calculateAmount(document.querySelector('[name="lima_ribu"]'), 'lima_ribu_amount', 5000);
            calculateAmount(document.querySelector('[name="dua_ribu"]'), 'dua_ribu_amount', 2000);
            calculateAmount(document.querySelector('[name="seribu"]'), 'seribu_amount', 1000);
            calculateAmount(document.querySelector('[name="lima_ratus"]'), 'lima_ratus_amount', 500);
            calculateAmount(document.querySelector('[name="seratus"]'), 'seratus_amount', 100);
            calculateAmount(document.querySelector('[name="coin_seribu"]'), 'coin_seribu_amount', 1000);
            calculateAmount(document.querySelector('[name="coin_lima_ratus"]'), 'coin_lima_ratus_amount', 500);
            calculateAmount(document.querySelector('[name="coin_dua_ratus"]'), 'coin_dua_ratus_amount', 200);
            calculateAmount(document.querySelector('[name="coin_seratus"]'), 'coin_seratus_amount', 100);
            calculateAmount(document.querySelector('[name="coin_lima_puluh"]'), 'coin_lima_puluh_amount', 50);
            calculateAmount(document.querySelector('[name="coin_dua_puluh_lima"]'), 'coin_dua_puluh_lima_amount', 25);
            calculateVarianceAplikasi();
            
        };
               
    </script>
@endsection
