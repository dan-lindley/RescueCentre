<canvas id="admissionschart"></canvas>

<script>
	new Chart(document.getElementById("admissionschart"), {
		type : 'line',
		data : {
			labels : [ "Jan", "Feb", "Mar", "Apr", "May", "Jun",
					"Jul", "Aug", "Sept", "Oct", "Nov", "Dec" ],
			datasets : [
//START DATA SECTION - 2023
					    {
						data : [ 
                            <?php //Get by month count for 2023
                            $stmt = $conn->prepare("SELECT
  											        MONTHNAME(m.month) MONTH_NAME,
  											        COUNT(a.admission_id)COUNT_ADMISSIONS23
													FROM rescue_month_data AS m
												    LEFT JOIN rescue_admissions AS a
      											ON EXTRACT(YEAR_MONTH FROM m.month) = EXTRACT(YEAR_MONTH FROM a.admission_date)     
      											AND a.centre_id = :centre_id
												WHERE
   												YEAR(m.month)=2023
												GROUP BY
   													MONTH(m.month)
												ORDER BY
  													MONTH(m.month)");
					        $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
                            // initialise an array for the results
                            $months = array();
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $count23 = $row["COUNT_ADMISSIONS23"];
                            print '"' . $count23 . '"	,'; } ?>
                         ],
						label : "2023",
						borderColor : "#3cba9f",
						fill : false,
                        tension : 0.4
					    },
//START DATA SECTION - 2024
					    {
						data : [ 
                            <?php //Get by month count for 2023
                            $stmt = $conn->prepare("SELECT
  											        MONTHNAME(m.month) MONTH_NAME,
  											        COUNT(a.admission_id)COUNT_ADMISSIONS24
													FROM rescue_month_data AS m
												    LEFT JOIN rescue_admissions AS a
      											ON EXTRACT(YEAR_MONTH FROM m.month) = EXTRACT(YEAR_MONTH FROM a.admission_date)     
      											AND a.centre_id = :centre_id
												WHERE
   												YEAR(m.month)=2024
												GROUP BY
   													MONTH(m.month)
												ORDER BY
  													MONTH(m.month)");
					        $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
                            // initialise an array for the results
                            $months = array();
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $count24 = $row["COUNT_ADMISSIONS24"];
                            print '"' . $count24 . '"	,'; } ?>
                         ],
						label : "2024",
						borderColor : "#e7e42bff",
						fill : false,
            tension: 0.4
					    },
//START DATA SECTION - 2025
					    {
						data : [ 
                            <?php //Get by month count for 2023
                            $stmt = $conn->prepare("SELECT
  											        MONTHNAME(m.month) MONTH_NAME,
  											        COUNT(a.admission_id)COUNT_ADMISSIONS25
													FROM rescue_month_data AS m
												    LEFT JOIN rescue_admissions AS a
      											ON EXTRACT(YEAR_MONTH FROM m.month) = EXTRACT(YEAR_MONTH FROM a.admission_date)     
      											AND a.centre_id = :centre_id
												WHERE
   												YEAR(m.month)=2025
												GROUP BY
   													MONTH(m.month)
												ORDER BY
  													MONTH(m.month)");
					        $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
                            // initialise an array for the results
                            $months = array();
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $count25 = $row["COUNT_ADMISSIONS25"];
                            print '"' . $count25 . '"	,'; } ?>
                         ],
						label : "2025",
						borderColor : "#14d814ff",
						fill : false,
                        tension : 0.4  
					    }
                    ] 
                    },
		options : {
			title : {
				display : true,
				text : 'Chart JS Line Chart Example'
			}
		}
	});
</script>