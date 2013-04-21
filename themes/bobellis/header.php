<?php
/**
 * Header Template
 *
 * Here we setup all logic and XHTML that is required for the header section of all screens.
 *
 * @package WooFramework
 * @subpackage Template
 */
global $woo_options, $woocommerce;
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="<?php if ( $woo_options['woo_boxed_layout'] == 'true' ) echo 'boxed'; ?> <?php if (!class_exists('woocommerce')) echo 'woocommerce-deactivated'; ?>">
<head>

<meta charset="<?php bloginfo( 'charset' ); ?>" />

<title><?php woo_title(''); ?></title>
<?php woo_meta(); ?>
<link rel="stylesheet" type="text/css" href="<?php bloginfo( 'stylesheet_url' ); ?>" media="screen" />
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
<?php
	wp_head();
	woo_head();
?>

</head>

<body <?php body_class(); ?>>
<?php woo_top(); ?>

<div id="wrapper">


    <?php woo_header_before(); ?>

	<header id="header" class="col-full">

	    <hgroup>
	    	<h1>
			    <img src="<?php echo get_stylesheet_directory_uri();?>/img/logo.png">
			</h1>

			<h3 class="nav-toggle"><a href="#navigation">&#9776; <span><?php _e('Navigation', 'woothemes'); ?></span></a></h3>

		</hgroup>

        <?php woo_nav_before(); ?>

		<nav id="navigation" class="col-full" role="navigation">

			<?php
			if ( function_exists( 'has_nav_menu' ) && has_nav_menu( 'primary-menu' ) ) {
				wp_nav_menu( array( 'depth' => 6, 'sort_column' => 'menu_order', 'container' => 'ul', 'menu_id' => 'main-nav', 'menu_class' => 'nav fr', 'theme_location' => 'primary-menu' ) );
			} else {
			?>
	        <ul id="main-nav" class="nav fl">
				<?php if ( is_page() ) $highlight = 'page_item'; else $highlight = 'page_item current_page_item'; ?>
				<li class="<?php echo $highlight; ?>"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php _e( 'Home', 'woothemes' ); ?></a></li>
				<?php wp_list_pages( 'sort_column=menu_order&depth=6&title_li=&exclude=' ); ?>
			</ul><!-- /#nav -->
	        <?php } ?>

		</nav><!-- /#navigation -->

		<?php woo_nav_after(); ?>

	</header><!-- /#header -->

	<div class="col-full">
		<?php bobellis_before_quick_nav();?>
	</div>

	<?php if(get_field('call_to_actions', 'options')): ?>
		<div id="quick-nav" class="three-columns col-full">
			<?php while(has_sub_field('call_to_actions', 'options')): ?>
			<div class="col-third">
				<?php the_sub_field('main');?> <a href="#<?php the_sub_field('action_id');?>" rel="modal:open"><?php the_sub_field('action_text');?></a>
			</div>
			<?php endwhile; ?>
		</div>
	<?php endif; ?>

	<?php woo_content_before(); ?>