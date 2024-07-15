<?php
/**
 * @package S4U Endomondo Challenges
 * @version 1.0.0
 */
/*
Plugin Name: DOTK Parse External Content 2 Posts
Plugin URI: https://solution4u.nl/
Description: Enter URL's and let a CRON-task retrieve all data and convert it to posts
Author: ing. Dirk Hornstra
Version: 1.0.0
Author URI: https://solution4u.nl/
*/
class Dotk_External_Content_To_Post {
    public function RunPlugin()
    {
        if (is_admin()) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            require_once('dotk-parse-external-content-to-posts-admin.php');
            $admin = new Dotk_External_Content_To_Posts_Admin();
            add_action('admin_menu', array($admin, 'AddAdminMenu'));
            //add_action('init', array($admin, 'InitAdminSettings'));
            if (isset($_GET['adminaction']))
            {
                $admin->HandleRequest($_GET['adminaction']);
            }
        }
        else {
            if (isset($_GET["cron"]) && $_GET["cron"] == "true")
            {
                require_once('dotk-parse-external-content-to-posts-admin.php');
                $admin = new Dotk_External_Content_To_Posts_Admin();
                $admin->HandleCron();
            }
            if (isset($_GET["cron"]) && $_GET["cron"] == "mail" && isset($_GET["receiver"]))
            {
                require_once('dotk-parse-external-content-to-posts-admin.php');
                $admin = new Dotk_External_Content_To_Posts_Admin();
                $admin->TestMail($_GET["receiver"]);
            }            
        }
    }
}
function insert_my_head() {
    echo "<base target=\"_blank\" />";
    echo "<style>";
    echo "img{width:100%}";
    echo "</style>";
}
$dotk_external_content_to_post = new Dotk_External_Content_To_Post();
$dotk_external_content_to_post->RunPlugin();
add_action('wp_head', 'insert_my_head');
?>