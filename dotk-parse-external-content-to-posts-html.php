<?php
class Dotk_External_Content_To_Posts_Html {

    private $_admin;

    public function __construct($admin)
    {
        $this->_admin = $admin;
    }

    public function ShowListOfUrls($posts)
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Url's</h1>
            <a href="/wp-admin/options-general.php?page=<?php echo $this->_admin->GetSlug();?>&adminaction=add_url" class="page-title-action">Nieuwe URL toevoegen</a>
            <hr class="wp-header-end">
            <h2 class="screen-reader-text">Berichtenlijst</h2>
            <table class="wp-list-table widefat fixed striped table-view-list posts">
                <caption class="screen-reader-text">Tabel gesorteerd op datum. Aflopend.</caption>	
                <thead>
                <tr>
                    <th scope="col" id="title" class="manage-column column-title column-primary" abbr="Titel">
                        <span>Titel</span>
                    </th>
                </tr>
                </thead>
                <tbody id="the-list">
                    <?php 
        if (count($posts) == 0)
        {?>
            <tr class="no-items">
                <td class="colspanchange" colspan="1">Geen URL's gevonden.</td>
            </tr>            
<?php   }
        else {
            foreach($posts as $post)
            {?>
            <tr>
                <td class="colspanchange" colspan="1"><a href="/wp-admin/options-general.php?page=<?php echo $this->_admin->GetSlug();?>&adminaction=edit_url&id=<?php echo $post->ID;?>"><?php echo $post->post_title;?></a></td>
            </tr>
<?php       }
        }

?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function ShowAddOrEditForm($post)
    {
        $editMode = is_int($post->ID);
        $adminAction = $editMode ? "edit_url_save" : "add_url_save";
        ?>
        <div id="wpbody" role="main">
            <div id="wpbody-content">
                <div class="wrap">
                    <h1>URL bewerken/toevoegen</h1>
                    <form method="post" action="/wp-admin/options-general.php?page=<?php echo $this->_admin->GetSlug();?>&adminaction=<?php echo $adminAction;?>">
                    <?php if ($editMode) { ?>
                    <input type="hidden" name="post_ID" value="<?php echo $post->ID;?>" />                        
                    <?php } ?>
                    <input type="hidden" name="module" value="<?php echo $this->_admin->GetSlug();?>" />
                    <input type="hidden" name="post_type" value="external_url" />
                    <input type="hidden" name="visibility" value="private" />
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                        <tr>
                            <th scope="row"><label for="url">Naam</label></th>
                            <td><input name="post_title" type="text" id="name" value="<?php echo $post->post_title;?>" class="regular-text code"></td>
                        </tr>                            
                        <tr>
                            <th scope="row"><label for="url">Url</label></th>
                            <td><input name="post_excerpt" type="text" id="url" value="<?php echo $post->post_excerpt;?>" class="regular-text code">
                            <?php 
                            if ($post->post_excerpt != "") { ?>
                            <p><a href="<?php echo $post->post_excerpt;?>" target="_blank">Website</a></p>
                            <?php }
                            ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="url">Code</label></th>
                            <td><textarea name="post_content" id="parsing" cols="50" rows="10"><?php echo $post->post_content;?></textarea></td>
                        </tr>                        
                        </tbody>
                    </table>
                    <div class="clear"></div>
                    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Opslaan"></p>
                    </form>
                </div>
            </div><!-- wpbody-content -->
            <div class="clear"></div>
        </div>
        <?php
    }
}
?>