<?php
$class = isset( $note->meta[ 'comment_status' ] ) ? implode( $note->meta[ 'comment_status' ] ) : get_comment_meta( $note->id, 'sumo_status', true );
?>
<li rel="<?php echo absint( $note->id ); ?>" class="<?php echo '' !== $class ? esc_attr( $class ) : 'pending'; ?>">
	<div class="note_content">
		<?php echo wp_kses_post( wpautop( wptexturize( $note->content ) ) ); ?>
	</div>
	<p class="meta">
		<abbr class="exact-date" title="<?php echo esc_attr( sumo_display_subscription_date( $note->date_created ) ); ?>"><?php echo esc_html( sumo_display_subscription_date( $note->date_created ) ); ?></abbr>
		<?php
		if ( ! empty( $note->added_by ) ) {
			/* translators: 1: note added user */
			printf( esc_html__( '  by %s', 'sumosubscriptions' ), esc_html( $note->added_by ) );
		}
		?>
		<a href="#" class="sumo_delete_note delete_note"><?php esc_html_e( 'Delete note', 'sumosubscriptions' ); ?></a>
	</p>
</li>
