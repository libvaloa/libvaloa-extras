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
 * 2008,2013 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2008,2013
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
 
if(!defined('LIBVALOA_AUTH_SSH2_HOSTNAME'))        { define('LIBVALOA_AUTH_SSH2_HOSTNAME', 'localhost'); }
if(!defined('LIBVALOA_AUTH_SSH2_PORT'))            { define('LIBVALOA_AUTH_SSH2_PORT', 22); }
if(!defined('LIBVALOA_SSH2_KEY_BASED_AUTH'))       { define('LIBVALOA_SSH2_KEY_BASED_AUTH', 0); }
if(!defined('LIBVALOA_SSH2_USERNAME'))             { define('LIBVALOA_SSH2_USERNAME', ''); }
if(!defined('LIBVALOA_SSH2_PASSWORD'))             { define('LIBVALOA_SSH2_PASSWORD', ''); }
if(!defined('LIBVALOA_SSH2_STDERR_STREAM'))        { define('LIBVALOA_SSH2_STDERR_STREAM', 0); }
 
class SSH2_Execute {

	private $command, $connection;

	public function __construct($command) {
		$this->output = new stdClass;
		$this->command = $command;
	}
	
	private function openSSHConnection() {
		$this->connection = ssh2_connect(
			LIBVALOA_AUTH_SSH2_HOSTNAME,
			LIBVALOA_AUTH_SSH2_PORT);
						

		switch(LIBVALOA_SSH2_KEY_BASED_AUTH) {
			case 1:
				// TODO: key-based auth
			
				break;
			default:
				if(!ssh2_auth_password($this->connection, LIBVALOA_SSH2_USERNAME, LIBVALOA_SSH2_PASSWORD)) {
					throw new Exception("System authentication failed. Please check your setup.");
				}
				
		}
	}
	
	/**
	 * Execute command through SSH connection and return output
	 * @param string $commandCommand to execute
	 * @return string $output
	 */	
	private function handleResource($command) {
		$this->output->stdout = false;
		$this->output->stderr = false;
		
		Debug::d("Executing {$command} @ ". LIBVALOA_AUTH_SSH2_HOSTNAME);
		
		// Open SSH connection
		$this->openSSHConnection();
		
		// Execute the command
		$stream = ssh2_exec($this->connection, $command);		
		stream_set_blocking($stream, true);
		
		if(LIBVALOA_SSH2_STDERR_STREAM == 0) {
			$this->output->stdout = stream_get_contents($stream);
		}
		
		// Get stderr too if wanted. This is experimental.
		if(LIBVALOA_SSH2_STDERR_STREAM == 1) {
			$errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);

			// Enable blocking for both streams
			stream_set_blocking($errorStream, true);
			stream_set_blocking($stream, true);

			// Whichever of the two below commands is listed first will receive its appropriate output.  
			// The second command receives nothing
			$this->output->stdout = stream_get_contents($stream);
			$this->output->stderr = stream_get_contents($errorStream);

			// Close the streams   
			if($errorStream != NULL) {   
				fclose($errorStream);
			}
		}
		if($stream != NULL) {
			fclose($stream);
		}

		// Close the connection
		unset($this->connection);
		return (string) $this->output->stdout;
	}

	/**
	 * Returns stdout output as string
	 * @return string $output Output as string
	 */
	function __toString() {
		try {
			return (string) $this->handleResource($this->command);
		} catch(Exception $e) {
			trigger_error($e->getMessage(), E_WARNING);
			return (string) 1;
		}
	}	

}
