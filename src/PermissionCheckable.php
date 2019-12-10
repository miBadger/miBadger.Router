<?php

namespace miBadger\Router;

interface PermissionCheckable
{

	/**
	 * @return Bool
	 */
	public function hasPermission($permission);

}