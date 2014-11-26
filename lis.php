<?php
/*
Plugin Name: LIS
Plugin URI: http://reddes.bvsalud.org/projects/lis/
Description: Create a Health Information Locator Site
Author: BIREME/OPAS/OMS
Version: 0.1
Author URI: http://reddes.bvsalud.org/
*/

define('LIS_VERSION', '0.1' );

define('LIS_SYMBOLIC_LINK', false );
define('LIS_PLUGIN_DIRNAME', 'lis-page' );

if(LIS_SYMBOLIC_LINK == true) {
    define('LIS_PLUGIN_PATH',  ABSPATH . 'wp-content/plugins/' . LIS_PLUGIN_DIRNAME );
} else {
    define('LIS_PLUGIN_PATH',  plugin_dir_path(__FILE__) );
}

define('LIS_PLUGIN_DIR',   plugin_basename( LIS_PLUGIN_PATH ) );
define('LIS_PLUGIN_URL',   plugin_dir_url(__FILE__) );

$plugin_slug = 'lis';

require_once(LIS_PLUGIN_PATH . '/settings.php');
require_once(LIS_PLUGIN_PATH . '/template-functions.php');

function lis_theme_redirect() {
    global $wp, $plugin_slug;
    $pagename = $wp->query_vars["pagename"];

    if ($pagename == $plugin_slug || $pagename == $plugin_slug . '/resource' 
        || $pagename == $plugin_slug . '/suggest-site' 
        || $pagename == $plugin_slug . '/suggest-site-details'
        || $pagename == $plugin_slug . '/lis-feed'
        ) {

        add_action( 'wp_enqueue_scripts', 'page_template_styles_scripts' );

        if ($pagename == $plugin_slug){
            $template = LIS_PLUGIN_PATH . '/template/lis-home.php';
        }elseif ($pagename == $plugin_slug . '/suggest-site'){
            $template = LIS_PLUGIN_PATH . '/template/lis-suggest-site.php';
        }elseif ($pagename == $plugin_slug . '/suggest-site-details'){
            $template = LIS_PLUGIN_PATH . '/template/lis-suggest-site-details.php';            
        }elseif ($pagename == $plugin_slug . '/lis-feed'){
            header("Content-Type: text/xml; charset=UTF-8");
            $template = LIS_PLUGIN_PATH . '/template/rss.php';
        }else{
            $template = LIS_PLUGIN_PATH . '/template/lis-resource.php';
        }
        // force status to 200 - OK
        status_header(200);

        // redirect to page and finish execution
        include($template);
        die();
    }
}

function page_template_styles_scripts(){
    wp_enqueue_script('lis-page',    LIS_PLUGIN_URL . 'template/js/functions.js');
    wp_enqueue_script('jquery-raty', LIS_PLUGIN_URL . 'template/js/jquery.raty.min.js', array( 'jquery' ));
    wp_enqueue_style ('lis-page',    LIS_PLUGIN_URL . 'template/css/style.css');
}

function lis_init() {
    global $plugin_slug;
    $lis_config = get_option('lis_config');

    if ($lis_config['plugin_slug'] != ''){
        $plugin_slug = $lis_config['plugin_slug'];
    }

}

function lis_load_translation(){
    // Translations
    load_plugin_textdomain( 'lis', false,  LIS_PLUGIN_DIR . '/languages' );
}

function lis_add_admin_menu() {

    add_options_page(__('LIS Settings', 'lis'), __('LIS', 'lis'), 'manage_options', 'lis.php', 'lis_page_admin');

    //add_submenu_page( 'options-general.php', __('LIS Settings', 'lis'), __('LIS', 'lis'), 'manage_options', 'lis','lis_page_admin');

    //call register settings function
    add_action( 'admin_init', 'lis_register_settings' );

}

function lis_register_settings(){

    register_setting('lis-settings-group', 'lis_config');

}

function lis_google_analytics_code(){
    global $wp, $plugin_slug;

    $pagename = $wp->query_vars["pagename"];
    $lis_config = get_option('lis_config');

    // check if is defined GA code and pagename starts with plugin slug
    if ($lis_config['google_analytics_code'] != ''
        && strpos($pagename, $plugin_slug) === 0){
?>

<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '<?php echo $lis_config['google_analytics_code'] ?>']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

<?php
    } //endif
}

function lis_search_form( $form ) {
    global $wp, $plugin_slug;
    $pagename = $wp->query_vars["pagename"];

    if ($pagename == $plugin_slug || $pagename == $plugin_slug .'/resource') {
        $form = preg_replace('/action="([^"]*)"(.*)/','action="' . home_url($plugin_slug) . '"',$form);
    }

    return $form;
}

function lis_register_sidebars(){
    $args = array(
        'name' => __('LIS sidebar', 'lis'),
        'id'   => 'lis-home',
        'description' => 'LIS Area',
        'before_widget' => '<section id="%1$s" class="row-fluid marginbottom25 widget_categories">',
        'after_widget'  => '</section>',        
        'before_title'  => '<header class="row-fluid border-bottom marginbottom15"><h1 class="h1-header">',
        'after_title'   => '</h1></header>',
    );
    register_sidebar( $args );
}

function lis_page_title(){
    global $wp, $plugin_slug;
    $pagename = $wp->query_vars["pagename"];

    if ( strpos($pagename, $plugin_slug) === 0 ) { //pagename starts with plugin slug        
        return 'LIS | ';
    }

}    

function lis_settings_link($links) {        
    $settings_link = '<a href="options-general.php?page=lis.php">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'lis_settings_link' );
add_action( 'init', 'lis_load_translation' );
add_action( 'admin_menu', 'lis_add_admin_menu');
add_action( 'plugins_loaded','lis_init' );
add_action( 'wp_head', 'lis_google_analytics_code');
add_action( 'template_redirect', 'lis_theme_redirect');
add_action( 'widgets_init', 'lis_register_sidebars' );
add_filter( 'wp_title', 'lis_page_title', 10, 2 );
add_filter( 'get_search_form', 'lis_search_form' );

?>
