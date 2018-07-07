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

//include 'options.php' ;

/*
 * toggle autolinking
 * _toggle automagical linking
 * set link character
 * set link escape character
 * choose post types where linking occurs
 */

// This just echoes the chosen line, we'll position it later
function automagical_links ()
{
    echo "<p>hello</p>";
}

// Now we set that function up to execute when the admin_notices action is called
// add_action( 'admin_notices', 'hello_dolly' );

// We need some CSS to position the paragraph
//function automagical_links_css ()
//{
//
//}

//add_action( 'admin_head', 'automagical_links_css' );


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
<?php echo 'autolinking: ' ; print_r ( get_option( 'autolinking' ) ) ; ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'automagical_links-plugin-settings-group' ); ?>
            <?php do_settings_sections( 'automagical_links-plugin-settings-group' ); ?>
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
//            print_r ( get_post_types());
            ?>

            <?php submit_button(); ?>

        </form>
    </div>
<?php }

function automagical_links_filter ( $content ) {

    /* Check if the page is being viewed as a singleton? Or is a certain post type?
     * Note: this runs every time a post is loaded, including multiple times on a given template.
     * It looks something like this...
     * Get a list of post titles for projects and people and essays.
     * Search the text for mention of these items. (Name could be inside another word, like "Jeff Bartell's essay."
     * If only one instance is found, and the text is not already a link, link it.
     * Return the text.
     * Maybe allow forcing auto-linking by surrounding text in parentheses? or disable?
     */
//echo 'get_option( \'autolinking\' ): "' . get_option( 'autolinking' ) . '"' ; ;
    if ( get_option( 'autolinking' ) ) {

        if ( is_single() ) {

            $post_types = array ( 'person', 'page', 'thesis', 'project', 'essay', 'work' );

            $all_pages = get_posts( array ( 'post_type' => $post_types, 'post_status' => 'publish', 'numberposts' => -1 ) );

            foreach ( $all_pages as $page ) {
                // Look for double brackets or not?
                $search = get_option( 'automagicality' ) ?  $page->post_title : '[[' . $page->post_title . ']]' ;
                $replace = sprintf( '<a href="%1$s">%2$s</a>', $page->guid, $page->post_title );

                $replace_pairs[ $search ] = $replace;

                // PROBLEM: duplicate page names. Only the last one will get picked (whichever one that is).
                // PROBLEM: when you want more text to link to it, like "View John Howrey's bio."
                // Possible alternative syntax: [[John Howrey:View John Howrey's bio]]
                // PROBLEM: ambiguity of names. Let's say there's a student with the name Jennifer Lawrence,
                // and someone writes an essay that mentions the actress Jennifer Lawrence. The system will link
                // to the student.
                // Possible solution: mechanism to disable the auto linking (like \\Jennifer \\Lawrence).
                // What's the performance hit?
                // WARNING: no tags can be people's names or page names!
            }

            $content = strtr ( $content, $replace_pairs ); // string strtr ( string $str , array $replace_pairs )
            $content = strtr ( $content, array (
                    get_option( 'link_start_characters' ) => '',
                    get_option( 'link_end_characters' ) => '' )
                    ); // remove brackets for any unmatched pages.

            $escape = get_option( 'link_escape_character' ) ;

            if ( $escape == '\\' ) {
                $escape .= $escape ;
            }

            $content = strtr ( $content, $escape , ' ' ); // remove double backslashes; yes, the second arg is a single space!

        }

    }

    return $content;

}

add_filter( 'the_content', 'automagical_links_filter' ) ;
