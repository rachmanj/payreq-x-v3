<script>
    $(function() {
        'use strict'

        var ticksStyle = {
            fontColor: '#6c757d',
            fontStyle: 'normal',
            fontFamily: "'Segoe UI', 'Helvetica Neue', Arial, sans-serif"
        }

        var mode = 'index'
        var intersect = false

        let payreqs = {!! json_encode($monthly_chart) !!}
        let months = payreqs.map(payreq => payreq.month_name)
        let amounts = payreqs.map(payreq => payreq.amount / 1000)

        var $monthlyChart = $('#monthly-chart')

        var monthlyChart = new Chart($monthlyChart, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Monthly Spending',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderColor: '#667eea',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: '#764ba2',
                    pointHoverBorderColor: '#fff',
                    fill: true,
                    tension: 0.4,
                    data: amounts
                }]
            },
            options: {
                maintainAspectRatio: true,
                responsive: true,
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                },
                tooltips: {
                    mode: mode,
                    intersect: intersect,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFontSize: 14,
                    bodyFontSize: 13,
                    xPadding: 12,
                    yPadding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            var value = tooltipItem.yLabel;
                            return ' Spending: Rp ' + value.toLocaleString('id-ID') + ' K';
                        }
                    }
                },
                hover: {
                    mode: mode,
                    intersect: intersect,
                    animationDuration: 400
                },
                legend: {
                    display: false
                },
                scales: {
                    yAxes: [{
                        gridLines: {
                            display: true,
                            color: 'rgba(0, 0, 0, .05)',
                            lineWidth: 1,
                            drawBorder: false,
                            zeroLineColor: 'rgba(0, 0, 0, .1)'
                        },
                        ticks: $.extend({
                            beginAtZero: true,
                            padding: 10,
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID') + 'K';
                            }
                        }, ticksStyle)
                    }],
                    xAxes: [{
                        display: true,
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: $.extend({
                            padding: 10
                        }, ticksStyle)
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

        var $activitiesChart = $('#activities-chart')
        var activitiesChart = new Chart($activitiesChart, {
            type: 'doughnut',
            data: {
                labels: username,
                datasets: [{
                    data: total_counts,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#4facfe',
                        '#43e97b',
                        '#fa709a'
                    ],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverBorderColor: '#fff',
                    hoverBorderWidth: 4
                }]
            },
            options: {
                maintainAspectRatio: true,
                responsive: true,
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 1000,
                    easing: 'easeInOutQuart'
                },
                tooltips: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFontSize: 14,
                    bodyFontSize: 13,
                    xPadding: 12,
                    yPadding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            var dataset = data.datasets[tooltipItem.datasetIndex];
                            var total = dataset.data.reduce(function(prev, current) {
                                return prev + current;
                            });
                            var currentValue = dataset.data[tooltipItem.index];
                            var percentage = Math.round((currentValue / total) * 100);
                            return ' ' + data.labels[tooltipItem.index] + ': ' + currentValue +
                                ' (' + percentage + '%)';
                        }
                    }
                },
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        boxWidth: 15,
                        fontColor: '#6c757d',
                        fontFamily: "'Segoe UI', 'Helvetica Neue', Arial, sans-serif",
                        fontSize: 12
                    }
                },
                cutoutPercentage: 65
            }
        })
    })
</script>
