<?php

/**
 * bovp_widget_verse()
 *
 * @package Widgets
 * @author André Brum Sampaio
 **/


class bovp_widget_verse extends WP_Widget {

	function __construct() {
		parent::__construct(
			'bovp_verse_widget', 
			__( 'Daily Verse', 'bovp' ), 
			array( 'description' => __('Use this widget to display the daily verse','bovp') ) // Args
		);
	}


  /* Displays the Widget in the front-end */
    function widget( $args, $instance ){

		extract( $args);

		$title = apply_filters('widget_title', empty($instance['title']) ? __('Bible Index','bovp') : $instance['title']);
		$subtitle = apply_filters('widget_title', empty($instance['title']) ? __('Widget ','bovp') : false);

		if($subtitle){$title = '<b>' . $subtitle . '</b> ' . $title;}

		echo $before_widget;

		if ( $title ) echo $before_title . $title . $after_title;

		$random = ( $instance['type'] == 0 ) ? false : true;

		$verse_array = bovp_show_verse( $random );

		extract($verse_array);

		echo "<blockquote title='$ref'>$text</blockquote><span><a href='$link'>$ref</a></span>";

		echo $after_widget;


	}

  	/*Saves the settings. */
    function update($new_instance, $old_instance){

		$instance = $old_instance;

		$instance['title'] = stripslashes($new_instance['title']);
		$instance['type'] = stripslashes($new_instance['type']);

		return $instance;

	}

  	/*Creates the form for the widget in the back-end. */
    function form($instance){

		$title = !empty( $instance['title'] ) ? $instance['title'] : __('Daily Verse','bovp');
		$type  = !empty( $instance['type'] ) ? $instance['type'] : 0;

		echo "<!-- $title - $type -->";

		# Title
		$select  ='<p><label for="' . $this->get_field_id('title') . '">' . __('Title:','bovp') . '</label><input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" /></p>';

		# Tipo
		$select .= '<p><label for="' . $this->get_field_id('type') . '">' . __('Type:','bovp') . '</label>';
		$select .= '<select class="widefat" id="' . $this->get_field_id('type') . '" name="' . $this->get_field_name('type') . '" >';
		$select .= '<option value="0"';
		if ($type=="0") { $select .= ' selected="selected" ';}
		$select .= '>' . __( 'Daily Fixed', 'bovp' ) . '</option>';
		$select .= '<option value="1"';
		if ($type=="1") { $select .= ' selected="selected" ';}
		$select .= '>' . __( 'Random Verse', 'bovp' ) . '</option></select></p>';

		echo $select;

	}

} 