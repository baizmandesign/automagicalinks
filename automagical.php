<?php
/**
 * @package Automagical_Links
 * @version 0.1
 */
/*
Plugin Name: AutomagicaLinks
Plugin URI: https://bitbucket.org/baizmandesign/automagicalinks
Description: Automagically convert text to internal links on your website.
Author: Saul Baizman
Version: 0.1
Author URI: http://baizmandesign.com/
*/

add_action( 'admin_menu', 'automagical_links_admin_menu' );

function automagical_links_admin_menu ()
{
    add_menu_page ( 'AutomagicaLinks Settings', 'AutomagicaLinks', 'administrator', __FILE__, 'automagical_links_settings_page', plugins_url( '/images/icon.png', __FILE__ ) );

    add_action( 'admin_init', 'automagical_links_settings' );

}

function automagical_links_settings ()
{
    register_setting( 'automagical_links-plugin-settings-group', 'autolinking' );
    register_setting( 'automagical_links-plugin-settings-group', 'automagicality' );
    register_setting( 'automagical_links-plugin-settings-group', 'link_start_characters' );
    register_setting( 'automagical_links-plugin-settings-group', 'link_end_characters' );
    register_setting( 'automagical_links-plugin-settings-group', 'link_escape_character' );
    register_setting( 'automagical_links-plugin-settings-group', 'allowed_post_types' );

}

function automagical_links_settings_page ()
{
    ?>
    <div class="wrap">
        <h1>AutomagicaLinks Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'automagical_links-plugin-settings-group' ); ?>
            <?php do_settings_sections( 'automagical_links-plugin-settings-group' ); ?>
            <table class="form-table">
            <tbody>
            <tr>
                <th scope="row" colspan="4"">Post Types</th>
            </tr>
            <?php

            $all_post_types = get_post_types()  ;

            $allowed_post_types = get_option ('allowed_post_types') ;

            $columns = 4 ;

            $column_counter = 0;

            $post_counter = 0 ;

            ksort ($all_post_types);

            foreach ( $all_post_types as $name => $value ) {

                if ( $column_counter%$columns==0 ) {
                    printf( '<tr>' );
                }

                $replace['-'] = ' ' ;
                $replace['_'] = ' ' ;
                $replace['wp'] = 'WP' ;
                $name = ucwords( strtr ( $name, $replace ) ) ;

                printf( '<td><input type="checkbox" name="allowed_post_types[%1$s]" id="posts_%3$d" value="1"' . checked ( '1', isset ( $allowed_post_types[$value] ), false ) . '> <label for="posts_%3$d">%2$s</label></td>', $value, $name, $post_counter );

                $column_counter++ ;

                if ( $column_counter%$columns==0 ) {
                    printf( '</tr>' );
                    $column_counter = 0;
                }

                $post_counter++ ;
            }

            ?>
            </tbody>
            </table>

            <!--////////////////////////////////////////////////////////////-->

            <table class="form-table">
                <tr valign="top">
                    <th scope="row" width="30%"><label for="autolinking">Enable Autolinking:</label></th>
                    <td width="70%"><input type="checkbox" name="autolinking" id="autolinking"
                               value="1" <?php checked ( '1', get_option( 'autolinking' ), true ); ?>/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><small>With autolinks, any <?php echo esc_attr( get_option( 'link_start_characters' ) ); ?>text<?php echo esc_attr( get_option( 'link_end_characters' ) ); ?> in the body of a page that matches a page name  will be linked to that page.</small></th>
                    <td></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Link Start Characters:</th>
                    <td><input type="text" name="link_start_characters" size="2" maxlength="2"
                               value="<?php echo esc_attr( get_option( 'link_start_characters' ) ); ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Link End Characters:</th>
                    <td><input type="text" name="link_end_characters" size="2" maxlength="2"
                               value="<?php echo esc_attr( get_option( 'link_end_characters' ) ); ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="automagicality">Enable Automagicality:</label></th>
                    <td><input type="checkbox" name="automagicality" id="automagicality"
                               value="1"<?php checked ( '1', get_option( 'automagicality' ), true ); ?>/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><small style="color: red">Note: enabling Automagicality ignores the link start  and end characters. (Don't worry, they'll be removed.) Use for extreme awesome.</small></th>
                    <td></td>
                </tr>
                <tr valign="top">
                    <th scope="row">AutomagicaLink Escape Characters:</th>
                    <td><input type="text" name="link_escape_character" size="2" maxlength="2"
                               value="<?php echo esc_attr( get_option( 'link_escape_character' ) ); ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><small>Each word in a phrase must be escaped to prevent automagical links from manifesting.</small></th>
                    <td></td>
                </tr>

            </table>
            <?php

            ?>

            <?php submit_button(); ?>

        </form>
    </div>
<?php }

function automagical_links_filter ( $content ) {

    $autolinking = get_option( 'autolinking' ) ;
    $automagicality = get_option( 'automagicality' ) ;
    $link_start_characters = get_option( 'link_start_characters' ) ;
    $link_end_characters = get_option( 'link_end_characters' ) ;
    $link_escape_character = get_option( 'link_escape_character' ) ;
    $allowed_post_types = get_option ('allowed_post_types') ;

    if ( $autolinking ) {

        if ( is_singular ( ) ) {

            $post_types = array_keys ( $allowed_post_types ) ;

            $all_pages = get_posts ( array ( 'post_type' => $post_types, 'post_status' => 'publish', 'numberposts' => -1 ) );

            foreach ( $all_pages as $page ) {
                // Look for double brackets or not?
                $search = $automagicality ?  $page->post_title : $link_start_characters . $page->post_title . $link_end_characters ;
                $replace = sprintf ( '<a href="%1$s">%2$s</a>', $page->guid, $page->post_title );

                $replace_pairs[ $search ] = $replace;

            }

            // Remove brackets for any unmatched pages.
            $content = strtr ( $content, $replace_pairs );

            // Remove escape character.
            $content = str_replace( $link_escape_character,'',$content) ;

        }

    }

    // Remove start characters, end characters, and escape characters in
    // case the user enables and later disables autolinking.
    // Requires the plugin to still be activated, of course!

    if ($link_start_characters) {
        $content = str_replace( $link_start_characters,'',$content) ;
    }
    if ($link_end_characters) {
        $content = str_replace( $link_end_characters,'',$content) ;
    }
    if ($link_escape_character) {
        $content = str_replace( $link_escape_character,'',$content) ;
    }

    return $content;

}

add_filter( 'the_content', 'automagical_links_filter' ) ;
