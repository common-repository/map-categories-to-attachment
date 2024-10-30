<?php
/*
Plugin Name: Map Categories To Attachment
Plugin URI: http://wordpress.org/extend/plugins/map-categories-to-attachment/
Description: Adds category meta box to attachment edit attachment page.

Installation:

1) Install WordPress 3.6 or higher

2) Download the latest from:

http://wordpress.org/extend/plugins/map-categories-to-attachment

3) Login to WordPress admin, click on Plugins / Add New / Upload, then upload the zip file you just downloaded.

4) Activate the plugin.

Version: 1.0
Author: TheOnlineHero - Tom Skroza
License: GPL2
*/

//constants
$kCmca_incponc='mca_incponc'; //include Attachment on category pages
$kCmca_showponp='mca_showponp'; //show posts on attachments
$kCmca_showponp_arg='mca_showponp_arg';//argument for title of posts to show on the attachments header=Posts&before=<h3>&after=</h3> 

//to make it compatible with WP3
add_action('init', 'mca_init');

function mca_init() {
	if(function_exists('register_taxonomy_for_object_type')){
  		register_taxonomy_for_object_type('category', 'attachment');
	}
}

add_action('admin_menu', 'add_category_box_on_attachment');

function add_category_box_on_attachment(){
	if(!function_exists('register_taxonomy_for_object_type')){
		add_meta_box('categorydiv', __('Categories'), 'attachment_categories_meta_box', 'attachment', 'side', 'low');
	}
}

function attachment_categories_meta_box($post) {
?>
<ul id="category-tabs">
	<li class="tabs"><a href="#categories-all" tabindex="3"><?php _e( 'All Categories' ); ?></a></li>
	<li class="hide-if-no-js"><a href="#categories-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>
</ul>

<div id="categories-pop" class="tabs-panel" style="display: none;">
	<ul id="categorychecklist-pop" class="categorychecklist form-no-clear" >
<?php $popular_ids = wp_popular_terms_checklist('category'); ?>
	</ul>
</div>

<div id="categories-all" class="tabs-panel">
	<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
<?php wp_category_checklist($post->ID, false, false, $popular_ids) ?>
	</ul>
</div>

<?php if ( current_user_can('manage_categories') ) : ?>
<div id="category-adder" class="wp-hidden-children">
	<h4><a id="category-add-toggle" href="#category-add" class="hide-if-no-js" tabindex="3"><?php _e( '+ Add New Category' ); ?></a></h4>
	<p id="category-add" class="wp-hidden-child">
	<label class="screen-reader-text" for="newcat"><?php _e( 'Add New Category' ); ?></label><input type="text" name="newcat" id="newcat" class="form-required form-input-tip" value="<?php esc_attr_e( 'New category name' ); ?>" tabindex="3" aria-required="true"/>
	<label class="screen-reader-text" for="newcat_parent"><?php _e('Parent category'); ?>:</label><?php wp_dropdown_categories( array( 'hide_empty' => 0, 'name' => 'newcat_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => __('Parent category') ) ); ?>
	<input type="button" id="category-add-sumbit" class="add:categorychecklist:category-add button" value="<?php esc_attr_e( 'Add' ); ?>" tabindex="3" />
<?php	wp_nonce_field( 'add-category', '_ajax_nonce', false ); ?>
	<span id="category-ajax-response"></span></p>
</div>
<?php
endif;

}

add_filter('the_content', 'mca_process_posts');

function mca_process_posts($content=''){
	global $kCmca_showponp, $kCmca_showponp_arg;
	if(is_page() && get_option($kCmca_showponp)==1){
		
		$args = get_option($kCmca_showponp_arg);
		$defaults = array('header' => '', 'before' => '', 'after' =>  '');
		$r = wp_parse_args($args, $defaults);
		extract( $r, EXTR_SKIP );
		if(strlen($before)==0 || strlen($after)==0){
			$before='<h3>';
			$after='</h3>';
		}
	
		global $post, $wp_query;
		$cat=array();
		foreach(get_the_category() as $category) { 
			$cat[]=$category->cat_ID; 
		}
		//var_dump($cat);
		if(count($cat)==0){
			return $content;
		}
		$showposts = -1; // -1 shows all posts
		$do_not_show_stickies = 1; // 0 to show stickies
		$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
		$args=array(
				'category__in' => $cat,
				'caller_get_posts' => $do_not_show_stickies,
				'posts_per_page'        =>      get_option('posts_per_page'),
				'paged' => $paged
		);

		$temp = $wp_query;  // assign orginal query to temp variable for later use  
		$wp_query = null;
		$wp_query = new WP_Query($args);
        ?>
        
        <?php if( $wp_query->have_posts() ) : ?>
        
        <?php $content.='<h2>'.$header.'</h2>'; ?>

		<?php while ($wp_query->have_posts()) : $wp_query->the_post(); ?>
        <?php

			$content.='		'.$before.'<a href="'.get_permalink().'" rel="bookmark" title="Permanent Link to '.the_title_attribute('echo=0').'">'.get_the_title().'</a>'.$after;
?>
		<?php endwhile; ?>
        <?php $content .= '
        <div class="navigation">
            <div class="alignleft">
              '. get_previous_posts_link() .'
            </div>
			<div class="alignright">
              '.get_next_posts_link() .'
            </div>
          </div>'; 
          ?>
          <?php $wp_query = $temp;  //reset back to original query ?>
	<?php endif; ?>
        <?php
		
	}
	return $content;
}

?>