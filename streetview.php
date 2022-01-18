<?php 
/**
 * @package streetview
 *
 * Plugin Name: Street View 
 * Plugin URI: 
 * Description:! Create a Streets custom post type which gets the data and sorts max once in a minutes. Every post contains a street name and related thumbnail.
 * Version: 1.0.0
 * Author: Asuka Watanabe
 * Author URI: https://dev.asukamethod.com
 * License: GPLv2 or later
 * Text Domain: streetview
*/



if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

if(! class_exists( 'Street_View' )){
 	class Street_View {

        function __construct(){
            $this->create_post_type();
            
        }

        /**
         * Activation functionality
         * 
         */
        public function activate(){
            // generate the custom post type 
            $this->streets_setup_post_type();

            // delete any old left over data
            $this->delete_data_streets();

            // re import the new data to the CPT
            $this->get_data_streets();
            
            // flush rewrite rules
            flush_rewrite_rules();
            
            //add option 
            add_option( 'Activated_Plugin', 'activated' );
        }

        /**
         * Deactivation functionality
         * 
         */
        public function deactivate(){
            //flush rewrite rules   
            flush_rewrite_rules(); 

            //delete any old left over data
            $this->delete_data_streets();

            //unregister the custom post type 
            unregister_post_type( 'street' );

            //delete option
            delete_option( 'Activated_Plugin' );
        }


         /**
         * Create the custom post type and make sure that the data is refreshed a max of once per minute
         * 
         */
        public function create_post_type(){
            if (  get_option( 'Activated_Plugin' ) == 'activated' ) {

                add_action('init', array( $this, 'streets_setup_post_type'));
            }
            
            // Run_task transient prevents this from running
            if ( ! get_transient( 'run_task' ) ) {
                set_transient( 'run_task', true, 1 * MINUTE_IN_SECONDS );
                add_action('init', array( $this, 'delete_data_streets'));
                add_action('init', array( $this, 'get_data_streets'));
            }
            
        }

        /**
         * Register the "street" custom post type
         * 
         */
        public function streets_setup_post_type() {
            register_post_type('streets',
                array(
                    'labels'      => array(
                        'name'          => __( 'Streets', 'streetview' ),
                        'singular_name' => __( 'Street', 'streetview' ),
                        'add_new' => __('Add Street', 'streetview'),
                        'all_items' => __('All Streets', 'streetview'),
                        'add_new_item' => __('Add Street', 'streetview'),
                        'edit_items' => __('Edit', 'streetview'),
                        'new_item' => __('New Street', 'streetview'),
                        'view_item' => __('View Street', 'streetview'),
                        'search_item' => __('Search Street', 'streetview'),
                    ),
                    'public'      => true,
                    'has_archive' => true,
                    'rewrite'     => array( 'slug' => 'streets' ), 
                    'supports' => array(
                        'title',
                        'thumbnail',
                        'revisions'
                    ),
                )
            );
        }

        /**
         * Set the css folder
         * 
         */
        public function enqueue(){
            wp_enqueue_style('plugin_style', plugins_url('/assets/main.css', __FILE__));
        }

          /**
         * Register the css file
         * 
         */
        public function register(){
            add_action('admin_enqueue_scripts', array($this, 'enqueue')); 
        }

        /**
         * Grab the data from geographic.org and sort + save to db
         * 
         */
        public function get_data_streets(){
            
            // Increment this number if you want to save more than 50 results
            $numberOfResults = 50;

            global $wpdb;
            require_once(plugin_dir_path( __FILE__ ) .'/includes/street-names.php');
            // Create DOM from URL or file
            $html = file_get_html(esc_url('https://geographic.org/streetview/netherlands/north_holland/amsterdam.html'));

            // Creating an array of elements
            $streets = [];
            $i = 1;
            foreach ($html->find('li') as $street) {
                
                // Control the amount of data saved
                if ($i >= $numberOfResults) {
                    break;
                }
                // Find item link element
                $streetDetails = $street->find('a', 0);
            
                // get title attribute
                $streetName = $streetDetails->alt;
                if (str_starts_with($streetName, 'Korte') == true && str_ends_with($streetName, 'gracht') == true) {
                    continue;
                } 

                // character sorting			
                if($streetName[1] == ' ' || $streetName[1] == '.'){
                	if($streetName[2] == ' ' || $streetName[2] == '.') {
                		$letter = strtolower($streetName[3]); 
                	} else {
                		$letter = strtolower($streetName[2]);
                	}
                } else {
                	$letter = strtolower($streetName[1]);
                }

                // check if the data already exists in the database
                $query = $wpdb->get_results("SELECT * FROM " .$wpdb->prefix."posts where post_title ='$streetName';");
              

                $result = (int)count($query);
               
                if ($result > 0){
                    continue;
                }

                //create a new street array 
                $street = ['name'=>$streetName, 'letter' => $letter,  'first' => strtolower($streetName[0])];
                
                array_push($streets, $street);
                
                $i++;
               
            }
            
            // sorting by the second (or third, fourth) letter 
            usort($streets, fn($x, $y) => $x['letter'] <=> $y['letter']);
  
            // // get the data of the thumbnail you link the new street to 
           
            $d = $wpdb->get_results("SELECT id FROM " .$wpdb->prefix."posts where post_title = 'Dappermarkt';");
            $g = $wpdb->get_results("SELECT id FROM " .$wpdb->prefix."posts where post_title = 'Gracht';");
            $k = $wpdb->get_results("SELECT id FROM " .$wpdb->prefix."posts where post_title = 'Kalverstraat';");
            $m = $wpdb->get_results("SELECT id FROM " .$wpdb->prefix."posts where post_title = 'Museumplein';");
            $e = $wpdb->get_results("SELECT id FROM " .$wpdb->prefix."posts where post_title = 'Elandsstraat';");

            // link the thumbnail to the street
			foreach($streets as $street){
                $file;
                switch ($street['first']) {
                    case 'd':
                        $file = $d;
                        break;
                    case 'g':
                        $file = $g;
                        break;
                    case 'k':
                        $file = $k;
                        break;
                    case 'm':
                        $file = $m;
                        break;
                    default:    
                        $file = $e;
                }
                  
                $streetName = $street['name'];
                
                $street = array(
                    'post_title'    => wp_strip_all_tags( $streetName ),
                    'post_status'   => 'publish',
                    'post_type' => 'streets',				
                );
                
                //insert the new street into the database
                $post_id = wp_insert_post($street);
                
                //insert the thumbnail data into the database based on the post id which you just inserted
                $file = $file[0]->id; 
                $table_name = $wpdb->prefix.'postmeta';
                $meta_key = '_thumbnail_id';
                $wpdb->insert($table_name, array(
                    'post_id' => $post_id,
                    'meta_key' => $meta_key,
                    'meta_value' => $file
                     ));         
			}

        }

        /**
         * Delete the data from database
         * 
         */
        public function delete_data_streets(){
            global $wpdb;
            $table_name = $wpdb->prefix.'posts';
            $post_type = 'streets';
            $wpdb->delete( $table_name, array( 'post_type' => $post_type ) );
            
        }

    }
}

// Register the plugin
if(class_exists('Street_View')){
    $streetPlugin = new Street_View();
    $streetPlugin->register();

}

// Hook the activate function
register_activation_hook( __FILE__, array($streetPlugin ,'activate'));

// Hook the deactivation function 
register_deactivation_hook( __FILE__, array($streetPlugin ,'deactivate'));