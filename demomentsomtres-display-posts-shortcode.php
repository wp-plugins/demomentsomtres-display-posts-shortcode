<?php
/**
 * Plugin Name: DeMomentSomTres Display Posts Shortcode
 * Plugin URI: http://demomentsomtres.com/english/wordpress-plugins/demomentsomtres-display-posts-shortcode/
 * Description: Display a listing of posts using the [display-posts] shortcode
 * Version: 2.1.1
 * Author: Marc Queralt
 * Author URI: http://demomentsomtres.com
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU 
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume 
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * Based on the plugin Display Post Shortcode 2.2 by http://www.billerickson.net/shortcode-to-display-posts/
 * includes the capability of showing post from another blog in the same network install
 * @package DeMomentSomTres Display Posts
 * @version 1.0
 * @author Marc Queralt <marc@demomentsomtres.com>
 * @copyright Copyright (c) 2012, DeMomentSomTres
 * @link http://demomentsomtres.com/catala/
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */
/**
 * To Customize, use the following filters:
 *
 * `display_posts_shortcode_args`
 * For customizing the $args passed to WP_Query
 *
 * `display_posts_shortcode_output`
 * For customizing the output of individual posts.
 * Example: https://gist.github.com/1175575#file_display_posts_shortcode_output.php
 *
 * `display_posts_shortcode_wrapper_open` 
 * display_posts_shortcode_wrapper_close`
 * For customizing the outer markup of the whole listing. By default it is a <ul> but
 * can be changed to <ol> or <div> using the 'wrapper' attribute, or by using this filter.
 * Example: https://gist.github.com/1270278
 */
define('DMS3_DPS_TEXT_DOMAIN', 'DeMomentSomTres-displayPostShortcode');

if (!in_array('demomentsomtres-tools/demomentsomtres-tools.php', apply_filters('active_plugins', get_option('active_plugins')))):
    add_action('admin_notices', 'DMS3_DPS_messageNoTools');
else:
    $dms3DPS = new DeMomentSomTresDisplayPostShortcode();
endif;

function DMS3_DPS_messageNoTools() {
    ?>
    <div class="error">
        <p><?php _e('DeMomentSomTres Display Post Shortcode requires the free DeMomentSomTres Tools plugin.', DMS3_DPS_TEXT_DOMAIN); ?>
            <br/>
            <a href="http://demomentsomtres.com/english/wordpress-plugins/demomentsomtres-tools/?utm_source=web&utm_medium=wordpress&utm_campaign=adminnotice&utm_term=dms3WCdeliveryDate" target="_blank"><?php _e('Download it here', DMS3_DPS_TEXT_DOMAIN); ?></a>
        </p>
    </div>
    <?php
}

class DeMomentSomTresDisplayPostShortcode {

    const TEXT_DOMAIN = DMS3_DPS_TEXT_DOMAIN;
    const MENU_SLUG = 'dmst_displayPostShortcode';
    const OPTIONS = 'dmst_displayPostShortcode_options';
    const PAGE = 'dmst_displayPostShortcode';
    const SECTION_OUTPUT = 'shortcodeOutput';
    const SECTION_JAVASCRIPT = 'javascript';
    const SECTION_EMPTY = 'empty';
    const OPTION_JAVASCRIPT_OUTPUT = 'jsoutput';
    const OPTION_JAVASCRIPT_FUNCTION = 'jsfunction';
    const OPTION_JAVASCRIPT_PARAMETERS = 'jsparams';
    const OPTION_EMPTY_MESSAGE = 'empty-message';

    private $pluginURL;
    private $pluginPath;
    private $langDir;

    /**
     * @since 1.1
     */
    function __construct() {
        $this->pluginURL = plugin_dir_url(__FILE__);
        $this->pluginPath = plugin_dir_path(__FILE__);
        $this->langDir = dirname(plugin_basename(__FILE__)) . '/languages';

        add_action('plugins_loaded', array(&$this, 'plugin_init'));
        add_shortcode('display-posts', array(&$this, 'demomentsomtres_display_posts_shortcode'));
        add_filter('display_posts_shortcode_args', array(&$this, 'demomentsomtres_display_posts_shortcode_metaorderby'), 10, 2); //v1.1+
        add_filter('display_posts_shortcode_no_results', array(&$this, 'filter_empty_query_message'));
        add_action('admin_menu', array(&$this, 'admin_menu'));
        add_action('admin_init', array(&$this, 'admin_init'));
    }

    /**
     * @since 2.0
     */
    function plugin_init() {
        load_plugin_textdomain(DMS3_DPS_TEXT_DOMAIN, false, $this->langDir);
    }

    /**
     * @since 2.0
     */
    function admin_menu() {
        add_options_page(__('DeMomentSomTres Display Posts', self::TEXT_DOMAIN), __('DeMomentSomTres Display Posts', self::TEXT_DOMAIN), 'manage_options', self::MENU_SLUG, array(&$this, 'admin_page'));
    }

    /**
     * @since 2.0
     */
    function admin_page() {
        ?>
        <div class="wrap">
            <h2><?php _e('DeMomentSomTres Display Posts Shortcode Customization', self::TEXT_DOMAIN); ?></h2>
            <form action="options.php" method="post">
                <?php settings_fields(self::OPTIONS); ?>
                <?php do_settings_sections(self::PAGE); ?>
                <br/>
                <input name="Submit" class="button button-primary" type="submit" value="<?php _e('Save Changes', self::TEXT_DOMAIN); ?>"/>
            </form>
        </div>
        <div style="background-color:#eee;/*display:none;*()">
            <h2><?php _e('Options', self::TEXT_DOMAIN); ?></h2>
            <pre style="font-size:0.8em;"><?php print_r(get_option(self::OPTIONS)); ?></pre>
        </div>
        <?php
    }

    /**
     * @since 2.0
     */
    function admin_init() {
        register_setting(self::OPTIONS, self::OPTIONS, array(&$this, 'admin_validate_options'));

        add_settings_section(self::SECTION_OUTPUT, __('Shortcode Output Format', self::TEXT_DOMAIN), array(&$this, 'admin_section_output'), self::PAGE);
        add_settings_section(self::SECTION_JAVASCRIPT, __('Javascript Output Parameters', self::TEXT_DOMAIN), array(&$this, 'admin_section_javascript'), self::PAGE);
        add_settings_section(self::SECTION_EMPTY, __('Empty message', self::TEXT_DOMAIN), array(&$this, 'admin_section_empty'), self::PAGE);

        add_settings_field(self::OPTION_JAVASCRIPT_OUTPUT, __('Javascript as default', self::TEXT_DOMAIN), array(&$this, 'admin_field_javascript_output'), self::PAGE, self::SECTION_OUTPUT);

        add_settings_field(self::OPTION_JAVASCRIPT_FUNCTION, __('Javascript funtion name', self::TEXT_DOMAIN), array(&$this, 'admin_field_javascript_function'), self::PAGE, self::SECTION_JAVASCRIPT);
        add_settings_field(self::OPTION_JAVASCRIPT_PARAMETERS, __('Javascript funtion parameters', self::TEXT_DOMAIN), array(&$this, 'admin_field_javascript_parameters'), self::PAGE, self::SECTION_JAVASCRIPT);

        add_settings_field(self::OPTION_EMPTY_MESSAGE, __('Empty message', self::TEXT_DOMAIN), array(&$this, 'admin_field_empty_message'), self::PAGE, self::SECTION_EMPTY);
    }

    /**
     * @since 2.0
     */
    function admin_validate_options($input = array()) {
        $input = DeMomentSomTresTools::adminHelper_esc_attr($input);
        return $input;
    }

    /**
     * @since 2.0
     */
    function admin_section_output() {
        echo '<p>' . __('Select method used to show the links by default. If none is selected, links will be shown as ordinary html links.', self::TEXT_DOMAIN) . '</p>';
        echo '<p><strong>' . __('This parameters can be overriden by each shortcode.', self::TEXT_DOMAIN) . '</strong></p>';
    }

    /**
     * @since 2.0
     */
    function admin_section_javascript() {
        echo '<p>' . __('Javascript parameters to be used by default when javascript is selected by default or via shortcode.', self::TEXT_DOMAIN) . '</p>';
        echo '<p><strong>' . __('This parameters can be overriden by each shortcode.', self::TEXT_DOMAIN) . '</strong></p>';
    }

    /**
     * @since 2.1
     */
    function admin_section_empty() {
        echo '<p>' . __('Message to show when no content is found.', self::TEXT_DOMAIN) . '</p>';
    }

    /**
     * @since 2.0
     */
    function admin_field_javascript_output() {
        $name = self::OPTION_JAVASCRIPT_OUTPUT;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name, 0);
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
            'type' => 'checkbox',
        ));
    }

    /**
     * @since 2.0
     */
    function admin_field_javascript_function() {
        $name = self::OPTION_JAVASCRIPT_FUNCTION;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name);
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
            'class' => 'regular-text',
        ));
    }

    /**
     * @since 2.0
     */
    function admin_field_javascript_parameters() {
        $name = self::OPTION_JAVASCRIPT_PARAMETERS;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name);
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
            'class' => 'regular-text',
        ));
        echo '<div style="font-size:0.8em;">' . __('Put as many parameters as you want in a format parameter="value".', self::TEXT_DOMAIN) . '</div>';
    }

    /**
     * @since 2.1
     */
    function admin_field_empty_message() {
        $name = self::OPTION_EMPTY_MESSAGE;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name, __('No results found.', self::TEXT_DOMAIN));
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
            'class' => 'regular-text',
        ));
    }

    function href($url, $atts) {
        if ($atts['js_output'] == 'on'):
            return 'href="' . $url . '" onclick="' . $atts['js_function'] . '(event)" ' . $atts['js_parameters'];
        endif;
        return 'href="' . $url . '"';
    }

    function demomentsomtres_display_posts_shortcode($atts) {
        global $blog_id; //MQB1.0+
        // Original Attributes, for filters
        $original_atts = $atts;

        //MQB2.0++
        $default_js_output = DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_JAVASCRIPT_OUTPUT, "off");
        $default_js_function = DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_JAVASCRIPT_FUNCTION, "");
        $default_js_parameters = DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_JAVASCRIPT_PARAMETERS, "");
        //MQB2.0++
        // Pull in shortcode attributes and set defaults
        $atts = shortcode_atts(array(
            'author' => '',
            'category' => '',
            'date_format' => '(n/j/Y)',
            'id' => false,
            'image_size' => false,
            'include_content' => false,
            'include_date' => false,
            'include_excerpt' => false,
            'offset' => 0,
            'order' => 'DESC',
            'orderby' => 'date',
            'post_parent' => false,
            'post_status' => 'publish',
            'post_type' => 'post',
            'posts_per_page' => '10',
            'tag' => '',
            'tax_operator' => 'IN',
            'tax_term' => false,
            'taxonomy' => false,
            'wrapper' => 'ul',
            'blog_id' => $blog_id, //MQB1.1+,
            'js_output' => $default_js_output, //MQB2.0+
            'js_function' => $default_js_function, //MQB2.0+
            'js_parameters' => $default_js_parameters, //MQB2.0+
                ), $atts);

        $author = sanitize_text_field($atts['author']);
        $category = sanitize_text_field($atts['category']);
        $date_format = sanitize_text_field($atts['date_format']);
        $id = $atts['id']; // Sanitized later as an array of integers
        $image_size = sanitize_key($atts['image_size']);
        $include_content = (bool) $atts['include_content'];
        $include_date = (bool) $atts['include_date'];
        $include_excerpt = (bool) $atts['include_excerpt'];
        $offset = intval($atts['offset']);
        $order = sanitize_key($atts['order']);
        $orderby = sanitize_key($atts['orderby']);
        $post_parent = $atts['post_parent']; // Validated later, after check for 'current'
        $post_status = $atts['post_status']; // Validated later as one of a few values
        $post_type = sanitize_text_field($atts['post_type']);
        $posts_per_page = intval($atts['posts_per_page']);
        $tag = sanitize_text_field($atts['tag']);
        $tax_operator = $atts['tax_operator']; // Validated later as one of a few values
        $tax_term = sanitize_text_field($atts['tax_term']);
        $taxonomy = sanitize_key($atts['taxonomy']);
        $wrapper = sanitize_text_field($atts['wrapper']);
        $blogid = $atts['blog_id']; //MQB1.1+
        // Set up initial query for post
        $args = array(
            'category_name' => $category,
            'order' => $order,
            'orderby' => $orderby,
            'post_type' => explode(',', $post_type),
            'posts_per_page' => $posts_per_page,
            'tag' => $tag,
        );

        // If Post IDs
        if ($id) {
            $posts_in = array_map('intval', explode(',', $id));
            $args['post__in'] = $posts_in;
        }

        // Post Author
        if (!empty($author))
            $args['author_name'] = $author;

        // Offset
        if (!empty($offset))
            $args['offset'] = $offset;

        // Post Status	
        $post_status = explode(', ', $post_status);
        $validated = array();
        $available = array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash', 'any');
        foreach ($post_status as $unvalidated)
            if (in_array($unvalidated, $available))
                $validated[] = $unvalidated;
        if (!empty($validated))
            $args['post_status'] = $validated;


        // If taxonomy attributes, create a taxonomy query
        if (!empty($taxonomy) && !empty($tax_term)) {

            // Term string to array
            $tax_term = explode(', ', $tax_term);

            // Validate operator
            if (!in_array($tax_operator, array('IN', 'NOT IN', 'AND')))
                $tax_operator = 'IN';

            $tax_args = array(
                'tax_query' => array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field' => 'slug',
                        'terms' => $tax_term,
                        'operator' => $tax_operator
                    )
                )
            );

            // Check for multiple taxonomy queries
            $count = 2;
            $more_tax_queries = false;
            while (
            isset($original_atts['taxonomy_' . $count]) && !empty($original_atts['taxonomy_' . $count]) &&
            isset($original_atts['tax_' . $count . '_term']) && !empty($original_atts['tax_' . $count . '_term'])
            ):

                // Sanitize values
                $more_tax_queries = true;
                $taxonomy = sanitize_key($original_atts['taxonomy_' . $count]);
                $terms = explode(', ', sanitize_text_field($original_atts['tax_' . $count . '_term']));
                $tax_operator = isset($original_atts['tax_' . $count . '_operator']) ? $original_atts['tax_' . $count . '_operator'] : 'IN';
                $tax_operator = in_array($tax_operator, array('IN', 'NOT IN', 'AND')) ? $tax_operator : 'IN';

                $tax_args['tax_query'][] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $terms,
                    'operator' => $tax_operator
                );

                $count++;

            endwhile;

            if ($more_tax_queries):
                $tax_relation = 'AND';
                if (isset($original_atts['tax_relation']) && in_array($original_atts['tax_relation'], array('AND', 'OR')))
                    $tax_relation = $original_atts['tax_relation'];
                $args['tax_query']['relation'] = $tax_relation;
            endif;

            $args = array_merge($args, $tax_args);
        }

        // If post parent attribute, set up parent
        if ($post_parent) {
            if ('current' == $post_parent) {
                global $post;
                $post_parent = $post->ID;
            }
            $args['post_parent'] = intval($post_parent);
        }

        // Set up html elements used to wrap the posts. 
        // Default is ul/li, but can also be ol/li and div/div
        $wrapper_options = array('ul', 'ol', 'div');
        if (!in_array($wrapper, $wrapper_options))
            $wrapper = 'ul';
        $inner_wrapper = 'div' == $wrapper ? 'div' : 'li';

        if ($blogid != $blog_id)://MQB1.0+
            switch_to_blog($blogid); //MQB1.0+
        endif; //MQB1.0+
        $listing = new WP_Query(apply_filters('display_posts_shortcode_args', $args, $original_atts));
        if (!$listing->have_posts())
            return apply_filters('display_posts_shortcode_no_results', false);

        $inner = '';

        $i = 0; //MQB1.3+
        while ($listing->have_posts()): $listing->the_post();
            global $post;

            $image = $date = $excerpt = $content = '';

            //$title = '<a class="title" href="' . get_permalink() . '">' . get_the_title() . '</a>';//MQB2.0-
            $title = '<a class="title" ' . $this->href(get_permalink(), $atts) . '>' . get_the_title() . '</a>'; //MQB2.0+

            if ($image_size && has_post_thumbnail())
            //$image = '<a class="image" href="' . get_permalink() . '">' . get_the_post_thumbnail($post->ID, $image_size) . '</a> ';//MQB2.0-
                $image = '<a class="image" ' . $this->href(get_permalink(), $atts) . '>' . get_the_post_thumbnail($post->ID, $image_size) . '</a> '; //MQB2.0+

            if ($include_date)
                $date = ' <span class="date">' . get_the_date($date_format) . '</span>';

            if ($include_excerpt)
                $excerpt = ' <span class="excerpt-dash">-</span> <span class="excerpt">' . get_the_excerpt() . '</span>';

            if ($include_content)
                $content = '<div class="content">' . apply_filters('the_content', get_the_content()) . '</div>';

            $class = array('listing-item');
            $class = apply_filters('display_posts_shortcode_post_class', $class, $post, $listing);
            $output = '<' . $inner_wrapper . ' class="' . implode(' ', $class) . '">' . $image . $title . $date . $excerpt . $content . '</' . $inner_wrapper . '>';

            $inner .= apply_filters('display_posts_shortcode_output', $output, $original_atts, $image, $title, $date, $excerpt, $inner_wrapper, $content);

            $i++; //MQB++
            if ($i >= $atts['posts_per_page']):
                break;
            endif;
        endwhile;
        wp_reset_postdata();

        $open = apply_filters('display_posts_shortcode_wrapper_open', '<' . $wrapper . ' class="display-posts-listing">');
        $close = apply_filters('display_posts_shortcode_wrapper_close', '</' . $wrapper . '>');
        restore_current_blog(); //MQB1.1+
        $return = $open . $inner . $close;

        return $return;
    }

    /**
     * @since 1.1
     */
    function demomentsomtres_display_posts_shortcode_metaorderby($args, $atts) {
//    echo '<pre>'.print_r($args,true).'</pre>';
        if (isset($atts['metaorderby'])) {
            $args['orderby'] = 'meta_value';
            $args['meta_key'] = sanitize_text_field($atts['metaorderby']);
        }
        if (isset($atts['metaorderbynum'])) {
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = sanitize_text_field($atts['metaorderbynum']);
        }
//    echo '<pre>'.print_r($args,true).'</pre>';
//    exit;
        return $args;
    }

    /**
     * @since 2.1
     */
    function filter_empty_query_message($output) {
        $name = DeMomentSomTresDisplayPostShortcode::OPTION_EMPTY_MESSAGE;
        $message = DeMomentSomTresTools::get_option(DeMomentSomTresDisplayPostShortcode::OPTIONS, $name, __('No results found.', DeMomentSomTresDisplayPostShortcode::TEXT_DOMAIN));
        $output = '<p>' . $message . '</p>';
        return $output;
    }

}
