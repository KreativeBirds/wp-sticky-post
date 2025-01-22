<?php
/**
 * Plugin Name: WP Sticky Post Banner
 * Description: A plugin to generate a shortcode for a mobile-friendly web news ticker displaying sticky posts.
 * Version: 1.2.0
 * Author: Denver Doran | KreativeBirds
 * Author URI: https://www.KreativeBirds.com
 */


// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Add plugin option to enable/disable, set excerpt length, max posts, scroll speed, display hours, and ticker label
function wp_sticky_post_banner_register_settings() {
    register_setting('wp_sticky_post_banner_options', 'wp_sticky_post_banner_enabled');
    register_setting('wp_sticky_post_banner_options', 'wp_sticky_post_banner_excerpt_length');
    register_setting('wp_sticky_post_banner_options', 'wp_sticky_post_banner_max_posts');
    register_setting('wp_sticky_post_banner_options', 'wp_sticky_post_banner_scroll_speed');
    register_setting('wp_sticky_post_banner_options', 'wp_sticky_post_banner_display_hours');
    register_setting('wp_sticky_post_banner_options', 'wp_sticky_post_banner_label');

    add_option('wp_sticky_post_banner_enabled', '1');
    add_option('wp_sticky_post_banner_excerpt_length', '100'); // Default to 100 characters
    add_option('wp_sticky_post_banner_max_posts', '5');
    add_option('wp_sticky_post_banner_scroll_speed', '30'); // Default to 30 seconds
    add_option('wp_sticky_post_banner_display_hours', '24'); // Default to 24 hours
    add_option('wp_sticky_post_banner_label', 'Breaking News:'); // Default label
}
add_action('admin_init', 'wp_sticky_post_banner_register_settings');

// Register the shortcode
function wp_sticky_post_banner_shortcode() {
    // Check if the plugin is enabled
    if (!get_option('wp_sticky_post_banner_enabled', '1')) {
        return '';
    }

    // Get sticky posts
    $sticky_posts = get_option('sticky_posts');
    if (empty($sticky_posts)) {
        return '';
    }

    $max_posts = absint(get_option('wp_sticky_post_banner_max_posts', '5'));
    $scroll_speed = absint(get_option('wp_sticky_post_banner_scroll_speed', '30'));
    $display_hours = absint(get_option('wp_sticky_post_banner_display_hours', '24'));
    $ticker_label = esc_html(get_option('wp_sticky_post_banner_label', 'Breaking News:'));
    $ticker_label = mb_substr($ticker_label, 0, 20); // Limit ticker label to 20 characters

    $args = [
        'post__in' => array_map('absint', $sticky_posts),
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
        'date_query' => [
            [
                'after' => "{$display_hours} hours ago",
                'inclusive' => true,
            ]
        ],
        'posts_per_page' => $max_posts,
    ];

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '';
    }

    $excerpt_length = absint(get_option('wp_sticky_post_banner_excerpt_length', '100')); // Excerpt length in characters

    // Start output buffering
    ob_start();
    ?>
    <div class="wp-sticky-ticker">
        <div class="ticker-container">
            <span class="ticker-label"><?php echo $ticker_label; ?></span>
            <div class="ticker-content">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <span class="ticker-item">
                        <span class="ticker-icon">★</span>
                        <a href="<?php echo esc_url(get_permalink()); ?>">
                            <?php
                            $excerpt = get_the_excerpt();
                            $trimmed_excerpt = mb_substr($excerpt, 0, $excerpt_length);
                            echo esc_html($trimmed_excerpt . (strlen($excerpt) > $excerpt_length ? '...' : ''));
                            ?>
                        </a>
                    </span>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <style>
        .wp-sticky-ticker {
            width: 100%;
            background: #222;
            color: #fff;
            font-family: Arial, sans-serif;
            overflow: hidden;
            white-space: nowrap;
            padding: 10px 0;
            position: relative;
            margin-bottom: 5px;
        }
        .ticker-container {
            display: flex;
            align-items: center;
        }
        .ticker-label {
            background: #FF0033;
            padding: 5px 10px;
            font-weight: bold;
            margin-right: 10px;
            flex-shrink: 0;
            z-index: 1;
            position: relative;
        }
        .ticker-content {
            display: flex;
            flex: 1;
            overflow: hidden;
            white-space: nowrap;
            animation: ticker-scroll <?php echo $scroll_speed; ?>s linear infinite;
            position: relative;
        }
        .ticker-item {
            display: inline-block;
            margin-right: 30px;
            white-space: nowrap;
        }
        .ticker-icon {
            color: #FFD700;
            margin-right: 5px;
        }
        .ticker-item a {
            color: #fff;
            text-decoration: none;
        }
        .ticker-item a:hover {
            text-decoration: underline;
        }
        @keyframes ticker-scroll {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
    </style>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('sticky_post_banner', 'wp_sticky_post_banner_shortcode');

// Add a menu page to the dashboard
function wp_sticky_post_banner_menu() {
    add_menu_page(
        'Sticky Post Banner', // Page title
        'Sticky Banner',      // Menu title
        'manage_options',    // Capability
        'sticky-post-banner',// Menu slug
        'wp_sticky_post_banner_settings_page', // Callback function
        'dashicons-megaphone', // Icon
        20                    // Position
    );
}
add_action('admin_menu', 'wp_sticky_post_banner_menu');

// Create the settings page content
function wp_sticky_post_banner_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized user.', 'wp-sticky-post-banner'));
    }
    ?>
    <div class="wrap" style="font-family: Arial, sans-serif;">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="<?php echo esc_url(plugins_url('logo.png', __FILE__)); ?>" alt="KreativeBirds" style="width: 150px; height: auto;">
            <h1 style="font-size: 24px; color: #333;">Sticky Post Banner Settings</h1>
        </div>
        <p><strong>Shortcode:</strong> Use <code>[sticky_post_banner]</code> to display the ticker on any page or post.</p>
        <p><strong>How It Works:</strong></p>
        <ul style="list-style-type: disc; padding-left: 20px;">
            <li>Displays posts marked as <strong>sticky</strong> in WordPress.</li>
            <li>Filters sticky posts to show only those published within the last specified <strong>hours</strong>.</li>
            <li>Allows you to enable or disable the banner via this settings page.</li>
            <li>Lets you customize the ticker label, number of characters shown, and the number of sticky posts displayed.</li>
            <li>Allows you to adjust the ticker scroll speed (recommended: 30 seconds).</li>
            <li>If no sticky posts meet the criteria, the banner will not display.</li>
        </ul>
        <form method="post" action="options.php" style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
            <?php settings_fields('wp_sticky_post_banner_options'); ?>
            <table class="form-table" style="width: 100%;">
                <tr valign="top">
                    <th scope="row" style="text-align: left;">Enable Sticky Post Banner</th>
                    <td><input type="checkbox" name="wp_sticky_post_banner_enabled" value="1" <?php checked(get_option('wp_sticky_post_banner_enabled'), '1'); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row" style="text-align: left;">Ticker Label</th>
                    <td><input type="text" name="wp_sticky_post_banner_label" value="<?php echo esc_attr(mb_substr(get_option('wp_sticky_post_banner_label', 'Breaking News:'), 0, 20)); ?>" maxlength="20" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row" style="text-align: left;">Excerpt Length (characters)</th>
                    <td><input type="number" name="wp_sticky_post_banner_excerpt_length" value="<?php echo esc_attr(get_option('wp_sticky_post_banner_excerpt_length', '100')); ?>" min="1" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row" style="text-align: left;">Max Sticky Posts</th>
                    <td><input type="number" name="wp_sticky_post_banner_max_posts" value="<?php echo esc_attr(get_option('wp_sticky_post_banner_max_posts', '5')); ?>" min="1" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row" style="text-align: left;">Scroll Speed (seconds)</th>
                    <td><input type="number" name="wp_sticky_post_banner_scroll_speed" value="<?php echo esc_attr(get_option('wp_sticky_post_banner_scroll_speed', '30')); ?>" min="5" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row" style="text-align: left;">Display Hours</th>
                    <td><input type="number" name="wp_sticky_post_banner_display_hours" value="<?php echo esc_attr(get_option('wp_sticky_post_banner_display_hours', '24')); ?>" min="1" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
