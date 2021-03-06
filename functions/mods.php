<?php
/* 
 * Use this file to create additional modifications to 
 * the theme.
 * Functions included are:
 * 1. Prevent file edits from WP admin area for extra security
 * 2. Change the WordPress title placeholder text for custom post types i.e. Enter title => Enter Name => Enter Job
 * 3. Add a custom class to the site front page (useful for styling elements on the home differently to inner pages)
 * 4. Remove Emojis - we don't need them!
 * 5. Remove inline styles for comments - remove this if comments are needed
 * 6. Remove WordPress Version numbers from source code
 * 7. Add the active class to custom post type archives
 * 8. Add custom numeric pagination 
 * 9. Add page-link class to WordPress next and previous links for BS4 pagination
 * 10. Add excerpt support to pages
 * 11. Remove comments from admin menu
 * 12. Remove dashboard items
 * 13. Custom excerpt length 
 * 14. Custom excerpt text
 * 15. Strip spaces from strings
 * 16. Strip hyphens from strings
 * 17. Convert a string to an id format i.e my-title-as-id
 * 18. Convert a string to an array, breaking at each new line or <br /> tag
 * 19. Add ACF options page (for ACF Pro)
 * 20. Deregister Contact Form 7 styles on pages where not needed
 * 21. Deregister Contact Form 7 JavaScript on pages where not needed
 * 22. Remove query strings from static resources (replaces no.6)
 * 23. Make medium images absolutely cropped (useful when creating blogs/case studies that need set image sizes)
 * 24. Function to create a custom H1 title for taxonomies - Depends on ACF plugin 
 */
 
/*
 * 1. Prevent file edits from WP admin area for extra security 
 */
define('DISALLOW_FILE_EDIT', true);

/*
 * 2. Filter the title for a given post type
 */
function xpand_change_default_title( $title ){
    $screen = get_current_screen();
    if ( 'put-post-type-name-here' == $screen->post_type ){
        $title = 'Enter your custom text here';
    }
    return $title;
}
// uncomment below to run this function
// add_filter( 'enter_title_here', 'xpand_change_default_title' );

/*
 * 3. Add a custom body class to the front page.
 * Can be useful for adding styles that only 
 * appear on the front page
 */
add_filter( 'body_class', 'my_body_class' );
function my_body_class( $classes ) {
	if ( is_front_page() )
		$classes[] = 'site-front-page';
		return $classes;
}

/*
 * 4. Remove emojis supplied by default from WordPres
 */
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

/*
 * 5. Remove inline styles for comments
 */
function xpand_remove_recent_comments_style() {
        global $wp_widget_factory;
        remove_action( 'wp_head', array( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style' ) );
    }
add_action( 'widgets_init', 'xpand_remove_recent_comments_style' );

/*
 * 6. Remove WordPress version number
 * Mainly to make it harder for hackers to find 
 * vulnerabilities based on WP version
 */
function xpand_remove_wp_version_strings( $src ) {
     global $wp_version;
     parse_str(parse_url($src, PHP_URL_QUERY), $query);
     if ( !empty($query['ver']) && $query['ver'] === $wp_version ) {
          $src = remove_query_arg('ver', $src);
     }
     return $src;
}
add_filter( 'script_loader_src', 'xpand_remove_wp_version_strings' );
add_filter( 'style_loader_src', 'xpand_remove_wp_version_strings' );
remove_action('wp_head', 'wp_generator');

/*
 * 7. Add an active class to custom post types
 * So custom post types get the Bootstrap active class
 */
function xpand_custom_active_item_classes($classes = array(), $menu_item = false){            
        global $post;
        $classes[] = ($menu_item->url == get_post_type_archive_link($post->post_type)) ? 'current-menu-item active' : '';
        return $classes;
    }
add_filter( 'nav_menu_css_class', 'xpand_custom_active_item_classes', 10, 2 );

/* 
 * 8. Create pagination links instead of standard next/previous posts links
 */
function xpand_custom_numeric_posts_nav() {

	if( is_singular() )
		return;

	global $wp_query;

	/** Stop execution if there's only 1 page */
	if( $wp_query->max_num_pages <= 1 )
		return;

	$paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;
	$max   = intval( $wp_query->max_num_pages );

	/**	Add current page to the array */
	if ( $paged >= 1 )
		$links[] = $paged;

	/**	Add the pages around the current page to the array */
	if ( $paged >= 3 ) {
		$links[] = $paged - 1;
		$links[] = $paged - 2;
	}

	if ( ( $paged + 2 ) <= $max ) {
		$links[] = $paged + 2;
		$links[] = $paged + 1;
	}
	
	// Target the following css selectors to style your links
	echo '<nav aria-label="Pagination"><ul class="pagination">' . "\n";

	/**	Previous Post Link */
	if ( get_previous_posts_link() )
		printf( '<li class="page-item">%s</li>' . "\n", get_previous_posts_link() );

	/**	Link to first page, plus ellipses if necessary */
	if ( ! in_array( 1, $links ) ) {
		$class = 1 == $paged ? ' active' : '';

		printf( '<li class="page-item %s"><a class="page-link" href="%s">%s</a></li>' . "\n", $class, esc_url( get_pagenum_link( 1 ) ), '1' );

		if ( ! in_array( 2, $links ) )
			echo '<li>…</li>';
	}

	/**	Link to current page, plus 2 pages in either direction if necessary */
	sort( $links );
	foreach ( (array) $links as $link ) {
		$class = $paged == $link ? ' active' : '';
		printf( '<li class="page-item %s"><a class="page-link" href="%s">%s</a></li>' . "\n", $class, esc_url( get_pagenum_link( $link ) ), $link );
	}

	/**	Link to last page, plus ellipses if necessary */
	if ( ! in_array( $max, $links ) ) {
		if ( ! in_array( $max - 1, $links ) )
			echo '<li class="page-item>…</li>' . "\n";

		$class = $paged == $max ? ' active' : '';
		printf( '<li class="page-item %s"><a class="page-link" href="%s">%s</a></li>' . "\n", $class, esc_url( get_pagenum_link( $max ) ), $max );
	}

	/**	Next Post Link */
	if ( get_next_posts_link() )
		printf( '<li class="page-item">%s</li>' . "\n", get_next_posts_link() );

	echo '</ul></nav>' . "\n";

}

/* 
 * 9. Add page-link class to WordPress next and previous links for BS4 pagination
 */
add_filter('next_posts_link_attributes', 'posts_link_attributes');
add_filter('previous_posts_link_attributes', 'posts_link_attributes');

function posts_link_attributes() {
    return 'class="page-link"';
}

/* 
 * 10. Add excerpt support to pages
 */
add_action( 'init', 'xpand_excerpts_to_pages' );
function xpand_excerpts_to_pages() {
     add_post_type_support( 'page', 'excerpt' );
}


/*
 * 11.  Remove comments from admin menu
 */
add_action( 'admin_init', 'my_remove_admin_menus' );
function my_remove_admin_menus() {
    remove_menu_page( 'edit-comments.php' );
}


/*
 * 12. Remove items from Dashboard
 */ 
function remove_dashboard_meta() {
  remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
  remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
  remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
  remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
  remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
  remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
  remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
  remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');//since 3.8
}
add_action( 'admin_init', 'remove_dashboard_meta' );


/*
 * 13. Add custom excerpt length.
 */
function custom_excerpt_length( $length ) {
	return 20;
}
add_filter( 'excerpt_length', 'custom_excerpt_length', 22 );



/*
 * 14. Change default excerpt more link.
 */
function new_excerpt_more($more) {
	global $post;
	return '...';
}
add_filter('excerpt_more', 'new_excerpt_more');


/* 
 * Utility functions
 */
 
// 15. Strip white space from text, i.e phone number
function stripSpaces($string) {
	$string = str_replace(' ', '', $string);
	return $string;
}

// 16. Strip hyphens from a string
function stripHyphens($string) {
	$string = str_replace('-', '', $string);
	return $string;
}

// 17. Convert a string into a format suitable for an ID
// i.e My Post Title becomes my-post-title
function covertToId($string) {
	$string = strtolower($string);
	$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
	$string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	
	return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
}

// 18. Convert a string to an array, breaking at each new line or <br /> tag
function convertStringToArray($string, $break, $tags = null) {
	$array = explode($break, $string);
	
	foreach($array as $key => $value) {
		echo '<' . $tags . '>' . $value . '</' . $tags . '>';
	} 
}


/* 
 * 19. Add ACF options page
 */
if( function_exists('acf_add_options_page') ) {

	acf_add_options_page('Global Settings');

}	


// 20. Deregister Contact Form 7 styles on pages where not needed
add_action( 'wp_print_styles', 'xpand_deregister_styles', 100 );
function xpand_deregister_styles() {
    if ( ! is_page( array(/* pages go here */) ) ) {
        wp_deregister_style( 'contact-form-7' );
    }
}
// 21. Deregister Contact Form 7 JavaScript files on all pages without a form
add_action( 'wp_print_scripts', 'xpand_deregister_javascript', 100 );
function xpand_deregister_javascript() {
    if ( ! is_page( array(/* pages go here */) ) ) {
        wp_deregister_script( 'contact-form-7' );
    }
}

// 22. Remove query strings from static resources
function _remove_script_version( $src ){
    $parts = explode( '?ver', $src );
        return $parts[0];
}
add_filter( 'script_loader_src', '_remove_script_version', 15, 1 );
add_filter( 'style_loader_src', '_remove_script_version', 15, 1 );

// 23. Make medium images absolutely cropped
if( function_exists( 'add_image_size' ) ) {
	add_image_size( 'medium', 350, 295, true );
}

// 24. Create a function to display a custom title for 
//     category archives - saves finding the code every time
// NOTE: Depends on having the ACF plugin installed!!!!!
function alt_title_for_taxonomies($field_name) {
	$term = get_queried_object();
	$taxonomy = $term->taxonomy;
	$term_id = $term->term_id; 			
	
	return get_field($field_name, $term);
}
//Use like so: 
//$h1_title = alt_title_for_taxonomies('h1_title');
// if($h1_title) ... etc 