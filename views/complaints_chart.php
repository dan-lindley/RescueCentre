<div>
  <canvas id="complaintsChart"></canvas>
</div>

<script>
  const ctx = document.getElementById('complaintsChart');
  const complaintsChartIsDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const complaintsChartText = complaintsChartIsDark ? '#eaf6f4' : '#162334';
  const complaintsChartMuted = complaintsChartIsDark ? '#9db0b5' : '#858796';
  const complaintsChartGrid = complaintsChartIsDark ? 'rgba(148, 163, 184, .18)' : 'rgba(226, 232, 240, .9)';

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: [<?php
                    //Get compalint labels
                    $stmt = $pdo->prepare("SELECT presenting_complaint, COUNT(presenting_complaint) AS total_complaint
											FROM rescue_admissions
											INNER JOIN rescue_patients
											ON rescue_admissions.patient_id = rescue_patients.patient_id
											WHERE rescue_admissions.centre_id = :centre_id
											GROUP BY presenting_complaint
											ORDER BY presenting_complaint
											");
					$stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
                    // initialise an array for the results
                    $months = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $complaint = $row["presenting_complaint"];
                         print '"' . $complaint . '"	,';
                    }?>],
      datasets: [{
        label: <?php echo json_encode(($lang['TOTAL'] ?? 'Total') . ' ' . ($lang['ADMISSIONS'] ?? 'Admissions')); ?>,
        backgroundColor: ['#5AAb16', '#FBDb25', '#0f4c5c', '#F5a701', '#E34d36', '#A71c5d', '#2e546c', '#4da67c', '#77e44c', '#caff00'  ],
        hoverBackgroundColor: "#2e59d9",
        borderColor: "#4e73df",
        data: [
           <?php //Get by complaint count
                    $stmt = $pdo->prepare("SELECT presenting_complaint, COUNT(presenting_complaint) AS total_complaint
											FROM rescue_admissions
											INNER JOIN rescue_patients
											ON rescue_admissions.patient_id = rescue_patients.patient_id
											WHERE rescue_admissions.centre_id = :centre_id
											GROUP BY presenting_complaint
											ORDER BY presenting_complaint
											");
					
                   $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
                    // initialise an array for the results
                    $months = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $complaint_count = $row["total_complaint"];
                         print '"' . $complaint_count . '"	,';
                    } ?>
        ],
        borderWidth: 1
      }]
    },
    options: {
    maintainAspectRatio: true,
    layout: {
      padding: {
        left: 10,
        right: 25,
        top: 25,
        bottom: 0
      }
    },

    legend: {
      display: false,
      labels: {
        fontColor: complaintsChartText
      }
    },
    tooltips: {
      titleMarginBottom: 10,
      titleFontColor: complaintsChartText,
      titleFontSize: 14,
      backgroundColor: complaintsChartIsDark ? '#162527' : "rgb(255,255,255)",
      bodyFontColor: complaintsChartMuted,
      borderColor: complaintsChartIsDark ? 'rgba(148, 163, 184, .18)' : '#dddfeb',
      borderWidth: 1,
      xPadding: 15,
      yPadding: 15,
      displayColors: false,
      caretPadding: 10,
      callbacks: {
        label: function(tooltipItem, chart) {
          var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
          return datasetLabel + ': ' + number_format(tooltipItem.yLabel);
        }
      }
    },
    plugins: {
      legend: {
        labels: {
          color: complaintsChartText
        }
      },
      tooltip: {
        backgroundColor: complaintsChartIsDark ? '#162527' : '#ffffff',
        titleColor: complaintsChartText,
        bodyColor: complaintsChartMuted,
        borderColor: complaintsChartIsDark ? 'rgba(148, 163, 184, .18)' : '#dddfeb',
        borderWidth: 1
      }
    },
    scales: {
      xAxes: [{
        ticks: { fontColor: complaintsChartMuted },
        gridLines: { color: complaintsChartGrid }
      }],
      yAxes: [{
        ticks: { fontColor: complaintsChartMuted, precision: 0 },
        gridLines: { color: complaintsChartGrid }
      }],
      x: {
        ticks: { color: complaintsChartMuted },
        grid: { color: complaintsChartGrid }
      },
      y: {
        beginAtZero: true,
        ticks: { color: complaintsChartMuted, precision: 0 },
        grid: { color: complaintsChartGrid }
      }
    },
  }
  });
</script>
