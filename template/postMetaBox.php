<?php
global $post;
$isDisabled = esc_attr(get_post_meta($post->ID, 'adquesto_disabled_in_the_post', true));
?>

<p><?php _e('post_metabox_option_hint', 'questo'); ?></p>
<p>
    <input type="radio" name="adquesto_disabled_in_the_post" class="post-format" id="adquesto_enabled_in_the_post"
           value="0" <?php echo(!$isDisabled ? 'checked="checked"' : '') ?>>
    <label for="adquesto_enabled_in_the_post">
        <?php _e('post_metabox_option_enabled_in_the_post', 'questo'); ?>
    </label>
</p>
<p>
    <input type="radio" name="adquesto_disabled_in_the_post" class="post-format" id="adquesto_disabled_in_the_post"
           value="1" <?php echo($isDisabled == '1' ? 'checked="checked"' : '') ?>>
    <label for="adquesto_disabled_in_the_post">
        <?php _e('post_metabox_option_disabled_in_the_post', 'questo'); ?>
    </label>
</p>