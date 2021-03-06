<?php
class GFUserData{

    public static function update_table(){
        global $wpdb;
        $table_name = self::get_user_registration_table_name();

        if ( ! empty($wpdb->charset) )
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if ( ! empty($wpdb->collate) )
            $charset_collate .= " COLLATE $wpdb->collate";

        require_once(ABSPATH . '/wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE $table_name (
              id mediumint(8) unsigned not null auto_increment,
              form_id mediumint(8) unsigned not null,
              is_active tinyint(1) not null default 1,
              meta longtext,
              PRIMARY KEY  (id),
              KEY form_id (form_id)
            )$charset_collate;";

        dbDelta($sql);

    }

    public static function get_user_registration_table_name(){
        global $wpdb;
        return $wpdb->prefix . "rg_userregistration";
    }

    public static function get_feeds($form_id = false, $is_active = false){
        global $wpdb;
        
        $table_name = self::get_user_registration_table_name();
        $form_table_name = RGFormsModel::get_form_table_name();
        $where = 'WHERE 1 = 1';
        
        if($form_id)
            $where .= " AND s.form_id = $form_id";
        
        if($is_active)
            $where .= " AND s.is_active = $is_active";
            
        $sql = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
                FROM $table_name s
                INNER JOIN $form_table_name f ON s.form_id = f.id
                $where";

        $results = $wpdb->get_results($sql, ARRAY_A);

        $count = sizeof($results);
        for($i = 0; $i < $count; $i++){
            $results[$i]["meta"] = maybe_unserialize($results[$i]["meta"]);
        }

        return $results;
    }

    public static function delete_feed($id){
        global $wpdb;
        $table_name = self::get_user_registration_table_name();
        $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id=%s", $id));
    }

    public static function get_feed_by_form($form_id, $only_active = false){
        global $wpdb;
        $table_name = self::get_user_registration_table_name();
        $active_clause = $only_active ? " AND is_active=1" : "";
        $sql = $wpdb->prepare("SELECT id, form_id, is_active, meta FROM $table_name WHERE form_id=%d $active_clause", $form_id);
        $results = $wpdb->get_results($sql, ARRAY_A);
        if(empty($results))
            return array();

        // deserializing meta
        $count = sizeof($results);
        for($i=0; $i<$count; $i++){
            $results[$i]["meta"] = maybe_unserialize($results[$i]["meta"]);
        }
        
        return $results;
    }
    
    /**
    * Provides a more accurately named function for getting feeds by form.
    * 
    * @param mixed $form_id
    * @param mixed $only_active
    */
    public static function get_feeds_by_form($form_id, $only_active = false) {
        return self::get_feed_by_form($form_id, $only_active);
    }

    public static function get_feed($id){
        global $wpdb;
        $table_name = self::get_user_registration_table_name();
        $sql = $wpdb->prepare("SELECT id, form_id, is_active, meta FROM $table_name WHERE id=%d", $id);
        $results = $wpdb->get_results($sql, ARRAY_A);
        if(empty($results))
            return array();

        $result = $results[0];
        $result["meta"] = maybe_unserialize($result["meta"]);
        return $result;
    }
    
    public static function get_update_feed($form_id, $is_active = true) {
        
        $feeds = self::get_feeds($form_id, $is_active);
        
        foreach($feeds as $feed) {
            if(rgars($feed, 'meta/feed_type') == 'update')
                return $feed;
        }
        
        return false;
    }
    
    public static function update_feed($id, $form_id, $is_active, $setting){
        global $wpdb;
        $table_name = self::get_user_registration_table_name();
        $setting = maybe_serialize($setting);
        if($id == 0){
            //insert
            $wpdb->insert($table_name, array("form_id" => $form_id, "is_active"=> $is_active, "meta" => $setting), array("%d", "%d", "%s"));
            $id = $wpdb->get_var("SELECT LAST_INSERT_ID()");
        }
        else{
            //update
            $wpdb->update($table_name, array("form_id" => $form_id, "is_active"=> $is_active, "meta" => $setting), array("id" => $id), array("%d", "%d", "%s"), array("%d"));
        }

        return $id;
    }

    public static function drop_tables(){
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS " . self::get_user_registration_table_name());
    }
    
    /**
    * Forms can have an unlimited number of feeds now.
    * 
    * @param mixed $active_form
    */
    
    public static function get_available_forms($feed_type, $current_feed = false){
        
        $forms = RGFormsModel::get_forms();
        $feeds = self::get_feeds();
        $available_forms = array();
        
        // forms can have multiple "create" feeds
        // forms can have only a single "update" feed
        // any given form can only have one type of feed, a form with a "create" feed can not have an "update" feed and vice versa
        $not_type = $feed_type == 'update' ? 'create' : 'update';
        
        foreach($forms as $form) {
            
            // if form already has an update feed, limit to one per form
            if($feed_type == 'update' && self::has_feed_type('update', $form, $feeds, $current_feed))
                continue;
            
            // filter out all feeds of opposing feed type
            if(self::has_feed_type($not_type, $form, $feeds, $current_feed))
                continue;
            
            $available_forms[] = $form;
            
        }
        
        return $available_forms;
    }
    
    public static function has_feed_type($feed_type, $form, $feeds, $current_feed = false) {
        foreach($feeds as $feed) {
            
            // skip current feed as it may be changing feed type
            if($current_feed && $feed['id'] == $current_feed)
                continue;
            
            // if there is no feed type specified, default to "create"
            if(!rgars($feed, 'meta/feed_type'))
                $feed['meta']['feed_type'] = 'create';
                            
            if($form->id == $feed['form_id'] && rgars($feed, 'meta/feed_type') == $feed_type)
                return true;
        }
        return false;
    }
    
    public static function get_user_by_entry_id($entry_id) {
        global $wpdb;
        
        if (!$user_id = $wpdb->get_var($wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'entry_id' AND meta_value = %d LIMIT 1", $entry_id )))
            return false;
            
        $user = new WP_User($user_id);
        
        return $user;
        
    }
    
    public static function insert_buddypress_data($bp_rows) {
        global $wpdb, $bp;
        require_once(WP_PLUGIN_DIR . '/buddypress/bp-xprofile/bp-xprofile-functions.php');

        $table = $bp->profile->table_name_data;
        
        foreach($bp_rows as $bp_row) {
            $success = xprofile_set_field_data($bp_row['field_id'], $bp_row['user_id'], $bp_row['value']);
	        xprofile_set_field_visibility_level( $bp_row['field_id'], $bp_row['user_id'], $bp_row['field']->default_visibility );
        }
        
    }
    
    public static function remove_password($form_id, $entry_id, $field_id){
        global $wpdb;
        $table = $wpdb->prefix . 'rg_lead_detail';
        $wpdb->query("DELETE FROM $table WHERE lead_id = $entry_id AND form_id = $form_id AND CAST(field_number as DECIMAL(4,2)) = $field_id");
    }
    
    public static function update_site_meta($site_id, $key, $value){
        global $wpdb;
        
        $wpdb->show_errors();
        
        $meta_id = $wpdb->get_results("SELECT meta_id FROM $wpdb->sitemeta WHERE site_id = '$site_id' AND meta_key = '$key'");
        
        if(!empty($meta_id)) {
            $meta_id = $meta_id[0]->meta_id;
            $result = $wpdb->query("UPDATE $wpdb->sitemeta SET meta_value = '$value' WHERE meta_id = '{$meta_id}'");
        } else {
            $result = $wpdb->query("INSERT INTO $wpdb->sitemeta (site_id, meta_key, meta_value) VALUES ('$site_id', '$key', '$value')");
        }
        
        return $result;
    }
    
    public static function get_site_by_entry_id($entry_id) {
        global $wpdb;
        
        $site_id = $wpdb->get_var("SELECT site_id FROM $wpdb->sitemeta WHERE meta_key = 'entry_id' AND meta_value = '$entry_id'");
        
        return $site_id;
    }
    
}
?>
