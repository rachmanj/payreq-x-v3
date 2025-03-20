<script>
    $(function() {
        'use strict'

        var ticksStyle = {
            fontColor: '#495057',
            fontStyle: 'bold'
        }

        var mode = 'index'
        var intersect = true

        let payreqs = {!! json_encode($monthly_chart) !!}
        let months = payreqs.map(payreq => payreq.month_name)
        let amounts = payreqs.map(payreq => payreq.amount / 1000)

        var $monthlyChart = $('#monthly-chart')

        var monthlyChart = new Chart($monthlyChart, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    backgroundColor: 'rgba(0, 123, 255, 0.2)',
                    borderColor: '#007bff',
                    pointRadius: 3,
                    pointColor: '#3b8bba',
                    pointStrokeColor: 'rgba(60,141,188,1)',
                    pointHighlightFill: '#fff',
                    pointHighlightStroke: 'rgba(60,141,188,1)',
                    fill: true,
                    tension: 0.3,
                    data: amounts
                }]
            },
            options: {
                maintainAspectRatio: false,
                tooltips: {
                    mode: mode,
                    intersect: intersect
                },
                hover: {
                    mode: mode,
                    intersect: intersect
                },
                legend: {
                    display: false
                },
                scales: {
                    yAxes: [{
                        // display: false,
                        gridLines: {
                            display: true,
                            lineWidth: '4px',
                            color: 'rgba(0, 0, 0, .2)',
                            zeroLineColor: 'transparent'
                        },
                        ticks: $.extend({
                            beginAtZero: true,
                            suggestedMax: 1000
                        }, ticksStyle)
                    }],
                    xAxes: [{
                        display: true,
                        gridLines: {
                            display: false
                        },
                        ticks: ticksStyle
                    }]
                }
            }
        })


        // activities chart
        let activities = {!! json_encode($chart_activites['activities']) !!};
        var username = activities.map(function(obj) {
            return obj.posted_name;
        });

        var total_counts = activities.map(function(obj) {
            return obj.total_count;
        });

        //  console.log(username);

        var $activitiesChart = $('#activities-chart')
        var activitiesChart = new Chart($activitiesChart, {
            type: 'pie',
            data: {
                labels: username,
                datasets: [{
                    data: total_counts,
                    backgroundColor: ['#007bff', '#28a745', '#333333', '#c3e6cb', '#dc3545',
                        '#6c757d'
                    ],
                }]
            },
            options: {
                maintainAspectRatio: false,
                tooltips: {
                    mode: mode,
                    intersect: intersect
                },
                hover: {
                    mode: mode,
                    intersect: intersect
                },
                legend: {
                    display: true
                },
                scales: {
                    yAxes: [{
                        display: false,
                        gridLines: {
                            display: true,
                            lineWidth: '4px',
                            color: 'rgba(0, 0, 0, .2)',
                            zeroLineColor: 'transparent'
                        },
                        ticks: $.extend({
                            beginAtZero: true,

                            // Include a dollar sign in the ticks
                            callback: function(value) {
                                if (value >= 1000000) {
                                    value /= 1000000
                                    value += 'Jt'
                                }

                                return '' + value
                            }
                        }, ticksStyle)
                    }],
                    xAxes: [{
                        display: false,
                        gridLines: {
                            display: false
                        },
                        ticks: ticksStyle
                    }]
                }
            }
        })



    })
</script>
