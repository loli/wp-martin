<?php
class WPCF7Submissions
{
    public function __construct()
    {
        add_action('init', array($this, 'post_type'));

        add_filter('wpcf7_mail_components', array($this, 'submission'), 999, 3);
        add_filter('wpcf7_posted_data', array($this, 'posted'), 999, 3);
    }

    /**
     * Register the post type
     */
    public function post_type()
    {
        $labels = array(
            'name'                => __('Contact Form Submissions', 'contact-form-submissions'),
            'singular_name'       => __('Submission', 'contact-form-submissions'),
            'menu_name'           => __('Submission', 'contact-form-submissions'),
            'all_items'           => __('Submissions', 'contact-form-submissions'),
            'view_item'           => __('Submission', 'contact-form-submissions'),
            'edit_item'           => __('Submission', 'contact-form-submissions'),
            'search_items'        => __('Search', 'contact-form-submissions'),
            'not_found'           => __('Not found', 'contact-form-submissions'),
            'not_found_in_trash'  => __('Not found in Trash', 'contact-form-submissions'),
        );
        $args = array(
            'label'               => __('Submission', 'contact-form-submissions'),
            'description'         => __('Post Type Description', 'contact-form-submissions'),
            'labels'              => $labels,
            'supports'            => false,
            'hierarchical'        => true,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'wpcf7',
            'show_in_admin_bar'   => false,
            'show_in_nav_menus'   => false,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'rewrite'             => false,
            'capability_type'     => 'page',
            'query_var'           => false,
            'capabilities' => array(
                'create_posts'  => false
            ),
            'map_meta_cap' => true
        );
        register_post_type('wpcf7s', $args);
    }

    /**
     * Hook into when a cf7 form is submitted to save the post data
     */
    public function posted($posted_data)
    {
        global $wpcf7s_posted_data;

        $wpcf7s_posted_data = $posted_data;

        return $posted_data;
    }

    /**
     * Hook into when a cf7 form has been submitted and the values have been inserted
     *
     * @param  [type] $components   [description]
     * @param  [type] $contact_form [description]
     * @param  [type] $mail         [description]
     *
     * @return [type]               [description]
     */
    public function submission($components, $contact_form, $mail)
    {
        global $wpcf7s_post_id, $wpcf7s_posted_data;

        if (!empty($wpcf7s_posted_data)) {
            foreach ($wpcf7s_posted_data as $name => $value) {
                if ('_wpcf7' !== substr($name, 0, 6)) {
                    $fields[$name] = $value;
                }
            }
        }

        $contact_form_id = 0;
        if (method_exists($contact_form, 'id')) {
            $contact_form_id = $contact_form->id();
        } elseif (property_exists($contact_form, 'id')) {
            $contact_form_id = $contact_form->id;
        }

        $body = $components['body'];
        $sender = wpcf7_strip_newline($components['sender']);
        $recipient = wpcf7_strip_newline($components['recipient']);
        $subject = wpcf7_strip_newline($components['subject']);
        $headers = trim($components['additional_headers']);

        // get the form file attachements
        if ( $submission = WPCF7_Submission::get_instance() ) {
            $attachments = $submission->uploaded_files();
        }

        $submission = array(
            'form_id'   => $contact_form_id,
            'body'      => $body,
            'sender'    => $sender,
            'subject'   => $subject,
            'recipient' => $recipient,
            'additional_headers' => $headers,
            'attachments' => $attachments,
            'fields'    => $fields
        );

        if (!empty($wpcf7s_post_id)) {
            $submission['parent'] = $wpcf7s_post_id;
        }

        // store the form submission
        $post_id = $this->save($submission);

        if (empty($wpcf7s_post_id)) {
            $wpcf7s_post_id = $post_id;
        }
        
        // CHANGES
        $components = $this->wpcf7s_attachment_to_link($components, $post_id);

        return $components;
    }

    /**
     * Save the form submission into the db
     */
    private function save($submission = array())
    {
        if(true === apply_filters('wpcf7s_save_submission', true, $submission['form_id']))
        {
            $post = array(
                'post_title'    => ' ',
                'post_content'  => $submission['body'],
                'post_status'   => 'publish',
                'post_type'     => 'wpcf7s',
            );

            if (isset($submission['parent'])) {
                $post['post_parent'] = $submission['parent'];
            }

            $post_id = wp_insert_post($post);

            // check the post was created
            if(!empty($post_id) && !is_wp_error($post_id)){

                add_post_meta($post_id, 'form_id', $submission['form_id']);
                add_post_meta($post_id, 'subject', $submission['subject']);
                add_post_meta($post_id, 'sender', $submission['sender']);
                add_post_meta($post_id, 'recipient', $submission['recipient']);
                add_post_meta($post_id, 'additional_headers', $submission['additional_headers']);

                $additional_fields = apply_filters('wpcf7s_submission_fields', $submission['fields'], $submission['form_id']);
                if (!empty($additional_fields)) {
                    foreach ($additional_fields as $name => $value) {
                        if (!empty($value)) {
                            add_post_meta($post_id, 'wpcf7s_posted-' . $name, $value);
                        }
                    }
                }

                $attachments = $submission['attachments'];
                if (!empty($attachments)) {

                    $wpcf7s_dir = $this->get_wpcf7s_dir();
                    // add a sub directory of the submission post id
                    $wpcf7s_dir .= '/' . $post_id;

                    mkdir($wpcf7s_dir, 0755, true);

                    foreach ($attachments as $name => $file_path) {
                        if (!empty($file_path)) {
                            // get the file name
                            $file_name = basename($file_path);

                            $copied = copy($file_path, $wpcf7s_dir . '/' . $file_name);

                            add_post_meta($post_id, 'wpcf7s_file-' . $name, $file_name, false);
                        }
                    }
                }
            }

            return $post_id;
        }
    }

    /**
     * Get the path of where uploads go
     *
     * @return string full path
     */
    public function get_wpcf7s_dir(){
        $upload_dir = wp_upload_dir();
        $wpcf7s_dir = apply_filters('wpcf7s_dir', $upload_dir['basedir'] .'/wpcf7-submissions');

        return $wpcf7s_dir;
    }

    /**
     * Get the url of where uploads go
     *
     * @return string full url
     */
    public function get_wpcf7s_url(){
        $upload_dir = wp_upload_dir();
        $wpcf7s_url = apply_filters('wpcf7s_url', $upload_dir['baseurl'] .'/wpcf7-submissions');

        return $wpcf7s_url;
    }
    
    public function wpcf7s_attachment_to_link ($components, $post_id) {
  
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
      
      // convert into link
      $wpcf7s_dir = $this->get_wpcf7s_url();
      $wpcf7s_dir .= '/' . $post_id;
      
      // convert into links
      global $wpdb;
      $replace = array();
      foreach ( $components['attachments'] as $link_to_attachement ) {
        $replace[] = $wpcf7s_dir . '/' . basename($link_to_attachement);
      }
      $replace = implode("\r\n", $replace);
      
      // replace tag
      $components['body'] = str_replace($WPAACF7_TRG_TAG, $replace, $components['body']);
      
      // remove attachements
      $components['attachments'] = array();
      
      return $components;
    }
}
  
