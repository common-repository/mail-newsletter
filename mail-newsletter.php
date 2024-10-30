<?php
/**
 * @package Mail Newsletter
 * @version 1.0
 */
/*
  Plugin Name: Mail Newsletter
  Description: This plugin generate leads from website and allow admin to send email them using admin panel.
  Author: ifourtechnolab
  Version: 1.0
  Author URI: http://www.ifourtechnolab.com/
  License: GPLv2 or later
  License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

define('MN_URL', plugin_dir_url(__FILE__));

global $wpdb, $wp_version;
define("WP_MAIL_TABLE", $wpdb->prefix . "mail_newsletter");

/*
 * Main class
 */
class Mail_Newsletter_Class {

    /**
     * @global type $wp_version
     */
    public function __construct() {
        global $wp_version;
        
        /* Front-Side */
        /* Run scripts and shortcode */
        add_action('wp_enqueue_scripts', array($this, 'mn_frontend_scripts'));
        add_shortcode('mail-newsletter-plugin', array($this, 'mail_form_shortcode'));
        
        /* insert emails */
        add_action('admin_action_insert-mail-newsletter',array($this, 'insertmailnewsletter'));
        
        /* Back-Side */
        /* Setup menu and run scripts */
        add_action('admin_menu', array($this, 'mn_plugin_setup_menu'));
        add_action('admin_enqueue_scripts', array($this, 'mn_backend_scripts'));
        
        /* Send all emails */
        add_action('admin_action_mail-news-action',array($this, 'mail_news_send'));
        
        add_filter('widget_text','do_shortcode');
		
		// Attach the tiny mce editor to this textarea
		if (function_exists('wp_tiny_mce')) {
			add_filter('teeny_mce_before_init', create_function('$a', '
			$a["theme"] = "advanced";
			$a["skin"] = "wp_theme";
			$a["height"] = "125";
			$a["width"] = "650";
			$a["onpageload"] = "";
			$a["mode"] = "exact";
			$a["elements"] = "intro";
			$a["editor_selector"] = "mceEditor";
			$a["plugins"] = "safari,inlinepopups,spellchecker";
			$a["forced_root_block"] = false;
			$a["force_br_newlines"] = true;
			$a["force_p_newlines"] = false;
			$a["convert_newlines_to_brs"] = true;
			return $a;'));
			
			wp_tiny_mce(true);
		}
		
    }
    
    /** Create table */
    function my_plugin_create_db() {
		
		global $wpdb;
		
		$sql = "CREATE TABLE " . WP_MAIL_TABLE . " (
			`email_id` mediumint(9) NOT NULL AUTO_INCREMENT,
			`email_address` tinytext NOT NULL,
			`email_status` char(3) NOT NULL default 'YES',
			`email_date` datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY (email_id)
			);";
				  
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
    }
    
    // Send all emails
    public function mail_news_send() {
		
		$contact_errors = false;
		$to = $wpdb->_escape(trim($_POST['listusersemail']));
		$subject = $wpdb->_escape(trim($_POST['mnlsubject']));
		$content = html_entity_decode(stripcslashes($_REQUEST['intro']));
        if(is_email($to)) {
			
			add_filter( 'wp_mail_content_type',array($this,'set_html_content_type'));
			
			if(!wp_mail($to, $subject, $content)) {
				$contact_errors = true;
			}
			remove_filter( 'wp_mail_content_type',array($this,'set_html_content_type') );
			
        } else {
			echo "Mail failed";
		}
        
		wp_redirect(admin_url( 'admin.php?page=mn' ));
		exit();
    }
    
    /**
     * Content html type
     */
    public function set_html_content_type() {
		return 'text/html';
	}
    
    /**
     * css and javascript scripts.
     */
    public function mn_backend_scripts() {
		wp_enqueue_style('mn-css-handler-backend', MN_URL.'assets/css/mail-newsletter.css');
		wp_enqueue_script('mn-js-handler-backend', MN_URL.'assets/js/mail-newsletter.js',array('jquery'),'1.0.0',true);
    }
	
    /**
     * Add Mail Newsletter to Admin Menu.
     * @global type $user_ID
     */
    public function mn_plugin_setup_menu() {
		global $user_ID;
		$title		 = apply_filters('mn_menu_title', 'Mail Newsletter');
		$capability	 = apply_filters('mn_capability', 'edit_others_posts');
		$page		 = add_menu_page($title, $title, $capability, 'mn',
			array($this, 'admin_mailnewsletter'), "", 9501);
		add_action('load-'.$page, array($this, 'help_tab'));
    }

    /**
     * Admin Mail Newsletter
     */
    public function admin_mailnewsletter() {
		global $wpdb;
		?>
		<div class="wrap">

			<h1>Mail Newsletter</h1>
			
			<p> [mail-newsletter-plugin] - Use this shortcode</p>
			
			<br>
			
			<form method="post" action="<?php echo admin_url( 'admin.php' ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="mail-news-action" />

				<div>
					<input type="text" name="mnlsubject" id="mnlsubject">
				</div>
				<div>	
					<?php wp_editor( '', 'intro', array('media_buttons' => true,'editor_height' => 175) ); ?>
				</div>
				<div>
					<input type="submit" id="sendmailnewsletter" value="Send">
				</div>

				<table id="mail-newsletter">
					<tr>
						<th style="width: 25%;">
							Check/Uncheck <input type="checkbox" name="checkAll" id="checkAll">
						</th>
						<th style="width: 50%;">Email</th>
						<th style="width: 25%;">Date Time</th>
					</tr>

					<?php
					$allusers = $wpdb->get_results("SELECT email_id,email_address,email_date FROM " . WP_MAIL_TABLE . "");
					foreach ($allusers as $singleuser) :
						?>    
						<tr>
							<td align="center"><input type="checkbox" name="listusersemail[]" value="<?php echo $singleuser->email_address; ?>"></td>
							<td><?php echo $singleuser->email_address; ?></td>
							<td align="center"><?php echo date('Y-m-d H:i', strtotime($singleuser->email_date)); ?></td>
						</tr>
						<?php
					endforeach;
					?>
				</table>
			
			</form>
			
		</div>
		<?php
    }


	/** Front end **/
    /** create form and Add short code */
	function mail_form_shortcode( $atts ) {
		add_action('wp_enqueue_scripts', array($this, 'mn_frontend_scripts'));
	?>
		<div class="mailnewsletterform">
			<!--<form name="mailnewsletterform" method="post" action="<?php //echo MN_URL; ?>insert-mail-newsletter.php" onsubmit="return ValidateMailNewsletter();">-->
			
			<form name="mailnewsletterform" method="post" action="<?php echo admin_url( 'admin.php' ); ?>" onsubmit="return ValidateMailNewsletter();">
				<h2>Mail NewsLetter</h2>
				
				<input type="hidden" name="action" value="insert-mail-newsletter" />
				
				<div class="txt"><input name="mnemail" placeholder="Enter your email address"/></div>
				
				<div class="btn"><input type="submit" value="Send"/></div>
			</form>
		</div>
	<?php
	}
	
	/** Front end record insert */
	function insertmailnewsletter() {
		
		global $wpdb;
		
		$mnemail = $wpdb->_escape(trim($_POST['mnemail']));
		
		if(is_email($mnemail)) {
			$wpdb->insert( WP_MAIL_TABLE, array( 'email_address' => $mnemail, 'email_status' => 'YES', 'email_date' => current_time( 'mysql' ) ) );
		} else {
			echo 'Email not valid';
		}
		
		header("location:".$_SERVER['HTTP_REFERER']);
	}	
    
    /**
     * Front-end Css and Javascript initialize.
     */
    public function mn_frontend_scripts() {
		wp_enqueue_style('mn-css-handler', MN_URL.'/assets/css/mail-newsletter.css');
		wp_enqueue_script('mn-js-handler', MN_URL.'/assets/js/mail-newsletter.js');
    }



    /**
     * Add the help tab to the screen.
     */
    public function help_tab()
    {
		$screen = get_current_screen();

		// documentation tab
		$screen->add_help_tab(array(
			'id' => 'documentation',
			'title' => __('Documentation', 'mn'),
			'content' => "<p><a href='http://www.ifourtechnolab.com/documentation/' target='blank'>Mail Newsletter</a></p>",
			)
		);
    }

    /**
     * Deactivation hook.
     */
    public function mn_deactivation_hook() {
		if (function_exists('update_option')) {
			
		}
    }

    /**
     * Uninstall hook
     */
    public function mn_uninstall_hook() {
		if (current_user_can('delete_plugins')) {
			global $wpdb;
			$table_name = $wpdb->prefix . WP_MAIL_TABLE;
			$sql = "DROP TABLE IF EXISTS $table_name";
			$wpdb->query($sql);
			delete_option('e34s_time_card_version');
		}
    }
}

$mailnewsletter = new Mail_Newsletter_Class();

register_activation_hook( __FILE__, array('Mail_Newsletter_Class', 'my_plugin_create_db') );

register_deactivation_hook(__FILE__,
    array('Mail_Newsletter_Class', 'mn_deactivation_hook'));

register_uninstall_hook(__FILE__,
    array('Mail_Newsletter_Class', 'mn_uninstall_hook'));
