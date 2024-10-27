<style>
    body {
        color: #444;
        font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;
        font-size: 13px;
        line-height: 1.4em;
        min-width: 600px;

        margin: .5em 0;
        padding: 2px;
    }
</style>
<?php $requirements = \Questo\Service\ConfigService::getRequirements(); ?>

<?php
$requirementsAreNotFulfilled = array();
foreach ($requirements as $name => $requirement) {
    if (!$requirement) {
        $requirementsAreNotFulfilled[] = $name;
    }
}

$numberOfRequirements = count($requirementsAreNotFulfilled);
?>

<?php if ($requirementsAreNotFulfilled): ?>
    <p>
        <?php _e('settings_requirements_info', 'questo') ?>
        <?php foreach ($requirementsAreNotFulfilled as $index => $name): ?>
            <?php echo $name ?><?php if (++$index < $numberOfRequirements): ?>,<?php endif ?>
        <?php endforeach; ?>
    </p>
<?php endif; ?>
