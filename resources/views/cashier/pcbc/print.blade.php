<!DOCTYPE html>
<html>
@include('cashier.pcbc.print_head')
<body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">

        <section class="invoice">

            <div class="row">
                <table class="table table-bordered">
                    
                </table>
            </div>

            <div class="row">
                <div class="col-xs-6 table-responsive">
                    @include('cashier.pcbc.print_kertas')
                </div>

                <div class="col-xs-6 table-responsive">
                    @include('cashier.pcbc.print_coin')
                </div>
            </div>

        </section>

    </div>

</body>
</html>