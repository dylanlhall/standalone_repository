<?php

$repos = new Repos;

class Repos {

    public $folders = array();
    public $files = array();
    
    public $bitsize = 0;
    public $line_count = 0;
    public $folder_count = 0;
    public $file_count = 0;
    public $image_count = 0;
    public $layout_image_count = 0;
    
    protected $path = "";
    
    public function __construct($auto = true) {
    
        $this->dir = (object) array();
            $this->dir->active = "";
        
        $this->exclude = (object) array(
            "files" => array(),
            "folders" => array()
        );
        
        $this->regex = "#[html|css|js|php|json|mdown]$#i";
        
        if ($auto):
        
            $this->rescan_filesystem();
            
            $this->get_active_file();
            $this->get_listings();
        
        endif;
    
    }
    
    protected function exclude($method, $value) {
    
        if ($method == "file"):
        
            array_push($this->exclude->files, $value);
        
        elseif ($method == "folder"):
        
            array_push($this->exclude->folders, $value);
        
        else:
        
            if (!isset($this->exclude->$method)): $this->exclude->$method = array(); endif;
            
            array_push($this->exclude->$method, $value);
        
        endif;
    
    }
    
    public function exclude_files($string) {
    
        if (!isset($this->exclude->files)): $this->exclude->files = array(); endif;
        
        $files = explode(", ", $string);
        $this->exclude->files = $files;
    
    }
    
    public function exclude_folders($string) {
    
        if (!isset($this->exclude->folders)): $this->exclude->folders = array(); endif;
        
        $folders = explode(", ", $string);
        $this->exclude->folders = $folders;
    
    }
    
    public function navigation_listing() {
    
        $result = array();
        
        foreach ($this->files as $file):
        
            $data = explode("/", $file);
            
            if (count($data) == 1):
            
                list($file) = $data;
                array_push($result, $file);
            
            elseif (count($data) == 2):
            
                list($folder, $file) = $data;
                
                if (!isset($result[$folder])): $result[$folder] = array(); endif;
                array_push($result[$folder], $file);
            
            elseif (count($data) == 3):
            
                list($folder, $folder2, $file) = $data;
                
                if (!isset($result[$folder][$folder2])): $result[$folder][$folder2] = array(); endif;
                array_push($result[$folder][$folder2], $file);
            
            elseif (count($data) == 4):
            
                list($folder, $folder2, $folder3, $file) = $data;
                if (!isset($result[$folder][$folder2][$folder3])): $result[$folder][$folder2][$folder3] = array(); endif;
                array_push($result[$folder][$folder2][$folder3], $file);
            
            endif;
        
        endforeach;
        
        $this->listing = $result;
    
    }
    
    public function get_active_file() {
    
        global $active;
        
        $result = (object) array();
        
        if (isset($_GET['file'])):
        
            $filename = str_replace(DIRECTORY_SEPARATOR, "/", $_GET['file']);
            $filename = str_replace(array("./", "../"), "", $_GET['file']);
            
            if (preg_match("#^\/#", $filename)): $filename = substr($filename, 1); endif;
            
            if (in_array($filename, $this->files) AND preg_match($this->regex, $filename)):
            
                $result->filename = $filename;
                $result->file = ROOT . $filename;
                
                $result->content = highlight_file($result->file, true);
                
                $loaded_result = $result; unset($result);
            
            endif; unset($filename);
        
        endif;
        
        if (!isset($loaded_result)):
        
            $filename = "pages/" . $active->page->file;
            
            $result->filename = $filename;
            $result->file = ROOT . $filename; unset($filename);
            
            $result->content = highlight_file($result->file, true);
            
            $loaded_result = $result; unset($result);
        
        endif;
        
        $this->active = $loaded_result; unset($loaded_result);
    
    }
    
    public function rescan_filesystem() {
    
        $scan = scandir(ROOT . $this->path); unset($dir);
            array_shift($scan);
            array_shift($scan);
        
        foreach ($scan as $name):
        
            if (is_dir(ROOT . $name)):
            
                if (!in_array($name, $this->exclude->folders)):
            
                    $this->folder_count++;
                    
                    array_push($this->folders, $name);
                    $this->save_dir($name);
                
                endif;
            
            else:
            
                if (preg_match($this->regex, $name)):
                
                    if (!in_array($name, $this->exclude->files)):
                    
                        $this->bitsize += filesize(ROOT . $name);
                        $this->line_count += count(file(ROOT . $name));
                        $this->file_count++;
                        
                        array_push($this->files, $name);
                    
                    endif;
                
                elseif (preg_match("#[jpg|jpeg|gif|png]$#i", $name)):
                
                    $this->image_count++;
                
                endif;
            
            endif;
        
        endforeach; unset($scan, $name);
    
    }
    
    public function save_dir($path) {
    
        if (is_dir(ROOT . $path)):
        
            $scan = scandir(ROOT . $path);
            array_shift($scan);
            array_shift($scan);
            
            foreach ($scan as $name):
            
                $filename = $path . "/" . $name;
                
                if (is_dir(ROOT . $filename)):
                
                    if (!in_array($filename, $this->exclude->folders)):
                    
                        $this->folder_count++;
                        
                        array_push($this->folders, $filename);
                        $this->save_dir($filename);
                    
                    endif;
                
                else:
                
                    if (preg_match($this->regex, $name)):
                    
                        if (!in_array($filename, $this->exclude->files)):
                        
                            $this->bitsize += filesize(ROOT . $filename);
                            $this->line_count += count(file(ROOT . $filename));
                            $this->file_count++;
                            
                            array_push($this->files, $filename);
                        
                        endif;
                    
                    elseif (preg_match("#[jpg|jpeg|gif|png]$#i", $name)):
                    
                        if (preg_match("/^layout/i", $filename)):
                        
                            $this->layout_image_count++;
                        
                        else:
                        
                            $this->image_count++;
                        
                        endif;
                    
                    endif;
                
                endif;
            
            endforeach;
        
        endif;
    
    }

}

?>
