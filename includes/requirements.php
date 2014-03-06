<?php
/*
	Copyright 2014 Michael Cannon (email: mc@aihr.us)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


function ldd_operations_requirements_check() {
	$valid_requirements = true;
	if ( ! is_plugin_active( LDD_OPERATIONS_REQ_BASE ) ) {
		$valid_requirements = false;
		add_action( 'admin_notices', 'ldd_operations_notice_version' );
	}

	if ( ! $valid_requirements ) {
		deactivate_plugins( LDD_OPERATIONS_BASE );
	}

	return $valid_requirements;
}


function ldd_operations_notice_version() {
	aihr_notice_version( LDD_OPERATIONS_REQ_BASE, LDD_OPERATIONS_REQ_NAME, LDD_OPERATIONS_REQ_SLUG, LDD_OPERATIONS_REQ_VERSION, LDD_OPERATIONS_NAME );
}

?>
