<?php
get_header();

if ( have_posts() ) {
	the_post();

	// If HivePress is active, render the full Listing template
	if ( function_exists( 'hivepress' ) ) {
		$listing = \HivePress\Models\Listing::query()->get_by_id( get_post() );

		if ( $listing ) {
			// Preload images (as in controller)
			$listing->get_images__id();

			$vendor = $listing->get_vendor();

			// Set request context for template
			hivepress()->request->set_context( 'listing', $listing );
			hivepress()->request->set_context( 'vendor', $vendor );

			// Render the official HivePress single listing template
			echo ( new \HivePress\Blocks\Template(
				[
					'template' => 'listing_view_page',
					'context'  => [
						'listing' => $listing,
						'vendor'  => $vendor,
					],
				]
			) )->render();
		} else {
			// Fallback: basic content
			?>
			<div class="row">
				<main class="col-sm-8 col-sm-offset-2 col-xs-12">
					<article <?php post_class( 'single-listing' ); ?>>
						<header class="listing-header">
							<h1 class="listing-title"><?php the_title(); ?></h1>
						</header>
						<div class="listing-content">
							<?php the_content(); ?>
						</div>
					</article>
				</main>
			</div>
			<?php
		}
	}
}

get_footer();


