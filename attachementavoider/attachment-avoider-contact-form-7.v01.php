<?php
/*
  Plugin Name: Attachment avoider for 'contact form 7'
  Description: Using 'contanct form 7' and 'save contact form 7', this plugin replaces all e-mail attachements with a hyperlink. This behaviour is triggered by adding an [attachments-to-links] tag to the e-mail body. Note: Because of a bug in 'save contact form 7', only one attachement can be converted to a valid link.
  Author: Oskar Maier
  Version: 0.1
*/

// WARNING: save-contact-form-7 apparently contains a bug in the current version (2.0), where a second upload file
//          is never copied to the permanent storage location. This script anyway creates a link for it, depite
//          the file not existing.


if (is_admin()) {
  function wpaacf7_cf7_required() {
    $url = network_admin_url('plugin-install.php?tab=search&type=term&s=Contact+Form+7&plugin-search-input=Search+Plugins');
    echo '<div class="error"><p>The <a href="' . $url . '">Contact Form 7 Plugin</a> is required for attachment avoider for \'contact form 7\' to work.</p></div>';
  }
  
  function wpaacf7_scf7_required() {
    $url = network_admin_url('plugin-install.php?tab=search&type=term&s=Save+Contact+Form+7&plugin-search-input=Search+Plugins');
    echo '<div class="error"><p>The <a href="' . $url . '">Save Contact Form 7 Plugin</a> is required for attachment avoider for \'contact form 7\' to work.</p></div>';
  }

  function wpaacf7_check_required() {
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
        add_action('admin_notices', 'wpaacf7_cf7_required');
    }
    if (!is_plugin_active('save-contact-form-7/save-contact-form-7.php')) {
        add_action('admin_notices', 'wpaacf7_scf7_required');
    }
  }
  
  add_action('plugins_loaded', 'wpaacf7_check_required');
}


if (!function_exists("wpaacf7_attachment_to_link")) {
  function wpaacf7_attachment_to_link ($components, $form, $mail) {
  
    // configuration
    $WPAACF7_TRG_TAG = '[attachments-to-links]';
    $WPAACF7_MSG_NO_ATTACHEMENTS = 'No attachements submitted.';
    
    // check for target tag existance
    if ( false == strpos($components['body'], $WPAACF7_TRG_TAG) ) {
      return $components;
    }
    
    // check for attachements
    if ( 0 == count($components['attachments']) ) {
      $components['body'] = str_replace($WPAACF7_TRG_TAG, $WPAACF7_MSG_NO_ATTACHEMENTS, $components['body']);
      return $components;
    }
    
    // convert into links
    global $wpdb;
    $replace = array();
    $nimble_dir_pah = wp_upload_dir();
    foreach ( $components['attachments'] as $link_to_attachement ) {
      $id = $wpdb->insert_id;
      $replace[] = str_replace($_SERVER['DOCUMENT_ROOT'],
                               $_SERVER['SERVER_NAME'] . '/',
                               $nimble_dir_pah['basedir'] ."/nimble_uploads/$id/" . basename($link_to_attachement));
    }
    $replace = implode("\r\n", $replace);
    
    // replace tag
    $components['body'] = str_replace($WPAACF7_TRG_TAG, $replace, $components['body']);
    
    // remove attachements
    $components['attachments'] = array();
    
    return $components;
  }
}
  
add_filter( 'wpcf7_mail_components', wpaacf7_attachment_to_link);
?>
