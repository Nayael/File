<?php 
/*!
 * A File class to easily manipulate files in PHP
 *
 * by Nicolas Vannier
 * http://www.nicolas-vannier.com
 * 
 * Date: Fri Dec 28 2012 03:47:30 GMT+0200
 */
namespace Nayael\Util;

class File
{	
	/**
 	 * The path to the file
	 *
	 * @var string
	 * @readonly
	 */
	private $path = '';

	/**
	 * The permissions on the file (similar to \fileperms($this->path))
	 *
	 * @var int
	 * @readonly
	 */
	private $perms = null;

	/**
	 * Datas on the owner of the file
	 *
	 * @var array
	 * @readonly
	 */
	private $owner = null;

	public function __get($value='')
	{
		switch ($value) {
			case 'owner':
				if (!$this->exists())
					return null;
				return ($this->owner = \function_exists('posix_getpwuid') ? \posix_getpwuid(\fileowner($this->path)) : null);
				break;

			case 'perms':
				return ($this->perms = $this->exists() ? \fileperms($this->path) : null);
				break;

			case 'path':
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
	}

	/**
	 * Returns all the lines in the file (without eol characters)
	 * @param bool $read_EOL If true, the array elements also contain the newline characters
	 * @return array
	 */
	public function readLines($read_EOL=false)
	{
		$lines = \file($this->path);
		if ($this->getEOL()=="\r" && count($lines)==1) {
			$lines = \explode("\r", $lines[0]);
			if ($read_EOL) {
				foreach ($lines as $index => &$line) {
					if ($index==count($lines) - 1) {
						if ($line==="")
							array_pop($lines);
						break;
					}
					$line .= "\r";
				}
			}
		}
		if (!$read_EOL) {
			foreach ($lines as &$line) {
				$line = \preg_replace('/\\r|\\n/', '', $line);
			}
		}
		return $lines;
	}

	/**
	 * Returns a line from the file by its position (starts at 0)
	 * @return string
	 */
	public function readLine($index)
	{
		$lines = $this->readLines();
		return $lines[$index];
	}

	/**
	 * Returns the newline character used in the file
	 * @return string
	 */
	public function getEOL()
	{
		$EOL = "";
		$resource = \fopen($this->path, 'r');
		$line = \fgets($resource);
		if (false!==\strpos($line, "\r\n"))
			$EOL = "\r\n";
		elseif (false!==\strpos($line, "\r"))
			$EOL = "\r";
		else
			$EOL = "\n";
		\fclose($resource);
		return $EOL;
	}

	/**
	 * Replaces the newline character in the file
	 * @param string $EOL The newline character to use
	 */
	public function replaceEOL($EOL)
	{
		if (!$this->exists() || $EOL == $this->getEOL() || !in_array($EOL, array("\r", "\n", "\r\n")))
			return;
		$lines = $this->readLines(true);
		$this->write('');
		foreach ($lines as $index => $line) {
			if (($line = \preg_replace('/\\r|\\n/', '', $line)) == $lines[$index])
				$this->append($line);
			else
				$this->append($line.$EOL);
		}
	}

	/**
	 * Writes data (overwrites by default)
	 * @param string $data The string that is to be written.
	 * @param bool $overwrite If true, the file will be erased before writing.
	 * @param int $length If the length argument is given, writing will stop after length bytes have been written or the end of string is reached, whichever comes first.
	 */
	public function write($data, $overwrite=true, int $length=null)
	{
		$resource = \fopen($this->path, $overwrite ? 'w' : 'a');
		if (null===$length)
			\fwrite($resource, $data);
		else
			\fwrite($resource, $data, $length);
		\fclose($resource);
	}

	/**
	 * Writes a carrage return, then the given data (overwrites by default)
	 * @param string $data The string that is to be written.
	 * @param bool $overwrite If true, the file will be erased before writing.
	 * @param int $length If the length argument is given, writing will stop after length bytes have been written or the end of string is reached, whichever comes first.
	 */
	public function writeLine($data, $overwrite=true, int $length=null)
	{
		$resource = \fopen($this->path, $overwrite ? 'w' : 'a');
		if (null===$length)
			\fwrite($resource, PHP_EOL.$data);
		else
			\fwrite($resource, PHP_EOL.$data, $length);
		\fclose($resource);
	}

	/**
	 * Takes an array as a parameter, and writes each element in a line (overwrites by default)
	 * @param array $lines The lines to write in the file
	 * @param bool $overwrite If true, the file will be erased before writing.
	 * @param bool $newline Shall a newline be added before writing in the file ?
	 */
	public function writeLines(array $lines, $overwrite=true, $newline=true)
	{
		foreach ($lines as $index => $line) {
			var_dump($line);
			if (!$newline && $index == 0)
				$this->write($line, $overwrite);
			elseif ($index == 0)
				$this->writeLine($line, $overwrite);
			else
				$this->appendLine($line);
		}
	}

	/**
	 * Writes data at the end of the file
	 * Alias for write($data, false)
	 */
	public function append($data, int $length=null)
	{
		$this->write($data, false, $length);
	}

	/**
	 * Writes a carrage return at the end of the file, then the given data
	 * Alias for writeLine($data, false)
	 */
	public function appendLine($data='', int $length=null)
	{
		$this->writeLine($data, false, $length);
	}

	/**
	 * Takes an array as a parameter, and writes each element in a line
	 * Alias for writeLines($data, false)
	 */
	public function appendLines(array $lines, $newline=true)
	{
		$this->writeLines($lines, false, $length);
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
			$time = \time();
		if (null===$atime)
			return \touch($this->path, $time);
		return \touch($this->path, $time, $atime);
	}

	/**
	 * Deletes the file
	 * @param resource $context
	 * @return bool
	 */
	public function unlink(resource $context=null)
	{
		if (\is_readable($this->path)) {
			if (null===$context)
				return \unlink($this->path);
			return \unlink($this->path, $context);
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
			return \copy($this->path, $dest);
		return \copy($this->path, $dest, $context);
	}

	/**
	 * Returns the path to the file's directory
	 * @return string
	 */
	public function dirname()
	{
		return \dirname($this->path);
	}

	/**
	 * Executes chmod on the file
	 * @param int $mode The mode parameter consists of three octal number components specifying access restrictions for the owner, the user group in which the owner is in, and to everybody else in this order.
	 * @return bool
	 */
	public function chmod(int $mode)
	{
		return \chmod($this->path, $mode);
	}

	/**
	 * Executes chown on the file
	 * @param mixed $user A user name or number.
	 * @return bool
	 */
	public function chown($user)
	{
		return \chown($this->path, $user);
	}

	////////////
	// GETTERS
	//
	/**
	 * Whether the file exists or not
	 * @return bool
	 */
	public function exists()
	{
		return \file_exists($this->path);
	}

	/**
	 * Returns some stats about the file (similar to stat($this->path))
	 * @return array
	 */
	public function getStats()
	{
		return ($this->exists() ? \stat($this->path) : null);
	}
}