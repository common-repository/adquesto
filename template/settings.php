<link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet">
<div class="questo-settings">
    <img class="questo-big-logo"
         src="<?php echo \Questo\Base::getInstance()->configService->getImageUrl('big-logo.png'); ?>"
         alt="Adquesto">
    <?php use \Questo\Service\ConfigService; ?>
    <?php use \Questo\Service\AdminService; ?>
    <div>
        <form method="post" action="options.php" onchange="formchange()" onsubmit="formsubmitting()">
            <?php $disabledRequirements = ConfigService::getDisabledRequirements(); ?>
            <?php if ($disabledRequirements): ?>
                <div class="questo-requirements">
                    <h1><?php _e('settings_requirements', 'questo') ?></h1>
                    <p><?php _e('settings_requirements_info', 'questo') ?></p>
                    <p><?php foreach ($disabledRequirements as $name => $enabled): ?><?php echo $name ?>, <?php endforeach; ?></p>
                </div>
            <?php endif ?>

            <?php settings_fields('questo_settings_group'); ?>
            <?php do_settings_sections('questo_settings_group'); ?>

            <div class="questo-row">
                <h1><?php _e('settings_display_header_master_switch', 'questo') ?></h1>
                <div class="questo-display-settings">
                    <input type="hidden" name="questo_master_switch" value="0">
                    <input type="checkbox" class="ios8-switch" id="checkbox-master-switch" value="1"
                           name="questo_master_switch"
                        <?php echo \Questo\Service\ContentService::isMasterSwitchEnabled() ? 'checked="checked"' : '' ?>
                    >
                    <label for="checkbox-master-switch">
                        <span id="master-switch-on">
                            <span class="adquesto-master-switch off"><?php _e('settings_master_switch_is_off', 'questo') ?></span>
                            <?php _e('settings_master_switch_is_off_hint', 'questo') ?>
                        </span>
                        <span id="master-switch-off">
                            <span class="adquesto-master-switch on"><?php _e('settings_master_switch_is_on', 'questo') ?></span>
                            <?php _e('settings_master_switch_is_on_hint', 'questo') ?>
                        </span>
                    </label>
                </div>
            </div>

            <div class="questo-row questo-display-settings-container">
                <h1><?php _e('settings_display_header', 'questo') ?></h1>
                <p><?php _e('settings_display_info', 'questo') ?></p>
                <?php $options = array('hide_empty' => false, 'orderby' => 'title', 'order' => 'ASC'); ?>
                <div class="questo-display-settings">
                    <div class="questo-pull-left">
                        <h2><?php _e('settings_display_post_types', 'questo') ?></h2>
                        <?php
                        $postTypes = get_post_types();
                        $preferedPostTypes = array('post', 'page');

                        $postTypes = array_diff($postTypes, $preferedPostTypes);
                        $postTypes = array_diff($postTypes, AdminService::getDisabledPostTypes());
                        sort($preferedPostTypes);

                        foreach ($preferedPostTypes as $preferedPostType) {
                            array_unshift($postTypes, $preferedPostType);
                        }


                        $gapAdded = false;
                        ?>
                        <?php foreach ($postTypes as $type): ?>
                            <?php if (!$gapAdded && !in_array($type, $preferedPostTypes)): ?>
                                <br>
                                <?php $gapAdded = true ?>
                            <?php endif ?>
                            <div><label><input value="<?php echo $type ?>" type="checkbox"
                                               data-name="post_type"><?php echo $type ?></label></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="questo-pull-left">
                        <h2><?php _e('settings_display_categories', 'questo') ?></h2>
                        <?php AdminService::showCheckboxes(AdminService::getNestedTaxonomy(get_categories($options))); ?>
                    </div>
                </div>
                <div class="questo-categories-options-container">
                    <div class="questo-newly-added-categories">
                        <div>
                            <label for="questo_newly_added_categories">
                                <input name="questo_newly_added_categories" id="questo_newly_added_categories" value="1"
                                    <?php echo get_option(\Questo\Service\ConfigService::OPTION_NEWLY_ADDED_CATEGORIES) ? 'checked="checked"' : '' ?>
                                       type="checkbox">
                                <?php _e('settings_newly_added_categories', 'questo') ?>
                            </label>
                        </div>
                        <div>
                            <label for="questo_display_for_users">
                                <input name="questo_display_for_users" id="questo_display_for_users"" value="1"
                                    <?php echo get_option(\Questo\Service\ConfigService::OPTION_HIDE_FOR_USERS) ? 'checked="checked"' : '' ?>
                                       type="checkbox">
                                <?php _e('settings_display_for_users_hint', 'questo') ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="questo-row">
                <h1><?php _e('settings_positioning_header', 'questo') ?></h1>
                <p>
                    <?php _e('settings_positioning_subheader', 'questo') ?>
                </p>
                <div class="questo-display-settings">
                    <div class="questo-display-option">
                        <input type="radio" id="questo-upper" name="questo_positioning" value="upper"
                            <?php echo get_option(\Questo\Service\ConfigService::OPTION_POSITIONING) == 'upper' ? 'checked' : null; ?>>
                        <label for="questo-upper"><?php _e('settings_positioning_upper', 'questo') ?></label>
                        <div class="questo-display-hint">
                            <?php _e('settings_positioning_upper_hint', 'questo') ?>
                        </div>
                    </div>
                </div>
                <div class="questo-display-settings">
                    <div class="questo-display-option">
                        <input type="radio" id="questo-lower" name="questo_positioning" value="lower"
                            <?php echo get_option(\Questo\Service\ConfigService::OPTION_POSITIONING) == 'lower' ? 'checked' : null; ?>>
                        <label for="questo-lower"><?php _e('settings_positioning_lower', 'questo') ?></label>
                        <div class="questo-display-hint">
                            <?php _e('settings_positioning_lower_hint', 'questo') ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="questo-row">
                <h1><?php _e('settings_configuration_header', 'questo') ?></h1>
                <p><?php _e('settings_configuration_info', 'questo') ?> <a href="https://system.adquesto.com/publisher"
                                                                           target="_blank"><?php _e('settings_configuration_link_text', 'questo') ?></a>.
                </p>
                <div class="questo-standard-form">
                    <div>
                        <label for="questo_token">
                            <img class="questo-key-icon"
                                 src="<?php echo \Questo\Base::getInstance()->configService->getImageUrl('key-icon.svg'); ?>">
                            Token
                        </label>
                        <input type="text" id="questo_token" name="questo_token"
                               value="<?php echo esc_attr(get_option(\Questo\Service\ConfigService::OPTION_TOKEN)); ?>"/>
                    </div>
                    <div>
                        <label for="questo_oauth_client_id">
                            <img class="questo-key-icon"
                                 src="<?php echo \Questo\Base::getInstance()->configService->getImageUrl('key-icon.svg'); ?>">
                            OAuth Client
                        </label>
                        <input type="text" id="questo_oauth_client_id" name="questo_oauth_client_id"
                               value="<?php echo esc_attr(get_option(\Questo\Service\ConfigService::OPTION_OAUTH_CLIENT_ID)); ?>"/>
                    </div>
                    <div>
                        <label for="questo_oauth_secret">
                            <img class="questo-key-icon"
                                 src="<?php echo \Questo\Base::getInstance()->configService->getImageUrl('key-icon.svg'); ?>">
                            OAuth Secret
                        </label>
                        <input type="text" id="questo_oauth_secret" name="questo_oauth_secret"
                               value="<?php echo esc_attr(get_option(\Questo\Service\ConfigService::OPTION_OAUTH_SECRET)); ?>"/>
                    </div>
                </div>
            </div>
            <input type="hidden" name="questo_display_settings"
                   value="<?php echo esc_attr(get_option(\Questo\Service\ConfigService::OPTION_DISPLAY_SETTINGS)); ?>">

            <div class="questo-footer">
                <input type="submit" name="submit" id="submit" value="<?php _e('settings_button_save', 'questo') ?>">
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    var masterSwitchCheckbox = document.getElementById('checkbox-master-switch');
    masterSwitchCheckbox.addEventListener('change', function(e) {
        var on = document.getElementById('master-switch-on'),
            off = document.getElementById('master-switch-off');
        on.style.display = 'none';
        off.style.display = 'none';
        document.getElementById('master-switch-' + (!e.target.checked ? 'on' : 'off')).style.display = 'inline-block';
    });
    masterSwitchCheckbox.dispatchEvent(new Event('change'));

    var formSaved = true;

    function formchange() {
        formSaved = false;
    }
    function formsubmitting() {
        formSaved = true;
    }

    var myEvent = window.attachEvent || window.addEventListener;
    var chkevent = window.attachEvent ? 'onbeforeunload' : 'beforeunload'; /// make IE7, IE8 compitable

    myEvent(chkevent, function(e) { // For >=IE7, Chrome, Firefox
        if (!formSaved) {
            var confirmationMessage = 'Are you sure to leave the page?';
            (e || window.event).returnValue = confirmationMessage;
            return confirmationMessage;
        }
    });
</script>