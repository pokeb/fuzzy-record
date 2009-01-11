<?php

class File {

	public $name;
	public $path;
	public $contents;
	public $is_uploaded_file = false;
	
	public function __construct($path="") {
		if ($path != "") {
			$this->path = $path;
		}
	}
	
	public static function file_with_uploaded_file($uploaded_file_array) {
		$file = new File();
		$file->is_uploaded_file = true;
		$file->name = $uploaded_file_array['name'];
		$file->path = $uploaded_file_array['tmp_name'];
		return $file;
	}
	
	public function name() {
		if (isset($this->name)) {
			return $this->name;
		}
		if (isset($this->path)) {
			return basename($this->path);
		}
		return NULL;
	}

	public function mime_type() {
	   $info = finfo_open(FILEINFO_MIME);
	   $mime = finfo_file($info, $this->path);
	   finfo_close($info);
	   $parts = explode(";",$mime);
	   return $parts[0];
	}
	
	public function output_to_browser() {
		header("Content-Type: ".$this->mime_type());
		echo $this->read();
		exit;
	}
	
	public function delete() {
		if (file_exists($this->path)) {
			unlink($this->path);
		}
	}

	public function read() {
		if (isset($this->path)) {
			$this->contents = file_get_contents($this->path);
			return $this->contents;
		}
		throw new FuzzyRecordException("Path must be set to read from a file'");
	}
	
	public function exists() {
		if (isset($this->path)) {
			return file_exists($this->path);
		}
		throw new FuzzyRecordException("No path set when checking if file exists'");
	}
	
	public function write($path=NULL) {
		if (isset($this->path)) {
			if ($this->is_uploaded_file) {
				if (!move_uploaded_file($this->path,$path)) {
					throw new FuzzyRecordException("Failed to move uploaded file from '$this->path' to '$this->path'");
				}
			} elseif (!copy($this->path,$path)) {
				throw new FuzzyRecordException("Failed to copy from '$this->path' to '$this->path'");
			}
			$this->path = $path;
			return;
		}
		if (file_put_contents($path,$this->contents) === false) {
			throw new FuzzyRecordException("Failed to write to '$this->path'");
		}
	}
}