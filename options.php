<?php

class Options
{
    
    protected $options = array();
      
    public function setValue($index,$value)
    {
        $this->options[$index] = $value;
    }
    
    public function getValue($index)
    {
        return $this->options[$index];
    }    
    
    public function loadOptions($filePath)
    {
        $contents = file_get_contents($filePath);    
        $contents = explode("\n",$contents);

        foreach($contents as $content)
        {
            list($index,$value) = explode('=',$content);
            
            if(is_numeric($value))
            {
                if(ctype_digit($value))
                    $value = (int) $value;
                else
                    $value = (float) $value;
            }
            elseif($value == 'true')
            {
                $value = true;
            }
            elseif($value == 'false')
            {
                $value = false;
            }
            $this->setValue($index,$value);
        }
    }
    
    public function saveOptions($filePath)
    {
        $content = "";
        
        foreach($this->options as $index => $value)
        {
            if($value === true)
                $value='true';
            elseif($value===false)
                $value='false';
                
            $content.= $index."=".$value."\n";
        }
        
        $content = substr($content,0,strlen($content)-1);
        
        file_put_contents($filePath,$content);
    }
}


$options = new Options();
$options->loadOptions('server.properties');
$options->setValue('white-list',false);
$options->saveOptions('server.properties');

?>