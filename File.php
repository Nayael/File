<?php 
/**
* A File class to easily manipulate files in PHP
*/
class File
{
	/**
	 * Whether the file exists or not (similar to file_exists())
	 * 
	 * @var bool
	 * @readonly
	 */
	private $exists = false;
	
	/**
 	 * The path to the file
	 *
	 * @var string
	 * @readonly
	 */
	private $path = '';

	/**
	 * Some stats about the file (similar to stat($this->path))
	 *
	 * @var array
	 * @readonly
	 */
	private $stat = array();

	/**
	 * The permissions on the file (similar to fileperms($this->path))
	 *
	 * @var int
	 * @readonly
	 */
	private $perms = null;

	/**
	 * The id of owner of the file (similar to fileowner($this->path))
	 *
	 * @var string
	 * @readonly
	 */
	private $owner = '';

	/**
	 * The name of owner of the file
	 *
	 * @var string
	 * @readonly
	 */
	private $ownerName = '';

	public function __get($value='')
	{
		switch ($value) {
			case 'exists':
				return file_exists($this->path);
				break;

			case 'stat': case 'perms': case 'path': case 'owner': case 'ownerName':
				return $this->$value;
				break;
			
			default:
				break;
		}
	}

	/**
	 * Creates a File instance, to easily manipulate a file
	 * @param string $path The path to the file
	 */
	public function __construct($path)
	{
		$this->path = $path;
		$this->exists = file_exists($this->path);
		if ($this->exists) {	// If the file exists, we set the necessary properties
			$this->owner = fileowner($this->path);
			if (function_exists('posix_getpwuid'))
				$this->ownerName = posix_getpwuid($this->owner);
			$this->perms = fileperms($this->path);
			$this->stat = stat($this->path);
		}
	}

	/**
	 * Writes data at the end of the file
	 * @param string $data The string that is to be written.
	 * @param int $length If the length argument is given, writing will stop after length bytes have been written or the end of string is reached, whichever comes first.
	 */
	public function write($data, int $length=null)
	{
		$resource = fopen($this->path, 'a');
		if (null===$length)
			fwrite($resource, $data);
		else
			fwrite($resource, $data, $length);
		fclose($resource);
	}

	/**
	 * Writes a carrage return at the end of the file, then the given data
	 * @param string $data The string that is to be written.
	 * @param int $length If the length argument is given, writing will stop after length bytes have been written or the end of string is reached, whichever comes first.
	 */
	public function writeLine($data='', int $length=null)
	{
		$resource = fopen($this->path, 'a');
		if (null===$length)
			fwrite($resource, "\r\n".$data);
		else
			fwrite($resource, "\r\n".$data, $length);
		fclose($resource);
	}

	/**
	 * Takes an array as a parameter, and writes each element in a line
	 * @param array $lines The lines to write in the file
	 * @param bool $newline Shall a newline be added before writing in the file ?
	 */
	public function writeLines($lines=array(), $newline=true)
	{
		foreach ($lines as $index => $line) {
			if (!$newline && $index == 0) {
				$this->write($line);
				continue;
			}
			$this->writeLine($line);
		}
	}

	/**
	 * Sets access and modification time of file, creates it if it doesn't exists
	 * @param int $time The touch time. If time is not supplied, the current system time is used.
	 * @param int $atime If present, the access time of the given filename is set to the value of atime. Otherwise, it is set to the value passed to the time parameter. If neither are present, the current system time is used.
	 * @return bool
	 */
	public function touch(int $time=null, int $atime=null)
	{
		if (null===$time)
			$time = time();
		if (null===$atime)
			return touch($this->path, $time);
		return touch($this->path, $time, $atime);
	}

	/**
	 * Deletes the file
	 * @param resource $context
	 * @return bool
	 */
	public function unlink(resource $context=null)
	{
		if (is_readable($this->path)) {
			if (null===$context)
				return unlink($this->path);
			return unlink($this->path, $context);
		}
		return false;
	}

	/**
	 * Deletes the file
	 * @param resource $context
	 * @return bool
	 */
	public function delete(resource $context=null)
	{
		return $this->unlink($context);
	}

	/**
	 * Copies the file
	 * @param string $dest The destination path. If dest is a URL, the copy operation may fail if the wrapper does not support overwriting of existing files.
	 * @param resource $context A valid context resource created with stream_context_create().
	 * @return bool
	 */
	public function copy($dest, resource $context=null)
	{
		if (null===$context)
			return copy($this->path, $dest);
		return copy($this->path, $dest, $context);
	}

	/**
	 * Executes chmod on the file
	 * @param int $mode The mode parameter consists of three octal number components specifying access restrictions for the owner, the user group in which the owner is in, and to everybody else in this order.
	 * @return bool
	 */
	public function chmod(int $mode)
	{
		return chmod($this->path, $mode);
	}

	/**
	 * Executes chown on the file
	 * @param mixed $user A user name or number.
	 * @return bool
	 */
	public function chown($user)
	{
		return chown($this->path, $user);
	}
}