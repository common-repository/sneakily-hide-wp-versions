<?php
/*
Plugin Name: Sneakily Hide WP Versions
Version: 1.0.6
Plugin URI: https://ruminativewp.com/products/sneakily-hide-wp-versions-plugin
Description: Hides the WordPress version everywhere it's displayed, including feed generator tags, static asset URLs, and load-styles.php and load-scripts.php.
Requires at least: 5.0
Requires PHP: 7.0
Author: Ruminative WP
Author URI: https://ruminativewp.com/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: sneakily-hide-wp-versions
Domain Path: /languages
*/

// WP loaded?
if ( ! function_exists('wp_fix_server_vars') ) {
	exit;
}

define( 'SHWV_DEBUG', true );    // don't ship / tag with true

define( 'SHWV_PREFIX', 'shwv' );

define( 'SHWV_OPTION_PREFIX', SHWV_PREFIX . '_' );

define( 'SHWV_TOP_MENU_SLUG', 'ruminativewp' );

define( 'SHWV_MENU_PAGE_SLUG', 'shwv' );

define( 'SHWV_LOAD_STYLES_SLUG', 'shwv-load-styles' );

define( 'SHWV_LOAD_SCRIPTS_SLUG', 'shwv-load-scripts' );

define( 'SHWV_MU_PLUGIN_PATH', WPMU_PLUGIN_DIR . '/' . 'shwv-mu-plugin.php' );

/*
 * Options
 */

define( 'SHWV_ACTIVE_OPTION_NAME', SHWV_OPTION_PREFIX . 'is_active' );

define( 'SHWV_NOTICES_OPTION_NAME', SHWV_OPTION_PREFIX . 'notices' );

define( 'SHWV_FAKE_VERS_OPTION_NAME', SHWV_OPTION_PREFIX . 'current_fake_wp_version' );

define( 'SHWV_NEEDS_CONFIG_DISPLAY_OPTION_NAME', SHWV_OPTION_PREFIX . 'needs_web_server_config_display' );

define( 'SHWV_GET_CREDS_ON_ACTIVATION_OPTION_NAME', SHWV_OPTION_PREFIX . 'get_creds_on_activation' );

define( 'SHWV_MU_PLUGIN_WRITTEN_OPTION_NAME', SHWV_OPTION_PREFIX . 'mu_plugin_written' );

define( 'SHWV_CREDS_FORM_ALREADY_ERROR_OPTION_NAME', SHWV_OPTION_PREFIX . 'creds_form_already_error' );

define( 'SHWV_UNKNOWN_FS_ERROR_OPTION_NAME', SHWV_OPTION_PREFIX . 'unknown_fs_error' );


define( 'SHWV_GET_CREDS_ON_DEACTIVATION_OPTION_NAME', SHWV_OPTION_PREFIX . 'get_creds_on_deactivation' );

/*
 * Actions
 */

define( 'SHWV_INCREASE_VERSION_ACTION', SHWV_PREFIX . '_' . 'increase_version' );

define( 'SHWV_DEACTIVATE_ACTION', SHWV_PREFIX . '_' . 'deactivate' );


function shwv_debug_log( $message ) {
	if ( SHWV_DEBUG ) {
		if ( is_array( $message ) ) {
			$message = print_r( $message, true );
		}
		$message = preg_replace( '#[^-a-zA-Z0-9.:,=>()/_&?% ]#', '', $message );
		error_log( 'SHWV: ' . $message );
	}
}

// Sometimes get_home_path() isn't available (e.g. JSON API)
function shwv_get_home_path() {
	if ( function_exists( 'get_home_path' ) ) {
		return get_home_path();
	} else {
		return ABSPATH;							// API
	}
}

// Same - got_mod_rewrite() sometimes isn't available
function shwv_got_mod_rewrite() {
	if ( function_exists( 'got_mod_rewrite' ) ) {
		return got_mod_rewrite();
	} else {
		$got_rewrite = apache_mod_loaded( 'mod_rewrite', true );	// API
		return apply_filters( 'got_rewrite', $got_rewrite );	
	}	
}

function shwv_can_write_htaccess() {
    if ( function_exists( 'get_home_path' ) ) {
        $htaccess_path = get_home_path() . '.htaccess';
    } else {
        $htaccess_path = ABSPATH . '.htaccess';     // API
    }
    if ( file_exists( $htaccess_path ) ) {
        return is_writable( $htaccess_path );
    } else {
        return is_writable( dirname( $htaccess_path ) );
    }
}

function shwv_get_webserver_name() {
    global $is_apache;
    global $is_nginx;
    
    if ( $is_apache ) {
        return 'Apache';
    } elseif ( $is_nginx ) {
        return 'nginx';
    } else {
        return 'unknown';
    }   
}   

function shwv_get_unknown_web_server_type_without_version() {
    $server = 'unknown';
    if ( array_key_exists( 'SERVER_SOFTWARE', $_SERVER ) ) {
        $server = $_SERVER['SERVER_SOFTWARE'];
    }
    if ( ! $server && array_key_exists( 'SERVER_SIGNATURE', $_SERVER ) ) {
        $server = $_SERVER['SERVER_SIGNATURE'];
    }
    $server = preg_replace( '/[^A-Za-z ]/', '', $server );
    return $server;
}

function shwv_remove_http_header_versions() {
    global $is_apache;
    global $is_nginx;

    if ( $is_nginx ) {
        $server_header_value = 'nginx';
    }
    elseif ( $is_apache ) {
        $server_header_value = 'Apache';
    }
    else {
        $server_header_value = shwv_get_unknown_web_server_type_without_version();
    }
    if ( ! headers_sent() ) {
        // Attempt to set HTTP Server: header, if SAPI allows (won't always work depending on config)
        header( 'Server: ' . $server_header_value );
        // Attempt to set X-Powered-By header - same limitations
        header( 'X-Powered-By: PHP' );
    }
}

// Returns true if user has needed capabilities, otherwise dies
function shwv_cap_check( $capability ) {
    if ( current_user_can( $capability ) ) {
        return true;
    } else {
        wp_die( esc_html__( 'You do not have the required permissions to perform this action', 'sneakily-hide-wp-versions' ) );
        return false;
    }
}

function shwv_display_notices() {
    // Attached to admin_notices - don't die
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $notices = get_option( SHWV_NOTICES_OPTION_NAME );

    if ( $notices ) {
        foreach ( $notices as $notice ) {
            switch( $notice['type'] ) {
                case 'info':
                    $add_class = '';
                    break;
                case 'error':
                    $add_class = 'error';
                    break;
                case 'success':
                default:                        // deliberate fall-through
                    $add_class = 'updated';
                    break;
            }
            $extra_class = '';
            if ( 'info' !== $notice['type'] ) {
                $extra_class = 'notice-' . $notice['type'];
            }
            ?>
                <div class="<?php echo esc_attr( $add_class ); ?> notice <?php echo esc_attr( $extra_class ); ?>">
                    <p><strong>Sneakily Hide WP Versions</strong>: <?php echo esc_html( $notice['message'] ); ?></p>
                </div>
            <?php
        }
        $notices = array();
        update_option( SHWV_NOTICES_OPTION_NAME, $notices );
    }
}

function shwv_add_notice( $message, $type = 'info' ) {
    if ( ! shwv_cap_check( 'manage_options' ) ) { return; }

    // These need to persist (possibly) between page loads, so use options
    $notices = get_option( SHWV_NOTICES_OPTION_NAME );
    if ( ! $notices ) {
        $notices = array();
    }
    $notices[] = array( 
        'message' => $message, 
        'type' => $type,
    );
    update_option( SHWV_NOTICES_OPTION_NAME, $notices );
}

function shwv_get_notices() : array {
    if ( ! shwv_cap_check( 'manage_options' ) ) { return array(); }

    $notices = get_option( SHWV_NOTICES_OPTION_NAME );
    if ( ! is_array( $notices ) ) {
        $notices = array( $notices );
    }
    return $notices;
}

function shwv_any_errors() {
    if ( ! shwv_cap_check( 'manage_options' ) ) { return; }

    foreach ( shwv_get_notices() as $notice ) {
        if ( ! $notice ) {
            continue;
        }
        if ( 'error' === $notice['type'] ) {
            return true;
        }
    }    
    return false;
}

// Remove any notices in DB, without removing the option
function shwv_zero_notices() {
    if ( ! shwv_cap_check( 'manage_options' ) ) { return; }

    $notices = array();
    update_option( SHWV_NOTICES_OPTION_NAME, $notices );
}

// Remove the option key from the DB
function shwv_delete_notices() {
    if ( ! shwv_cap_check( 'manage_options' ) ) { return; }

    delete_option( SHWV_NOTICES_OPTION_NAME );
}

// Hook the admin notice-display action. Note: don't use SHWV_NOTICES_OPTION_NAME directly, use
// provided functions.

add_action( 'admin_notices', 'shwv_display_notices' );
add_action( 'admin_notices', 'shwv_display_web_server_config' );

// Need early action to setup WP_Filesystem* and handle errors in writing mu-plugin
// (if there's an error, admin_init is early enough to either redirect or add a notice)
add_action( 'admin_init', 'shwv_setup_wp_filesystem' );
// But display should happen later
add_action( 'admin_notices', 'shwv_show_creds_form_create' );

// Same with deletion
add_action( 'admin_init', 'shwv_deactivate_cleanly' );
add_action( 'admin_notices', 'shwv_show_creds_form_delete' );


add_action( 'admin_enqueue_scripts', 'shwv_admin_enqueue_scripts' );


// https://developer.wordpress.org/reference/hooks/wp_headers/
// comment: if caching enabled, wp_headers filter doesn't work
add_action( 'init', 'shwv_remove_http_header_versions' );

// Replacement pages for load-styles.php, load-scripts.php
add_filter( 'pre_handle_404',		'shwv_handle_404' );
add_action( 'template_redirect',	'shwv_virtual_pages_content' );
add_filter( 'query_vars',			'shwv_filter_query_vars' );


register_activation_hook( __FILE__, 'shwv_plugin_activate' );

function shwv_plugin_activate() {
	global $wp_rewrite;
	global $wp_filesystem;
	global $is_nginx;
	global $is_apache;

	if ( ! shwv_cap_check( 'manage_options' ) ) { return; }

	// esc_html__ handles possibly unsafe translation data, not 'Plugin activated'
	shwv_debug_log( esc_html__( 'Plugin activated', 'sneakily-hide-wp-versions' ) );
	shwv_debug_log( 
		sprintf(
			// translators: %s: a web server
			esc_html__( 'Webserver detected: %s', 'sneakily-hide-wp-versions' ),
			esc_html( shwv_get_webserver_name() )
		) 
	);

	// Apache
	// - all about writability - if using permalinks (and with any WP hosting), allowoverride should be
	//	 enabled, because htaccess required.	
	// Nginx
	// - always display rules
	update_option( SHWV_NEEDS_CONFIG_DISPLAY_OPTION_NAME, false, 'no' );

	if ( $is_nginx ) {
		update_option( SHWV_NEEDS_CONFIG_DISPLAY_OPTION_NAME, 'true', 'no' );
	}

	if ( $is_apache) {
		if ( ! shwv_got_mod_rewrite() ) {
			// Because of default arg used in got_mod_rewrite() - this only displays
			// if apache_mod_loaded() result's filtered. Follow WP core in assuming present / default true.
			shwv_add_notice(
				esc_html__( 
					"mod_rewrite appears to be missing, but is required for this plugin to work. Unless mod_rewrite is already installed and enabled, please enable mod_rewrite, then deactivate and reactivate this plugin.",
					"sneakily-hide-wp-versions"	
				),
				"error"
			);
		}

		/* unless find another way to write htaccess rules - disabled for now
		if ( ! get_option( 'permalink_structure' ) && 'apache' === $server_type ) {
			update_option( SHWV_NEEDS_CONFIG_DISPLAY_OPTION_NAME, 'true', 'no' );
		}
		 */
		if ( ! get_option( 'permalink_structure' ) || $wp_rewrite->using_index_permalinks() ) {
			shwv_add_notice(
				esc_html__( 
					"Your site is not using mod_rewrite permalinks - permalinks are required for this plugin to work.",
					"sneakily-hide-wp-versions"	
				),
				"error"
			);
		}

		// Core code uses this logic to determine whether htaccess rule display needed:
		// $permalink_structure && ! $using_index_permalinks && ! $writable && $htaccess_update_required
		// If enabling this plugin, update is required.
		if ( get_option( 'permalink_structure' )
		&&	 ! $wp_rewrite->using_index_permalinks()
		&&	 ! shwv_can_write_htaccess()
		) {
			update_option( SHWV_NEEDS_CONFIG_DISPLAY_OPTION_NAME, 'true', 'no' );
		}
	}

    // Do we need credentials to write filesystem files? Either determine and set flag that
    // credentials are needed, or write the mu-plugin
    update_option( SHWV_GET_CREDS_ON_ACTIVATION_OPTION_NAME, 'true', 'no' ); 
    update_option( SHWV_MU_PLUGIN_WRITTEN_OPTION_NAME, false, 'no' );
	update_option( SHWV_CREDS_FORM_ALREADY_ERROR_OPTION_NAME, false, 'no' );
	update_option( SHWV_UNKNOWN_FS_ERROR_OPTION_NAME, false, 'no' );
	update_option( SHWV_GET_CREDS_ON_DEACTIVATION_OPTION_NAME, false, 'no' );
    shwv_setup_wp_filesystem();	

	// Generate and store the fake version number, if there isn't one
	shwv_get_current_fake_wp_version();

	update_option( SHWV_ACTIVE_OPTION_NAME, 'true', 'no' );
	
	if ( get_option( 'permalink_structure' ) ) {
		$wp_rewrite->flush_rules( true );		// this is equivalent to flush_rewrite_rules()
	}

	// TODO multisite
}

// Problem: mu plugin might have been created using FTP creds, which aren't extant when
// the main plugin is deactivated. As a mu-plugin it will continue taking effect, and needs
// to be deleted
function shwv_possibly_remove_mu_plugin() {
	global $wp_filesystem;

	if ( is_file( SHWV_MU_PLUGIN_PATH ) ) {
		return $wp_filesystem->delete( SHWV_MU_PLUGIN_PATH );
	}
	return true;
}

register_deactivation_hook( __FILE__, 'shwv_plugin_deactivate' );

// This is only part of deactivation - also _deactivate_cleanly() to remove mu-plugin
function shwv_plugin_deactivate() {
	global $wp_rewrite;
	global $wp_filesystem;

	if ( ! shwv_cap_check( 'manage_options' ) ) { return; }

	update_option( SHWV_ACTIVE_OPTION_NAME, false, 'no' ); 

    update_option( SHWV_GET_CREDS_ON_ACTIVATION_OPTION_NAME, false, 'no' );
    update_option( SHWV_MU_PLUGIN_WRITTEN_OPTION_NAME, false, 'no' );
	update_option( SHWV_NEEDS_CONFIG_DISPLAY_OPTION_NAME, false, 'no' );
	update_option( SHWV_CREDS_FORM_ALREADY_ERROR_OPTION_NAME, false, 'no' );
	update_option( SHWV_UNKNOWN_FS_ERROR_OPTION_NAME, false, 'no' );
	update_option( SHWV_GET_CREDS_ON_DEACTIVATION_OPTION_NAME, false, 'no' );

	if ( get_option( 'permalink_structure' ) ) {
		$wp_rewrite->flush_rules( true );
	}
	
	shwv_debug_log( esc_html__( 'Plugin deactivated', 'sneakily-hide-wp-versions' ) );
	shwv_zero_notices();
	// TODO multisite
}

register_uninstall_hook( __FILE__, 'shwv_plugin_uninstall' );

function shwv_plugin_uninstall() {
	if ( ! shwv_cap_check( 'manage_options' ) ) { return; }

	// Resetting fake version number on reinstall is ok, so deleting during uninstall is probably
	// the correct thing.
	delete_option( SHWV_NEEDS_CONFIG_DISPLAY_OPTION_NAME );
	delete_option( SHWV_FAKE_VERS_OPTION_NAME );
	delete_option( SHWV_ACTIVE_OPTION_NAME );
    delete_option( SHWV_GET_CREDS_ON_ACTIVATION_OPTION_NAME );
    delete_option( SHWV_MU_PLUGIN_WRITTEN_OPTION_NAME );	
	delete_option( SHWV_CREDS_FORM_ALREADY_ERROR_OPTION_NAME );
	delete_option( SHWV_UNKNOWN_FS_ERROR_OPTION_NAME );
	delete_option( SHWV_GET_CREDS_ON_DEACTIVATION_OPTION_NAME );
	shwv_delete_notices();
	// TODO multisite
}

function shwv_admin_enqueue_scripts( $hook_suffix ) {
	if ( 'plugins.php' === $hook_suffix 
	||   'tools_page_ruminativewp_shwv' === $hook_suffix 
	) {
		wp_enqueue_style( SHWV_PREFIX . '_styles', plugins_url( 'shwv.css', __FILE__ ), array(), '1.0' );
	}
}

function shwv_display_web_server_config() {
	global $is_nginx;
	global $is_apache;

	// Don't use shwv_cap_check() - attached to admin_notices
	if ( ! current_user_can( 'manage_options' ) ) { 
		return; 
	}
	if ( ! get_option( SHWV_NEEDS_CONFIG_DISPLAY_OPTION_NAME ) ) {
		return;
	}
	
	if ( $is_nginx ):
	?>
	<div class="updated notice">
		<h2>Sneakily Hide WP Versions</h2>
		<h4><?php esc_html_e( 'Please insert these rules in your Nginx config', 'sneakily-hide-wp-versions' ); ?></h4>	
		<p><?php esc_html_e( "We've detected you're using:", "sneakily-hide-wp-versions" ); ?> <strong>nginx</strong>.
		<?php 
		// translators: 1. 'server {' 2. 'location {' 3. 'try_files'
		printf( 
			esc_html__(
				'Please insert the following rules into your Nginx config within a %1$s block, and before your %2$s block (this is probably a block with a %3$s directive), and reload Nginx. If you\'re using a caching plugin, clear your caches after reloading nginx.', 
				'sneakily-hide-wp-versions'
			),
			'<code>server {</code>',
			'<code>location /</code>',
			'<code>try_files</code>'
		); 
		echo '<strong> '; esc_html_e( 'Remember to remove the rules if you deactivate this plugin.', 'sneakily-hide-wp-versions' ); echo '</strong>';
		?></p>
		<pre class="shwv-web-server-config"><code><?php echo esc_html( shwv_get_nginx_rules() ); ?></code></pre>
	</div>
	<?php		
	endif;	// nginx

	if ( $is_apache ):
	?>
	<div class="updated notice">
		<h2>Sneakily Hide WP Versions</h2>
		<h4><?php esc_html_e( "Please insert these rules in your .htaccess file or Apache config. If you're using a caching plugin, clear your caches after reloading Apache.", "sneakily-hide-wp-versions"); ?> <?php esc_html_e( 'Remember to remove the rules if you deactivate this plugin.', 'sneakily-hide-wp-versions' ); ?></h4>
		<p><?php 
			// translators: %s: 1. '.htaccess' 2. 'RewriteBase /'
			printf(
				esc_html__( 'If %1$s, after %2$s', 'sneakily-hide_wp-versions' ),
				'<code>.htaccess</code>',
				'<code>RewriteBase /</code>'
			);
		?></p>
		<p><?php esc_html_e( "We've detected you're using:", "sneakily-hide-wp-versions" ); ?> <strong>Apache</strong>.</p>

<?php // TODO breaking layout ?>
		<pre class="shwv-web-server-config"><code><?php echo esc_html( shwv_get_apache_rules() ); ?></code></pre>
	</div>
	<?php
	endif;	// apache

	delete_option( SHWV_NEEDS_CONFIG_DISPLAY_OPTION_NAME );
}

function shwv_null_error_handler() {
	// An error handler that doesn't do anything, and doesn't continue to PHP's error
	// handlers, to prevent some UI breakage with WP_DEBUG
	return true;
}

// A mu-plugin is able to change WP version in upgrade.php - write one when plugin activated
// and remove it when deactivated. This is to avoid directly loading core files etc.
function shwv_write_mu_plugin_file() {
    global $wp_filesystem;
    global $is_apache;

    if ( ! shwv_cap_check( 'manage_options' ) ) { return; }

    // Filesystem subsystem needs to be initialized by now

	if ( ! $wp_filesystem->is_dir( WPMU_PLUGIN_DIR ) ) {
		$ret = $wp_filesystem->mkdir( WPMU_PLUGIN_DIR );
		if ( ! $ret ) { 
			shwv_add_notice(
				sprintf(
					// translators: %s: WPMU_PLUGIN_DIR
					esc_html__(
						"Failed to create mu-plugins directory (%s). Please check the permissisions so this directory exists or can be created, and deactivate/reactivate the plugin",
						"sneakily-hide-wp-versions" 
					),  
					WPMU_PLUGIN_DIR
				),  
				"error"
			);
			return false;
		}   
	}

    $ret = $wp_filesystem->put_contents(
		SHWV_MU_PLUGIN_PATH,
		"<?php
\$fake_wp_version = get_option( '" . SHWV_FAKE_VERS_OPTION_NAME . "' );

add_action( 'init', 'shwv_mu_init' );

function shwv_mu_init() {
    global \$fake_wp_version;

    // Replace WP version in singletons
    \$wp_scripts = wp_scripts();
    \$wp_scripts->default_version = \$fake_wp_version;
    \$wp_styles  = wp_styles();
    \$wp_styles->default_version  = \$fake_wp_version;

    // There's 2 ways css is loaded using wp_admin_css() - the singletons WP_Styles / WP_Scripts,
    // handled above, and direct html output with wp_admin_css_uri(). Handle that.
    add_filter( 'wp_admin_css', 'shwv_replace_wp_version_in_url', 1000, 2 );
}

function shwv_replace_wp_version_in_url( \$stylesheet_link, \$file ) {
    global \$wp_version;
    global \$fake_wp_version;
    \$link = str_replace( \"version=\$wp_version\", \"version=\$fake_wp_version\", \$stylesheet_link );
    return \$link;
}
		",
		FS_CHMOD_FILE
    );
    if ( $ret ) {
        shwv_add_notice(
			esc_html__(
				"Saved mu plugin." . ( get_option( SHWV_NEEDS_CONFIG_DISPLAY_OPTION_NAME ) ? "" : " Activation completed." ),
				"sneakily-hide-wp-versions"
			),
            "success"
        );
        if ( $is_apache && ! get_option( SHWV_NEEDS_CONFIG_DISPLAY_OPTION_NAME ) ) {
            // Use existing translations
            shwv_add_notice(
                esc_html__( "If you're using a caching plugin, clear your caches now", "sneakily-hide-wp-versions" ),
                "success"
            );
        }
    } else {
        shwv_add_notice(
            sprintf(
                esc_html__(
					// translators: %s: WPMU_PLUGIN_DIR
                    "Couldn't write mu plugin - please make sure the mu-plugins directory (%s) is writable or can be created - ask your system administrator if needed.",
                    "sneakily-hide-wp-versions"
                ),
                WPMU_PLUGIN_DIR
            ),
            "error"
        );

        // Redirect to avoid displaying error on page *after* post result page - confusing
        wp_redirect( shwv_get_settings_page_url() . '&write_error=true', 302 );
        exit;
    }
}

function shwv_setup_wp_filesystem() {			// admin_init
	global $wp_filesystem;

    // Post-condition: these 2 options init'ed, SHWV_GET_CREDS_ON_ACTIVATION_OPTION_NAME, SHWV_MU_PLUGIN_WRITTEN_OPTION_NAME, ob_end or equiv in all branches

    // Don't use shwv_cap_check() - because this is attached to admin_init (e.g. Author using admin)
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    // If Filesystem API creds were provided and correct, but writing files didn't work, don't continue
    if ( isset( $_GET['write_error'] ) && 'true' === $_GET['write_error'] ) {
        return;
    }

	if ( ! get_option( SHWV_GET_CREDS_ON_ACTIVATION_OPTION_NAME ) 
	&&	   get_option( SHWV_MU_PLUGIN_WRITTEN_OPTION_NAME ) 
	) {
        // Nothing to do
        return;
    }

    $url = shwv_get_settings_page_url();

	// Determine context. WP_Filesystem not needed for this, since WP core loads MU-plugins this way
	if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
		$context = dirname( WPMU_PLUGIN_DIR );
	} else {
		$context = WPMU_PLUGIN_DIR;	
	}

    //  Use our error handler - avoid body class php-error with WP_DEBUG. TODO core?
    // TODO ok leaving enabled for whole function?
    $prev = set_error_handler( 'shwv_null_error_handler' );

    // request_filesystem_credentials() does nonce handling & $_POST data extraction
    // need to handle:
    //  * @return bool|array True if no filesystem credentials are required,
    //  *                    false if they are required but have not been provided,
    //  *                    array of credentials if they are required and have been provided.
    ob_start();
    $creds = request_filesystem_credentials( $url, '', false, $context, null, true );   // TODO relaxed
    ob_end_clean();

    if ( true === $creds ) {
        if ( WP_Filesystem( false, $context, true ) ) { // TODO relaxed
			shwv_write_mu_plugin_file();
            update_option( SHWV_GET_CREDS_ON_ACTIVATION_OPTION_NAME, false, 'no' ); 
            update_option( SHWV_MU_PLUGIN_WRITTEN_OPTION_NAME, 'true', 'no' );
        } else {
            update_option( SHWV_GET_CREDS_ON_ACTIVATION_OPTION_NAME, 'true', 'no' ); 
            update_option( SHWV_MU_PLUGIN_WRITTEN_OPTION_NAME, false, 'no' );
			update_option( SHWV_UNKNOWN_FS_ERROR_OPTION_NAME, 'true', 'no' );
            shwv_add_notice(
                esc_html__(
                    "Couldn't initialize WP_Filesystem. This shouldn't happen",
                    "sneakily-hide-wp-versions"
                ),
                "error"
            );
        }
    } elseif ( false === $creds ) {
        // Does need credentials
        update_option( SHWV_GET_CREDS_ON_ACTIVATION_OPTION_NAME, 'true', 'no' ); 
        update_option( SHWV_MU_PLUGIN_WRITTEN_OPTION_NAME, false, 'no' );
    } else {
        if ( WP_Filesystem( $creds, $context, true ) ) {    // TODO relaxed
			shwv_write_mu_plugin_file();
            update_option( SHWV_GET_CREDS_ON_ACTIVATION_OPTION_NAME, false, 'no' ); 
            update_option( SHWV_MU_PLUGIN_WRITTEN_OPTION_NAME, 'true', 'no' );
        } else {
            update_option( SHWV_GET_CREDS_ON_ACTIVATION_OPTION_NAME, 'true', 'no' ); 
            update_option( SHWV_MU_PLUGIN_WRITTEN_OPTION_NAME, false, 'no' );
			update_option( SHWV_CREDS_FORM_ALREADY_ERROR_OPTION_NAME, 'true', 'no' );
        }
    }
    set_error_handler( $prev );
}

function shwv_show_creds_form_create() {					// admin_notices
    // Don't use shwv_cap_check() - consider e.g. Author using admin (this is attached to admin_notices)
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( ! get_option( SHWV_GET_CREDS_ON_ACTIVATION_OPTION_NAME ) ) {
        return;
    }

	$url = shwv_get_settings_page_url();	
	?>
    <div class="updated notice">
        <h2>Sneakily Hide WP Versions</h2>
        <p><?php
		if ( ! get_option( SHWV_UNKNOWN_FS_ERROR_OPTION_NAME, false ) ) {
			esc_html_e(
                "We need to create some filesystem files, but permissions aren't set to allow this. Please enter your web server connection details.",
                "sneakily-hide-wp-versions"
            );
			?>
		</p>
		<?php
			// Determine context. WP_Filesystem not needed for this, since WP core loads MU-plugins this way
			if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
				$context = dirname( WPMU_PLUGIN_DIR );
			} else {
				$context = WPMU_PLUGIN_DIR;	
			}
			$error = false;
			if ( get_option( SHWV_CREDS_FORM_ALREADY_ERROR_OPTION_NAME, false ) ) {
				$error = true;
			}
			request_filesystem_credentials( $url, '', $error, $context, null, true );
		} 
		?>
    </div> <!-- .updated .notice -->
<?php
}

function shwv_get_fs_data() {
    if ( ! shwv_cap_check( 'manage_options' ) ) { return; }

    $pdp = plugin_dir_path( __FILE__ );
    $ret = $pdp . "\n\n";
    $ret .= print_r( stat( $pdp ), true ) . "\n\n";
    $ret .= "uid: " . (string) posix_getuid() . ", gid: " . (string) posix_getgid() . "\n\n";
    $ret .= "euid: " . (string) posix_geteuid() . ", egid: " . (string) posix_getegid() . "\n\n";
    return $ret;
}

function shwv_get_file( $path ) { 
	// From noop.php
    $path = realpath( $path );

    if ( ! $path || ! @is_file( $path ) ) { 
        return ''; 
    }   

    return @file_get_contents( $path );
} 

function shwv_sanitize_load_handles( $load_handles ) {
	if ( is_array( $load_handles ) ) { 
		ksort( $load_handles );
		$load_handles = implode( '', $load_handles );
	}
	return preg_replace( '/[^a-z0-9,_-]+/i', '', $load_handles );
}

function shwv_sanitize_dir( $dir ) {
	if ( 'rtl' !== $dir && 'ltr' !== $dir ) {
		return '';
	}
	return $dir;
}

function shwv_sanitize_action( $action ) {
	return preg_replace( '/[^a-zA-Z_-]/', '', $action );
}

function shwv_sanitize_nonce( $nonce ) {
	return preg_replace( '/[^a-f0-9]/', '', $nonce );
}

// Might be too restrictive in the general case - use internally only
function shwv_sanitize_plugin( $plugin ) {
	return preg_replace( '#[^-a-zA-Z/.]#', '', $plugin );
}

function shwv_load_styles() {
	$fake_wp_version = shwv_get_current_fake_wp_version();

	// load-styles.php from 5.8.1, reworked to replace WP version with fake version

	if ( array_key_exists( 'SERVER_PROTOCOL', $_SERVER ) ) {
		$protocol = $_SERVER['SERVER_PROTOCOL'];
		if ( ! in_array( $protocol, array( 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0' ), true ) ) { 
			$protocol = 'HTTP/1.0';
		}
	} else {
		$protocol = 'HTTP/1.0';	
	}

	if ( array_key_exists( 'load', $_GET ) ) {
		$load = shwv_sanitize_load_handles( $_GET['load'] );
	} else {
		header( "$protocol 400 Bad Request" );
		exit;
	}
	$load = array_unique( explode( ',', $load ) );

	if ( empty( $load ) ) {
		header( "$protocol 400 Bad Request" );
		exit;
	}

	$rtl            = ( isset( $_GET['dir'] ) && 'rtl' === shwv_sanitize_dir( $_GET['dir'] ) );
	$expires_offset = 31536000; // 1 year.
	$out            = '';

	$wp_styles = new WP_Styles();
	wp_default_styles( $wp_styles );

	if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) === $fake_wp_version ) {
		header( "$protocol 304 Not Modified" );
		exit;
	}

	foreach ( $load as $handle ) {
		if ( ! array_key_exists( $handle, $wp_styles->registered ) ) {
			continue;
		}

		$style = $wp_styles->registered[ $handle ];

		if ( empty( $style->src ) ) {
			continue;
		}

		$path = ABSPATH . $style->src;

		if ( $rtl && ! empty( $style->extra['rtl'] ) ) {
			// All default styles have fully independent RTL files.
			$path = str_replace( '.min.css', '-rtl.min.css', $path );
		}

		$content = shwv_get_file( $path ) . "\n";

		if ( strpos( $style->src, '/' . WPINC . '/css/' ) === 0 ) {
			$content = str_replace( '../images/', '../' . WPINC . '/images/', $content );
			$content = str_replace( '../js/tinymce/', '../' . WPINC . '/js/tinymce/', $content );
			$content = str_replace( '../fonts/', '../' . WPINC . '/fonts/', $content );
			$out    .= $content;
		} else {
			$out .= str_replace( '../images/', 'images/', $content );
		}
	}

	header( "Etag: $fake_wp_version" );
	header( 'Content-Type: text/css; charset=UTF-8' );
	header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $expires_offset ) . ' GMT' );
	header( "Cache-Control: public, max-age=$expires_offset" );

	echo $out;		// This is returned to browser with a set MIME type
	exit;          
}

function shwv_load_scripts() {
	$fake_wp_version = shwv_get_current_fake_wp_version();
	
	// load-scripts.php from 5.8.1, reworked to replace WP version with fake version

	if ( array_key_exists( 'SERVER_PROTOCOL', $_SERVER ) ) {
		$protocol = $_SERVER['SERVER_PROTOCOL'];
		if ( ! in_array( $protocol, array( 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0' ), true ) ) { 
			$protocol = 'HTTP/1.0';
		}
	} else {
		$protocol = 'HTTP/1.0';	
	}

	if ( array_key_exists( 'load', $_GET ) ) {
		$load = shwv_sanitize_load_handles( $_GET['load'] );
	} else {
		header( "$protocol 400 Bad Request" );
		exit;
	}
	$load = array_unique( explode( ',', $load ) );

	if ( empty( $load ) ) {
		header( "$protocol 400 Bad Request" );
		exit;
	}

	$expires_offset = 31536000; // 1 year.
	$out            = '';

	$wp_scripts = new WP_Scripts();
	wp_default_scripts( $wp_scripts );
	wp_default_packages_vendor( $wp_scripts );
	wp_default_packages_scripts( $wp_scripts );

	// TODO (and load-styles) - problem with this header and enable/disable this plugin?
	if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) === $fake_wp_version ) {
		header( "$protocol 304 Not Modified" );
		exit;
	}

	foreach ( $load as $handle ) {
		if ( ! array_key_exists( $handle, $wp_scripts->registered ) ) {
			continue;
		}

		$path = ABSPATH . $wp_scripts->registered[ $handle ]->src;
		$out .= shwv_get_file( $path ) . "\n";
	}

	header( "Etag: $fake_wp_version" );
	header( 'Content-Type: application/javascript; charset=UTF-8' );
	header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $expires_offset ) . ' GMT' );
	header( "Cache-Control: public, max-age=$expires_offset" );

	echo $out;			// This is returned to browser with a set MIME type
	exit;   
}

function shwv_handle_404( $preempt ) {
	if ( get_query_var( 'shwv_virtual_pagename' ) ) {
		return true;
	} else {
		return $preempt;
	}
}

function shwv_filter_query_vars( $vars ) {
	$vars[] = 'shwv_virtual_pagename';
	$vars[] = 'load';
	$vars[] = 'c';
	$vars[] = 'dir';
	return $vars;
}

function shwv_virtual_pages_content() {				// action template_redirect
	$virtual_pagename = get_query_var( 'shwv_virtual_pagename' );

	switch ( $virtual_pagename ) {
		case SHWV_LOAD_STYLES_SLUG:
			shwv_load_styles();
			break;
		case SHWV_LOAD_SCRIPTS_SLUG:
			shwv_load_scripts();
			break;
	}
}

// Code to build URLs to rewrite - load-styles.php, load-scripts.php
function shwv_get_rewrite_url( $slug ) {
	// Constructing and then parsing URL looks like a waste of time, but necessary if 
	// WP installed at e.g. domain.com/wordpress/index.php
	$url = get_site_url( null, "index.php?shwv_virtual_pagename=$slug" );						// TODO multisite
	$parsed_url = parse_url( $url );

	$rewrite_url = $parsed_url['path'];	
	$rewrite_url .= '?' . $parsed_url['query'];
	if ( '/' === $rewrite_url[0] ) {
		$rewrite_url = substr( $rewrite_url, 1 );
	}
	return $rewrite_url;
}

function shwv_get_apache_rules() {
	$load_scripts_url = shwv_get_rewrite_url( SHWV_LOAD_SCRIPTS_SLUG );
	$load_styles_url  = shwv_get_rewrite_url( SHWV_LOAD_STYLES_SLUG );

	$rules = "
RewriteRule .*wp-admin/load-scripts.php /$load_scripts_url&%{QUERY_STRING} [NC,L]
RewriteRule .*wp-admin/load-styles.php /$load_styles_url&%{QUERY_STRING} [NC,L]
ServerSignature Off

";
	return $rules;
}

function shwv_get_nginx_rules() {
	$load_scripts_url = shwv_get_rewrite_url( SHWV_LOAD_SCRIPTS_SLUG );
	$load_styles_url  = shwv_get_rewrite_url( SHWV_LOAD_STYLES_SLUG );

	$rules = "
server_tokens Off;
rewrite (?i)^.*wp-admin/load-scripts.php /$load_scripts_url&\$args last;
rewrite (?i)^.*wp-admin/load-styles.php /$load_styles_url&\$args last;
";
	return $rules;
}

// Old way of URL filtering (plugins_loaded action then check $_SERVER['REQUEST_URI'] is not 
// actually working. an install.php in /wp-content/ is loaded
// and that can overwrite the default_version on the WP_Styles singleton - that'll
// take effect in install.php and upgrade.php, but these need different handling, and it's
// best to block install.php entirely anyway.

add_filter( 'mod_rewrite_rules', 'shwv_filter_rules', 100, 1 );

// Filter mod_rewrite rules for Apache, adding some rules after RewriteBase,
// handling activate and deactivate states. As long as permalinks are enabled,
// this works almost all the time - the exception is when WP core has an upgrade. 
// Then, if upgrade_560() runs, HTTP_AUTHORIZATION rewrite rule is added, and 
// save_mod_rewrite_rules() is called without our plugin being active (wp_get_active_and_valid_plugins()
// is called in the context of WP_INSTALLING true, returning no plugins), so our rules aren't included.
// .maintenance doesn't help much, because while the site isn't accessible, /wp-admin/install.php,
// /wp-admin/upgrade.php, and /wp-admin/load-* are accessible, and without our rules, the real
// version is exposed through these URLs.
// This affects WP versions older than ~ 5.6. Stats page 14 Sept 2021, about 1/3rd of WP 
// installations will have this problem - while 2/3 of WP installations tracked by 
// wordpress.org/about/stats/ are using 5.6 or greater and are ok. Do the best we can with versions < 5.6
// with after_db_upgrade action - first (?) action called in non WP_INSTALLING context. This keeps the 
// window of exposure, where real version is visible, as small as possible.

function shwv_filter_rules( $rules ) {
	$plugin_active = get_option( SHWV_ACTIVE_OPTION_NAME, 'default' );
	if ( ! $plugin_active || 'default' === $plugin_active ) {
		return $rules;
	}	
	// WP core uses \n line endings only for .htaccess
	$lines = explode( "\n", $rules );
	$base_prev = false;
	$filtered_rules = '';

	foreach ( $lines as $line ) {
		if ( $base_prev ) {
			$base_prev = false;

			// Additions 
			$filtered_rules .= shwv_get_apache_rules();
		}
		if ( preg_match( '#^RewriteBase #', $line ) ) {
			$base_prev = true;
		}
		$filtered_rules .= "$line\n";
	}
	return $filtered_rules;
}

// See comment above
add_action( 'after_db_upgrade', 'shwv_after_db_upgrade' );

function shwv_after_db_upgrade() {
	global $wp_rewrite;

	// This doesn't work if save_mod_rewrite_rules() isn't defined!
	require_once shwv_get_home_path() . 'wp-admin/includes/misc.php';
	$wp_rewrite->flush_rules( true );
}


if ( SHWV_DEBUG ):

// Testing: dispatch the upgrader_process_complete action
function shwv_perform_fake_upgrade() {
	// Only admins should be allowed to trigger the fake-WP-upgrade
	if ( ! shwv_cap_check( 'update_core' ) ) {
		return; 
	}

	// Bootstrapping admin still doesn't pull in everything needed - globals etc
	include_once ABSPATH . 'wp-admin/includes/admin.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	do_action(
		'upgrader_process_complete',
		new Core_Upgrader(),
		array(
			'action' => 'update',
			'type'	 => 'core',
		)
	);
	shwv_add_notice( 
		esc_html__( 'Dispatched fake WP upgrade action', 'sneakily-hide-wp-versions' ),
		'success'
	);
}

endif;	// SHWV_DEBUG


add_action( 'wp_loaded', 'shwv_change_admin_default_version' );

function shwv_change_admin_default_version() {
	$fake_version = shwv_get_current_fake_wp_version();
	// Update the WP_Scripts and WP_Styles singletons with fake version to use in admin 
	// scripts / styles URLs.
	// The $wp_styles default works as early as 'plugin_loaded', but script-loader.php
	// overwrites the $wp_scripts default version - so attach this either *latest* onto
	// the wp_default_scripts action, or (safer) a later action e.g. wp_loaded.
	$wp_scripts = wp_scripts();
	$wp_scripts->default_version = $fake_version;
	$wp_styles	= wp_styles();
	$wp_styles->default_version  = $fake_version;
}

function shwv_filter_the_generator( $generator_type, $type ) {
	// TODO allow fake generator
	return '';
}

/*
 * Use 1,000,000 for the priority, to run the callback as late as possible and hopefully avoid other 
 * filters potentially re-adding information 
 */

add_filter( 'the_generator', 'shwv_filter_the_generator', 1000000, 2 );
add_filter( 'get_the_generator_html', 'shwv_filter_the_generator', 1000000, 2 );
add_filter( 'get_the_generator_xhtml', 'shwv_filter_the_generator', 1000000, 2 );

// Older WP versions
add_filter( 'get_the_generator_atom', 'shwv_filter_the_generator', 1000000, 2 );
add_filter( 'get_the_generator_rss2', 'shwv_filter_the_generator', 1000000, 2 );
add_filter( 'get_the_generator_rdf',  'shwv_filter_the_generator', 1000000, 2 );
add_filter( 'get_the_generator_comment', 'shwv_filter_the_generator', 1000000, 2 );

function shwv_replace_wp_vers_in_url( $url ) {
	// Naively replacing the version string with nothing works to hide the WP version,
	// but it breaks cache-busting: upgrading WP will result in clients using assets
	// from the previous WP version, potentially breaking the site. Use a fake WP version
	// and bump it whenever WP core is upgraded to hide the actual WP version and still have
	// working cache-busting.
	$wp_vers_regex = str_replace( '.', '[.]', get_bloginfo('version') );
	$fake_version = shwv_get_current_fake_wp_version();
	$tmp = preg_replace( '#[?]ver=' . $wp_vers_regex . '#',   "?ver=$fake_version",		$url );
	$tmp = preg_replace( '#[?]ver=' . $wp_vers_regex . '&#',  "?ver=$fake_version&",	$tmp );
	$tmp = preg_replace( '#&amp;ver=' . $wp_vers_regex . '#', "&amp;ver=$fake_version", $tmp );
	$tmp = preg_replace( '#&ver=' . $wp_vers_regex . '#',	  "&ver=$fake_version",		$tmp );
	return $tmp;
}

add_filter( 'style_loader_tag', 'shwv_filter_style_loader_tags', 100, 4 );
function shwv_filter_style_loader_tags( $html, $handle, $href, $media ) {
	return shwv_replace_wp_vers_in_url( $html );
}

add_filter( 'script_loader_tag', 'shwv_filter_script_loader_tags', 100, 3 );
function shwv_filter_script_loader_tags( $tag, $handle, $src ) {
	return shwv_replace_wp_vers_in_url( $tag );
}

// Perversely, emoji assets are handled differently, so handle them separately
// 'concatemoji' => apply_filters( 'script_loader_src', includes_url( "js/wp-emoji-release.min.js?$version" ), 'concatemoji' ),
add_filter( 'script_loader_src', 'shwv_filter_emoji_src', 100, 2 );
function shwv_filter_emoji_src( $src, $handle ) {
	$fake_version = shwv_get_current_fake_wp_version();
	if ( $handle == 'concatemoji' ) {
		return includes_url( "js/wp-emoji-release.min.js?$fake_version" ); 
	} else {
		return $src;
	}
}

function shwv_get_randomish_float() {
	// Get a pseudo-random float value that can't be 0.0 (to eval true and
	// prevent get_option() returning false if there's something in the DB)
	// Add at least 10.0 - to avoid any real WordPress versions for the forseeable future
	$val = ( ( mt_rand() % 100000 ) / 10.0 ) + 10.0;
	// Ensure a decimal point - better likelihood of match w version-number regex
	if ( false === strpos( (string) $val, '.' ) ) {
		$val += 0.1;		// This value needs to ensure a decimal point - random values can cancel
	}
	return $val;
}

function shwv_update_db_fake_vers() {
	$fake_version = shwv_get_randomish_float();
	shwv_debug_log(
		sprintf(
			// translators: %s: a version number
			esc_html__( 'Fake version: %s', 'sneakily-hide-wp-versions' ),
			esc_html( $fake_version )
		)
	);
	update_option( SHWV_FAKE_VERS_OPTION_NAME, $fake_version, 'no' );
}

function shwv_get_current_fake_wp_version() : string {
	// This option stores a random float (a fake version-y looking number). 
	// This is used instead of the real WP version. Whenever WP core is updated,
	// this fake number is increased, to keep cache-busting working.
	$fake_version = get_option( SHWV_FAKE_VERS_OPTION_NAME );
	// If something's gone wrong with the DB, update the value 
	if ( ! (float) $fake_version ) {
		shwv_update_db_fake_vers();
	}
	return $fake_version;
}

/** This action is documented in wp-admin/includes/class-wp-upgrader.php */
/*
		do_action(
			'upgrader_process_complete',
			$this,
			array(
				'action' => 'update',
				'type'	 => 'core',
			)	
		);	
 */

add_action( 'upgrader_process_complete', 'shwv_bump_fake_version_callback', 10, 2 );

// TODO doesn't work if manually unpack WP and upgrade DB - works only auto upgrade
// (store wp version, if it's changed and we have a page load, bump version - doesn't seem to be a reliable
// way to detect manual upgrade - in particular, some WP versions have same DB version)
function shwv_bump_fake_version_callback( WP_Upgrader $upgrader, $extra ) {
	global $wp_rewrite;

	if ( ! shwv_cap_check( 'manage_options' ) ) { 
		return; 
	}

	if ( array_key_exists( 'action', $extra ) && 'update' == $extra['action']
	&&	 array_key_exists( 'type', $extra )   && 'core'   == $extra['type']
	) {
		shwv_debug_log( esc_html__( 'Got core update action, bumping fake WP version', 'sneakily-hide-wp-versions' ) );
		shwv_bump_fake_version();
		if ( ! shwv_any_errors() ) {
			shwv_add_notice(
				// translators: %s: version number
				sprintf(
					esc_html__( "Increased the fake version with core upgrade, new value = %s", "sneakily-hide-wp-versions" ),
					esc_html( shwv_get_current_fake_wp_version() )
				),
				"success"
			);
			if ( get_option( 'permalink_structure' ) ) {
				$wp_rewrite->flush_rules( true );		// this is equivalent to flush_rewrite_rules()
			}
		}
	}
}

function shwv_bump_fake_version() {
	if ( ! shwv_cap_check( 'manage_options' ) ) { return; }

	// When WP core updated, increase the fake version stored in DB by a random amount
	// This keeps cache-busting working with version string in static asset urls
	$fake_version = (float) shwv_get_current_fake_wp_version();
	shwv_debug_log(
		sprintf(
			// translators: %s: version number
			esc_html__( "old fake version: %s", "sneakily-hide-wp-versions" ),
			esc_html( $fake_version )
		)
	);

	$bump_amount = shwv_get_randomish_float();
	$fake_version += $bump_amount;
	if ( false === strpos( (string) $fake_version, '.' ) ) {
		// Ensure . in new fake version
		$bump_amount += 0.1;
		$fake_version += 0.1;
	}

	shwv_debug_log(
		sprintf(
			// translators: %s: random floating-point quantity (similar to version number)
			esc_html__( "bump amount: %s", "sneakily-hide-wp-versions" ),
			esc_html( $bump_amount )
		)
	);
	shwv_debug_log(
		sprintf(
			// translators: %s: version number
			esc_html__( "new fake version: %s", "sneakily-hide-wp-versions" ),
			esc_html( $fake_version )
		)
	);
	update_option( SHWV_FAKE_VERS_OPTION_NAME, $fake_version, 'no' );
}

function shwv_get_settings_page_slug() {
	return SHWV_TOP_MENU_SLUG . '_' . SHWV_MENU_PAGE_SLUG;
}

function shwv_get_settings_page_url() {
	// TODO multisite
	// TODO > 1 plugin
	return get_admin_url( null, '/tools.php?page=' . shwv_get_settings_page_slug() );		
}

add_action( 'admin_menu', 'shwv_options_menu' );

// If one RWP plugin installed, add settings page to Tools menu (recommended 
// in this page https://developer.wordpress.org/plugins/administration-menus/). If > 1 RWP plugin
// installed, create RWP top-level menu and add there.

function shwv_options_menu() {
	global $menu;
	$add_top_menu = true;

	// Check if other RWP plugins present
	$plugins = get_plugins();

	$count_ruminative = 0;
	foreach ( $plugins as $file => $data ) {
		if ( is_plugin_active( $file ) 
			 && array_key_exists( 'Author', $data ) 
			 &&	false !== strpos( $data['Author'], 'Ruminative WP' ) 
		) {
			$count_ruminative += 1;
		}
	}
	if ( $count_ruminative > 1 ) {
		// TODO enable when another plugin available - don't waste bandwidth - 80K base64.txt
//		shwv_debug_log( "Found other RWP plugin" );
//
//		// TODO (when another plugin is available)
//		foreach ( $menu as $i => $item ) {
//			if ( SHWV_TOP_MENU_SLUG === $item[2] ) {
//				$add_top_menu = false;
//				break;
//			}
//		}
//
//		if ( $add_top_menu ) {
//			add_menu_page(
//				'',
//				'Ruminative WP',
//				'manage_options',
//				SHWV_TOP_MENU_SLUG,
//				'',
//				'data:image/svg+xml;base64,' . file_get_contents(
//					plugin_dir_path( __FILE__ ) . 'admin/images/brain_base64.txt'
//				)
//			);
//		}
//
//		add_submenu_page(
//			SHWV_TOP_MENU_SLUG,
//			'Sneakily Hide WP Versions - ' . esc_html__( 'Settings', 'sneakily-hide-wp-versions' ),
//			'Sneakily Hide WP Versions',
//			'manage_options',
//			SHWV_TOP_MENU_SLUG . '_' . SHWV_MENU_PAGE_SLUG,
//			'shwv_options_page_html' 
//		);
	} else {
		add_submenu_page(
			'tools.php',
			'Sneakily Hide WP Versions - ' . esc_html__( 'Settings', 'sneakily-hide-wp-versions' ),
			'Sneakily Hide WP Versions',
			'manage_options',
			shwv_get_settings_page_slug(),
			'shwv_options_page_html'
		);
	}

}

function shwv_options_page_html() {
       global $is_apache;

       if ( ! shwv_cap_check( 'manage_options' ) ) { return; }
       ?>
       <div class="wrap">
       <h1>Sneakily Hide WP Versions &mdash; <?php esc_html_e( 'Settings', 'sneakily-hide-wp-versions' ); ?></h1>
       <form action="<?php echo esc_url( shwv_get_settings_page_url() ); ?>" method="post">
		   <input type="hidden" name="action" value="<?php echo esc_attr( SHWV_INCREASE_VERSION_ACTION ); ?>">
<?php // TODO add_submenu_page action hook? ?>
		   <input type="hidden" name="nonce"  value="<?php echo esc_attr( wp_create_nonce( SHWV_INCREASE_VERSION_ACTION ) ); ?>">
		   <?php submit_button( esc_html__( 'Force Increase the Fake Version Number', 'sneakily-hide-wp-versions' ) ); ?>   
	   </form>
	   <p class="shwv-settings-p"><?php esc_html_e( "After increasing the version number, remember to clear WordPress cache if you're using a caching plugin. If you view your site's source code, the new version number might not appear until you reload without browser cache - either hold down the Control key and press F5, or hold down Shift and click the reload button.", "sneakily-hide-wp-versions" ); ?></p>
		<p class="shwv-settings-p"><?php esc_html_e( "Note: if you manually upgrade WordPress, deactivate this plugin before and activate it after, and force increase the fake version after the upgrade is complete.", "sneakily-hide-wp-versions" ); ?></p>
		<br><br>

		<h3><?php esc_html_e( 'Plugin Fake Versions', 'sneakily-hide-wp-versions' ); ?></h3>
		<p><strong><?php esc_html_e( 'Fake WordPress version:', 'sneakily-hide-wp-versions' ); ?></strong> <?php echo esc_html( shwv_get_current_fake_wp_version() ); ?></p>
		<br><br>

		<h3><?php esc_html_e( 'Software', 'sneakily-hide-wp-versions' ); ?></h3>
		<p><?php esc_html_e( 'You are using:', 'sneakily-hide-wp-versions' ); ?><br><strong><?php esc_html_e( 'Web Server', 'sneakily-hide-wp-versions' ); ?></strong>: <?php echo esc_html( shwv_get_webserver_name() ); ?> 
		<?php 
			if ( $is_apache && function_exists( 'apache_get_version' ) ) { 
				echo esc_html( '(' . apache_get_version() . ')' ); 
			} 
		?><br>

		<?php 
		if ( $is_apache ) {
			if ( shwv_got_mod_rewrite() ) { 
				esc_html_e( 'mod_rewrite is present', 'sneakily-hide-wp-versions'); 
			} else {
				echo '<strong style="color: #dc3232;">'; 
				esc_html_e( 'mod_rewrite appears to be missing - mod_rewrite is required for this plugin to work', 'sneakily-hide-wp-versions' );
				echo '</strong>';
			}
			echo '<br>';
		}
		?>

		<strong>PHP</strong>: <?php echo esc_html( phpversion() ); ?><br>
		<strong><?php esc_html_e( 'WordPress version', 'sneakily-hide-wp-versions' ); ?></strong>: <?php echo esc_html( get_bloginfo( 'version' ) ); ?>
		</p>	

	</div>
	<?php
}


add_action( 'admin_init', 'shwv_handle_options_submit' );

function shwv_handle_options_submit() {
	// Don't use shwv_cap_check() - attached to admin_init
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['action'] ) 
	&&	 isset( $_POST['nonce'] )
	&&	 SHWV_INCREASE_VERSION_ACTION === shwv_sanitize_action( $_POST['action'] )
	&&	 wp_verify_nonce( shwv_sanitize_nonce( $_POST['nonce'] ), SHWV_INCREASE_VERSION_ACTION )
	) {
		shwv_bump_fake_version();
		if ( ! shwv_any_errors() ) {
			shwv_add_notice( 
				sprintf(
					// translators: %s: version number
					esc_html__( "Increased the fake version, new value = %s", "sneakily-hide-wp-versions" ),
					esc_html( shwv_get_current_fake_wp_version() )
				),
				"success"
			);
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . shwv_get_settings_page_slug() ) );
		exit;
	}
}

function shwv_add_remove_mu_fs_error() {
	shwv_add_notice(
		sprintf(
			esc_html__(
				// translators: %s: file path
				"Couldn't initialize WP_Filesystem. This shouldn't happen. Not deactivating - please delete Sneakily Hide WP Versions plugin manually, AND remove the must-use plugin (%s) from the MU-plugins directory",
				"sneakily-hide-wp-versions"
			),
			SHWV_MU_PLUGIN_PATH
		),
		"error"
	);
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'shwv_filter_plugin_action_links', 1000, 4 );

function shwv_filter_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
	global $page, $s;

	if ( current_user_can( 'deactivate_plugin', $plugin_file ) 
	&&	 array_key_exists( 'deactivate', $actions )
	&&	 '' !== $actions['deactivate'] 	
	) {
		
		$actions['deactivate'] = sprintf(
			'<a href="%s" id="deactivate-sneakily-hide-wp-versions" aria-label="%s">%s</a>',
			wp_nonce_url( 
				'plugins.php?action=' . SHWV_DEACTIVATE_ACTION 
					. '&amp;plugin=' . urlencode( $plugin_file ) 
					. '&amp;plugin_status=' . $context 
					. '&amp;paged=' . $page 
					. '&amp;s=' . $s, 
				SHWV_DEACTIVATE_ACTION 
			),
			esc_attr( sprintf( _x( 'Deactivate %s', 'plugin' ), $plugin_data['Name'] ) ),
			__( 'Deactivate' )
		);
	}
	return $actions;
}


// TODO test mobile admin
// TODO API
function shwv_deactivate_cleanly() {				// admin_init
	global $page, $s, $status;

	// Don't use shwv_cap_check() - attached to admin_init
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['action'] )
	||	 ! isset( $_GET['plugin'] )
	||	 ! isset( $_GET['_wpnonce'] )
	) {
		return;
	}

	$nonce = shwv_sanitize_nonce( $_GET['_wpnonce'] );

	if ( SHWV_DEACTIVATE_ACTION === $_GET['action']
	&&	 wp_verify_nonce( $nonce, SHWV_DEACTIVATE_ACTION )
	) {
		$plugin = shwv_sanitize_plugin( $_GET['plugin'] );

		if ( ! is_file( SHWV_MU_PLUGIN_PATH ) ) {
			deactivate_plugins( $plugin );
			return;
		}

		update_option( SHWV_GET_CREDS_ON_DEACTIVATION_OPTION_NAME, false, 'no' );
		
		// If mu-plugin exists, handle removing the mu-plugin which might need creds
		$nonce	= wp_create_nonce( SHWV_DEACTIVATE_ACTION );
		$url = get_admin_url( null, "/plugins.php?action=" . SHWV_DEACTIVATE_ACTION . "&plugin=$plugin&_wpnonce=$nonce&plugin_status=$status&paged=$page&s=$s" );
		$context = WPMU_PLUGIN_DIR;

		$prev = set_error_handler( 'shwv_null_error_handler' );

		ob_start();
		$creds = request_filesystem_credentials( $url, '', false, $context, null, true );   // TODO relaxed
		ob_end_clean();

		if ( true === $creds ) {
			$wp_filesystem_ret = WP_Filesystem( false, $context, true ); // TODO relaxed
			$shwv_ret = false;
			if ( $wp_filesystem_ret ) {
				$shwv_ret = shwv_possibly_remove_mu_plugin();
			}
			if ( ! $wp_filesystem_ret || ! $shwv_ret ) {
				// Shouldn't happen, don't deactivate
				shwv_add_remove_mu_fs_error();
				update_option( SHWV_UNKNOWN_FS_ERROR_OPTION_NAME, 'true', 'no' );
			} else {
				// Removing mu-plugin succeeded
				deactivate_plugins( $plugin );
			}
		} elseif ( false === $creds ) {
			// Does need credentials, show form
			update_option( SHWV_GET_CREDS_ON_DEACTIVATION_OPTION_NAME, 'true', 'no' );
		} else {
			$wp_filesystem_ret = WP_Filesystem( $creds, $context, true );    // TODO relaxed
			$shwv_ret = false;
			if ( $wp_filesystem_ret ) {
				$shwv_ret = shwv_possibly_remove_mu_plugin();
			}
			if ( ! $wp_filesystem_ret || ! $shwv_ret ) {
				// Wrong creds - show form error=true
				update_option( SHWV_GET_CREDS_ON_DEACTIVATION_OPTION_NAME, 'true', 'no' );
				update_option( SHWV_CREDS_FORM_ALREADY_ERROR_OPTION_NAME, 'true', 'no' );
			} else {
				// Removing mu-plugin succeeded
				deactivate_plugins( $plugin );
			}
		}
		set_error_handler( $prev );
	}
}

function shwv_show_creds_form_delete() {					// admin_notices
	global $page, $s, $status;

    // Don't use shwv_cap_check() - consider e.g. Author using admin (this is attached to admin_notices)
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
	if ( ! get_option( SHWV_GET_CREDS_ON_DEACTIVATION_OPTION_NAME, false ) ) {
		return;
	}

	$plugin = plugin_basename( __FILE__ );
	$nonce	= wp_create_nonce( SHWV_DEACTIVATE_ACTION );
	$url = get_admin_url( null, "/plugins.php?action=" . SHWV_DEACTIVATE_ACTION . "&plugin=$plugin&_wpnonce=$nonce&plugin_status=$status&paged=$page&s=$s" );
	$context = WPMU_PLUGIN_DIR;

	if ( ! get_option( SHWV_UNKNOWN_FS_ERROR_OPTION_NAME, false ) ) {
		?>
		<div class="updated notice">
			<h2>Sneakily Hide WP Versions</h2>
			<p><strong><?php esc_html_e( 'To deactivate, we need to remove the must-use plugin that was created, but we need credentials to do this.', 'sneakily-hide-wp-versions' ); ?></strong></p>
			<?php
			$error = false;
			if ( get_option( SHWV_CREDS_FORM_ALREADY_ERROR_OPTION_NAME, false ) ) {
				$error = true;
			}
			request_filesystem_credentials( $url, '', $error, $context, null, true );
	} 
	?>
    </div> <!-- .updated .notice -->
	<?php
}

