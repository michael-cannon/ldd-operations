<?php
/**
 * Copyright 2014 Michael Cannon (email: mc@aihr.us)
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

if ( ! function_exists( 'ldd_operations_time_elasped' ) ) {
	function ldd_operations_time_elasped( $args, $field, $meta ) {
		$delivery_id = get_the_ID();

		$order_date = get_post_meta( $delivery_id, 'order_date', true );
		$last_update = get_post_meta( $delivery_id, 'last_update', true );

		echo human_time_diff( $order_date, $last_update );
	}
}
?>
