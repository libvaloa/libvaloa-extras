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
 * Examples:
 *
 * Receive file contents:
 *
 * $scp = new SSH2_SCP("/home/tester/test.txt");
 * $fileContents = (string) $scp;
 * unset($scp); // closes connection
 *
 * Put a remote file:
 *
 * $scp = new SSH2_SCP("/home/tester/test.txt");
 * $scp->fromFile("/home/localtester/localtest.txt");
 * $scp->putRemoteFile();
 * unset($scp); // closes connection
 *
 */

if(!defined('LIBVALOA_AUTH_SSH2_HOSTNAME'))        { define('LIBVALOA_AUTH_SSH2_HOSTNAME', 'localhost'); }
if(!defined('LIBVALOA_AUTH_SSH2_PORT'))            { define('LIBVALOA_AUTH_SSH2_PORT', 22); }
if(!defined('LIBVALOA_SSH2_KEY_BASED_AUTH'))       { define('LIBVALOA_SSH2_KEY_BASED_AUTH', 0); }
if(!defined('LIBVALOA_SSH2_USERNAME'))             { define('LIBVALOA_SSH2_USERNAME', ''); }
if(!defined('LIBVALOA_SSH2_PASSWORD'))             { define('LIBVALOA_SSH2_PASSWORD', ''); }
if(!defined('LIBVALOA_SSH2_STDERR_STREAM'))        { define('LIBVALOA_SSH2_STDERR_STREAM', 0); }
if(!defined('LIBVALOA_SSH2_DEFAULT_CHMOD'))        { define('LIBVALOA_SSH2_DEFAULT_CHMOD', 0644); }

class SSH2_SCP {

	private $remoteFile,     // target remote file (for recv and send)
		$localFile,          // local file (send)
		$localTempFile,      // local temporary file (recv). NOTE!! This gets destroyed/unlinked on exit and after transfers.
		$localFileContents,  // local file contents (recv, send)
		$remoteFileContents, // remote file contents (recv, send)
		$connection = false;

	public function __construct($remoteFile = false) {
		if(!$remoteFile) {
			throw new InvalidArgumentException("remote target file not set - set this first in class constructor.");
		}

		$this->remoteFile = $remoteFile;
		$this->remoteFileContents 
			= $this->localFileContents 
			= $this->localFile 
			= $this->localTempFile = false;

		// Generate temporary local file. 
		// NOTE!! This gets destroyed/unlinked on exit and after transfers.
		$this->setLocalTempFile(tempnam("/tmp", "LIBVALOA_SSH2_TEMPFILE"));
	}
	
	private function openSSHConnection() {
		if(!$this->connection) {
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
	}
	
	public function fromFile($localFile) {
		$this->localFile = $this->localTempFile;
		return file_put_contents($this->localTempFile, file_get_contents($localFile));
	}

	public function fromString($localFileContents) {
		$this->localFile = $this->localTempFile;
		return file_put_contents($this->localTempFile, $localFileContents);
	}

	public function setLocalTempFile($localTempFile) {
		$this->localTempFile = $localTempFile;
	}

	public function getRemoteFile() {
		if(!$this->remoteFileContents) {
			$this->remoteFileContents = $this->recv();
		}
		return $this->remoteFileContents;
	}

	public function putRemoteFile() {
		$this->send();
	}

	private function recv() {
		if(!$this->connection) {
			// Open SSH connection
			$this->openSSHConnection();
		}

		if(!$this->localTempFile) {
			throw new InvalidArgumentException("local target file not set - set this first with setLocalTempFile(path).");
		}

		// transfers might get truncated if connection isn't closed propely
		ssh2_scp_recv($this->connection, $this->remoteFile, $this->localTempFile);
		ssh2_exec($this->connection, 'exit');
		$this->connection = false;

		// Read the data from local temp file and unlink it
		$tmp = @file_get_contents($this->localTempFile);
		unlink($this->localTempFile);
		if(!$tmp || empty($tmp)) {
			throw new UnexpectedValueException("receiving remote file failed or file was empty.");
		}
		return (string) $tmp;
	}

	private function send() {
		if(!$this->connection) {
			// Open SSH connection
			$this->openSSHConnection();
		}

		if(!$this->localFile) {
			throw new InvalidArgumentException("local target file not set - set this first with fromFile() or fromString().");
		}

		$retval = ssh2_scp_send($this->connection, $this->localFile, $this->remoteFile, LIBVALOA_SSH2_DEFAULT_CHMOD);

		// transfers might get truncated if connection isn't closed propely
		ssh2_exec($this->connection, 'exit');
		$this->connection = false;
		return $retval;
	}

	/**
	 * Returns remote file as string
	 * @return string $output remote file as string
	 */
	function __toString() {
		try {
			return (string) $this->getRemoteFile();
		} catch(Exception $e) {
			trigger_error($e->getMessage(), E_USER_NOTICE);
			return (string) 1;
		}
	}

	function __destruct() {
		// Close any open connections
		if($this->connection) {
			ssh2_exec($this->connection, 'exit');
			$this->connection = false;
		}

		// Unlink the temporary file
		if(is_readable($this->localTempFile)) {
			unlink($this->localTempFile);
		}
	}

}