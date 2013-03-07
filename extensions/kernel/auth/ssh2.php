<?php
/**
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Copyright (C) 
 * 2013 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2013
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 */
/**
 * Auth driver for SSH2. 
 * http://www.php.net/manual/en/book.ssh2.php
 * 
 * @package    Kernel
 * @subpackage Auth
 */

if(!defined('LIBVALOA_AUTH_SSH2_HOSTNAME')) define('LIBVALOA_AUTH_SSH2_HOSTNAME', 'localhost');
if(!defined('LIBVALOA_AUTH_SSH2_PORT'))     define('LIBVALOA_AUTH_SSH2_PORT', 22);

class Auth_SSH2 implements Auth_IFace, Auth_PWResetIFace {

	private $connection;

	public function __construct() {
		$this->connection = ssh2_connect(
			LIBVALOA_AUTH_SSH2_HOSTNAME,
			LIBVALOA_AUTH_SSH2_PORT);

	}

	public function authentication($user, $pass) {
		return (bool) ssh2_auth_password($this->connection, $user, $pass);
	}

	public function authorize($userid, $module) {
		return false;
	}

	public function getExternalUserID($user) {
		return $user;
	}

	public function getExternalSessionID($user) {
		return false;
	}

	public function logout() {
		unset($this->connection);
	}

	public function updatePassword($user, $pass) {
		return false;
	}

}