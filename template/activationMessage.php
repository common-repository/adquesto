<?php $requirements = \Questo\Service\ConfigService::getRequirements(); ?>

<?php
$requirementsAreNotFulfilled = array();
foreach ($requirements as $name => $requirement) {
    if (!$requirement) {
        $requirementsAreNotFulfilled[] = $name;
    }
}
?>

<?php if ($requirementsAreNotFulfilled): ?>
    <div class="error">
        <p><?php _e('settings_requirements_info', 'questo') ?></p>
        <ul>
            <?php foreach ($requirementsAreNotFulfilled as $name): ?>
                <li><?php echo $name ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
