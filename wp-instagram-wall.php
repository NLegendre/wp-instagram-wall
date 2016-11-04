<?php
/*
Plugin Name: WP Instagram Wall
Description: Display Instagram pictures from your account
Version: 1.0
Author: Nicolas Legendre
Author URI: http://www.nicoalslegendre.com
Plugin URI: https://github.com/NLegendre/wp-instagram-wall
*/

if ( ! class_exists( 'Wp_Instagram_Wall_Plugin' ) ) :

class Wp_Instagram_Wall_Plugin
{

    /**
     * Static property to hold our singleton instance
     */
    static $instance = false;


    /**
     * Wp_Instagram_Wall_Plugin constructor
     */
    public function __construct() {
        //front
        add_action( 'wp_enqueue_scripts', array(&$this, 'wp_instagram_wall_style') );

        //admin
        add_action( 'admin_menu', array($this, 'addAdminMenu') );
        add_action( 'admin_notices', array($this, 'errorNotice') );
    }


    public static function init() {
        $class = __CLASS__;
        new $class;
    }

    /**
     * Load stylesheet
     */
    function wp_instagram_wall_style() {
        $plugin_url = plugin_dir_url( __FILE__ );
        wp_enqueue_style( 'wp-instagram-wall', $plugin_url . 'css/wp-instagram-wall.css' );
    }


    /**
     * If an instance exists, this returns it. If not, it creates one and retuns it.
     *
     * @return Wp_Instagram_Wall_Plugin
     */
    public static function getInstance() {
        if ( !self::$instance )
            self::$instance = new self;
        return self::$instance;
    }


    /**
     * Get plugin options
     *
     * @return array
     */
    private function getPluginOptions() {
        $upload = wp_upload_dir();
        $cache_directory = $upload['basedir'].'/cache/wp-instagram-wall/';
        if (!file_exists($cache_directory)) {
            mkdir($cache_directory, 0777, true);
        }
        $options = array(
            'client_id' => get_option( 'wp_instagram_wall_clientid' ),
            'client_secret' => get_option( 'wp_instagram_wall_clientsecret' ),
            'token' => get_option( 'wp_instagram_wall_token' ),
            'user_id' => get_option( 'wp_instagram_wall_userid' ),
            'template' => get_option( 'wp_instagram_wall_template' ),
            'cache_directory' => $cache_directory
        );
        return $options;
    }


    /**
     * Generate Instagram wall
     *
     * @return string
     */
    public function generateWall() {
        $block = '';
        $options = $this->getPluginOptions();

        //test if cache file is available
        $cache_file = $options['cache_directory'].date('Ymd').'.json';
        
        if(file_exists($cache_file)) {
            //use the cache
            $result = file_get_contents($cache_file);
        } else {
            //delete old cache
            $files = glob($options['cache_directory'].'*');
            foreach($files as $file) {
                if(is_file($file)) {
                    unlink($file);
                }
            }

            //get images from Instagram
            if( isset($options['token']) and !empty($options['token']) ) {
                $url = "https://api.instagram.com/v1/users/".$options['user_id']."/media/recent/?access_token=".$options['token'];
                $result = $this->getPics($url);
                $json_data = json_decode($result);
                //save in new file (cache) - only if no errors
                if(isset($json_data->data) and !empty($json_data->data)) {
                    $new_cache = $options['cache_directory'].date('Ymd').'.json';
                    $test = file_put_contents($new_cache, $result);
                }
            }
        }

        if(isset($result) and !empty($result)) {
            $result = json_decode($result);
            $block .= '<div class="wp_instagram_wall">';
            if(isset($options['template']) and !empty($options['template'])) {
                $template = $options['template'];
            } else {
                $template = 'default.php';
            }
            include('templates/'.$template);
            $block .= '</div>';
        }

        return $block;
    }


    /**
     * Get Instagram user's pics
     *
     * @param $url
     * @return mixed
     */
    public function getPics($url) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "postman-token: 1a20a6d8-a0ca-77a0-d855-306432ac7aa0"
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }


    /**
     * Get user's token from code (given by Instagram)
     *
     * @param $code
     * @return string
     */
    public function getToken($code) {
        $plugin_url = menu_page_url('wp-instagram-wall', false);
        $options = $this->getPluginOptions();
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.instagram.com/oauth/access_token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => "client_id=".$options['client_id']."&client_secret=".$options['client_secret']."&grant_type=authorization_code&redirect_uri=".urlencode($plugin_url)."&code=".$code,
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded"
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response);
        if(isset($result->access_token) and !empty($result->access_token)) {
            return $result->access_token;
        } else {
            return '';
        }
    }


    /**
     * Get list of available templates
     * Listing made from files in directory "templates"
     *
     * @return array
     */
    private function getWallTemplates() {
        $files = array();
        $dir = plugin_dir_path( __FILE__ ).'templates';
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file != '.' && $file != '..' && $file != 'default.php') {
                        $files[] = $file;
                    }
                }
                closedir($dh);
            }
            natsort($files);
        }
        return $files;
    }


    /**
     * Test if plugin is completely set
     *
     * @return bool
     */
    private function isPluginOk() {
        $options = $this->getPluginOptions();
        if(empty($options['client_id']) or empty($options['client_secret']) or empty($options['token']) or empty($options['user_id'])) {
            return false;
        }
        return true;
    }


    /**
     * Display error in admin if plugin isn't completely set
     *
     * @return void
     */
    public function errorNotice() {
        if(!$this->isPluginOk()) {
            ?>
            <div class="error notice">
                <p><?php _e( 'Instagram Wall: your Instagram account is not completely set', 'wp_instagram_wall' ); ?></p>
            </div>
            <?php
        }
    }


    /**
     * Admin page link
     *
     * @return void
     */
    public function addAdminMenu(){
        add_menu_page('WP Instagram Wall', 'Instagram Wall', 'manage_options', 'wp-instagram-wall', array($this, 'adminWpInstagramWall'));
    }


    /**
     * Admin page
     *
     * Manage plugin options
     *
     * @return void
     */
    public function adminWpInstagramWall() {
        $module_url = menu_page_url('wp-instagram-wall', false);

        //updated options
        $update_options = array();

        //if back from instagram login
        if(isset($_GET['code']) and !empty($_GET['code'])) {
            //request token from code given
            $token_instagram = $this->getToken($_GET['code']);
            //save it
            $update_options['wp_instagram_wall_token'] = update_option( 'wp_instagram_wall_token', $token_instagram );
            //redirect to avoid code update error on page reload
            echo '<script> window.location="'.$module_url.'&success_token'.'"; </script> ';
        }

        //if form submitted
        if(isset($_POST['sub'])) {
            //save options
            if(isset($_POST['wp_instagram_wall_clientid']) and !empty($_POST['wp_instagram_wall_clientid'])) {
                $update_options['wp_instagram_wall_clientid'] = update_option( 'wp_instagram_wall_clientid', $_POST['wp_instagram_wall_clientid'] );
            }
            if(isset($_POST['wp_instagram_wall_clientsecret']) and !empty($_POST['wp_instagram_wall_clientsecret'])) {
                $update_options['wp_instagram_wall_clientsecret'] = update_option( 'wp_instagram_wall_clientsecret', $_POST['wp_instagram_wall_clientsecret'] );
            }
            if(isset($_POST['wp_instagram_wall_userid']) and !empty($_POST['wp_instagram_wall_userid'])) {
                $update_options['wp_instagram_wall_userid'] = update_option( 'wp_instagram_wall_userid', $_POST['wp_instagram_wall_userid'] );
            }
            if(isset($_POST['wp_instagram_wall_template']) and !empty($_POST['wp_instagram_wall_template'])) {
                $update_options['wp_instagram_wall_template'] = update_option( 'wp_instagram_wall_template', $_POST['wp_instagram_wall_template'] );
            } else {
                $update_options['wp_instagram_wall_template'] = update_option( 'wp_instagram_wall_template', 'default.php' );
            }
        }

        //load options
        $options = $this->getPluginOptions();

        //build url to connect to Instagram API
        $connect_url = 'https://api.instagram.com/oauth/authorize/?client_id='.$options['client_id'].'&redirect_uri='.urlencode($module_url).'&response_type=code';

        ?>
        <div class="wrap">
            <h2>
                <?php _e( 'Configure your Instagram account', 'wp_instagram_wall' ); ?>
            </h2>
            <?php
            if(count($update_options) > 0) {
                ?>
                <div id="message" class="updated notice notice-success is-dismissible">
                    <p>
                        <?php _e( 'Options updated', 'wp_instagram_wall' ); ?>
                    </p>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e( 'Close id', 'wp_instagram_wall' ); ?></span></button>
                </div>
                <?php
            }
            if(isset($_GET['success_token'])) {
                ?>
                <div id="message" class="updated notice notice-success is-dismissible">
                    <p>
                        <?php _e( 'Your token has been successfully retrieved and saved', 'wp_instagram_wall' ); ?>
                    </p>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e( 'Close id', 'wp_instagram_wall' ); ?></span></button>
                </div>
                <?php
            }
            ?>
            <p>
                <?php _e( 'Create an app on <a href="https://www.instagram.com/developer/" target="_blank">Instagram Developer</a>, and make sure your redirect uri is', 'wp_instagram_wall' ); ?> <strong><?php echo $module_url; ?></strong>
            </p>
            <form action="<?php echo $module_url; ?>" method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e( 'Client id', 'wp_instagram_wall' ); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input name="wp_instagram_wall_clientid" type="text" id="wp_instagram_wall_clientid" value="<?php echo (isset($options['client_id']) and $options['client_id'] != '') ? $options['client_id'] : ''; ?>" style="width:400px">
                                    <br>
                                    <span class="description">
                                        <a href="https://www.instagram.com/developer/clients/manage/" target="_blank">
                                            <?php _e( 'From your Instagram app', 'wp_instagram_wall' ); ?>
                                        </a>
                                    </span>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Client secret', 'wp_instagram_wall' ); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input name="wp_instagram_wall_clientsecret" type="text" id="wp_instagram_wall_clientsecret" value="<?php echo (isset($options['client_secret']) and $options['client_secret'] != '') ? $options['client_secret'] : ''; ?>" style="width:400px">
                                    <br>
                                    <span class="description">
                                        <a href="https://www.instagram.com/developer/clients/manage/" target="_blank">
                                            <?php _e( 'From your Instagram app', 'wp_instagram_wall' ); ?>
                                        </a>
                                    </span>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'User id', 'wp_instagram_wall' ); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input name="wp_instagram_wall_userid" type="text" id="wp_instagram_wall_userid" value="<?php echo (isset($options['user_id']) and $options['user_id'] != '') ? $options['user_id'] : ''; ?>" style="width:400px">
                                    <br>
                                    <span class="description">
                                        <a href="http://www.otzberg.net/iguserid/" target="_blank">
                                            <?php _e( 'Use this tool to know your User id', 'wp_instagram_wall' ); ?>
                                        </a>
                                    </span>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Instagram user token', 'wp_instagram_wall' ); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <?php
                                    if(empty($options['client_id']) or empty($options['client_secret']) or empty($options['user_id'])) {
                                        ?>
                                        <em>
                                            <?php _e( 'Please complete the information below first', 'wp_instagram_wall' ); ?>
                                        </em>
                                        <?php
                                    } else {
                                        ?>
                                        <input name="wp_instagram_wall_token" type="text" id="wp_instagram_wall_token" value="<?php echo (isset($options['token']) && $options['token'] != '') ? $options['token'] : ''; ?>" style="width:400px" disabled>
                                        <br>
                                        <span class="description">
                                            <a href="<?php echo $connect_url; ?>" class="button">
                                                <?php _e( 'Get my token', 'wp_instagram_wall' ); ?>
                                            </a>
                                        </span>
                                        <?php
                                    }
                                    ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Template</th>
                        <td>
                            <fieldset>
                                <label>
                                    <select name="wp_instagram_wall_template" id="wp_instagram_wall_template" style="width:400px">
                                        <option value="">default.php</option>
                                        <?php
                                        $templates = $this->getWallTemplates();
                                        foreach($templates as $t) {
                                            echo '<option value="'.$t.'" ';
                                            if($t == $options['template']) {
                                                echo 'selected="selected"';
                                            }
                                            echo '>'.$t.'</option>';
                                        }
                                        ?>
                                    </select>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"></th>
                        <td>
                            <fieldset>
                                <input type="submit" name="sub" id="sub" class="button" value="<?php _e( 'Save', 'wp_instagram_wall' ); ?>">
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <?php
        return;
    }
}

endif;

Wp_Instagram_Wall_Plugin::getInstance();