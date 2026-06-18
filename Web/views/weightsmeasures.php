    <div class="xform">

        <div class="xform-grid">

            <!-- COLUMN 1 -->
            <div class="xform-field span-2">
                <div class="rc-card rc-card-muted">
                 <?php include ('views/weights_chart.php'); ?> 
                <h5>Add Weight</h5><br>
                <?php include ('controllers/add_weight.php'); ?>
                </div>
            </div>

            <!-- COLUMN 2 -->
            <div class="xform-field span-2">
                <div class="rc-card rc-card-muted">
                <?php include ('views/measurements_chart.php'); ?> 
                <h5>Add Measurement</h5><br>
                <?php include ('controllers/add_measurement.php'); ?>
                </div>
            </div>

        </div>

    </div>
