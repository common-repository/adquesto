<?php

namespace Questo\Service;

class AdminService extends BaseService
{
    /**
     * Route for settings in admin panel
     */
    const QUESTO_SETTINGS_ROUTE = 'questo_settings';

    /**
     * @var ContentService
     */
    private $contentService;

    /**
     * @return ContentService
     */
    public function getContentService()
    {
        return $this->contentService;
    }

    /**
     * @param ContentService $contentService
     * @return BaseService
     */
    public function setContentService($contentService)
    {
        $this->contentService = $contentService;
        return $this;
    }

    /**
     * Init wordpress functions
     */
    public function __construct()
    {
        // Display the admin notification
        add_action('admin_menu', array($this, 'createAdminMenu'));
        add_action('admin_init', array($this, 'setupTinyMCEPlugin'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_enqueue_scripts', array($this, 'registerEnqueueScripts'));
        add_action('load-post.php', array($this, 'setupMetaBoxes'));
        add_action('load-post-new.php', array($this, 'setupMetaBoxes'));

        if (get_option(ConfigService::OPTION_NEWLY_ADDED_CATEGORIES)) {
            add_action('create_category', array($this, 'createCategory'), 10, 2);
        }

        add_action('delete_category', array($this, 'deleteCategory'), 10, 2);

        add_action('enqueue_block_editor_assets', array($this, 'setupGutenbergEditorAssets'));
    }

    /**
     * @param array $links
     * @return array
     */
    public function addSettingsLink($links)
    {
        $link = '<a href="options-general.php?page=%s">%s</a>';
        array_unshift($links, sprintf($link, self::QUESTO_SETTINGS_ROUTE, __('Settings')));
        return $links;
    }

    /**
     * Create anchor in admin panel
     */
    public function createAdminMenu()
    {
        //create new top-level menu
        add_menu_page(
            'Adquesto',
            'Adquesto',
            'administrator',
            self::QUESTO_SETTINGS_ROUTE,
            array($this, 'settingsPage')
        );

        wp_enqueue_style('questo-menu', $this->getConfigService()->getCssUrl('questo-menu.css'), array(), ConfigService::VERSION);
    }

    /**
     * Create our additional options
     *
     * @param string $hook
     */
    public function registerEnqueueScripts($hook)
    {
        if ($hook != 'toplevel_page_questo_settings') {
            return;
        }
        $configService = $this->getConfigService();
        wp_enqueue_style('questo', $configService->getCssUrl('questo.css'), array(), ConfigService::VERSION);
        wp_enqueue_script('questo', $configService->getJavascriptUrl('questo.js'), array(), ConfigService::VERSION);
    }

    /**
     * After update token
     */
    public function updateOptionToken()
    {
        LoggerService::activation(ConfigService::getWordpressConfiguration());
        $this->contentService->updateJavascript();
    }

    /**
     * Create our additional options
     */
    public function registerSettings()
    {
        add_action('update_option_' . ConfigService::OPTION_TOKEN, array($this, 'updateOptionToken'), 10, 2);
        register_setting('questo_settings_group', ConfigService::OPTION_TOKEN);
        register_setting('questo_settings_group', ConfigService::OPTION_OAUTH_CLIENT_ID);
        register_setting('questo_settings_group', ConfigService::OPTION_OAUTH_SECRET);
        register_setting('questo_settings_group', ConfigService::OPTION_DISPLAY_SETTINGS);
        register_setting('questo_settings_group', ConfigService::OPTION_NEWLY_ADDED_CATEGORIES);
        register_setting('questo_settings_group', ConfigService::OPTION_HAS_ACTIVE_CAMPAIGNS);
        register_setting('questo_settings_group', ConfigService::OPTION_MASTER_SWITCH);
        register_setting('questo_settings_group', ConfigService::OPTION_POSITIONING);
        register_setting('questo_settings_group', ConfigService::OPTION_HIDE_FOR_USERS);
    }

    /**
     * Our settings template
     */
    public function settingsPage()
    {
        include $this->getConfigService()->getTemplatePath('settings.php');
    }

    /**
     * setup tinyMCE plugin
     */
    public function setupTinyMCEPlugin()
    {
        if (current_user_can('edit_posts') && current_user_can('edit_pages') && get_user_option('rich_editing') === 'true') {
            add_filter('mce_external_plugins', array($this, 'addTinyMCEPlugin'));
            add_filter('mce_css', array($this, 'addTinyMCEPluginCss'));
            add_filter('mce_css', array($this, 'addTinyMCEPluginCssLocale'));
            add_filter('mce_buttons', array($this, 'addTinyMCEToolbarButton'));
        }
    }

    /**
     * @param array $plugins array of registered TinyMCE Plugins
     *
     * @return array Modified array of registered TinyMCE Plugins
     */
    public function addTinyMCEPlugin($plugins)
    {
        $plugins['questoButton'] = $this->getConfigService()->getJavascriptUrl('questo-tinymce.js?v=' . ConfigService::VERSION);

        return $plugins;
    }

    /**
     * @param string $css
     *
     * @return string Modified string of registered TinyMCE Plugins
     */
    public function addTinyMCEPluginCss($css)
    {
        if (!empty($css)) {
            $css .= ',';
        }

        $css .= $this->getConfigService()->getCssUrl('questo-editor.css?v=' . ConfigService::VERSION);

        return $css;
    }

    /**
     * @param string $css
     *
     * @return string Modified string of registered TinyMCE Plugins
     */
    public function addTinyMCEPluginCssLocale($css)
    {
        if (!empty($css)) {
            $css .= ',';
        }

        $css .= $this->getConfigService()->getCssUrl(
            'questo-editor-' . (get_locale() == 'pl_PL' ? 'pl' : 'en') . '.css?v=' . ConfigService::VERSION
        );

        return $css;
    }

    /**
     * @param array $buttons array of registered TinyMCE Buttons
     *
     * @return array Modified array of registered TinyMCE Buttons
     */
    public function addTinyMCEToolbarButton($buttons)
    {
        array_push($buttons, '|', 'questoButton');

        return $buttons;
    }

    /**
     * Include a template for meta box
     */
    public function postDisabledMetaBox()
    {
        include $this->getConfigService()->getTemplatePath('postMetaBox.php');
    }

    /**
     * Create meta boxes to be displayed on the post editor screen
     */
    public function addPostMetaBoxes()
    {
        add_meta_box(
            'adquesto-disabled-in-the-post',
            'Adquesto',
            array($this, 'postDisabledMetaBox'),
            null,
            'side',
            'default'
        );
    }

    /**
     * Meta box setup function
     */
    public function setupMetaBoxes()
    {
        add_action('add_meta_boxes', array($this, 'addPostMetaBoxes'));
        add_action('save_post', array($this, 'savePostMetaBox'), 10, 2);
    }

    /**
     * Save meta box settings for the post
     *
     * @param int    $postId
     * @param object $post
     * @return mixed
     */
    public function savePostMetaBox($postId, $post)
    {
        if (!isset($_POST['adquesto_disabled_in_the_post'])) {
            return;
        }

        $postType = get_post_type_object($post->post_type);

        if (!current_user_can($postType->cap->edit_post, $postId)) {
            return;
        }

        if ($_POST['adquesto_disabled_in_the_post']) {
            update_post_meta($postId, 'adquesto_disabled_in_the_post', 1);
        } else {
            delete_post_meta($postId, 'adquesto_disabled_in_the_post');
        }

        return;
    }

    /**
     * @param array $data
     * @param int   $parent
     * @return array
     */
    public static function getNestedTaxonomy(array $data, $parent = 0)
    {
        $branch = array();

        foreach ($data as $row) {
            if ($row->category_parent == $parent) {
                $row->children = self::getNestedTaxonomy($data, $row->term_id);
                $branch[] = $row;
            }
        }

        return $branch;
    }

    /**
     * @param array $items
     * @param int   $level
     */
    public static function showCheckboxes($items, $level = 0)
    {
        foreach ($items as $item) {
            if ($item->parent == 0) {
                $level = 0;
            }
            echo '<div><label><input value="' . $item->term_id . '" type="checkbox" data-name="' . $item->taxonomy . '" class="checkbox-level-' . $level . '">' . $item->name . '</label></div>';
            if ($item->children) {
                self::showCheckboxes($item->children, ++$level);
            }
        }
    }

    public function forceUpdateDisplaySettings()
    {
        $displaySettings = array(
            'postTypes'  => array('post'),
            'categories' => array(),
        );

        foreach (get_categories(array('hide_empty' => false)) as $category) {
            $displaySettings['categories'][] = $category->term_id;
        }

        update_option(ConfigService::OPTION_DISPLAY_SETTINGS, json_encode($displaySettings));
    }

    /**
     * @param int $termId
     */
    public function createCategory($termId)
    {
        $this->addDisplaySettings($termId, 'categories');
    }

    /**
     * @param int $termId
     */
    public function deleteCategory($termId)
    {
        $this->deleteDisplaySettings($termId, 'categories');
    }

    /**
     * @param int    $id
     * @param string $type
     */
    public function addDisplaySettings($id, $type)
    {
        $displaySettings = json_decode(get_option(ConfigService::OPTION_DISPLAY_SETTINGS), true);
        if (isset($displaySettings[$type])) {
            $displaySettings[$type][] = $id;
        }
        update_option(ConfigService::OPTION_DISPLAY_SETTINGS, json_encode($displaySettings));
    }

    /**
     * @param int    $idToRemove
     * @param string $type
     */
    public function deleteDisplaySettings($idToRemove, $type)
    {
        $displaySettings = json_decode(get_option(ConfigService::OPTION_DISPLAY_SETTINGS), true);

        if (isset($displaySettings[$type])) {
            foreach ($displaySettings[$type] as $key => $id) {
                if ($id == $idToRemove) {
                    unset($displaySettings[$type][$key]);
                    $displaySettings[$type] = array_values($displaySettings[$type]);
                }
            }
        }

        update_option(ConfigService::OPTION_DISPLAY_SETTINGS, json_encode($displaySettings));
    }

    /**
     * @return array
     */
    public static function getDisabledPostTypes()
    {
        return array(
            'attachment',
            'revision',
            'nav_menu_item',
            'custom_css',
            'customize_changeset',
            'oembed_cache',
        );
    }

    /**
     * Enqueue the block's assets for the editor
     */
    public function setupGutenbergEditorAssets()
    {
        wp_enqueue_script(
            'questo-gutenberg',
            $this->getConfigService()->getJavascriptUrl('questo-gutenberg.js'),
            array('wp-blocks', 'wp-i18n', 'wp-element'),
            ConfigService::VERSION
        );

        wp_enqueue_style(
            'questo-gutenberg',
            $this->getConfigService()->getCssUrl('questo-editor.css'),
            array('wp-edit-blocks'),
            ConfigService::VERSION
        );

        $questoTinyMceLocale = 'questo-editor-' . (get_locale() == 'pl_PL' ? 'pl' : 'en');
        wp_enqueue_style(
            $questoTinyMceLocale,
            $this->getConfigService()->getCssUrl($questoTinyMceLocale . '.css'),
            array('wp-edit-blocks'),
            ConfigService::VERSION
        );
    }
}
