<?php

function picture($obj, $options=array(), $tag=true) {
  $picture = new Picture($obj, $options);
  return ($tag) ? $picture->tag() : $picture->url();
}

class Picture {

  var $obj = null;
  var $root = false;
  var $url = false;
  var $sourceWidth = 0;
  var $sourceHeight = 0;
  var $width = 0;
  var $height = 0;
  var $tmpWidth = 0;
  var $tmpHeight = 0;
  var $maxWidth = 0;
  var $maxHeight = 0;
  var $mime = false;
  var $status = array();
  var $upscale = false;
  var $quality = 100;
  var $alt = false;
  
  // Options
  var $crop = false;
  var $grayscale = false;
  var $datauri = false;
  var $lazyload = false;

  var $sources = array();
  var $sizes = array(
    array(
      'width'      => 1600,
      'height'     => 1600,
    ),
    array(
      'width'      => 1280,
      'height'     => 1280,
    ),
    array(
      'width'      => 800,
      'height'     => 800,
    ),
    array(
      'width'      => 400,
      'height'     => 400,
     )
  );

  function __construct($image, $options=array()) {


    $this->root = c::get('picture.cache.root', c::get('root') . '/pictures');
    $this->url  = c::get('picture.cache.url',  c::get('url')  . '/pictures');

    if(!$image) return false;
    
    $this->obj = $image;
    
    // set some values from the image
    $this->sourceWidth  = $this->obj->width();
    $this->sourceHeight = $this->obj->height();
    $this->width        = $this->sourceWidth;
    $this->height       = $this->sourceHeight;
    $this->source       = $this->obj->root();
    $this->mime         = $this->obj->mime();
    
    // set the max width and height
    $this->maxWidth     = @$options['width'];
    $this->maxHeight    = @$options['height'];

    // set lazyload on/off
    $this->lazyload = @$options['lazyload'];

    // set crop on/off
    $this->crop = @$options['crop'];
    
    // set greyscale on/off
    $this->grayscale = @$options['grayscale'];
	
    // set the quality
    $this->quality = a::get($options, 'quality', c::get('picture.quality', 100));

    // set the default upscale behavior
    $this->upscale = a::get($options, 'upscale', c::get('picture.upscale', false));
	
    // set datauri on/off
    $this->datauri = a::get($options, 'datauri', c::get('picture.datauri', false));

    // set the alt text
    $this->alt = a::get($options, 'alt', $this->obj->name());

    // set the className text
    $this->className = @$options['class'];

//    $this->create();

    // Create our images at different sizes!
    foreach ( $this->sizes as $size ) {
      $this->size( $size['width'], $size['height'] );
      $this->create();
    }
  }

  function srcset() {

    $sources = array_reverse($this->sources);

    $srcset = array();

    foreach ($sources as $src) {

      $parts = explode('.', $src);

      $srcset[] = $src . ' ' . $parts[1] . 'w';
    }

    return implode( ', ' , $srcset );
  }
  
  function tag() {

    if(!$this->obj) return false;
    
    $class = (!empty($this->className)) ? ' class="' . $this->className . '"' : '';
    
    return sprintf('<img %1$s data-width="%2$s" data-height="%3$s" %4$s %5$s alt="%6$s" />',
      $class,
      $this->obj->width(),
      $this->obj->height(),
      $this->lazyload ? 'data-sizes="100vw"' : 'sizes="100vw"',
      $this->lazyload ? 'data-srcset="' . $this->srcset() . '"' : 'srcset="' . $this->srcset() . '"',
      html( $this->alt )
    );
  }
  
  function filename() {
  
    $options = false;
  
    $options .= ($this->maxWidth)  ? '.' . $this->maxWidth  : '.' . 0;
    $options .= ($this->maxHeight) ? '.' . $this->maxHeight : '.' . 0;
    $options .= ($this->upscale)   ? '.' . $this->upscale   : '.' . 0;
    $options .= ($this->crop)      ? '.' . $this->crop      : '.' . 0;
    $options .= ($this->grayscale) ? '.' . $this->grayscale : '.' . 0;
    $options .= '.' . $this->quality;

    return md5($this->source) . $options . '.' . $this->obj->extension;

  }
      
  function file() {
    return $this->root . '/' . $this->filename();
  }

  function url() {
    if($this->datauri == true) {
      return (error($this->status)) ? $this->obj->url() : 'data:' . $this->mime . ';base64,' . base64_encode(file_get_contents($this->file()));
  	} else {
        return (error($this->status)) ? $this->obj->url() : $this->url . '/' . $this->filename() . '?' . filemtime($this->file());
  	}
  }
  
  function size( $width = false , $height = false ) {
        

    if ($width) {
      $this->maxWidth = $width;
    }

    if ($height) {
      $this->maxHeight = $height;
    }


    $maxWidth  = $this->maxWidth;
    $maxHeight = $this->maxHeight;
    $upscale   = $this->upscale;    


    
    if($this->crop == true) {

      if(!$maxWidth)  $maxWidth  = $maxHeight;      
      if(!$maxHeight) $maxHeight = $maxWidth;      

      $sourceRatio = size::ratio($this->sourceWidth, $this->sourceHeight);
      $thumbRatio  = size::ratio($maxWidth, $maxHeight);
                      
      if($sourceRatio > $thumbRatio) {
        // fit the height of the source
        $size = size::fit_height($this->sourceWidth, $this->sourceHeight, $maxHeight, true);
      } else {
        // fit the height of the source
        $size = size::fit_width($this->sourceWidth, $this->sourceHeight, $maxWidth, true);                
      }
                          
      $this->tmpWidth  = $size['width'];
      $this->tmpHeight = $size['height'];
      $this->width     = $maxWidth;
      $this->height    = $maxHeight;
          
      return $size;

    }
        
    // if there's a maxWidth and a maxHeight
    if($maxWidth && $maxHeight) {
      
      // if the source width is bigger then the source height
      // we need to fit the width
      if($this->sourceWidth > $this->sourceHeight) {
        $size = size::fit_width($this->sourceWidth, $this->sourceHeight, $maxWidth, $upscale);
        
        // do another check for the maxHeight
        if($size['height'] > $maxHeight) $size = size::fit_height($size['width'], $size['height'], $maxHeight);
        
      } else {
        $size = size::fit_height($this->sourceWidth, $this->sourceHeight, $maxHeight, $upscale);                    

        // do another check for the maxWidth
        if($size['width'] > $maxWidth) $size = size::fit_width($size['width'], $size['height'], $maxWidth);

      }
                
    } elseif($maxWidth) {
      $size = size::fit_width($this->sourceWidth, $this->sourceHeight, $maxWidth, $upscale);
    } elseif($maxHeight) {
      $size = size::fit_height($this->sourceWidth, $this->sourceHeight, $maxHeight, $upscale);
    } else {
      $size = array('width' => $this->sourceWidth, 'height' => $this->sourceHeight);
    }

    $this->width  = $size['width'];
    $this->height = $size['height'];
    
    return $size;
        
  }
  
  function create() {
    
    $file = $this->file();            

    if(!function_exists('gd_info')) return $this->status = array(
      'status' => 'error',
      'msg'    => 'GD Lib is not installed'
    );

    if(file_exists($file) && (filectime($this->source) < filectime($file) || filemtime($this->source) < filemtime($file))) {

      array_push( $this->sources, $this->url . '/' . $this->filename() );

      return $this->status = array(
        'status' => 'success',
        'msg'    => 'The file exists: '
      );
    }

    if(!is_writable(dirname($file))) return $this->status = array(
      'status' => 'error',
      'msg'    => 'The image file is not writable'
    );


    switch($this->mime) {
      case 'image/jpeg':
        $image = @imagecreatefromjpeg($this->source); 
        break;
      case 'image/png':
        $image = @imagecreatefrompng($this->source); 
        break;
      case 'image/gif':
        $image = @imagecreatefromgif($this->source); 
        break;
      default:
        return $this->status = array(
          'status' => 'error',
          'msg'    => 'The image mime type is invalid'
        );
        break;
    }       

    if(!$image) return array(
      'status' => 'error',
      'msg'    => 'The image could not be created'
    );
              
    // make enough memory available to scale bigger images (option should be something like 36M)
    if(c::get('picture.memory')) ini_set('memory_limit', c::get('picture.memory'));


    if($this->crop == true) {

      // Starting point of crop
      $startX = floor($this->tmpWidth  / 2) - floor($this->width / 2);
      $startY = floor($this->tmpHeight / 2) - floor($this->height / 2);
          
      // Adjust crop size if the image is too small
      if($startX < 0) $startX = 0;
      if($startY < 0) $startY = 0;
      
      // create a temporary resized version of the image first
      $thumb = imagecreatetruecolor($this->tmpWidth, $this->tmpHeight); 
      imagesavealpha($thumb, true);
      $color = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
      imagefill($thumb, 0, 0, $color);
      imagecopyresampled($thumb, $image, 0, 0, 0, 0, $this->tmpWidth, $this->tmpHeight, $this->sourceWidth, $this->sourceHeight); 
      
      // crop that image afterwards      
      $cropped = imagecreatetruecolor($this->width, $this->height); 
      imagesavealpha($cropped, true);
      $color   = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
      imagefill($cropped, 0, 0, $color);
      imagecopyresampled($cropped, $thumb, 0, 0, $startX, $startY, $this->tmpWidth, $this->tmpHeight, $this->tmpWidth, $this->tmpHeight); 
      imagedestroy($thumb);
      
      // reasign the variable
      $thumb = $cropped;

    } else {


      $thumb = imagecreatetruecolor($this->width, $this->height); 
      imagesavealpha($thumb, true);
      $color = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
      imagefill($thumb, 0, 0, $color);
      imagecopyresampled($thumb, $image, 0, 0, 0, 0, $this->width, $this->height, $this->sourceWidth, $this->sourceHeight); 
    }    

	if($this->grayscale == true) {
		imagefilter($thumb, IMG_FILTER_GRAYSCALE);
	}
    
    switch($this->mime) {
      case 'image/jpeg': imagejpeg($thumb, $file, $this->quality); break;
      case 'image/png' : imagepng($thumb, $file, 0); break; 
      case 'image/gif' : imagegif($thumb, $file); break;
    }

    imagedestroy($thumb);

    return $this->status = array(
      'status' => 'success',
      'msg'    => 'The image has been created: ' . $file,
    );
  }
}