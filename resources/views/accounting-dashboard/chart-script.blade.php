<script>
    $(function () {
        'use strict'
      
        var ticksStyle = {
          fontColor: '#495057',
          fontStyle: 'bold'
        }
      
        var mode = 'index'
        var intersect = true
    
        let outgoings = {!! json_encode($chart_outgoings) !!};
        var bulans = outgoings.map(function(obj) {
            return obj.month;
        });
        var monthNames = bulans.map(function(bulan) {
            var date = new Date(`2000-${bulan}-01`);
            return date.toLocaleString('default', { month: 'short' });
        });
        
        var amounts = outgoings.map(function(obj) {
            return obj.amount;
        });
    

        // OUTGOINGS CHART
        var $outgoingsChart = $('#outgoings-chart')
      // eslint-disable-next-line no-unused-vars
      var outgoingsChart = new Chart($outgoingsChart, {
        type: 'bar',
        data: {
          labels: monthNames,
        //   labels: ['JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'],
          datasets: [
            {
              backgroundColor: '#007bff',
              borderColor: '#007bff',
              data: amounts
            //   data: [1000, 2000, 3000, 2500, 2700, 2500, 3000, 2500, 1500, 2000, 2500, 3000]
            },
          ]
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
    
                // Include a dollar sign in the ticks
                callback: function (value) {
                  if (value >= 1000000) {
                    value /= 1000000
                    value += 'Jt'
                  }
    
                  return '' + value
                }
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

       //ACTIVITY CHART
       let activities = {!! json_encode($chart_activites) !!};
         var username = activities.map(function(obj) {
              return obj.user_id;
         });
         
         var total_counts = activities.map(function(obj) {
              return obj.total_count;
         });

         console.log(total_counts);


      var $activitiesChart = $('#activities-chart')
      // eslint-disable-next-line no-unused-vars
      var activitiesChart = new Chart($activitiesChart, {
        type: 'pie',
        data: {
          labels: username,
          datasets: [
            {
              data: total_counts,
              backgroundColor: ['#007bff', '#28a745', '#333333', '#c3e6cb', '#dc3545', '#6c757d'],
            }
          ]
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
                callback: function (value) {
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