<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>AdminLTE 2 | Invoice</title>

        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

        {{-- <link rel="stylesheet" href="../../bower_components/font-awesome/css/font-awesome.min.css">

        <link rel="stylesheet" href="../../bower_components/Ionicons/css/ionicons.min.css"> --}}

        <link rel="stylesheet" href="{{ asset('adlte2/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ asset('adlte2/AdminLTE.min.css') }}">

        <link rel="stylesheet" href="../../dist/css/skins/_all-skins.min.css">


        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->

        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
    </head>
    <body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">
   
        <section class="content-header">
            <h1>Invoice<small> #007612</small></h1>
        </section>

        <section class="invoice">

            <div class="row">
                <div class="col-xs-12">
                    <h2 class="page-header"><i class="fa fa-globe"></i> AdminLTE, Inc. <small class="pull-right">Date: 2/10/2014</small></h2>
                </div>
            </div>

            <div class="row invoice-info">
                <div class="col-sm-4 invoice-col">
                    From
                    <address>
                        <strong>Admin, Inc.</strong><br>
                        795 Folsom Ave, Suite 600<br>
                        San Francisco, CA 94107<br>
                        Phone: (804) 123-5432<br>
                        Email: <a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="8ce5e2eae3ccede0e1edffede9e9e8fff8f9e8e5e3a2efe3e1">[email&#160;protected]</a>
                    </address>
                </div>

                <div class="col-sm-4 invoice-col">
                To
                    <address>
                    <strong>John Doe</strong><br>
                    795 Folsom Ave, Suite 600<br>
                    San Francisco, CA 94107<br>
                    Phone: (555) 539-1037<br>
                    Email: <a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="52383d3a3c7c363d3712372a333f223e377c313d3f">[email&#160;protected]</a>
                    </address>
                </div>

                <div class="col-sm-4 invoice-col">
                    <b>Invoice #007612</b><br>
                    <br>
                    <b>Order ID:</b> 4F3S8J<br>
                    <b>Payment Due:</b> 2/22/2014<br>
                    <b>Account:</b> 968-34567
                </div>

            </div>


            <div class="row">
                <div class="col-xs-12 table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Qty</th>
                                <th>Product</th>
                                <th>Serial #</th>
                                <th>Description</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Call of Duty</td>
                                <td>455-981-221</td>
                                <td>El snort testosterone trophy driving gloves handsome</td>
                                <td>$64.50</td>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>Need for Speed IV</td>
                                <td>247-925-726</td>
                                <td>Wes Anderson umami biodiesel</td>
                                <td>$50.00</td>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>Monsters DVD</td>
                                <td>735-845-642</td>
                                <td>Terry Richardson helvetica tousled street art master</td>
                                <td>$10.70</td>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>Grown Ups Blue Ray</td>
                                <td>422-568-642</td>
                                <td>Tousled lomo letterpress</td>
                                <td>$25.99</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="row">

                <div class="col-xs-6">
                    <p class="lead">Payment Methods:</p>
                    <p class="text-muted well well-sm no-shadow" style="margin-top: 10px;">
                    Etsy doostang zoodles disqus groupon greplin oooj voxy zoodles, weebly ning heekya handango imeem plugg
                    dopplr jibjab, movity jajah plickers sifteo edmodo ifttt zimbra.
                    </p>
                </div>

                <div class="col-xs-6">
                    <p class="lead">Amount Due 2/22/2014</p>
                    <div class="table-responsive">
                        <table class="table">
                            <tr>
                                <th style="width:50%">Subtotal:</th>
                                <td>$250.30</td>
                            </tr>
                            <tr>
                                <th>Tax (9.3%)</th>
                                <td>$10.34</td>
                            </tr>
                            <tr>
                                <th>Shipping:</th>
                                <td>$5.80</td>
                            </tr>
                            <tr>
                                <th>Total:</th>
                                <td>$265.24</td>
                            </tr>
                        </table>
                    </div>
                </div>

            </div>


            <div class="row no-print">
                <div class="col-xs-12">
                    <a href="invoice-print.html" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
                    <button type="button" class="btn btn-success pull-right"><i class="fa fa-credit-card"></i> Submit Payment</button>
                    <button type="button" class="btn btn-primary pull-right" style="margin-right: 5px;">
                    <i class="fa fa-download"></i> Generate PDF</button>
                </div>
            </div>
        </section>

        <div class="clearfix"></div>
    </div>

    <aside class="control-sidebar control-sidebar-dark">

    <ul class="nav nav-tabs nav-justified control-sidebar-tabs">
    <li><a href="#control-sidebar-home-tab" data-toggle="tab"><i class="fa fa-home"></i></a></li>
    <li><a href="#control-sidebar-settings-tab" data-toggle="tab"><i class="fa fa-gears"></i></a></li>
    </ul>

    <div class="tab-content">

    <div class="tab-pane" id="control-sidebar-home-tab">
    <h3 class="control-sidebar-heading">Recent Activity</h3>
    <ul class="control-sidebar-menu">
    <li>
    <a href="javascript:void(0)">
    <i class="menu-icon fa fa-birthday-cake bg-red"></i>
    <div class="menu-info">
    <h4 class="control-sidebar-subheading">Langdon's Birthday</h4>
    <p>Will be 23 on April 24th</p>
    </div>
    </a>
    </li>
    <li>
    <a href="javascript:void(0)">
    <i class="menu-icon fa fa-user bg-yellow"></i>
    <div class="menu-info">
    <h4 class="control-sidebar-subheading">Frodo Updated His Profile</h4>
    <p>New phone +1(800)555-1234</p>
    </div>
    </a>
    </li>
    <li>
    <a href="javascript:void(0)">
    <i class="menu-icon fa fa-envelope-o bg-light-blue"></i>
    <div class="menu-info">
    <h4 class="control-sidebar-subheading">Nora Joined Mailing List</h4>
    <p><span class="__cf_email__" data-cfemail="7917160b18391c01181409151c571a1614">[email&#160;protected]</span></p>
    </div>
    </a>
    </li>
    <li>
    <a href="javascript:void(0)">
    <i class="menu-icon fa fa-file-code-o bg-green"></i>
    <div class="menu-info">
    <h4 class="control-sidebar-subheading">Cron Job 254 Executed</h4>
    <p>Execution time 5 seconds</p>
    </div>
    </a>
    </li>
    </ul>

    <h3 class="control-sidebar-heading">Tasks Progress</h3>
    <ul class="control-sidebar-menu">
    <li>
    <a href="javascript:void(0)">
    <h4 class="control-sidebar-subheading">
    Custom Template Design
    <span class="label label-danger pull-right">70%</span>
    </h4>
    <div class="progress progress-xxs">
    <div class="progress-bar progress-bar-danger" style="width: 70%"></div>
    </div>
    </a>
    </li>
    <li>
    <a href="javascript:void(0)">
    <h4 class="control-sidebar-subheading">
    Update Resume
    <span class="label label-success pull-right">95%</span>
    </h4>
    <div class="progress progress-xxs">
    <div class="progress-bar progress-bar-success" style="width: 95%"></div>
    </div>
    </a>
    </li>
    <li>
    <a href="javascript:void(0)">
    <h4 class="control-sidebar-subheading">
    Laravel Integration
    <span class="label label-warning pull-right">50%</span>
    </h4>
    <div class="progress progress-xxs">
    <div class="progress-bar progress-bar-warning" style="width: 50%"></div>
    </div>
    </a>
    </li>
    <li>
    <a href="javascript:void(0)">
    <h4 class="control-sidebar-subheading">
    Back End Framework
    <span class="label label-primary pull-right">68%</span>
    </h4>
    <div class="progress progress-xxs">
    <div class="progress-bar progress-bar-primary" style="width: 68%"></div>
    </div>
    </a>
    </li>
    </ul>

    </div>


    <div class="tab-pane" id="control-sidebar-stats-tab">Stats Tab Content</div>


    <div class="tab-pane" id="control-sidebar-settings-tab">
        <form method="post">
            <h3 class="control-sidebar-heading">General Settings</h3>
            <div class="form-group">
                <label class="control-sidebar-subheading">
                Report panel usage
                <input type="checkbox" class="pull-right" checked>
                </label>
                <p>
                Some information about this general settings option
                </p>
            </div>

            <div class="form-group">
                <label class="control-sidebar-subheading">
                    Allow mail redirect
                    <input type="checkbox" class="pull-right" checked>
                </label>
                <p>Other sets of options are available</p>
            </div>

            <div class="form-group">
                <label class="control-sidebar-subheading">
                    Expose author name in posts
                    <input type="checkbox" class="pull-right" checked>
                </label>
                <p>Allow the user to show his name in blog posts</p>
            </div>

            <h3 class="control-sidebar-heading">Chat Settings</h3>
            <div class="form-group">
                <label class="control-sidebar-subheading">
                Show me as online
                <input type="checkbox" class="pull-right" checked>
                </label>
            </div>

            <div class="form-group">
                <label class="control-sidebar-subheading">
                    Turn off notifications
                    <input type="checkbox" class="pull-right">
                </label>
            </div>

            <div class="form-group">
                <label class="control-sidebar-subheading">
                Delete chat history
                    <a href="javascript:void(0)" class="text-red pull-right"><i class="fa fa-trash-o"></i></a>
                </label>
            </div>

        </form>
    </div>

    </aside>

    <div class="control-sidebar-bg"></div>
    </div>

    </body>
</html>