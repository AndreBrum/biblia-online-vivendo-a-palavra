<?php

add_action( 'wp_enqueue_scripts', 'default_stylesheet' );

function default_stylesheet() {

    wp_enqueue_style('material-montserrat', '//fonts.googleapis.com/css?family=Montserrat:300,400,600,700');

}

?>