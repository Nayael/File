<?php 
/**
 * A File library to easily manipulate files in PHP
 *
 * by Nicolas Vannier
 * http://www.nicolas-vannier.com
 * 
 * Date: Sun Mar 17 2013 00:34:30 GMT+0200
 */
namespace Nayael\File;

/**
 * Thrown when File returns an execption
 *
 * @author Nicolas Vannier
 */
class FileException extends \Exception {
    /**
     * Throws a FileException
     * @param string    $path The path to the file that generated an Exception
     * @param int       $code The exception code
     * @param \Exception    $previous The previous exception used for the exception chaining.
     */
    public function __construct($path, $code=0, \Exception $previous=null)
    {
        $message = "An error occurred with the file ".$path;
        switch ($code) {
            case 1:
                $message = "The File class only handles files, not directories !";
                break;
            case 404:
                $message = "The file \"".$path."\" doesn't exist.";
                break;
            case 405:
                $message = "The file \"".$path."\" doesn't exist or is not readable.";
                break;
            case 405:
                $message = "The file \"".$path."\" is not writeable.";
                break;
            case 406:
                $message = "The file \"".$path."\" is not a valid configuration file.";
                break;
        }
        parent::__construct($message, $code, $previous);
    }
}

/**
 * Represents the file to manipulate.
 * Provides useful methods to easily handle file writing, reading, etc.
 *
 * @author Nicolas Vannier
 */
class File
{   
    /**
     * [READONLY] The path to the file
     * @var string
     */
    private $path = '';

    /**
     * Magic getter method
     * @param string    $property The property to return
     */
    public function __get($property='')
    {
        switch ($property) {
            case 'path':
                return $this->$property;
                break;
            
            default:
                break;
        }
    }

    /**
     * Creates a File instance, to manipulate a file
     * @param string    $path The path to the file
     */
    public function __construct($path)
    {
        if (\is_dir($path))
            throw new FileException($path, 1);
            
        $this->path = $path;
    }

    /**
     * Checks if the file exists.
     * Throws an error if not.
     */
    private function checkExistence()
    {
        if (!$this->exists())
            throw new FileException($this->path, 404);
    }

    /**
     * Checks if the file is readable.
     * Throws an error if not.
     */
    private function checkReadable()
    {
        if (!\is_readable($this->path))
            throw new FileException($this->path, 405);
    }

    /**
     * Checks if the file is writable.
     * Throws an error if not.
     */
    private function checkWritable()
    {
        $this->checkExistence();
        if (!\is_writable($this->path))
            throw new FileException($this->path, 406);
    }

    /**
     * Reads the file and returns its content as a string
     * @param bool  $use_include_path The FILE_USE_INCLUDE_PATH can be used to trigger include path search.
     * @param \resource $context A valid context resource created with stream_context_create(). If you don't need to use a custom context, you can skip this parameter by NULL.
     * @param int   $offset The offset where the reading starts on the original stream. Seeking (offset) is not supported with remote files. Attempting to seek on non-local files may work with small offsets, but this is unpredictable because it works on the buffered stream.
     * @param int   $maxlen Maximum length of data read.
     * @return string The file content
     */
    public function read($use_include_path=false, \resource $context=null, $offset=-1, $maxlen=-1)
    {
        $this->checkReadable();
        if ($maxlen!==-1)
            return \file_get_contents($this->path, $use_include_path, $context, $offset, $maxlen);
        return \file_get_contents($this->path, $use_include_path, $context, $offset);
    }

    /**
     * Returns all the lines in the file (without eol characters)
     * @param bool  $read_EOL If true, the array elements also contain the newline characters
     * @return array
     */
    public function readLines($read_EOL=false)
    {
        $this->checkReadable();
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
                $line = \trim($line, "\r\n");
            }
        }
        return $lines;
    }

    /**
     * Returns a line from the file by its position (starts at 0)
     * @param int   $index The line to be read
     * @return string
     */
    public function readLine($index)
    {
        $lines = $this->readLines();
        return $lines[$index];
    }

    /**
     * Parses the file (if configuration file) and returns data as an array
     * @param bool $process_sections By setting the process_sections parameter to TRUE, you get a multidimensional array, with the section names and settings included.
     * @return array The configuration file's data
     */
    public function parse($process_sections = false)
    {
        $this->checkReadable();
        $data = array();
        if (!($data = parse_ini_file($this->path, $process_sections))) {
            throw new FileException($this->path, 406);
        }
        return $data;
    }

    /**
     * Returns the newline character used in the file
     * @return string
     */
    public function getEOL()
    {
        $this->checkReadable();
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
     * @param string    $EOL The newline character to use
     */
    public function replaceEOL($EOL)
    {
        if (!$this->exists() || $EOL==$this->getEOL() || !in_array($EOL, array("\r", "\n", "\r\n")))
            return;
        $lines = $this->readLines(true);
        $this->write('');
        foreach ($lines as $index => $line) {
            if (($line = \trim($line, "\r\n"))==$lines[$index])
                $this->append($line);
            else
                $this->append($line.$EOL);
        }
    }

    /**
     * Writes data (overwrites by default)
     * @param string    $data The string that is to be written.
     * @param bool      $overwrite If true, the file will be erased before writing.
     * @param int       $length If the length argument is given, writing will stop after length bytes have been written or the end of string is reached, whichever comes first.
     */
    public function write($data, $overwrite=true, $length=null)
    {
        $this->checkWritable();
        $resource = \fopen($this->path, $overwrite ? 'w' : 'a');
        if (null===$length)
            \fwrite($resource, $data);
        else
            \fwrite($resource, $data, $length);
        \fclose($resource);
    }

    /**
     * Writes a new line, then the given data (overwrites by default)
     * @param string    $data The string that is to be written.
     * @param bool      $overwrite If true, the file will be erased before writing.
     * @param int       $length If the length argument is given, writing will stop after length bytes have been written or the end of string is reached, whichever comes first.
     */
    public function writeLine($data, $overwrite=true, $length=null)
    {
        $this->checkWritable();
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
     * @param bool  $overwrite If true, the file will be erased before writing.
     * @param bool  $newline Shall a newline be added before writing in the file ?
     * @param int   $length If the length argument is given, writing will stop after length bytes have been written or the end of string is reached, whichever comes first.
     */
    public function writeLines(array $lines, $overwrite=true, $newline=true, $length=null)
    {
        foreach ($lines as $index => $line) {
            if (!$newline && $index==0)
                $this->write($line, $overwrite, $length);
            elseif ($index==0)
                $this->writeLine($line, $overwrite, $length);
            else
                $this->appendLine($line, $length);
        }
    }

    /**
     * Writes data at the end of the file
     * @param string    $data The string that is to be written.
     * @param int       $length If the length argument is given, writing will stop after length bytes have been written or the end of string is reached, whichever comes first.
     * @see File::write()
     */
    public function append($data, $length=null)
    {
        $this->write($data, false, $length);
    }
    /**
     * Writes a new line at the end of the file, then the given data
     * @param string    $data The string that is to be written.
     * @param int       $length If the length argument is given, writing will stop after length bytes have been written or the end of string is reached, whichever comes first.
     * @see File::writeLine()
     */
    public function appendLine($data='', $length=null)
    {
        $this->writeLine($data, false, $length);
    }

    /**
     * Takes an array as a parameter, and writes each element in a line
     * @param array $lines The lines to write in the file
     * @param bool  $newline Shall a newline be added before writing in the file ?
     * @param int   $length If the length argument is given, writing will stop after length bytes have been written or the end of string is reached, whichever comes first.
     * @see File::writeLines()
     */
    public function appendLines(array $lines, $newline=true, $length=null)
    {
        $this->writeLines($lines, false, $newline, $length);
    }

    /**
     * Sets access and modification time of file, creates it if it doesn't exists
     * @param int   $time The touch time. If time is not supplied, the current system time is used.
     * @param int   $atime If present, the access time of the given filename is set to the value of atime. Otherwise, it is set to the value passed to the time parameter. If neither are present, the current system time is used.
     * @return bool
     */
    public function touch($time=null, $atime=null)
    {
        if (null===$time)
            $time = \time();
        if (null===$atime)
            return \touch($this->path, $time);
        return \touch($this->path, $time, $atime);
    }

    /**
     * Creates the file if it doesn't exist
     */
    public function create()
    {
        if (!$this->exists())
            return $this->touch();
        return false;
    }

    /**
     * Deletes the file name
     * @param \resource $context
     * @return bool
     */
    public function unlink(\resource $context=null)
    {
        $this->checkReadable();
        if (null===$context)
            return \unlink($this->path);
        return \unlink($this->path, $context);
    }

    /**
     * Deletes the file name
     * @param \resource $context
     * @see File::unlink()
     */
    public function delete(\resource $context=null)
    {
        return $this->unlink($context);
    }

    /**
     * Copies the file
     * @param string    $dest The destination path. If dest is a URL, the copy operation may fail if the wrapper does not support overwriting of existing files.
     * @param \resource $context A valid context resource created with stream_context_create().
     * @return bool
     */
    public function copy($dest, \resource $context=null)
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
     * @param int   $mode The mode parameter consists of three octal number components specifying access restrictions for the owner, the user group in which the owner is in, and to everybody else in this order.
     * @return bool
     */
    public function chmod($mode)
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
     * Returns data on the owner of the file
     * @return array
     */
    public function getOwner()
    {
        if (!$this->exists())
            return null;
        return (\function_exists('posix_getpwuid') ? \posix_getpwuid(\fileowner($this->path)) : null);
    }
}
