<?php

/** 
* Die if directly access 
*/

if(!defined("WPINC")) { die('Access denied. Listen... God is speaking to you'); }

/**************************/
/* REQUEST DATA FUNCTIONS */
/**************************/ 



function bovp_log( $inf ) {

	$ip = $_SERVER['REMOTE_ADDR'];
	$log = fopen( BOVP_PATH . '/log/log.bovp', 'a');
	$insert = date( 'd/m/Y h:i:s', time() ) . ' - IP: ' . $ip . ' - INF: ' . $inf . "\n";
	fwrite($log, $insert);
	fclose($log);

}


/**
 * Recovery params from URL.
 *
 * @since 1.5.3
 *
 * @return void
 */

function bovp_set_params() {

	global $bovp_sets;

	$bovp_keys = array('bovp_type','bovp_book','bovp_cp','bovp_vs','bovp_search','bovp_cpg');

	if( isset( $bovp_sets->furl ) ) {

		if( isset( $_SERVER['REQUEST_URI'] ) AND !empty( $_SERVER['REQUEST_URI'] ) ) {

			$bovp_set_params = $_SERVER['REQUEST_URI'];

			if ( preg_match( '/log/', $bovp_set_params ) ) {

				$explode_params = explode('log/', $bovp_set_params);
				$bovp_set_params =  $explode_params[0];
				$bovp_sets->key_log = trim( str_replace( '/', '', $explode_params[1] ) );

			}

			/**
			* Url Fix for old versions'/book|search|search_in|favorites|index/'
			*/

			$slugs = array($bovp_sets->slug_book, $bovp_sets->slug_search, $bovp_sets->slug_search_in, $bovp_sets->slug_index, $bovp_sets->slug_favorites);

			$fix_slug_macth = "'/" . implode( '|', array_values( $slugs ) ) . "/'";

			if ( !preg_match( $fix_slug_macth, $bovp_set_params ) ) { 

				if( preg_match( "/($bovp_sets->slug_page\/?)$/", $bovp_set_params ) ) {

					$bovp_set_params = str_replace( $bovp_sets->slug_page, $bovp_sets->slug_page . '/' . $bovp_sets->on_front , $bovp_set_params );

				} else {

					$bovp_set_params = str_replace( $bovp_sets->slug_page, $bovp_sets->slug_page . '/' . $bovp_sets->slug_book , $bovp_set_params );
				}
			
			}

			$params = explode( $bovp_sets->slug_page , $bovp_set_params );

			$params = $params[1];
		
            if( substr( $params, -1 ) === '/' ) { $params = substr( $params, 0, -1 ) ; }
            if( substr( $params, 0, 1 ) === '/' ) { $params = substr( $params, 1 ) ; }

			$filter = explode( '/', $params );

			switch ( $filter[0] ) {

				case $bovp_sets->slug_book:

					foreach ( $filter as $key => $value ) {

						$set_params[$bovp_keys[$key]] = $value;
					}

					if( !isset( $set_params['bovp_book'] ) ) $set_params['bovp_book'] = $bovp_sets->books_info[1]['slug_name'];

					if( !is_numeric( $set_params['bovp_cp'] ) ) $set_params['bovp_cp'] = 1;

					break;
				
				case $bovp_sets->slug_search:

					$search_params = urldecode( $filter[1] );

					if( strrpos( $search_params, '-' ) ) {

						$array_search = explode( '-', $search_params );

						foreach ($array_search as $param) {

							if( strlen( $param ) > 1 ) { $array_terms[] = bovp_sanitize_search( $param ); }
						}

						//if( count( $array_terms ) > 1 ) { $array_terms[] = bovp_sanitize_search( $search_params ); }

						$set_params['bovp_search'] = $array_terms;

					} else { $set_params['bovp_search'] = bovp_sanitize_search( $search_params ); }

					$set_params['bovp_type'] = $filter[0];
					$set_params['bovp_search_slug'] = $search_params;
					if( isset( $filter[2] ) ) $set_params['bovp_cpg'] = $filter[2];				
					break;

				case $bovp_sets->slug_search_in:

					$set_params['bovp_type'] = false;
					break;

				case $bovp_sets->slug_favorites: 

					$set_params['bovp_type'] = $filter[0];
					break;

				case $bovp_sets->slug_index:

					$set_params['bovp_type'] = $filter[0];
					break;

				default:
					break;

			}

		} 

	} else {

		
		$_REQUEST['bovp_type'] = isset( $_REQUEST['bovp_type'] ) ? $_REQUEST['bovp_type'] : $bovp_sets->slug_index;

		foreach ( $_REQUEST as $key => $value ) {

			if( in_array( $key, $bovp_keys ) ) { $filter[$key] = $value; }
			
		}

		if( isset( $filter['bovp_book'] ) AND !isset( $filter['bovp_cp'] ) ) { $filter['bovp_cp'] = 1; }

		if( isset( $filter['bovp_search'] ) AND !isset( $filter['bovp_cpg'] ) ) { $filter['bovp_cpg'] = 1; }

		$set_params =  $filter;

	} 


return $set_params;

}

/**
 * Prepare URL for recovery data from bovp table.
 *
 * @since 1.5.3
 *
 * @return string
 */

function bovp_set_query() {

	global $bovp_sets;
	global $wpdb;

	$query = "SELECT * FROM ". $bovp_sets->table . " WHERE ";

	if( !isset( $bovp_sets->params ) ) { return false; } 

	$bovp_type = $bovp_sets->params['bovp_type'];

	$keys = array_keys( $bovp_sets->params );

	foreach ( $bovp_sets->params as $key => $value ) {

		if( $key != 'bovp_type' AND $key != 'bovp_cpg' ) {

			$param = str_replace('bovp_', "", $key);

			$param_value = $value;

			if( $param == 'search' ) { 

				if( is_array( $value ) ) {

					foreach ( $value as $key => $word ) {

						$query .= " ( LCASE(text) RLIKE '[[:<:]]" . $word ."[[:>:]]' ) ";

						if( $key < ( count( $value ) - 1 ) ) $query .= ' AND ';

					}

				} else {

					$query .= " ( LCASE(text) RLIKE '[[:<:]]" . $value ."[[:>:]]' ) ";
	
				}
		 
				return $query;

			} else {

				if( $param == 'book' AND !is_numeric( $param_value ) ) { 

					foreach ($bovp_sets->books_info as $book => $info) {
						
						if( $info['slug_name'] == $param_value ) {

							$param_value = (int)$book;
							break;
						}
					}

				} 

				$query .= "$param = $param_value";
			}
				
			if ( $key != end( $keys ) ) { $query .= " AND "; }

		}

	}

	$query .= ";";

	return $query;

}

/***********************/
/*  */
/***********************/

/**
 * Latin Sanitize Search.
 *
 * @since 1.5.1
 *
 * @return void
 */

function bovp_sanitize_search( $word, $type='search' ) {

	$replace = array(
		"a" => "(a|á|ã)",
		"e" => "(e|é|ê)",
		"i" => "(i|í)",
		"o" => "(o|ó|õ|ô)",
		"u" => "(u|ú)",
		"c" => "(c|ç)",
		"-" => "(-)"
		);

	if( $type == 'search' ) {

		$word =  preg_replace( $replace , array_values( $replace ), $word );

	} else {

		$word =  preg_replace( $replace , array_keys( $replace ), $word );

	}

	

	return str_replace( "-", '%', $word );

}




/***************************************************************************/
/******************* INCLUDE DEPENDENCES FUNCTIONS *************************/
/***************************************************************************/

/**
 * Include css and js dependences files.
 *
 * @since 1.5.3
 *
 * @return void
 */

function bovp_enqueue_dependences() {

	global $bovp_sets;
	
	$style_sheet = $bovp_sets->plugin_url ."/themes/". $bovp_sets->theme .".css";
	wp_enqueue_style( "bible_style", $style_sheet);
	wp_enqueue_script("jquery");
	wp_enqueue_script( "bible_js", $bovp_sets->plugin_url ."/includes/bovp.js");

}


/***************************************************************************/
/************************** FRONT-END FUNCTIONS ****************************/
/***************************************************************************/

/**
 * Show font size selector. 
 *
 * @since 1.5.1
 *
 * @return string
 *
 **/

function bovp_font_size_select() {

	global $bovp_sets;

	$return  = '<div class="bovp_fsize">';
	$return .= "<span><a href='javascript:void(0)' class='decrease' style='font-size:12px;'>a</a></span>";
	$return .= "<span><a href='javascript:void(0)' class='increase' style='font-size:22px;'>a</a></span>";
	$return .= "</div>";

	return $return;		

}


/**
 * Show all books. 
 *
 * @since 1.5.3
 *
 * @return string
 *
 **/

function bovp_print_books_index() {

	global $bovp_sets;

	if( isset( $bovp_sets->menu_on_footer ) ) { 

		$list_books =  bovp_list_books( 'bovp_footer_index' );

		return $list_books;

	}
	
}


/**
 * Show Bible title on the page title
 *
 * @since 1.5.3
 *
 * @return String
 */

function bovp_show_title() {

	global $bovp_sets;

	if( isset( $bovp_sets->furl ) ) {

		$books = bovp_invert_array_books();

		$book_name = $books[$bovp_sets->params['bovp_book']]['name'];

	} else {

		$book_name = $bovp_sets->books_info[$bovp_sets->params['bovp_book']]['name'];

	}

	$title = get_bloginfo('name') . ' - ' . $book_name . ':'. $bovp_sets->params['bovp_cp'];

	return $title;

}


/**
 * Show Bible form search
 *
 * @since 1.5.3
 *
 * @return Bible Text
 */

function bovp_search_form() {

	global $bovp_sets;

	$img_lupa = $bovp_sets->plugin_url . "themes/img/lupa.png";

	if( isset( $bovp_sets->furl ) ) {

		$action = $bovp_sets->url . $bovp_sets->slug_page .'/' . $bovp_sets->slug_search . '/';
		$url_class = 'furl_submit';

	} else {

		$action = $bovp_sets->url;
		$hidden_fields = "<input type='hidden' name='page_id' value='" . $bovp_sets->page . "'>";
		$hidden_fields .= "<input type='hidden' name='bovp_type' value='" . $bovp_sets->slug_search . "'>";
		$url_class = '';

	}
	
	$search_form = "<div id='bovp_search_container'>";
    $search_form .= "<form id='bovp_search_form' name='bovp_search_form' class='$url_class' method='get' action='$action'>";
    $search_form .= $hidden_fields;
    $search_form .= "<img src='$img_lupa'/>";
    $search_form .= "<input name='bovp_search' type='text' id='bovp_search_text' placeholder='buscar...'/>";
    $search_form .= "<div id='cx_search_submit'>";
    $search_form .= "<span class='arrow_search_submit'></span>";
   	$search_form .= "<button class='btn_search_submit'>Buscar</button>";
   	$search_form .= "</div>";
	$search_form .= "</form>";
	$search_form .= "</div>";

	return $search_form;

}


/**
 * Replace page content with Bible content
 *
 * @since 1.5.3
 *
 * @return Bible Text
 */

function bovp_show_content( $content ) {

	global $bovp_sets;
	global $wpdb;

	$cur_page = get_the_ID();

	if( !isset( $bovp_sets->table ) AND empty( $bovp_sets->table ) ) { return;	}



	$return = "";

	if( $cur_page == $bovp_sets->page  OR $cur_page == 1218 ) { 

		$bovp_sets->params = bovp_set_params();
		$bovp_sets->query = bovp_set_query();
		$time = microtime(true);

		if( empty( $bovp_sets->table ) ) {

			$return = '<h4>' . __('Please, visit the Online Bible Settings Menu to install your prefered translation.', 'bovp') . '</h4>';
			
		} else {

			
			$return = bovp_start_section();

			$return .= bovp_search_form();

			switch ( $bovp_sets->params['bovp_type'] ) {

				case $bovp_sets->slug_book:
					$return .= bovp_show_book();
					break;

				case $bovp_sets->slug_search:
					$return .= bovp_show_search();
					break;

				case $bovp_sets->slug_search_in:
					$return .= bovp_show_search_in();
					break;

				case $bovp_sets->slug_favorites:
					$return .= bovp_show_favorites();
					break;
				
				case $bovp_sets->slug_index:
					$return .= bovp_show_index();
					break;

				default:
					$return .= __('Nothing Found!', 'bovp');
					$return .= print_r($bovp_sets->params, true);
					break;
			}
		}


		if( isset( $_REQUEST['log'] ) OR isset( $bovp_sets->key_log ) ) { 

			$key_log = isset( $_REQUEST['log'] ) ? $_REQUEST['log'] : $bovp_sets->key_log;

			if( $key_log == $bovp_sets->key_log_tester ) {

				$return .='<pre>'. __('Execution Time :', 'bovp') . count_execution_time($time).'</pre>';
				$return .= '<pre>' . print_r( bovp_show_sets(), true ) .'</pre>';

			} 

		} 

		$return .= bovp_end_section();


	} else {

		$return = $content; 
	}
	

	return $return;

}

/**
 * Return Selected Book Content
 *
 * @since 1.5.3
 *
 * @return string
 */

function bovp_show_book() {

	global $wpdb;
	global $bovp_sets;

	$results = $wpdb->get_results( $bovp_sets->query );

	$bovp_sets->results = $results;

	if( isset( $bovp_sets->furl ) ) { 

		$books = bovp_invert_array_books(); 
		$bovp_sets->current_book = $books[$bovp_sets->params['bovp_book']]['book_id'];
		$bovp_sets->current_book_name = $books[$bovp_sets->params['bovp_book']]['name'];

		$prev_book = $bovp_sets->current_book - 1;
		$prev_book_name = $bovp_sets->books_info[$prev_book]['name'];
		$prev_book_link = $bovp_sets->url . $bovp_sets->slug_page . '/' . $bovp_sets->slug_book . '/' . $bovp_sets->books_info[$prev_book]['slug_name'];

		$next_book = $bovp_sets->current_book + 1;
		$next_book_name = $bovp_sets->books_info[$next_book]['name'];
		$next_book_link = $bovp_sets->url . $bovp_sets->slug_page . '/' . $bovp_sets->slug_book . '/' . $bovp_sets->books_info[$next_book]['slug_name'];

	} else { 

		$books = $bovp_sets->books_info;
		$bovp_sets->current_book = $bovp_sets->params['bovp_book'];
		$bovp_sets->current_book_name = $books[$bovp_sets->params['bovp_book']]['name'];

		$prev_book = $bovp_sets->current_book - 1;
		$prev_book_name = isset( $books[$prev_book]['name'] ) ? $books[$prev_book]['name'] : "" ;
		$prev_book_link = $bovp_sets->url . "?page_id=" . $bovp_sets->page . '&bovp_type=' . $bovp_sets->slug_book . '&bovp_book=' . $prev_book;

		$next_book = $bovp_sets->current_book + 1;
		$next_book_name = isset( $books[$next_book]['name'] ) ? $books[$next_book]['name'] : "";
		$next_book_link = $bovp_sets->url . "?page_id=" . $bovp_sets->page . '&bovp_type=' . $bovp_sets->slug_book . '&bovp_book=' . $next_book;
	}

	$bovp_sets->lastpage = $books[$bovp_sets->params['bovp_book']]['pages'];

	$book_name = $books[$bovp_sets->params['bovp_book']]['name'];

	
	$bovp_content  = "<header class='bovp_header'>";
	$bovp_content .= "<div id='prev_book'><a href='" . $prev_book_link . "'>" . $prev_book_name . "</a></div>";
	$bovp_content .= "<div id='curr_book'>" . $bovp_sets->current_book_name . "</div>";
	$bovp_content .= "<div id='next_book'><a href='" . $next_book_link . "'>" . $next_book_name . "</a></div>";
	$bovp_content .= "</header>";

	if( $bovp_sets->lastpage > 1 ) $bovp_content .= bovp_caps_from_curr_book();	

	$bovp_content .= "<article class='bovp_content'>";

	$bovp_content .= bovp_font_size_select();


	if( isset( $_COOKIE['bovpcurrentsize'] ) ) { 

		$fsize = "style='font-size:".$_COOKIE['bovpcurrentsize']."px'";

	} else {

		$fsize = "style='font-size:".$bovp_sets->fsize."px'";

	}

	$bovp_content .= "<!-- book: $fsize -->";

	if( function_exists( 'bovp_show_resume_book' ) ) { $bovp_content .=  bovp_show_resume_book(); }

	$bovp_content .= "<ul class='bovp_ul'>";

	foreach( $bovp_sets->results as $result ) {

		if ( isset( $bovp_sets->tag ) ) { $result->text = bovp_tagger( $result->text ); }

		$bovp_content .= "<li class='bovp_text' $fsize ><span class='bovp_cap_ref'>" . $result->vs . "</span>" . $result->text . "</li>";
	}

	$bovp_content .= "</ul>";
	$bovp_content .= "</article>";

	if( $bovp_sets->lastpage > 1 ) $bovp_content .= bovp_caps_from_curr_book();	

	$bovp_content .= bovp_show_footer();

	return $bovp_content;

}

/**
 * Return Search Results
 *
 * @since 1.5.3
 *
 * @return string
 */

function bovp_show_search() { 

	global $bovp_sets;
	global $wpdb;

	$results = $wpdb->get_results( $bovp_sets->query );

	$bovp_sets->total_results =  $wpdb->num_rows;

	if( $bovp_sets->total_results <= $bovp_sets->itpp ) {

		$bovp_sets->results = $results;

	} else {

		if( !isset( $bovp_sets->params['bovp_cpg'] ) ) $bovp_sets->params['bovp_cpg'] = 1; 
		$bovp_sets->lastpage = ceil( $bovp_sets->total_results/$bovp_sets->itpp );
		$bovp_sets->start =  ( $bovp_sets->params['bovp_cpg'] > 1 ) ? ( $bovp_sets->params['bovp_cpg'] - 1 ) * $bovp_sets->itpp : 1;
		$bovp_sets->query .= '  LIMIT ' . $bovp_sets->start . ", " . $bovp_sets->itpp;
		$bovp_sets->results = $wpdb->get_results( $bovp_sets->query );

	}

	$bovp_search  = "<header class='bovp_header'>";
	$bovp_search .= "<title>" . __( 'Results', 'bovp' ) . " &raquo; " . str_replace( '-', ' ', $bovp_sets->params['bovp_search_slug'] ) . "</title>";
	$bovp_search .= "</header>";

	$bovp_search .= "<article class='bovp_content'>";
	

	$bovp_search .= bovp_font_size_select();

	if( isset( $_COOKIE['bovpcurrentsize'] ) ) { 

		$fsize = "style='font-size:".$_COOKIE['bovpcurrentsize']."px'";

	} else {

		$fsize = "style='font-size:".$bovp_sets->fsize."px'";

	}

	$bovp_search .= "<!-- search: $fsize -->";

	$bovp_search .= "<ul class='bovp_ul'>";

	foreach( $bovp_sets->results as $result ) {

		$book_name = $bovp_sets->books_info[$result->book]['slug_name'];

		$bovp_type = $bovp_sets->slug_book;

		$link_params = array(

			'bovp_type'=>$bovp_type, 
			'bovp_book_name'=>$book_name,
			'bovp_cp'=>$result->cp,
			'bovp_vs'=>$result->vs

			);

		$link = bovp_create_link( $link_params );


		$bovp_search .= "<li class='bovp_text' $fsize><span class='bovp_cap_ref'><a href='$link'>$book_name $result->cp:$result->vs</span></a>$result->text</li>";
	}

	$bovp_search .= "</ul>";
	$bovp_search .= "</article>";
	if( $bovp_sets->lastpage > 1 ) $bovp_search .= bovp_show_navigation();	
	$bovp_search .= bovp_show_footer();

	return $bovp_search;

}

/**
 * Return Search in a Specific Book
 *
 * @since 1.5.4
 *
 * @return string
 */

function bovp_show_search_in() { return 'Show search in book '; }

/**
 * Return Favorites Verses
 *
 * @since 1.5.4
 *
 * @return string
 */

function bovp_show_favorites() { 

	if( isset( $_COOKIE['bovp_favorites'] ) ) {

		return 'Show favorites verses ' . $_COOKIE['bovp_favorites']; 

	} else { return 'Favorites verses not set'; }
}


/**
 * Show index in the initial page.
 *
 * @since 1.5.1
 *
 * @version 1.5.3
 *
 * @return string
 *
 */

function bovp_show_index() {

	global $wpdb;
	global $bovp_sets;

	$array_books[] = array( 'name' => __('The Pentateuch','bovp'),'itens' => 5 );
	$array_books[] = array( 'name' => __('The Historical books','bovp'),'itens' => 12 );
	$array_books[] = array( 'name' => __('The Poetic and Wisdom writings','bovp'),'itens' => 5 );
	$array_books[] = array( 'name' => __('The Major Prophets','bovp'),'itens' => 5 );
	$array_books[] = array( 'name' => __('The Minor Prophets','bovp'),'itens' => 12 );
	$array_books[] = array( 'name' => __('God Spell','bovp'),'itens' => 4 );
	$array_books[] = array( 'name' => __('Acts','bovp'),'itens' => 1 );
	$array_books[] = array( 'name' => __('Paulines Epistles','bovp'),'itens' => 13 );
	$array_books[] = array( 'name' => __('General Epistles','bovp'),'itens' => 8 );
	$array_books[] = array( 'name' => __('Prophecy','bovp'),'itens' => 1 );

	$bovp_sets->book_div = $array_books;

	$books_groups = '';
	$start = 1;
	$end = 0;

	$books_groups .= "<ul>";

	foreach ( $bovp_sets->book_div as $key => $value ) {

		$end += $value['itens'];
		$books_groups .= "<li class='li_books'>";
		$books_groups .= '<label>' . $value['name'] . '</label><ul>';

		for ($i=$start; $i <= $end ; $i++) { 

			if( isset( $bovp_sets->furl ) ) {

				$link = $bovp_sets->url . '/' . $bovp_sets->slug_page ."/". $bovp_sets->slug_book ."/" . $bovp_sets->books_info[$i]['slug_name'];	

			} else {

				$link = $bovp_sets->url . "?page_id=" . $bovp_sets->page . '&bovp_type=' . $bovp_sets->slug_book . "&bovp_book=" . $i;

			}

			$books_groups .= "<li class='li_book'><a href='$link'>" . $bovp_sets->books_info[$i]['name'] . '</a></li>';
		}

		$books_groups .= "</ul>";
		$books_groups .= "</li>";
		$start += $value['itens'];

	}

	$books_groups .= '</ul>';

	$bovp_index  = "<header class='bovp_header bovp_clear'>";
	$bovp_index .= "<title>" . __( 'Books of Bible', 'bovp' ) . "</title>";
	$bovp_index .= "</header>";
	$bovp_index .= "<article class='bovp_index bovp_clear'>";
	$bovp_index .= $books_groups;
	$bovp_index .= "</ul>";
	$bovp_index .= "</article>";
	$bovp_index .= bovp_show_footer();

	return $bovp_index;
		
}


/**
 * Bovp Information footer.
 *
 * @since 1.5.3
 *
 * @return string
 *
 */

function bovp_show_footer() {

	global $bovp_sets;

	if( !isset( $bovp_sets->web117_link ) ) $bovp_sets->web117_link = 'http://web117.com.br/';
	if( !isset( $bovp_sets->bovp_link ) ) $bovp_sets->bovp_link = 'http://vivendoapalavra.org/';

	global $bovp_sets;

	$content  = "<footer class='bovp_footer bovp_clear'>";

	$content .= "<smaal><a target='_blank' href='" . $bovp_sets->bovp_link . "'>Vivendo a Palavra</a>" . __('by', 'bovp') . "<a target='_blank' href='" . $bovp_sets->web117_link . "'>Web117</a>" . "</smaal>";

	$content .= "</footer>";

	return $content;

}

/**
 * Open bible wrap cntent.
 *
 * @since 1.5.3
 *
 * @return string
 *
 */

function bovp_start_section() {

	global $bovp_sets;

	$class = "class='bovp_clear ";

	$class .= isset( $bovp_sets->class ) ? $class = "$bovp_sets->class'" : "'";

	return "<section id='bovp_main' $class >";

}

/**
 * Close bible wrap cntent.
 *
 * @since 1.5.3
 *
 * @return string
 */

function bovp_end_section() {

	return "</section>";
	
}

/**
 * Mark the tags in posts.
 *
 * @since 1.5.1
 *
 * @return string
 */

function bovp_tagger( $replace ) {

	global $wpdb;

	$array_replace = array();

	$query_tags = "SELECT DISTINCT $wpdb->terms.* FROM $wpdb->terms ";

	$tags = $wpdb->get_results( $query_tags, ARRAY_A );

		if( $tags ) {

			foreach( $tags as $tag ) {

				$tag_id = "tag_id_".trim( $tag['term_id'] );
				$tag_name = trim( $tag['name'] );
				$tag_slug = trim( $tag['slug'] );
				
				$replace = preg_replace( "/$tag_name/i", "#$tag_id#", $replace );

				$link_url = get_option( 'siteurl' ) . "?tag=" . $tag_slug;
				$link = "<a class='bovp_tag_link' href='$link_url'>$tag_name</a>";
				$array_replace["#$tag_id#"] = $link;
			}

			$replace = str_replace( array_keys($array_replace), $array_replace, $replace );

			return $replace;

		} else {return false;}

	}


/**
 * Highlight Matchs in Bible Search
 *
 * @since 1.4
 *
 * @return highlighted matchs
 */

function bovp_highlight_matchs($matches) {

	return '<font class="bovp_text_found">'.$matches[0].'</font>';

}

function bovp_list_books( $div_id ) {

	global $bovp_sets;

	$list_books = "<div id='$div_id'>";

    foreach ( $bovp_sets->books_info as $key => $value ) {

    	if( isset( $bovp_sets->furl ) ) {

			if( isset( $bovp_sets->params['bovp_book'] ) AND $bovp_sets->params['bovp_book'] == $value['slug_name'] ) { $current_item = "class='current_item'"; } else { $current_item = ""; }

	    	$link = $bovp_sets->url . '/' . $bovp_sets->slug_page . '/' . $bovp_sets->slug_book . '/' . $value['slug_name'];

	    	$list_books .= "<a $current_item href='" . $link . "' title='" . $value['name'] . "'>" . $value['name'] . "</a>";

    	} else {

    		if( isset( $_REQUEST['bovp_book'] ) AND $_REQUEST['bovp_book'] == $key ) { $current_item = "class='current_item'"; } else { $current_item = ""; }

	    	$link = $bovp_sets->url . '?page_id=' . $bovp_sets->page . '&bovp_type=' . $bovp_sets->slug_book . '&bovp_' . $bovp_sets->slug_book . '=' . $key;

	    	$list_books .= "<a $current_item href='" . $link . "' title='" . $value['name'] . "'>" . $value['name'] . "</a>";

    	} 	

    }
    
    $list_books .= "</div>";

    return $list_books;

}


function bovp_book_select( $id_form ) {

	global $bovp_sets;

	$select_books = "<option value = '-1' >" . __('Books', 'bovp') ."</option>";

	    if( isset( $bovp_sets->furl ) ) {

    		foreach ( $bovp_sets->books_info as $key => $value ) {

	    		$select_books .= "<option value = '".$value['slug_name']."' >" . $value['name'] ."</option>";

	    	}

	    	$page = $bovp_sets->slug_page;
	    	$id_select = 'bovp_furl_index_select';

    	} else {

    		foreach ( $bovp_sets->books_info as $key => $value ) {

	    		$select_books .= "<option value = '".$key."' >" . $value['name'] ."</option>";

	    	}

	    	
	    	$page = $bovp_sets->page;
	    	$id_select = 'bovp_index_select';

    	} 	

    $return  = "<form id='$id_form' method='post' action='" . $bovp_sets->url . "' name='bovp_form_index' class='bovp_clear'>";
	$return .= "<input id='bovp_page' name='page_id' type='hidden' value='" . $page . "'/>";
	$return .= "<input id='bovp_type' name='bovp_type' type='hidden' value='" . $bovp_sets->slug_book . "'/>";
	$return .= "<select id='$id_select' name='bovp_book'>";
	$return .= $select_books;
	$return .= "</select>";
	$return .= "</form>";

    echo $return;

}

function bovp_caps_from_curr_book() {

	global $bovp_sets;

	$num_caps = $bovp_sets->books_info[$bovp_sets->current_book]['pages'];

	if( isset( $bovp_sets->furl) ) { 

		$slug_name = $bovp_sets->books_info[$bovp_sets->current_book]['slug_name'];
		$base_link = $bovp_sets->url . $bovp_sets->slug_page . '/' . $slug_name . '/';

	} else {

		$book = $bovp_sets->params['bovp_book'];
		$base_link = $bovp_sets->url . '?page_id=' . $bovp_sets->page. '&bovp_type=book' . '&bovp_book=' . $book . '&bovp_cp=';

	}

	$link_caps  = "<div class='current_cap down_arrow' style='position: relative;'>Capítulo ". $bovp_sets->params['bovp_cp'] ."<span class='down_arrow'></span></div>";

	$link_caps  .= "<div id='choose_cap' class='bovp_clear closed_caps' style='display: none;'>";

	for( $i = 1; $i <= $num_caps; $i++ ) {

		if( $i == $bovp_sets->params['bovp_cp'] ) { 

			$link_caps .= "<span>" . $i . "</span>";
		
		} else {

			$link_caps .= "<a href='" . $base_link .  $i ."'>" . $i . "</a>";
		}
	
	}

	$link_caps .= "</div>";

	return "<div id='bovp_toggle_pagination'>" . $link_caps . "</div>";
}

/***************************************************************************/
/*********************** DATA MANIPULATION FUNCTIONS ***********************/
/***************************************************************************/


/**
 * Invert the array books, updating key to slug_name 
 *
 * @since 1.5.3
 *
 * @return array
 *
 */

function bovp_invert_array_books() {

	global $bovp_sets;

	$invert = array();

	foreach ($bovp_sets->books_info as $key => $value) {

		$new_key = $value['slug_name'];
		$value['book_id'] = $key;

		$invert[$new_key] = $value;
	}

	return $invert;

}


/**
 * Create array of Bible books info.
 *
 * @since 1.5.3
 *
 * @return array
 */

function bovp_set_array_books() {

	global $bovp_sets;
	global $wpdb;

	$query_books = "SELECT * FROM `$bovp_sets->table` WHERE `book` = 0 AND cp != 0 AND vs != 0";
	$books = $wpdb->get_results ($query_books , ARRAY_A);

	foreach($books as $book) {

		$nbook = $book['cp'];

		$data = array(
			'name' => $book['text'],
			'slug_name' => str_replace(' ', '-', strtolower(remove_accents($book['text']))),
			'pages' => $book['vs']
			);

		$array_books[$nbook] = $data;

	}

	return $array_books;

}

/**
 * return info selected from specified book. 
 *
 * @since 1.5.3
 *
 * @return string
 */

function bovp_get_book_info( $book, $info ) {

	global $bovp_sets;

	if( is_int( $book ) ) {

		return $bovp_sets->books_info[$book][$info];

	} else {

		$slug_book = sanitize_title( $book, 'slug' );

		$invert = array();

		foreach ($bovp_sets->books_info as $key => $value) {

			$new_key = $value['slug_name'];
			$value['book_id'] = $key;

			$invert[$new_key] = $value;
		}

		return $invert[$slug_book][$info];


	}

}

function bovp_short_code( $atts ) {

		/* [bovp_vd ref='genesis 2:2'] */

		global $wpdb;
		global $bovp_sets;

		$atts = shortcode_atts( array('ref' => false), $atts, 'bovp_vd' );

		if( $atts['ref'] != false ) { 

			$explode = preg_split( '/:|,| /', $atts['ref'] );

			$book_name = bovp_get_book_info( $explode[0],'name' );

			$book = bovp_get_book_info( $explode[0],'book_id' );

			$slug_book = $bovp_sets->books_info[$book]['slug_name'];
			$cp = $explode[1];
			$vs = $explode[2];

			$bovp_user_settings = get_option('bovp_user_settings');
			$bovp_type = $bovp_user_settings['slug_book'];

			$link_params = array(

			'bovp_type'=>$bovp_type, 
			'bovp_book'=>$book,
			'bovp_book_name'=>$slug_book,
			'bovp_cp'=>$cp,
			'bovp_vs'=>$vs

			);

			$link = bovp_create_link( $link_params );

			$ref = "($book_name $cp:$vs)";

			$sql = "SELECT CONCAT(`vs`,' ',`text`) AS 'text' FROM ". $bovp_sets->table ." WHERE `book` = $book AND `cp`= $cp ";

		 	if (preg_match('/-/',$vs)) {

				$between = explode('-', $vs);
				$start = $between[0];
				$end = $between[1];

				$sql .= "AND `vs` BETWEEN ". $start ." AND ". $end;				
 
			} else {

				$sql .= "AND `vs` = " . $vs;

			}

			$results = $wpdb->get_results ( $sql, ARRAY_A );

			if( $results ) {

				$text = '';

				foreach ($results as $verse) {

					$text .= $verse['text'] . ' ';
				}


			} else { 

				$return = false; 
			}


		} else {

			$verse_array = bovp_show_verse( false );
			extract( $verse_array );		
		}


	$return = "<div class='bovp_reference'>$text<span class='bovp_reference_link'><a href='$link'>$ref</a></span></div>";

	if( $return == false ) { $return = __('Error: Invalid format','bovp'); }

	return $return;
		

	}

	


function bovp_create_link( $params ) {

	global $bovp_sets;

	extract( $params );

	if( isset( $bovp_sets->furl ) ) {

		$base_link = $bovp_sets->url . $bovp_sets->slug_page;

		//if( $bovp_type == $bovp_sets->slug_book ) {
			
			$base_link .= ( isset( $bovp_type ) ) ? '/'.$bovp_type : "";
			$base_link .= ( isset( $bovp_book_name ) ) ? '/'.$bovp_book_name : "";
			$base_link .= ( isset( $bovp_cp ) ) ? '/'.$bovp_cp : "";
			$base_link .= ( isset( $bovp_vs ) ) ? '/'.$bovp_vs : "";
			$base_link .= ( isset( $bovp_search ) ) ? '/'.$bovp_search : "";

		//} else {

			// Gera link busca amigavel
			//$base_link .= ( isset( $bovp_type ) ) ? '/'.$bovp_type : "";

		//}

	} else {

		$base_link = $bovp_sets->url . "?page_id=" . $bovp_sets->page;

		if( $bovp_type == $bovp_sets->slug_book ) {

			$base_link .= ( isset( $bovp_type ) ) ? '&bovp_type='.$bovp_type : "";
			$base_link .= ( isset( $bovp_book ) ) ? '&bovp_book='.$bovp_book : "";
			$base_link .= ( isset( $bovp_cp ) ) ? '&bovp_cp='.$bovp_cp : "";
			$base_link .= ( isset( $bovp_vs ) ) ? '&bovp_vs='.$bovp_vs : "";
			$base_link .= ( isset( $bovp_search ) ) ? '&bovp_search='.$bovp_search : "";

		} else {

			// Gera link busca

		}

	}


	return $base_link;

}



/* TEST AND MESSAGE FUNCTIONS */

/**
 * Show bovp settings. For BOVP variable test only.
 *
 * @since 1.5.3
 *
 * @return void
 */

function bovp_show_sets() {

	global $bovp_sets;

	$show_sets = "";

	foreach($bovp_sets as $key => $vaule) {

		$show_sets .=  $key .'<br>';
		$show_sets .= '<pre>'. print_r($bovp_sets->$key, true) . '</pre>';

	}

	return $show_sets;
}

/**
 * Average the time for Bible query.
 *
 * @version 1.5.3
 *
 * @return string
 *
 */

function count_execution_time( $time ) {

	return  number_format( ( microtime( true ) - $time ), 6 ) . ' s';

}


/**
 * Message System for Bible events.
 *
 * @since 1.5.3
 *
 * @return void
 */

function bovp_adm_notice() {

	global $bovp_sets;

	$msg = $bovp_sets->message;

	$class = $msg[0];

	echo "<div class='$class' style='margin: 0px;'><p>" . $msg[1] . "</p></div>"; 

	bovp_unset_option( 'message' );

}


/* ADM PANEL FUNCTIONS */

/**
 * Insert stylesheet for bovp adm page .
 *
 * @since 1.4
 *
 * @return void
 */

function bovp_adm_styles() {

	global $bovp_sets;

	wp_enqueue_style( "bovp_adm_styles", $bovp_sets->plugin_url ."/includes/bovp_adm_style.css");

}

/**
 * Return all existing pages to polpulate select.
 *
 * @since 1.5.3
 *
 * @return string
 */

function bovp_choose_page(){

	global $wpdb;
	global $bovp_sets;

	$options = "<option value='0'>".  __('Select a page','bovp') ."</option>";

	$query = "SELECT id, post_title FROM " . $wpdb->prefix . "posts WHERE post_type = 'page' AND post_status='publish'";
	$pages = $wpdb->get_results ( $query, ARRAY_A );

	foreach ( $pages as $page ) {

		$page['id'] == $bovp_sets->page ? $selected = "selected='selected' " : $selected = '';  

      	$options .= "<option value='". $page['id'] ."' $selected>". $page['post_title'] ."</option>";

    }

    return $options;

}


/* INTALATION FUNCTIONS */

/**
 * Update rewrite rules.
 *
 * @since 1.5.1
 *
 * @return void
 */


function bovp_update_rules() {
	
	global $wp_rewrite;

   	$wp_rewrite->flush_rules();
}

/**
 * ADD new rewrite rules for the Bible.
 *
 * @since 1.5.1
 *
 * @version 1.5.3
 *
 * @return void
 */


function bovp_rewrite_rules($rules) {

	global $bovp_sets;
	
	$slug_bovp = $bovp_sets->slug_page;

	$newrules = array();

	$newrules["($slug_bovp)/(.*?)$"] = 'index.php?pagename=$matches[1]&bovp_set_params=$matches[2]';
	$newrules["(biblia-no-seu-site)/(.*?)$"] = 'index.php?pagename=$matches[1]&bovp_set_params=$matches[2]';

	return $newrules + $rules;

}


/**
 * Drop Bible table.
 *
 * @since 1.5.3
 *
 * @return true if create / false on error
 */

function bovp_drop_table( $table ){

	global $wpdb;

	$bovp_versions = bovp_translate_info();

	$return = false;

	$tables = '';

			$bovp_table_exist = bovp_table_exist($table);

			if($bovp_table_exist) {

				$delete_table = $wpdb->query("DROP TABLE IF EXISTS `" . $table . "`");

				if($delete_table) $return = true;

			} 


	return $return;

}


/**
 * Instalation bovp Bible table.
 *
 * @since 1.5.3
 *
 * @return (int) true if installed / false if not installed
 */

function bovp_install_table( $table_name, $file_path ) {

	global $wpdb;
	global $bovp_sets;

	include  BOVP_PATH .'includes/bovp_csv_import.class.php';

        $insert = new bovp_csv_import;
        $insert->setFileName( $file_path );
        $insert->setTable( $table_name );
        $inserted = $insert->insertFile();

        if( $inserted ) { return true; } else { return false; }	
}



/* PLUGIN INTERNATIONALIZATION */


/**
 * Plugin internationalization.
 *
 * @since 1.5.3
 *
 * @return void
 */

function bovp_load_text_domain(){ 	

  load_plugin_textdomain( 'bovp', false,  BOVP_FOLDER . '/languages/' );  

}





#Generate new Daily Verse


function bovp_new_verse( $random = false ) {

	global $bovp_sets;
	global $wpdb;

	if( !isset( $bovp_sets->table ) ) { 

		return false; 

	} elseif( !isset( $bovp_sets->bovp_daily_verse['date'] ) ) {

		$bovp_sets->bovp_daily_verse['date'] = "01/01/1900";

	}

	$valid_verse = ( date( 'd/m/Y',time() ) === $bovp_sets->bovp_daily_verse['date'] ) ? true : false ;

	if( !$valid_verse OR $random ) {

		switch( $bovp_sets->vsource ) {

		case 0: $params = ""; break;
		case 1: $params = "WHERE `book` =< 39"; break;
		case 2: $params = "WHERE `book` >= 40"; break;
		case 3: $params = "WHERE `book` = 19"; break;

		}

		$sql = "SELECT * FROM ". $bovp_sets->table ." $params ORDER BY rand() LIMIT 1";

		$new_verse = $wpdb->get_row( $sql );

		if( $new_verse ){

			$start_verse = $new_verse->vs;
			$end_verse = $new_verse->vs ;
			$id_verse = $new_verse->id;

			$daily_verse = $new_verse->text;

			while ( strripos( ".?!", substr( trim( $daily_verse ), -1 ) ) === FALSE ) { 

				$id_verse ++;
				$add_verse = $wpdb->get_row( "SELECT * FROM ". $bovp_sets->table ." WHERE id = '". $id_verse ."' LIMIT 1" );
				$daily_verse .= ' '.$add_verse->text;
				$end_verse = $add_verse->vs;

			} 

			$vs_ref = ( $start_verse == $end_verse ) ? $start_verse : $start_verse .'-'.$end_verse;

			$set_verse['id'] = $new_verse->id;
			$set_verse['date'] = date('d/m/Y',time()); 
			$set_verse['book'] = $new_verse->book;
			$set_verse['cp'] = $new_verse->cp;
			$set_verse['vs'] = $new_verse->vs;
			$set_verse['book_name'] = $bovp_sets->books_info[$new_verse->book]['name'];
			$set_verse['slug_name'] = $bovp_sets->books_info[$new_verse->book]['slug_name'];
			$set_verse['text'] = $daily_verse;

			if( !$random ) bovp_set_option( 'bovp_daily_verse', $set_verse );

		}

	}
				
	if ( !$random ) {

		return $bovp_sets->bovp_daily_verse;

	} else {

		return $set_verse;
	}	

}


function bovp_show_verse( $random = false ) {

	$settings = get_option('bovp_settings');

	if( !isset( $settings['table'] ) ) { return false; exit(); }

	global $bovp_sets;

	$return = array();	

	$show_verse = bovp_new_verse( $random );

	extract($show_verse);

	if( isset( $bovp_sets->furl ) ) {

		$return['link'] = sprintf("%s/%s/%s/%s/%d/%d", $bovp_sets->url, $bovp_sets->slug_page, __( 'book', 'bovp' ) , $slug_name, $cp, $vs );

	} else {

		$return['link'] = sprintf("%s?page_id=%d&bovp_type=%s&bovp_book=%d&bovp_cp=%d&bovp_vs=%d", $bovp_sets->url, $bovp_sets->page,  __( 'book', 'bovp' ) , $book, $cp, $vs);
	}

	$return['ref'] = "(". $book_name . " " . $cp . $bovp_sets->sep . $vs .")";

	$return['text'] = $text;

	return $return;

}


/**
 * Bovp init Widgets
 *
 * @since 1.5.3
 *
 * @return void
 */

function bovp_widgets_init() {

	register_widget('bovp_widget_verse');
	register_widget('bovp_widget_index');

}

/**
 * Recovery slug name for the Bible page
 *
 * @since 1.5.1
 *
 * @return string
 */


function bovp_slug_name( $post_id=false ){
	
	if( !$post_id ){ $post_id = $post->ID; }
	
	$post_data = get_post( $post_id, ARRAY_A );

	return $post_data['post_name'];

}

/**
 * Create BOVP admin menu
 *
 * @since 1.5.1
 *
 * @return void
 */

function bovp_admin_menu(){	

	global $bovp_sets;

	$option = isset( $bovp_sets->icon ) ? $bovp_sets->icon : '';

	add_menu_page(__('Online Bible','bovp'),__('Online Bible','bovp'),'manage_options','bovp_about_menu','bovp_init_page',$option); 
	add_submenu_page('bovp_about_menu',__('About','bovp'),__('About','bovp'),'manage_options','bovp_about_menu','bovp_init_page' );
	add_submenu_page('bovp_about_menu',__('Settings','bovp'),__('Settings','bovp'),'manage_options','bovp_setting_menu','bovp_setting_page' );

}

/**
 * Create user variables
 *
 * @since 1.5.1
 *
 * @return void
 */

function bovp_user_settings() {	

	register_setting( 'bovp_options', 'bovp_user_settings');
}



/**
 * Manipulate bovp options
 *
 * @since 1.5.3
 *
 * @param $opt - option to manipulate
 * @param $act - action to execute
 *
 * @return false on error
 */

function bovp_unset_option($opt) {

	$bovp_settings = get_option( 'bovp_settings' );
	$bovp_user_settings = get_option( 'bovp_user_settings' );

	if( in_array( $opt, array_keys( $bovp_user_settings ) ) ) {

		unset( $bovp_user_settings[$opt] );
		update_option( 'bovp_user_settings', $bovp_user_settings );
		return true;

	} else {

		unset( $bovp_settings[$opt] );
		update_option( 'bovp_settings', $bovp_settings );
		return true;

	}

}

function bovp_set_option($opt, $value) {

	global $bovp_sets;

	$bovp_settings = get_option( 'bovp_settings' );
	$bovp_user_settings = get_option( 'bovp_user_settings' );

	if( in_array( $opt, array_keys( $bovp_user_settings ) ) ) {

		$bovp_user_settings[$opt] = $value;
		update_option( 'bovp_user_settings', $bovp_user_settings );
		$bovp_sets->$opt = $value;
		return true;

	} else {

		$bovp_settings[$opt] = $value;
		update_option( 'bovp_settings', $bovp_settings );
		$bovp_sets->$opt = $value;
		return true;

	} 


}


function bovp_form_donation() {

	global $bovp_sets;

	echo "<div class='bovp_box'>";

	echo "<div class='bovp_info'>";
	echo "<span class='dashicons dashicons-heart icon_title_bovp'></span> " . __('Donation','bovp');
	echo "</div>";

	echo "<h3 class='bovp_adm_h3'>" .  __('Consider a Donation','bovp') . "</h3>";
	echo "<p class='bovp_adm_p'>";
	_e("If you use Online Bible plugin and want to contribute to the project's maintenance, you can use the link below to make a donation.",'bovp');
	echo "</p>";

	echo "<form action='https://pagseguro.uol.com.br/checkout/v2/donation.html' method='post' style='float:left;' target='_blank'>";
	echo "<input type='hidden' name='receiverEmail' value='andre@vivendoapalavra.org' />";
	echo "<input type='hidden' name='currency' value='BRL' />";
	echo "<input type='image' src='https://p.simg.uol.com.br/out/pagseguro/i/botoes/doacoes/209x48-doar-azul-assina.gif' name='submit' alt='Doe com PagSeguro - é rápido, grátis e seguro!' />";
	echo "</form>";

	echo "<form action='https://www.paypal.com/cgi-bin/webscr' method='post'  target='_blank'>";
	echo "<input type='hidden' name='cmd' value='_s-xclick'>";
	echo "<input type='hidden' name='hosted_button_id' value='9KV25MLWLPKQN'>";
	echo "<input type='image' src='" . $bovp_sets->plugin_url . "/img/paypal.jpg' border='0' name='submit' alt='PayPal – The safer, easier way to pay online.'>";
	echo "<img alt='' border='0' src='https://www.paypalobjects.com/pt_BR/i/scr/pixel.gif' width='1' height='1'>";
	echo "</form>";

	echo "</div>";

}


function bovp_setting_page() {

	global $wpdb;
	global $bovp_sets;

	$img_folder = BOVP_PLUGIN_URL . 'themes/img/';


?>

<div class="bovp_wrap">

<div class="bovp_settings_header">
<h2 class="bovp_h2"><?php _e('OnlineBible','bovp');  ?></h2>
<span >By VivendoaPalavra.org</span>
</div>

<?php if( isset( $bovp_sets->message ) ) { bovp_adm_notice(); } ?>

<div class="bovp_box">

	<div class="bovp_info">
	<span class="dashicons dashicons-admin-settings icon_title_bovp"></span> <?php _e('Settings','bovp');?>
	</div>

	<h3 class="bovp_adm_h3"><?php _e('Edit Settings','bovp');?></h3>
    
	<form method="post" action="options.php">

	      <?php settings_fields( 'bovp_options' ); ?>

			<div class="bovp_item-setting">
				
				<span><?php _e('Choose and Install the Bible version','bovp');?></span>
							  		        
				        <select name="bovp_user_settings[translate]" id="bovp_install">

					        <?php

					        

					        if ( !$bovp_sets->active_translate ) {

					            echo "<option value='-1' selected>" . __('Not Installed','bovp') . "</option>";

					        } else {

					          	echo "<option value='-1'>" . __('Not Installed','bovp') . "</option>";

					        }
				        
				            foreach( $bovp_sets->translate_info as $name => $file ) {

								echo "<option value='" . $file . "'"; 

								if ( $bovp_sets->active_translate == $file ) {echo " selected";}

								echo ">" . $name . "</option>";

				            }

				            echo "</select>";

				            ?>

				<?php  if(isset($bovp_message)) echo $bovp_message; ?>

			</div>
	         
	        <div class="bovp_item-setting">
	          <span><?php _e('Page where the online Bible will be displayed','bovp');?></span>
	          <select name="bovp_user_settings[page]">
	          <?php echo bovp_choose_page(); ?>
	          </select>
	        </div>

	        <div class="bovp_item-setting">

	          <span><?php _e('Source of the daily verse','bovp');?></span>
	      	     
	             <select name="bovp_user_settings[vsource]">
	                <option value="0" <?php if ($bovp_sets->vsource==0) {echo 'selected';} ?> ><?php _e('All the Bible','bovp') ?></option>
	                <option value="1" <?php if ($bovp_sets->vsource==1) {echo 'selected';} ?> ><?php _e('Old Testament','bovp') ?></option>
	                <option value="2" <?php if ($bovp_sets->vsource==2) {echo 'selected';} ?> ><?php _e('New Testament','bovp') ?></option>
	                <option value="3" <?php if ($bovp_sets->vsource==3) {echo 'selected';} ?> ><?php _e('The Book of Psalms','bovp') ?></option>
	              </select>
	        </div>
	      	
	        <div class="bovp_item-setting">

	          <span><?php _e('Search results to be displayed per page','bovp');?></span>
	      
	        	<select name="bovp_user_settings[itpp]">
	                <option value="20" <?php if ($bovp_sets->itpp=="20") {echo 'selected';} ?> >20</option>
	                <option value="30" <?php if ($bovp_sets->itpp=="30") {echo 'selected';} ?> >30</option>
	                <option value="40" <?php if ($bovp_sets->itpp=="40") {echo 'selected';} ?> >40</option>	            
	                <option value="50" <?php if ($bovp_sets->itpp=="50") {echo 'selected';} ?> >50</option>	            
	            </select>

	        </div>

	        <div class="bovp_item-setting">
	          <span><?php _e('Chapter separator','bovp');?></span>
	            <select name="bovp_user_settings[sep]">
	                <option value=":" <?php if ($bovp_sets->sep==":") {echo 'selected';} ?> >:</option>
	                <option value="," <?php if ($bovp_sets->sep==",") {echo 'selected';} ?> >,</option>
	            </select>

	        </div>

	        <div class="bovp_item-setting">

	         <span><?php _e('Show on front:','bovp');?></span>
	            <select name="bovp_user_settings[on_front]">
	                <option value='<?php _e('index', 'bovp'); ?>' <?php if ($bovp_sets->on_front=="index") {echo 'selected';} ?> ><?php _e('Show index','bovp') ?></option>
	                <option value='<?php _e('book', 'bovp'); ?>' <?php if ($bovp_sets->on_front=="book") {echo 'selected';} ?> ><?php _e('Show Book of Genesis','bovp') ?></option>
	                <!-- <option value='<?php _e('favorites', 'bovp'); ?>' <?php if ($bovp_sets->on_front=="favorites") {echo 'selected';} ?> ><?php _e('Show favorites verses','bovp') ?></option> -->
	            </select>
	        </div>

	        <div class="bovp_item-setting">
		        <span><?php _e('Default font size:','bovp');?></span>
	            <select name="bovp_user_settings[fsize]">
	            	<option value='12' <?php if ($bovp_sets->fsize=="12") {echo 'selected';} ?> >12px</option>
	                <option value='14' <?php if ($bovp_sets->fsize=="14") {echo 'selected';} ?> >14px</option>
	                <option value='16' <?php if ($bovp_sets->fsize=="16") {echo 'selected';} ?> >16px</option>
	                <option value='18' <?php if ($bovp_sets->fsize=="18") {echo 'selected';} ?> >18px</option> 
	                <option value='20' <?php if ($bovp_sets->fsize=="20") {echo 'selected';} ?> >20px</option>  
	                <option value='22' <?php if ($bovp_sets->fsize=="22") {echo 'selected';} ?> >22px</option>        	
	            </select>
	        </div>

	        <div class="bovp_item-setting">
	          	<span><?php _e('Activate Resources:','bovp');?></span>
	          	<input type="checkbox" name="bovp_user_settings[tag]" <?php if (isset($bovp_sets->tag)) {echo ' checked="checked"';} ?> /> <?php _e('activate tagger - Increases page load time','bovp') ?><br>
	        </div>

	        <div class="bovp_item-setting">
	          	<span><?php _e('Choose theme','bovp');?></span>
	            <select name="bovp_user_settings[theme]">
	                <option value="default" <?php if ($bovp_sets->theme=="default") {echo 'selected';} ?> >Default</option>
	                <!-- <option value="ichthys" <?php if ($bovp_sets->theme=="ichthys") {echo 'selected';} ?> >Ichthys</option>
	                <option value="pb" <?php if ($bovp_sets->theme=="pb") {echo 'selected';} ?> >P&B</option> -->
	            </select>

	        </div>

	        <div class="bovp_item-setting clearfix">

	          <span><?php _e('URL slugs','bovp');?></span>

				<div class="slugs_label" style="width: 200px;float: left;">
		          <label><?php _e('book', 'bovp'); ?>
		          <input type='text' name="bovp_user_settings[slug_book]" value ="<?php if( isset( $bovp_sets->slug_book ) ) {echo $bovp_sets->slug_book;} else { _e('book', 'bovp'); } ?>" />
		          </label>
		        </div>
		        <div class="slugs_label" style="width: 200px;float: left;">  
				  <label><?php _e('search', 'bovp'); ?>
		          <input type='text' name="bovp_user_settings[slug_search]" value ="<?php if( isset( $bovp_sets->slug_search ) ) {echo $bovp_sets->slug_search;} else { _e('search', 'bovp'); } ?>" />
		          </label>
		        </div>
		        <div class="slugs_label" style="width: 200px;float: left;">
				  <label><?php _e('search_in', 'bovp'); ?>          
				  <input type='text' name="bovp_user_settings[slug_search_in]" value ="<?php if( isset( $bovp_sets->slug_search_in ) ) {echo $bovp_sets->slug_search_in;} else { _e('search_in', 'bovp'); } ?>" />
		          </label>
				</div>
		        <div class="slugs_label" style="width: 200px;float: left;">
				  <label><?php _e('index', 'bovp'); ?>          
				  <input type='text' name="bovp_user_settings[slug_index]" value ="<?php if( isset( $bovp_sets->slug_index ) ) {echo $bovp_sets->slug_index;} else { _e('index', 'bovp'); } ?>" />
		          </label>
		        </div>
				<div class="slugs_label" style="width: 200px;float: left;">
				  <label><?php _e('favorites', 'bovp'); ?>          
				  <input type='text' name="bovp_user_settings[slug_favorites]" value ="<?php if( isset( $bovp_sets->slug_favorites ) ) {echo $bovp_sets->slug_favorites;} else { _e('favorites', 'bovp'); } ?>" />
				</div>

	        </div>

	        <?php if( isset( $bovp_sets->key_log_tester ) ) { echo "<p class='bovp_key_log'>". $bovp_sets->key_log_tester . "</p>"; } ?>

	          		
	      <p class="submit"><input type="submit" class="button-primary" value="<?php _e('Update Settings','bovp') ?>" /></p>

	</form>

</div>

<?php


}


function bovp_init_page() {

	global $bovp_sets;

	$img_folder = BOVP_PLUGIN_URL . 'themes/img/';

	?>

	<div class="bovp_wrap">

	<div class="bovp_settings_header">
	<h2 class="bovp_h2"><?php _e('OnlineBible','bovp');  ?></h2>
	<span >By VivendoaPalavra.org</span>
	</div>

	<?php bovp_form_donation(); ?>


	<div class="bovp_box">

		<div class="bovp_info">
		<span class="dashicons dashicons-warning icon_title_bovp"></span> <?php _e('Informations','bovp');?>
		</div>

		<h3 class="bovp_adm_h3"><?php _e('About','bovp');?></h3>

		<p><?php _e('Plugin for implementation of Bible Online in your Wordpress blog. With it, you can spread the Word of God and bless your website\'s users. The plugin allows to consult all of 66 books of the Holy Bible.','bovp'); ?></p>
		
		
		<ul><li><span class="dashicons dashicons-admin-users icon_bovp"></span><span class='bovp_items_adm'>Author:&nbsp;<a href="https://www.facebook.com/andrebrumsampaio">Andre Brum Sampaio</a></span>
		</li><li><span class="dashicons dashicons-admin-links icon_bovp"></span><span class='bovp_items_adm'><?php echo __('Author URI: ','bovp') . '&nbsp;<a href="http://www.vivendoapalavra.org/">http://www.vivendoapalavra.org/</a>' ?></span>
		</li><li><span class="dashicons dashicons-admin-appearance icon_bovp"></span><span class='bovp_items_adm'><?php echo __('Theme Designer: ','bovp'); ?>Lucas Tolle</span>
		</li><li><span class="dashicons dashicons-update icon_bovp"></span><span class='bovp_items_adm'><?php echo __('Version: ','bovp') . $bovp_sets->version; ?></span></li></ul>

		<h3 class="bovp_adm_h3"><?php _e('Bible Translates:','bovp'); ?></h3>

		<ul><li><span class="dashicons dashicons-book-alt icon_bovp"></span><span class='bovp_items_adm'><?php _e('English Bible.','bovp'); ?></span></li></ul>			

		<h3 class="bovp_adm_h3"><?php _e('Settings:','bovp'); ?></h3>

		<p><?php _e('In the <b><a href="?page=bovp_setting_menu">SETTINGS PAGE</a></b>, select desired version and then click to install. Wait the bible text installation complete and then choose the options (page, itens per page, theme, verse source).','bovp');?></p>

	</div>

	</div>

	<?php


}


#pagination
function bovp_show_navigation() {

	global $bovp_sets;

	$adjacents = 2;
	
	if( $bovp_sets->params['bovp_type']  == $bovp_sets->slug_search ) {

		$prev = $bovp_sets->params['bovp_cpg'] - 1; 
		$next = $bovp_sets->params['bovp_cpg'] + 1;

	} elseif ( $bovp_sets->params['bovp_type']  == $bovp_sets->slug_book ) {

		$prev = $bovp_sets->params['bovp_cp'] - 1; 
		$next = $bovp_sets->params['bovp_cp'] + 1;

	}

	$lpm1 = $bovp_sets->lastpage - 1;

	$navigation = "<nav class='bovp_clear'>";

			$link_params = array(

			'bovp_type'=>$bovp_sets->params['bovp_type'], 
			'bovp_book'=>$book,
			'bovp_book_name'=>$slug_book,
			'bovp_cp'=>$cp,
			'bovp_vs'=>$vs,
			'bovp_search'=>$bovp_sets->params['bovp_search_slug'], 

			);

			$link = bovp_create_link( $link_params );


	if( $bovp_sets->lastpage ){

		if($bovp_sets->params['bovp_cpg']){

			// PREV BUTTOM

			if($bovp_sets->params['bovp_cpg'] > 1)

					$navigation .= "<a href='$link/$prev' class='prev'>" . __('Previous','bovp') . "</a>";
			else
					$navigation .= "<span class='disabled'>" . __('Previous','bovp') . "</span>";

		}

		//PAGES

		if ( $bovp_sets->lastpage < 7 + ( $adjacents * 2 ) ){

			for ($counter = 1; $counter <= $bovp_sets->lastpage; $counter++){

				if ($counter == $bovp_sets->params['bovp_cpg'])

						$navigation .= "<span class='current'>$counter</span>";

					else

						$navigation .= "<a href='$link/$counter'>$counter</a>";
				}
			}

		elseif($bovp_sets->lastpage > 5 + ($adjacents * 2)){//enough pages to hide some

			//close to beginning; only hide later pages

			if($bovp_sets->params['bovp_cpg'] < 1 + ($adjacents * 2)){

				for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++){

						if ($counter == $bovp_sets->params['bovp_cpg'])

							$navigation .= "<span class='current'>$counter</span>";

						else

							$navigation .= "<a href='$link/$counter'>$counter</a>";

				}

				$navigation .= "...";

				$navigation .= "<a href='$link/$lpm1'>$lpm1</a>";

				$navigation .= "<a href='$link/$bovp_sets->lastpage'>$bovp_sets->lastpage</a>";

			}

				//in middle; hide some front and some back

				elseif($bovp_sets->lastpage - ($adjacents * 2) > $bovp_sets->params['bovp_cpg'] && $bovp_sets->params['bovp_cpg'] > ($adjacents * 2)){

						$navigation .= "<a href='$link/1'>1</a>";

						$navigation .= "<a href='$link/2'>2</a>";

						$navigation .= "...";

						for ($counter = $bovp_sets->params['bovp_cpg'] - $adjacents; $counter <= $bovp_sets->params['bovp_cpg'] + $adjacents; $counter++)

							if ($counter == $bovp_sets->params['bovp_cpg'])

									$navigation .= "<span class='current'>$counter</span>";

								else

									$navigation .= "<a href='$link/$counter'>$counter</a>";

						$navigation .= "...";

						$navigation .= "<a href='$link/$lpm1'>$lpm1</a>";

						$navigation .= "<a href='$link/$bovp_sets->lastpage'>$bovp_sets->lastpage</a>";

					}

				//close to end; only hide early pages

				else {
						$navigation .= "<a href='$link/1'>1</a>";

						$navigation .= "<a href='$link/2'>2</a>";

						$navigation .= "...";

						for ($counter = $bovp_sets->lastpage - (2 + ($adjacents * 2)); $counter <= $bovp_sets->lastpage; $counter++)

							if ($counter == $bovp_sets->params['bovp_cpg'])

									$navigation .= "<span class='current'>$counter</span>";

								else

									$navigation .= "<a href='$link/$counter'>$counter</a>";

					}

			}

			

		if($bovp_sets->params['bovp_cpg']){

				//NEXT BUTTON

				if ($bovp_sets->params['bovp_cpg'] < $counter - 1)

						$navigation .= "<a href='$link/$next' class='next'>" . __('Next','bovp') . "</a>";

					else

						$navigation .= "<span class='disabled'>" . __('Next','bovp') . "</span>";

	}

}

$navigation .= "</nav>";


return $navigation;

}


/**
 * uninstall plugin and remove sets for BOVP Bible. 
 *
 * @since 1.5.3
 *
 * @return void
 **/

function bovp_uninstall(){

	global $wpdb;	
	global $bovp_sets;

	$table = $wp_prefix . 'bovp_' . $sets->table;

	$wpdb->query( "DELETE FROM `wp_options` WHERE `option_name` LIKE '%bovp%_%'");

	$wpdb->query( "DROP TABLE IF EXISTS '$table");

}

/**
 * Recovery info from activate Bible translate.
 *
 * @since 1.4
 *
 * @return void
 */

function bovp_set_info(){

	global $bovp_sets;

	$bovp_sets->plugin_url = BOVP_PLUGIN_URL;
	$bovp_sets->url = get_option( 'siteurl' ) . "/";
	$bovp_sets->path = BOVP_PATH;
	$bovp_sets->icon = BOVP_PLUGIN_URL . 'img/icone_bovp.png';
	$bovp_sets->foldername = BOVP_FOLDER;
	$bovp_sets->version = '1.6.0';

	$bovp_sets->translate_info = bovp_list_translates();

	if( get_option( 'rewrite_rules' ) ) { 

		$bovp_sets->slug_page = bovp_slug_name( $bovp_sets->page );

		$bovp_sets->furl = true; 

		add_filter( 'rewrite_rules_array','bovp_rewrite_rules' );
		add_filter( 'init','bovp_update_rules' );

	} 

	if( isset( $bovp_sets->table ) AND !empty( $bovp_sets->table ) ) { 

		$bovp_sets->books_info = bovp_set_array_books();

 	}

	
	
}

 
/**
 * Install default sets for bible. 
 *
 * @since 1.5.3
 *
 * @return void
 *
 **/

function bovp_install() {

	$key_log_tester = wp_generate_password( 20, false );

	# all settings of online bible in array
	$bovpset = array(		

		'bdv'=>'1.5.3',
		'table'=>'',
		'active_translate'=>'not_set',
		'gooimgsh'=>BOVP_PLUGIN_URL . 'img/google_share_180_120.jpg',
		'fbimgsh'=>BOVP_PLUGIN_URL . 'img/fb_share_200_200.jpg',
		'twimgsh'=>BOVP_PLUGIN_URL . 'img/twitter_share_120_120.jpg',
		'key_log_tester'=>$key_log_tester

	);

	$usersets = array(

		'page'=> false,
		'on_front'=> __('index','bovp'),
		'fsize'=>'14',
		'vsource'=>'0',
		'itpp'=>'20',
		'sep'=>':',
		'translate'=>'-1',
		'theme'=>'default',
		'web117_link' => 'http://www.web117.com.br',
		'bovp_link' => 'http://www.vivendoapalavra.org'
	);

	add_option("bovp_settings", $bovpset);
	add_option("bovp_user_settings", $usersets);

}


// Add filters
add_filter('the_content','bovp_show_content'); // Show Bible Content

// Add actions
add_action( 'admin_menu', 'bovp_admin_menu' ); // Add ADM Menu
add_action( 'admin_head', 'bovp_adm_styles' ); // Include Styles Sheets
add_action( 'plugins_loaded', 'bovp_load_text_domain' ); // Activate Plugin Translate
add_action( 'wp_enqueue_scripts', 'bovp_enqueue_dependences' ); // Include Script Files
add_action( 'widgets_init', 'bovp_widgets_init' ); // Widgets Init
add_action( 'admin_init', 'bovp_user_settings' ); // Register Settings



// Add Shortcodes
add_shortcode('bovp_vd', 'bovp_short_code'); // Activate Shortcode Use.


function bovp_list_translates() {

	$rule = BOVP_PATH .  "data/*.csv";

	$translates = array();

	$files = glob( $rule );

	if ( $files !== false ) {

	    foreach ( $files as $file ) {

	    	$explode = explode('/', $file);

	    	$file_name = str_replace('.csv', '', end( $explode )) ;

	        $open_file = fopen( $file , 'r' );

			if ( $open_file ) {

				$line = fgetcsv( $open_file, 0, ";" );

				$translate_name = end( $line );

				$translates[$translate_name] = $file_name;

			}


	    }  

	} 
		
	return $translates;

}