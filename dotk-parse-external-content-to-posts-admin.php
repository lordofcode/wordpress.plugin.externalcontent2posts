<?php
class Dotk_External_Content_To_Posts_Admin {
    private $_slug = "dotk-external-content-2-posts";
    private $_postType = "external_url";
    private $_action;

    public function __construct()
    {
    }

    public function InitAdminSettings()
    {
        register_post_type($this->_postType,
            array(
                'labels'      => array(
                    'name'          => "Url",
                    'singular_name' => "Url's",
                ),
                'public'      => true,
                'has_archive' => true,
            )
        );
    }


    public function AddAdminMenu()
    {
        add_options_page( 'Externe URL\'s', 'URL Beheer', 'administrator', $this->_slug, array($this, 'SettingsPage'), 1 );        
    }

    public function GetSlug()
    {
        return $this->_slug;
    }

    public function SettingsPage()
    {
        $this->InitAdminSettings();
        require_once('dotk-parse-external-content-to-posts-html.php');
        $html = new Dotk_External_Content_To_Posts_Html($this);
        switch($this->_action)
        {
            case "add_url":
                $html->ShowAddOrEditForm($this->BuildPostObject());
                break;
            case "edit_url":
                if (!isset($_GET["id"]))
                {
                    return;
                }
                $html->ShowAddOrEditForm(get_post($_GET["id"]));
                break;
            case "add_url_save":
            case "edit_url_save":                
                if (!isset($_POST['post_type']))
                {
                    return;
                }
                wp_write_post();
                if ($_POST["post_ID"] != "")
                {
                    update_metadata( 'post', $_POST["post_ID"], 'lastsync', date("Y-m-d H:i", mktime(0,0,0,1,1,1970)));
                }
                $html->ShowListOfUrls($this->GetListOfUrls());
                break;
            default:
                $html->ShowListOfUrls($this->GetListOfUrls());
        }
    }

    private function BuildPostObject()
    {
        $post = new stdClass();
        $post->ID = "";
        $post->post_title = "";
        $post->post_excerpt = "";
        $post->post_content = "";
        return $post;
    }

    private function GetListOfUrls()
    {
        return get_posts(array('post_type' => 'external_url', 'post_status' => 'private', 'numberposts' => 1000, 'orderby' => 'post_title', 'order' => 'ASC'));
    }

    private function GetMetaDataOfExistingPosts()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key IN ('origin', 'original_html')");
    }

    private function GetMetaDataOfUrls()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key IN ('lastsync')");        
    }

    private function GetIgnoreWords()
    {
        $nvtCategory = get_categories(array('hierarchical' => 'true', 'hide_empty' => 0, 'slug' => 'nvt'));
        return get_categories(array('hierarchical' => 'true', 'hide_empty' => 0, 'child_of' => $nvtCategory[0]->cat_ID));
    }

    public function HandleRequest($requestAction)
    {
        $this->_action = $requestAction;
    }

    public function TestMail($recipient)
    {
        require_once('./wp-includes/pluggable.php');        
        wp_mail($recipient, "controle e-mail", "dit is mijn boodschap en daar moet je het mee doen.");
        echo "mailtje verzonden!";
        exit;
    }

    public function HandleCron()
    {
        /*
        if ( ! function_exists( 'is_user_logged_in' ) ) :
            function is_user_logged_in() {
                return true;
            }
        endif;
        */
        require_once('./wp-includes/pluggable.php');
        /*
        $GLOBALS['wp_rewrite'] = new WP_Rewrite();
        */

        $ignoreCategories = $this->GetIgnoreWords();
        $existingPostData = $this->GetMetaDataOfExistingPosts();

        $now = date_parse(date('Y-m-d H:i'));
        $processCount = 0;

        $posts = $this->GetListOfUrls();
        $metaDataOfPosts = $this->GetMetaDataOfUrls();
        //var_dump($metaDataOfPosts);
        foreach($posts as $post)
        {
            $skipAdd = false;
            foreach($metaDataOfPosts as $c)
            {
                if ($c->post_id == $post->ID)
                {
                    $lastUpdate = date_parse($c->meta_value);
                    if ($lastUpdate["year"] == $now["year"] && $lastUpdate["month"] == $now["month"] && $lastUpdate["day"] == $now["day"] && $lastUpdate["hour"] == $now["hour"])
                    {
                        $skipAdd = true;
                    }
                }
            }
            if ($skipAdd)
            {
                continue;
            }

            update_metadata( 'post', $post->ID, 'lastsync', date("Y-m-d H:i"));
            $url = trim($post->post_excerpt);
            $matchValue = trim($post->post_content);
    
            if (strlen($matchValue) == 0 || strlen($url) == 0)
            {
                continue;
            }
    
            echo "process " . $post->post_title . "<HR/>";            
            $http = new WP_Http;
            $args = array('method' => 'GET', 'headers' => [
                'Content-Type' => 'text/html'
            ]);
            $response = $http->request($url, $args);
            if ( is_wp_error( $response ) ) {
                echo "could not process " . $post->post_title . "<HR/>";
                continue;
            }
            $data = str_replace(array("\r", "\n"), "", $response["body"]);
            $items = array();
            if (preg_match('/'.$matchValue.'(.*)/', $data, $matches)) {
                $data = $matches[0];
                $counter = 1;
                while (true)
                {
                    if ($counter > 100) {
                        break;
                    }
                    $nextPos = strpos($data, $matchValue, strlen($matchValue));
                    if ($nextPos <= 0) {
                        if (strpos("_".$data, $matchValue) > 0)
                        {
                            array_push($items, $data);        
                        }
                        break;
                    }
                    $item = substr($data, 0, $nextPos);
                    $data = substr($data, $nextPos);
                    array_push($items, $item);
                    $counter++;
                }
            }
    
            if (isset($_GET["debug"]) && $_GET["debug"] == "true")
            {
                var_dump($items);
            }
            $counter = 1;
            $processCount++;
            foreach($items as $item) {
                $lowerCaseItem = strtolower($item);
                $existingPostIds = array();
                foreach($existingPostData as $compare)
                {
                    if ($compare->meta_key == "original_html" && $compare->meta_value == $lowerCaseItem)
                    {
                        array_push($existingPostIds, $compare->post_id);
                    }
                }
                $skipAdd = false;            
                if (count($existingPostIds) > 0)
                {
                    foreach($existingPostIds as $id)
                    {
                        foreach($existingPostData as $compare)
                        {
                            if ($compare->meta_key != "origin" && $compare->post_id != $id)
                            {
                                continue;
                            }
                            if ($compare->meta_value == $url)
                            {
                                $skipAdd = true;
                            }
                        }   
                    }            
                }
                foreach($ignoreCategories as $ignoreCategory)
                {
                    if ($skipAdd)
                    {
                        continue;
                    }
                    if (strpos($lowerCaseItem, $ignoreCategory->name) > 0) {
                        $skipAdd = true;
                    }
                }    
                if ($skipAdd)
                {
                    continue;
                }                    
                $postAsArray = array();
                $postAsArray['post_title'] = $post->post_title.'-'.date('Ymd').'-'.$counter;
                $postAsArray['post_author'] = 1;
                $postAsArray['post_excerpt']  = "";
                $postAsArray['post_content']  = $this->RemoveHtmlTags($item, $url);
                $postAsArray['_status']  = 'draft';
                $postAsArray['post_status']  = 'draft';
                //var_dump($postAsArray);

                $postID = wp_insert_post($postAsArray);
                add_post_meta( $postID, 'origin', $url );
                add_post_meta( $postID, 'original_html', $lowerCaseItem );
                $counter++;
            }
        }
        echo "Processed " . $processCount;
        exit;
    }

    private function RemoveHtmlTags($data, $url)
    {
        $data = str_replace(array(">"), ">\n", $data);
        $data = str_replace(array("<"), "\n<", $data);
        $lines = explode("\n", $data);
        $filteredLines = "";
        $skipLine = false;
        foreach($lines as $line)
        {
            if (trim($line) == "")
            {
                continue;
            }
            $checkLine = "_".$line;
            if ((strpos($checkLine, "<script") > 0 ) || (strpos($checkLine, "</script>") > 0 ))
            {
                $skipLine = strpos($checkLine, "<script") > 0 ? true : false;
                continue;
            }
            if ($skipLine)
            {
                continue;
            }
            $filteredLines .= $line . "\n";
        }
        $data = $filteredLines;

        preg_match_all('/<(.*)/', $data, $matches);
        if (is_array($matches) && is_array($matches[0]))
        {
            foreach($matches[0] as $match)
            {
                $checkMatch = "_".$match;
                if ((strpos($checkMatch, "<a ") > 0 ) || (strpos($checkMatch, "</a>")  > 0  ) || (strpos($checkMatch, "<img ")  > 0 ) || (strpos($checkMatch, "</img>") > 0  ))
                {
                    if (strpos($checkMatch, "<img ")  > 0 )
                    {
                        $data = $this->PatchImageSource($data, $match, $url);
                    }
                    continue;
                }
                $data = str_replace($match, "", $data);
            }
        }
        $data = preg_replace('/\n/', ' ', $data);
        return $data;
    }

    private function PatchImageSource($data, $imgMatch, $url)
    {
        $baseUrl = '';
        $parts = explode('/', $url);
        $counter = 0;
        foreach($parts as $part)
        {
            if ($counter > 2)
            {
                continue;
            }
            $baseUrl .= $part . "/";
            $counter++;
        }
        $baseUrl = trim($baseUrl, '/');

        $parts = explode(" ", $imgMatch);
        $patched = "";
        foreach($parts as $part)
        {
            if (strpos("_".$part, "src=")  > 0 )
            {
                $imgUrl = str_replace('"', '', substr($part, 4));
                if (strpos($imgUrl, "/bovag_garantie.png") > 0 || strpos($imgUrl, "/nap_weblabel.png") > 0 || strpos($imgUrl, "/nap.svg") > 0)
                {
                    return str_replace($imgMatch, "", $data);
                }
                if (substr($imgUrl, 0, 1) == '/')
                {
                    //$part = "src=\"".$baseUrl . $imgUrl . "\"";
                    $imgUrl = $baseUrl . $imgUrl;
                }
                return str_replace($imgMatch, "<img src=\"$imgUrl\" />", $data);
            }
            $patched .= $part . " ";
        }
        return str_replace($imgMatch, trim($patched), $data);
    }
}
?>