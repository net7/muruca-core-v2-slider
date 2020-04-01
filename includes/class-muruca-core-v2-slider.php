<?php


/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://netseven.it
 * @since      1.0.0
 *
 * @package    Muruca_Core_V2_Slider
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Muruca_Core_V2_Slider
 * @author     Netseven <info@netseven.it>
 */
class Muruca_Core_V2_Slider
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */

    const ACF_PREFIX =  'field_mrc_';

    private $slide_post_type;

    public function __construct()
    {
    }

    public function run(){

        add_action("init", array($this, "register_slides"));
        add_action('rest_api_init', array($this, 'register_rest_route'));
        add_filter('use_block_editor_for_post_type', array($this, 'disable_gutenberg'),  10, 2);
        add_filter('tiny_mce_before_init', array($this, "tiny_editor_settings"), 10, 2);
        add_filter('wp_editor_settings', array($this, "editor_settings"), 10, 2);
    }

    public function register_slides()
    {
        $this->slide_post_type = MURUCA_CORE_PREFIX . "_slide";
        $args = array(
            'public' => true,
            'labels' =>  array(
                'name'               => _x('Slides', 'post type general name', MURUCA_CORE_TEXTDOMAIN),
                'singular_name'      => _x('Slide', 'post type singular name', MURUCA_CORE_TEXTDOMAIN),
                'menu_name'             => _x('Muruca Slider', 'Admin Menu text', MURUCA_CORE_TEXTDOMAIN),
                'add_new_item'          => __('Add New Slide', MURUCA_CORE_TEXTDOMAIN),
                'add_new_item'          => __('New Slide', MURUCA_CORE_TEXTDOMAIN),
            ),
            'menu_icon' => 'dashicons-pressthis',
            'show_in_rest' => true,
            "menu_position" => 4,
            'show_in_menu' => true,
            'supports' => ["title", "editor"],
            'menu_icon' => 'dashicons-images-alt2'
        );
        register_post_type($this->slide_post_type, $args);
        $this->add_custom_fields();
    }

    public function create_option_page()
    {
        add_menu_page(
            "Muruca Slider",
            'Muruca Slider',
            'edit_posts',
            MURUCA_CORE_SLIDER_PLUGIN_NAME,
            '',
            'dashicons-images-alt2',
            5
        );
    }

    public function disable_gutenberg($current_status, $post_type)
    {
        if ($post_type == $this->slide_post_type) return false;
        return $current_status;
    }

    public function editor_settings($args, $id)
    {
        global $current_screen;
        if ($this->slide_post_type == $current_screen->post_type) {
            $args['media_buttons'] = false;
            $args['quicktags'] = false;
        }
        return $args;
    }
    public function tiny_editor_settings($args, $id)
    {
        global $current_screen;
        if ($this->slide_post_type == $current_screen->post_type) {
            $args['toolbar1'] = "bold,italic,link";
            $args['toolbar2'] = "";
        }
        return $args;
    }

    public function register_rest_route() {
        register_rest_route( MURUCA_CORE_PLUGIN_NAME . "/" . MURUCA_CORE_V2_REST_VERSION, '/' .'slides', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'rest_response' )
            ),
        ));
    }

    public function rest_response( $data ) {

        $slides = get_posts(array(
                "post_type" => $this->slide_post_type,
                "post_number" => -1
            )
        );

        $slide_obj = [];
        foreach ($slides as $slide){
            $s = ["items" => []];
            $pre_title = get_field(MURUCA_CORE_PREFIX . "_slide_pretitle", $slide->ID);
            $subtitle = get_field(MURUCA_CORE_PREFIX . "_slide_subtitle", $slide->ID);

            if ($pre_title && $pre_title != ""){
                $s["items"][] = ["text" => $pre_title];
            }
            $s["items"][]= ["title" => $slide->post_title];

            if ($subtitle && $subtitle != ""){
                $s["items"][] = ["text" => $subtitle];
            }

            $s["items"][] = ["text" => $slide->post_content];

            $type = get_field(MURUCA_CORE_PREFIX . "_slide_type", $slide->ID);

            if( $type == "upload") {
                $s['background']['image'] = get_field(MURUCA_CORE_PREFIX . "_slide_image", $slide->ID);
            } elseif ($type == "url") {
                $s['background']['image'] = get_field(MURUCA_CORE_PREFIX . "_slide_image_url", $slide->ID);
            } elseif ($type == "video") {
                $s['background']['video'] = get_field(MURUCA_CORE_PREFIX . "_slide_video", $slide->ID);
            } elseif ($type == "none") {
                $color =get_field(MURUCA_CORE_PREFIX . "_slide_bg_color", $slide->ID);
                if( $color ) $s['background']['color'] = get_field(MURUCA_CORE_PREFIX . "_slide_bg_color", $slide->ID);
            }

            if( have_rows( MURUCA_CORE_PREFIX  .  '_slide_metadata', $slide->ID ) ){

                $meta = [];
                while ( have_rows(MURUCA_CORE_PREFIX  .  '_slide_metadata', $slide->ID ) ) {
                    the_row();
                    $meta[] = [
                        "key" => get_sub_field( MURUCA_CORE_PREFIX  .  '_slide_key'),
                        "value" => get_sub_field(MURUCA_CORE_PREFIX  .  '_slide_value')
                    ];
                }
                $s[]["metadata"] = $meta;
            }
            $slide_obj[] = $s;
        }
        return $slide_obj;
    }


    public function add_custom_fields(){
        if (function_exists('acf_add_local_field_group')) :

            acf_add_local_field_group(array(
                'key' => 'group_mrc_slider_options',
                'title' => 'Muruca slider',
                'fields' => array(
                    array(
                        'key' => self::ACF_PREFIX . 'slide_type',
                        'label' => 'type',
                        'name' => MURUCA_CORE_PREFIX  .  '_slide_type',
                        'type' => 'radio',
                        'wrapper' => array(
                            'width' => '30',
                        ),
                        'choices' => array(
                            'none' => 'no Image',
                            'upload' => 'Upload image',
                            'url' => 'image url',
                            'video' => 'image video',
                        ),
                        'layout' => 'vertical',
                        'return_format' => 'value'
                    ),
                    array(
                        'key' => self::ACF_PREFIX . 'slide_image',
                        'label' => 'Image',
                        'name' => MURUCA_CORE_PREFIX  .  'slide_image',
                        'type' => 'image',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => self::ACF_PREFIX . 'slide_type',
                                    'operator' => '==',
                                    'value' => 'upload',
                                ),
                            ),
                        ),
                        'wrapper' => array(
                            'width' => '70',
                        ),
                        'return_format' => 'url',
                        'preview_size' => 'medium',
                        'library' => 'all'
                    ),
                    array(
                        'key' => self::ACF_PREFIX . 'slide_image_url',
                        'label' => 'Image url',
                        'name' => MURUCA_CORE_PREFIX  .  '_slide_image_url',
                        'type' => 'url',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => self::ACF_PREFIX . 'slide_type',
                                    'operator' => '==',
                                    'value' => 'url',
                                ),
                            ),
                        ),
                        'wrapper' => array(
                            'width' => '70',
                        ),
                    ),
                    array(
                        'key' => self::ACF_PREFIX . 'slide_video_url',
                        'label' => 'Video url',
                        'name' => MURUCA_CORE_PREFIX  .  '_slide_video_url',
                        'type' => 'url',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => self::ACF_PREFIX . 'slide_type',
                                    'operator' => '==',
                                    'value' => 'video',
                                ),
                            ),
                        ),
                        'wrapper' => array(
                            'width' => '70',
                        ),
                    ),
                    array(
                        'key' => self::ACF_PREFIX . 'slide_bg_color',
                        'label' => '',
                        'name' => MURUCA_CORE_PREFIX  .  '_slide_bg_color',
                        'type' => 'color_picker',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => self::ACF_PREFIX . 'slide_type',
                                    'operator' => '==',
                                    'value' => 'none',
                                ),
                            ),
                        ),
                        'wrapper' => array(
                            'width' => '50',
                        ),
                    ),
                    array(
                        'key' => self::ACF_PREFIX . 'slide_action_label',
                        'label' => 'action label',
                        'name' => MURUCA_CORE_PREFIX  .  '_slide_action_label',
                        'type' => 'text',
                        'wrapper' => array(
                            'width' => '50',
                        ),
                    ),
                    array(
                        'key' => self::ACF_PREFIX . 'slide_action_payload',
                        'label' => 'link',
                        'name' => MURUCA_CORE_PREFIX  .  '_slide_action_payload',
                        'type' => 'text',
                        'wrapper' => array(
                            'width' => '50',
                        ),
                    ),
                    array(
                        'key' => self::ACF_PREFIX . 'slide_metadata',
                        'label' => 'metadati',
                        'name' => MURUCA_CORE_PREFIX  .  '_slide_metadata',
                        'type' => 'repeater',
                        'wrapper' => array(
                            'width' => '50',
                        ),
                        'layout' => 'row',
                        'button_label' => 'add metadata',
                        'sub_fields' => array(
                            array(
                                'key' => self::ACF_PREFIX . 'slide_metadata_key',
                                'label' => 'Key',
                                'name' => MURUCA_CORE_PREFIX  .  '_slide_key',
                                'type' => 'text',
                            ),
                            array(
                                'key' => self::ACF_PREFIX . 'slide_metadata_value',
                                'label' => 'Value',
                                'name' => MURUCA_CORE_PREFIX  .  '_slide_value',
                                'type' => 'text',
                            ),
                        ),
                    ),

                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => $this->slide_post_type,
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
            ));

            acf_add_local_field_group(array(
                'key' => 'group_mrc_slider_titles',
                'title' => 'Muruca slider',
                'fields' => array(
                    array(
                        'key' => self::ACF_PREFIX . 'slide_pretitle',
                        'label' => 'pre-title text',
                        'name' => MURUCA_CORE_PREFIX  .  '_slide_pretitle',
                        'type' => 'text'
                    ),
                    array(
                        'key' => self::ACF_PREFIX . 'slide_subtitle',
                        'label' => 'subtitle text',
                        'name' => MURUCA_CORE_PREFIX  .  '_slide_subtitle',
                        'type' => 'text'
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => $this->slide_post_type,
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'acf_after_title',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
            ));

        endif;
    }
}
