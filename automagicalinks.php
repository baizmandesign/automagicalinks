<?php
/**
 * @package automagicalinks
 * @version 0.1
 */
/*
Plugin Name: automagicalinks
Plugin URI: https://bitbucket.org/baizmandesign/automagicalinks
Description: Automagically convert text to internal links on your website.
Author: Saul Baizman
Version: 0.1
Author URI: http://baizmandesign.com/
*/

add_action( 'admin_menu', 'automagicalinks_admin_menu' );

function automagicalinks_admin_menu ()
{
    add_menu_page ( 'automagicalinks Settings', 'automagicalinks', 'administrator', __FILE__, 'automagicalinks_settings_page', 'dashicons-admin-links' );

    add_action( 'admin_init', 'automagicalinks_settings' );

}

function automagicalinks_settings ()
{
    register_setting( 'automagicalinks-plugin-settings-group', 'autolinking' );
    register_setting( 'automagicalinks-plugin-settings-group', 'automagicality' );
    register_setting( 'automagicalinks-plugin-settings-group', 'link_start_characters' );
    register_setting( 'automagicalinks-plugin-settings-group', 'link_end_characters' );
    register_setting( 'automagicalinks-plugin-settings-group', 'link_escape_character' );
    register_setting( 'automagicalinks-plugin-settings-group', 'allowed_post_types' );
    register_setting( 'automagicalinks-plugin-settings-group', 'excluded_elements' );
}

function automagicalinks_settings_page ()
{
    ?>
    <div class="wrap">
        <h1>automagicalinks settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'automagicalinks-plugin-settings-group' ); ?>
            <?php do_settings_sections( 'automagicalinks-plugin-settings-group' ); ?>
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
                    <th scope="row">automagicalink Escape Characters:</th>
                    <td><input type="text" name="link_escape_character" size="2" maxlength="2"
                               value="<?php echo esc_attr( get_option( 'link_escape_character' ) ); ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><small>Each word in a phrase must be escaped to prevent automagical links from manifesting.</small></th>
                    <td></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Globally excluded phrases:<br></th>
                    <td><textarea name="excluded_elements" rows="8" cols="50" placeholder="Enter exclusion item per line."><?php echo esc_attr( get_option( 'excluded_elements' ) ); ?></textarea></td>
                </tr>

            </table>
            <?php

            ?>

            <?php submit_button(); ?>

        </form>
    </div>
<?php }

function automagicalinks_filter ( $content ) {

    global $wpdb;

    $autolinking = get_option( 'autolinking' ) ;
    $automagicality = get_option( 'automagicality' ) ;
    $link_start_characters = get_option( 'link_start_characters' ) ;
    $link_end_characters = get_option( 'link_end_characters' ) ;
    $link_escape_character = get_option( 'link_escape_character' ) ;
    $allowed_post_types = get_option ('allowed_post_types') ;
    $excluded_elements = get_option ('excluded_elements') ;

    if ( $autolinking ) {

        if ( is_singular ( ) ) {

            $replace_pairs = array ( ) ;
            $duplicates_pairs = array ( );

            $post_types = array_keys ( $allowed_post_types ) ;

            if ( ! $post_types ) {
                return $content ;
            }

            // Note our permalink structure below.
            // This may have to be customized for other sites!
            // In the wp_options table there is a record where option_name = "permalink_structure",
            // according to https://wordpress.stackexchange.com/questions/58625/where-is-permalink-info-stored-in-database

            $all_pages_sql = sprintf ("SELECT ID, post_title, post_name, post_type, concat_ws('/','%s', post_type, post_name,'') AS permalink FROM %s WHERE post_type IN ('%s') AND post_title != '%s'",
                'http://' . $_SERVER['HTTP_HOST'],
                $wpdb->posts,
                implode("','",$post_types),
                'Auto Draft') ;

            $all_pages = $wpdb->get_results($all_pages_sql);

            if ( $all_pages ) {

                foreach ( $all_pages as $page ) {

                    // Look for double brackets or not?
                    $search = $automagicality ? $page->post_title : $link_start_characters . $page->post_title . $link_end_characters;
                    $replace = sprintf( '<a href="%1$s">%2$s</a>', $page->permalink, $page->post_title );

                    // Fix "real" links that get nested after we automagically link them:
                    // <a href=""><a href="">Word</a></a>
                    $dupe_search = sprintf( '<a href="%1$s">',$page->permalink).sprintf( '<a href="%1$s">',$page->permalink).$page->post_title.'</a>'.'</a>';
                    $dupe_replace = $replace;

                    $replace_pairs[ $search ] = $replace;
                    $duplicates_pairs[ $dupe_search ] = $dupe_replace;

                }

                /*
                 * Excluded the exceptions.
                 */
                $excluded_elements_array = explode("\n",$excluded_elements) ;

                if ($excluded_elements_array) {
                    foreach ( $excluded_elements_array as $excluded_element ) {
                        if ( isset ( $replace_pairs[$excluded_element] ) ) {
                            unset ( $replace_pairs[$excluded_element] ) ;
                            unset ( $duplicates_pairs[$excluded_element]) ;
                        }
                    }
                }

                // The magic happens here.
                $content = strtr( $content, $replace_pairs );
                $content = strtr( $content, $duplicates_pairs );

                // Remove escape character.
                $content = str_replace( $link_escape_character, '', $content );
            }
            else {
                return $content;
            }

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

add_filter( 'the_content', 'automagicalinks_filter' ) ;
