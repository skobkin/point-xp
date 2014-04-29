<?php
/**
 * Plugin Name: Point.im Crossposter
 * Plugin URI: https://github.com/skobkin/juick-xp
 * Description: A simple Point.im crossposter plugin (fork of Juick Crossposter)
 * Version: 0.4
 * Author: Sand Fox modified by Skobk.in
 * Author URI: http://sandfox.im/
 * License: GNU GPL v2
 */

add_action('publish_post', 'pointxp_post');

function pointxp_post($post_id)
{
    $point_jid = get_option('pointxp_custom_jid');
    $include_text = get_option('pointxp_include_text', false);

    if (!function_exists('xmpp_send')) // no xmpp sender plugin
    {
        return;
    }

    $tags_s = '';
    $tags = array();

    $tags_custom = explode(' ', get_option('pointxp_jtags_custom'));

    if (count($tags_custom) > 0) {
        foreach ($tags_custom as $tag) {
            if (!empty($tag)) {
                $tags[] = $tag;
            }
        }
    }


    $post = get_post($post_id);
    $post_link = get_permalink($post_id);

    if (get_option('pointxp_jtags_categories')) {
        foreach (wp_get_object_terms($post_id, 'category') as $tag) {
            $tags [] = str_replace(' ', '-', $tag->name);
        }
    }

    if (get_option('pointxp_jtags_tags')) {
        foreach (wp_get_object_terms($post_id, 'post_tag') as $tag) {
            $tags [] = str_replace(' ', '-', $tag->name);
        }
    }

    // Maximum tags count
    $k = 10;

    foreach ($tags as $tag) {
        if (!$k--) {
            break;
        }

        $tags_s .= '*' . $tag . ' ';
    }

    if (empty($point_jid)) {
        $point_jid = 'p@point.im';
    }

    if ($post->post_type != 'post') // no pages or attachments!
    {
        return;
    }

    $message = $tags_s . PHP_EOL;
    // Markdown link for post original
    $message .= '[' . $post->post_title . '](' . $post_link . ')' . PHP_EOL;

    if ($include_text) {
        $message .= "\n" . strip_tags($post->post_excerpt ? $post->post_excerpt : $post->post_content);
    }

    xmpp_send($point_jid, $message);
}

/* ----- settings section -------- */

add_action('admin_menu', 'pointxp_create_menu');

function pointxp_create_menu()
{
    if (!function_exists('xmpp_send')) // in case XMPP Enabled is not present
    {
        add_submenu_page('tools.php', 'Point.im Crossposter Settings', 'Point.im Crossposter', 'administrator', __FILE__, 'pointxp_settings_page');
    }

    add_submenu_page('xmpp-enabled', 'Point.im Crossposter Settings', 'Point.im Crossposter', 'administrator', __FILE__, 'pointxp_settings_page');
    add_action('admin_init', 'register_pointxp_settings');
}


function register_pointxp_settings()
{
    register_setting('pointxp-settings', 'pointxp_include_text');

    register_setting('pointxp-settings', 'pointxp_jtags_custom');
    register_setting('pointxp-settings', 'pointxp_jtags_categories');
    register_setting('pointxp-settings', 'pointxp_jtags_tags');

    register_setting('pointxp-settings', 'pointxp_custom_jid');
}

function pointxp_settings_page()
{

    ?>
    <div class="wrap">
    <h2>Point.im crossposter settings</h2>
    <?php if (!function_exists('xmpp_send')):
        ?><p style="color: red">Error: <strong>XMPP Enabled</strong> is not installed.
        Please install the <strong>XMPP Enabled</strong> plugin for this plugin to work</p>

        <ul>
            <li><a href="http://wordpress.org/extend/plugins/xmpp-enabled/">
                    http://wordpress.org/extend/plugins/xmpp-enabled/</a>
            </li>
            <li>
                <a href="http://sandfox.org/projects/xmpp-enabled.html">
                    http://sandfox.org/projects/xmpp-enabled.html</a>
            </li>
        </ul>

        <hr/>

    <?php
    endif;

    ?>

    <form method="post" action="options.php">
        <?php settings_fields('pointxp-settings'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Custom tags (separated by space)<br/>
                    <small>(prefix * is added automatically)</small>
                </th>
                <td>
                    <input type="text" name="pointxp_jtags_custom"
                           value="<?php echo get_option('pointxp_jtags_custom', 'wp-point-xp'); ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row" colspan="2">
                    <input type="checkbox" value="1" name="pointxp_jtags_categories" id="pointxp_jtags_categories"
                        <?php if (get_option('pointxp_jtags_categories', true)) echo 'checked="checked"' ?>
                        /> <label for="pointxp_jtags_categories">Include post categories as Point.im tags</label>
                </th>
            </tr>
            <tr>
                <th scope="row" colspan="2">
                    <input type="checkbox" value="1" name="pointxp_jtags_tags" id="pointxp_jtags_tags"
                        <?php if (get_option('pointxp_jtags_tags', true)) echo 'checked="checked"' ?>
                        /> <label for="pointxp_jtags_tags">Include post tags as Point.im tags</label>
                </th>
            </tr>
            <tr>
                <th scope="row" colspan="2">
                    The order is {custom, categories, tags} limited by 10
                </th>
            </tr>
            <tr>
                <th scope="row" colspan="2">
                    <input type="checkbox" value="1" name="pointxp_include_text" id="pointxp_include_text"
                        <?php if (get_option('pointxp_include_text', false)) echo 'checked="checked"' ?>
                        /> <label for="pointxp_include_text">Include excerpt<br/>
                        <small>Experimental feature</small>
                    </label>
                </th>
            </tr>
            <tr valign="top">
                <th scope="row">Custom Point.im JID<br/>
                    <small>Leave blank for p@point.im</small>
                </th>
                <td>
                    <input type="text" name="pointxp_custom_jid"
                           value="<?php echo get_option('pointxp_custom_jid'); ?>"/>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>"/>
        </p>

    </form>
    </div><?php
}
