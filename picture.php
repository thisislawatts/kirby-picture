<?php

/**
 * [picture description]
 * @param  Image  $obj     [description]
 * @param  array   $options [description]
 * @return [type]           [description]
 */


class Picture {

  var $thumbs;
  
  function __construct( $image, $options = array() ) {

  	$sizes = self::getSizes();

  	$this->thumbs = array();

  	foreach ( $sizes as $size ) {
  		$t = new Thumb( $image, $size );

  		$this->thumbs[] = sprintf('%s %sw',
  			$t->url(),
  			array_shift($size)
  		);
  	}

  	$this->attributes = array (
  		'class' => a::get( $options, 'class' )
  	);

  	return;

    if ( gettype($image) === 'string' )
      $image = self::getImageFromUrl( $image );

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
    $this->maxWidth     = a::get($options, 'width', false);
    $this->maxHeight    = a::get($options, 'height', false);

    $this->data = a::get($options, 'data', array() );

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
  
    // set the alt text
    $this->alt = a::get($options, 'alt', $this->obj->name());

    // set the className text
    $this->className = @$options['class'];

    $this->id = md5( $image->url() );

    $this->generateImages();
  }

  public static function getImageFromUrl( $url ) {

    $kirby = kirby();

    $str = str_replace($kirby->urls->index(), $kirby->roots->index(), $url );

    return new Picture( new Media( $str ) );
  }

  function tag() {

  	return html::tag('img', null, array_merge(
  		$this->attributes,
  		array(
	  		'srcset' => implode(',', $this->thumbs ),
	  	)
  	) );
  }

  function className() {
  	return '';
  }

  function dataAttr() {

    $dataString = '';

    foreach ( $this->data as $property => $value ) {
      if ($value)
        $dataString .= ' data-' . $property . '="' . $value . '"';
    }

    return $dataString;
  }
  
  /**
   * Creates a filename for the generated image
   * based on it's original filename
   * @return [type] [description]
   */
  function filename() {
  
    $options = false;
  
    $options .= ($this->maxWidth)  ? '.' . $this->maxWidth  : '.' . 0;
    $options .= ($this->maxHeight) ? '.' . $this->maxHeight : '.' . 0;

    return md5( $this->source ) . $options . '.' . $this->obj->extension();
  }
      
  function file() {

    return $this->root . '/' . $this->filename();
  }

  function url() {
    return (error($this->status)) ? $this->obj->url() : $this->url . '/' . $this->filename() . '?' . filemtime($this->file());
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

      $sourceRatio = $this->sourceWidth / $this->sourceHeight;
      $thumbRatio  = $maxWidth / $maxHeight;
                      
      // if($sourceRatio > $thumbRatio) {
      //   // fit the height of the source
      //   $size = size::fit_height($this->sourceWidth, $this->sourceHeight, $maxHeight, true);
      // } else {
      //   // fit the height of the source
      //   $size = size::fit_width($this->sourceWidth, $this->sourceHeight, $maxWidth, true);                
      // }

      $size = array(
      	'width' => $this->sourceWidth,
      	'height' => $this->sourceHeight
      );
                          
      $this->tmpWidth  = $size['width'];
      $this->tmpHeight = $size['height'];
      $this->width     = $maxWidth;
      $this->height    = $maxHeight;
          
      return $size;

    }
        
    // // if there's a maxWidth and a maxHeight
    // if($maxWidth && $maxHeight) {
      
    //   // if the source width is bigger then the source height
    //   // we need to fit the width
    //   if($this->sourceWidth > $this->sourceHeight) {
    //     $size = size::fit_width($this->sourceWidth, $this->sourceHeight, $maxWidth, $upscale);
        
    //     // do another check for the maxHeight
    //     if($size['height'] > $maxHeight) $size = size::fit_height($size['width'], $size['height'], $maxHeight);
        
    //   } else {
    //     $size = size::fit_height($this->sourceWidth, $this->sourceHeight, $maxHeight, $upscale);                    

    //     // do another check for the maxWidth
    //     if($size['width'] > $maxWidth) $size = size::fit_width($size['width'], $size['height'], $maxWidth);

    //   }
                
    // } elseif($maxWidth) {
    //   $size = size::fit_width($this->sourceWidth, $this->sourceHeight, $maxWidth, $upscale);
    // } elseif($maxHeight) {
    //   $size = size::fit_height($this->sourceWidth, $this->sourceHeight, $maxHeight, $upscale);
    // } else {
    //   $size = array('width' => $this->sourceWidth, 'height' => $this->sourceHeight);
    // }

    $size = array(
		'width' => $this->sourceWidth,
		'height' => $this->sourceHeight
	);


    $this->width  = $size['width'];
    $this->height = $size['height'];
    
    return $size;
        
  }

  public static function getSizes() {
    return c::get('picture.sizes', array(
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
    ) ) );
  }
  
  function create() {
    
    $file = $this->file();

    if(!function_exists('gd_info')) {
      $this->status = 'error';
      throw new Exception('GD Lib is not installed');

      return $this;
    }

    if(file_exists($file) && (@filectime($this->source) < @filectime($file) || filemtime($this->source) < filemtime($file))) {

      array_push( $this->sources, $this->url . '/' . $this->filename() );

      $this->status = 'success';
      throw new Exception('The file exists');

      return $this;
    }


    if(!is_writable(dirname($file))) {

      $this->status = 'error';
      throw new Exception('The image file is not writable: ' . dirname( $file ) );

      return $this;
    }

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
        $this->status = 'error';
        throw new Exception('The image mime type is invalid');
        return $this;
        break;
    }   

    if(is_null($image)) {
      
      $this->status = 'error';
      throw new Exception('The image could not be created');

      return $this;
    }


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

    $this->status = 'success';
    $this->msg    = 'The image has been created: ' . $file;

    return $this;
  }
}


kirbytext::$tags['image'] = array(
  'attr' => array(
    'width',
    'height',
    'alt',
    'text',
    'title',
    'class',
    'imgclass',
    'linkclass',
    'caption',
    'link',
    'target',
    'popup',
    'rel'
  ),
  'html' => function($tag) {

    $url     = $tag->attr('image');
    $alt     = $tag->attr('alt');
    $title   = $tag->attr('title');
    $link    = $tag->attr('link');
    $caption = $tag->attr('caption');
    $file    = $tag->file($url);

    // use the file url if available and otherwise the given url
    $url = $file ? $file->url() : url($url);

    // alt is just an alternative for text
    if($text = $tag->attr('text')) $alt = $text;

    // try to get the title from the image object and use it as alt text
    if($file) {

      if(empty($alt) and $file->alt() != '') {
        $alt = $file->alt();
      }

      if(empty($title) and $file->title() != '') {
        $title = $file->title();
      }

    }

    if(empty($alt)) $alt = pathinfo($url, PATHINFO_FILENAME);

    // link builder
    $_link = function($image) use($tag, $url, $link, $file) {

      if(empty($link)) return $image;

      // build the href for the link
      if($link == 'self') {
        $href = $url;
      } else if($file and $link == $file->filename()) {
        $href = $file->url();
      } else {
        $href = $link;
      }

      return html::a(url($href), $image, array(
        'rel'    => $tag->attr('rel'),
        'class'  => $tag->attr('linkclass'),
        'title'  => $tag->attr('title'),
        'target' => $tag->target()
      ));

    };

    // image builder
    $_image = function($class) use($tag, $url, $alt, $title) {
    	$picture = picture::getImageFromUrl( $url );

      return $picture->tag();

      return html::tag( 'img', null, array(
      	'srcset' => $t->url(),
        'width'  => $tag->attr('width'),
        'height' => $tag->attr('height'),
        'class'  => $class,
        'title'  => $title,
        'alt'    => $alt
      ));
    };

    if(kirby()->option('kirbytext.image.figure') or !empty($caption)) {
      $image  = $_link($_image($tag->attr('imgclass')));
      $figure = new Brick('figure');
      $figure->addClass($tag->attr('class'));
      $figure->append($image);
      if(!empty($caption)) {
        $figure->append('<figcaption>' . html($caption) . '</figcaption>');
      }
      return $figure;
    } else {
      $class = trim($tag->attr('class') . ' ' . $tag->attr('imgclass'));
      return $_link($_image($class));
    }

  }
);

function picture($obj, $options=array()) {
  $picture = new Picture($obj, $options);
  echo $picture->tag();
};
