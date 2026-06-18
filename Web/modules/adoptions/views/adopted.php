<?php
// modules/adoptions/views/adopted.php
if (!defined('APP_LOADED')) exit;

require_once dirname(__DIR__) . '/controllers/adoptions_lib.php';

$adoptionsCentreId = adoptions_centre_id();
$adoptionCounts = adoptions_count_by_disposition($pdo, $adoptionsCentreId);
$adoptionPatients = adoptions_fetch_patients($pdo, 'Adopted', $adoptionsCentreId);
?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" aria-hidden="true"><path d="M320 96C260.6 96 213.3 139.5 205.7 197.3C202.2 197 198.6 196.8 195 196.8C133.3 196.8 83.2 246.9 83.2 308.6C83.2 370.3 133.3 420.4 195 420.4L227.5 420.4L227.5 512C227.5 529.7 241.8 544 259.5 544L380.5 544C398.2 544 412.5 529.7 412.5 512L412.5 420.4L445 420.4C506.7 420.4 556.8 370.3 556.8 308.6C556.8 246.9 506.7 196.8 445 196.8C441.4 196.8 437.8 197 434.3 197.3C426.7 139.5 379.4 96 320 96zM274.4 229.2C286.9 216.7 307.2 216.7 319.7 229.2L320 229.5L320.3 229.2C332.8 216.7 353.1 216.7 365.6 229.2C378.1 241.7 378.1 262 365.6 274.5L320 320L274.4 274.5C261.9 262 261.9 241.7 274.4 229.2z"/></svg>
        </div>
        <div class="txt">
            <h2>Adoptions</h2>
            <p>Patients recorded as adopted.</p>
        </div>
    </div>
</div>

<nav class="rc-tabs rc-tabs-pill" aria-label="Adoption views">
    <a class="rc-tab" href="module.php?module=adoptions&view=awaiting">Awaiting Adoption (<?= (int)$adoptionCounts['For Adoption'] ?>)</a>
    <a class="rc-tab is-active" href="module.php?module=adoptions&view=adopted">Adopted (<?= (int)$adoptionCounts['Adopted'] ?>)</a>
</nav>

<div class="rc-card">
    <h3>Adopted</h3>
    <?php adoptions_render_patient_table($adoptionPatients, 'No patients are currently recorded as adopted.'); ?>
</div>
