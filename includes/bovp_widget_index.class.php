<?php

/**
 * bovp_widget_index()
 *
 * @package Widgets
 * @author André Brum Sampaio
 **/

class bovp_widget_index extends WP_Widget {

	private $user_sets;
	private $url;

	function __construct() {

		global $bovp_sets;

		parent::__construct(
			'bovp_index',
			__( 'Bible Index', 'bovp' ),
			array( 'description' =>  __('This widget show an index of books of Bible in your sidebar.','bovp') ) // Args
		);

		$this->user_sets = (object) get_option( 'bovp_user_settings' );

		$this->url = get_option( 'siteurl' ) . "/";

	}


  /* Displays the Widget in the front-end */
    function widget($args, $instance){

		extract($args);

		$title = apply_filters('widget_title', empty($instance['title']) ? __('Bible Index','bovp') : $instance['title']);
		$subtitle = apply_filters('widget_title', empty($instance['title']) ? __('Widget ','bovp') : false);

		if($subtitle){$title = '<b>' . $subtitle . '</b> ' . $title;}

		echo $before_widget;

		if ( $title ) echo $before_title . $title . $after_title;

		bovp_book_select("bovp_book_select");

		echo $after_widget;

	}

  /*Saves the settings. */

    function update($new_instance, $old_instance){

		$instance = $old_instance;
		$instance['title'] = stripslashes($new_instance['title']);
		return $instance;

	}

  /*Creates the form for the widget in the back-end. */
    function form($instance){

		//Defaults
		$instance = wp_parse_args( (array) $instance, array('title'=>__('Bible Search','bovp')) );
		$title = htmlspecialchars($instance['title']);

		# Title
		echo '<p><label for="' . $this->get_field_id('title') . '">' . __('Title:','bovp') . '</label><input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" /></p>';

	}

}


// END class