<html>

	<head>
		<!-- You MUST include jQuery before Fomantic -->
		<script src="https://unpkg.com/jquery@3.3.1/dist/jquery.js"></script>
		<link rel="stylesheet" type="text/css" href="https://unpkg.com/fomantic-ui@2.8.8/dist/semantic.min.css">
		<script src="https://unpkg.com/fomantic-ui@2.8.8/dist/semantic.min.js"></script>
		<!--<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>-->
		<script src="https://cdn.jsdelivr.net/npm/chart.js/dist/chart.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>		
		<link rel="icon" type="image/x-icon" href="plant.png">
	</head>
	
	<?php
		// Create connection
		$conn = mysqli_connect("localhost", "root", "password", "greenhouse");
		// Check connection
		if (!$conn) {
			die("Connection failed: " . mysqli_connect_error());
		}
	?>

	<body style="padding: 15px">
		<div class="ui container">
			<div class="ui segment">
				<div class="ui grid">
					<div class="six wide column">
						<h1 class="ui header">
							<img src="plant.png">
							<div class="content">
								Greenhouse
								<div class="sub header"><?php echo date("D M j, Y g:i:s a"); ?></div>
							</div>
						</h1>
					</div>
					<div class="six wide column">
						<?php
							$sql = "select * from sensors";
							$result = mysqli_query($conn, $sql);
							if (mysqli_num_rows($result) > 0) {
								while($row = mysqli_fetch_assoc($result)) {
									$recents[$row['sensor_name']] = number_format(($row['recent_temp'] * 9.0/5.0) + 32.0, 1);
									if ($recents[$row['sensor_name']] < -100) { $recents[$row['sensor_name']] = "--"; }
									$recentdate = $row['recent_date'];
								}
							}
						?>
						<div class="ui two column very compact grid">
							<?php
								$ceiling = $recents['Ceiling'];
								if ($ceiling > 95) { $icon = "<i class='red temperature high icon'></i>"; $color = "#cc3333"; }
								elseif ($ceiling < 34) { $icon = "<i class='blue snowflake icon'></i>"; $color = "#3333bb"; }
								else { $icon = ""; $color = ""; }
							?>
							<div class="ui right aligned column">
								Ceiling
							</div>
							<div class="ui column" style="color: <?php echo $color; ?>">
								<b><?php echo $ceiling; ?> ºF</b> &nbsp; <?php echo $icon; ?>
							</div>

							<?php
								$bench = $recents['Bench'];
								if ($bench > 95) { $icon = "<i class='red temperature high icon'></i>"; $color = "#cc3333"; }
								elseif ($bench < 34) { $icon = "<i class='blue snowflake icon'></i>"; $color = "#3333bb"; }
								else { $icon = ""; $color = ""; }
							?>
							<div class="ui right aligned column">
								Bench
							</div>
							<div class="ui column" style="color: <?php echo $color; ?>">
								<b><?php echo $bench; ?> ºF</b> &nbsp; <?php echo $icon; ?>
							</div>

							<?php
								$floor = $recents['Floor'];
								if ($floor > 95) { $icon = "<i class='red temperature high icon'></i>"; $color = "#cc3333"; }
								elseif ($floor < 34) { $icon = "<i class='blue snowflake icon'></i>"; $color = "#3333bb"; }
								else { $icon = ""; $color = ""; }
							?>
							<div class="ui right aligned column">
								Floor
							</div>
							<div class="ui column" style="color: <?php echo $color; ?>">
								<b><?php echo $floor; ?> ºF</b> &nbsp; <?php echo $icon; ?>
							</div>

							<?php
								$waterbarrel = $recents['Water barrel'];
								if ($waterbarrel > 95) { $icon = "<i class='red temperature high icon'></i>"; $color = "#cc3333"; }
								elseif ($waterbarrel < 34) { $icon = "<i class='blue snowflake icon'></i>"; $color = "#3333bb"; }
								else { $icon = ""; $color = ""; }
							?>
							<div class="ui right aligned column">
								Water barrel
							</div>
							<div class="ui column" style="color: <?php echo $color; ?>">
								<b><?php echo $waterbarrel; ?> ºF</b> &nbsp; <?php echo $icon; ?>
							</div>

							<?php
								$outside = $recents['Outside'];
								if ($outside > 95) { $icon = "<i class='red temperature high icon'></i>"; $color = "#cc3333"; }
								elseif ($outside < 34) { $icon = "<i class='blue snowflake icon'></i>"; $color = "#3333bb"; }
								else { $icon = ""; $color = ""; }
							?>
							<div class="ui right aligned column">
								Outside
							</div>
							<div class="ui column" style="color: <?php echo $color; ?>">
								<b><?php echo $outside; ?> ºF</b> &nbsp; <?php echo $icon; ?>
							</div>
						</div>
					</div>
					<div class="four wide column">
						<b>System stats</b><br>
						CPU load <?php echo sys_getloadavg()[0]; ?><br>
						<?php $temp = shell_exec("cat /sys/class/thermal/thermal_zone0/temp") / 1000.0; ?>
						CPU temp <?php echo number_format($temp, 1) . "ºC"; ?>
					</div>
				</div>
			</div>
			<div class="ui segment">
			
			<canvas id="myChart" style="height:500px"></canvas>

			<?php

				$sql = "select sensor_id, temp_c, temp_time from temps where temp_time > date_add(now(), interval -1 day) order by temp_time asc";
				$result = mysqli_query($conn, $sql);

				if (mysqli_num_rows($result) > 0) {
					// output data of each row
					while($row = mysqli_fetch_assoc($result)) {
						$sensor = $row['sensor_id'];
						if ($sensor == 6)
							$temp = $row['temp_c'];
						else
							$temp = number_format(($row['temp_c'] * (9.0/5.0)) + 32.0, 1);
						$date = $row['temp_time'];
						
						if ($temp < -100) { $temp = "null"; }
						
						if ($sensor == "1") {
							$data1[] = "{x:'$date', y: $temp}";
						}
						elseif ($sensor == "2") {
							$data2[] = "{x:'$date', y: $temp}";
						}
						elseif ($sensor == "3") {
							$data3[] = "{x:'$date', y: $temp}";
						}
						elseif ($sensor == "4") {
							$data4[] = "{x:'$date', y: $temp}";
						}
						elseif ($sensor == "5") {
							$data5[] = "{x:'$date', y: $temp}";
						}
						elseif ($sensor == "6") {
							$data6[] = "{x:'$date', y: $temp}";
						}
					}
					$chartdata1 = implode(",", $data1);
					$chartdata2 = implode(",", $data2);
					$chartdata3 = implode(",", $data3);
					$chartdata4 = implode(",", $data4);
					$chartdata5 = implode(",", $data5);
					$chartdata6 = implode(",", $data6);
				}
				else {
					echo "0 results";
				}

				mysqli_close($conn);
			?>
				</table>
				
				<!-- create and display the chart -->
				<script>
					const ctx = document.getElementById('myChart').getContext('2d');
					const data = {
						datasets: [
							{
								label: 'Ceiling',
								borderColor: 'rgb(51, 102, 204)',
								backgroundColor: 'rgb(51, 102, 204)',
								borderWidth: 2,
								fill: false,
								data: [<?=$chartdata1?>],
								yAxisID: 'y'
							},
							{
								label: 'Bench',
								borderColor: 'rgb(0, 153, 51)',
								backgroundColor: 'rgb(0, 153, 51)',
								borderWidth: 2,
								fill: false,
								data: [<?=$chartdata2?>],
								yAxisID: 'y'
							},
							{
								label: 'Floor',
								borderColor: 'rgb(0,180,0)',
								backgroundColor: 'rgb(0,180,0)',
								borderWidth: 2,
								fill: false,
								data: [<?=$chartdata5?>],
								yAxisID: 'y'
							},
							{
								label: 'Water barrel',
								borderColor: 'rgb(0, 102, 255)',
								backgroundColor: 'rgb(0, 102, 255)',
								borderWidth: 2,
								fill: false,
								data: [<?=$chartdata3?>],
								yAxisID: 'y'
							},
							{
								label: 'Outside',
								borderColor: 'rgb(192, 192, 192)',
								backgroundColor: 'rgb(192, 192, 192)',
								borderWidth: 2,
								fill: false,
								data: [<?=$chartdata4?>],
								yAxisID: 'y'
							},
							{
								label: 'Light',
								borderColor: 'rgb(235, 235, 0)',
								backgroundColor: 'rgb(235, 235, 0)',
								borderWidth: 2,
								fill: false,
								data: [<?=$chartdata6?>],
								yAxisID: 'y1'
							}
						]
					}
					const myChart = new Chart(ctx, {
						type: 'line',
						data: data,
						options: {
							elements: {
								point:{
									radius: 0
								}
							},
							plugins: {
								decimation: {enabled: true, algorithm: 'lttb'},
								title: {
									display: true,
									text: 'Daily Temperatures',
								}
							},
							scales: {
								x: {
									type: 'time',
									time: {
										unit: 'hour'
									}
								},
								y: {
									title: {
										display: true,
										text: 'Temperature ºF'
									}
								},
								y1: {
									title: {
										display: true,
										text: 'Light, Lux'
									},
									position: 'right',
									grid: {
										drawOnChartArea: false, // only want the grid lines for one axis to show up
									}
								}
							}
						}
					});
				</script>				
			</div>
		</div>
	</body>
	
</html>