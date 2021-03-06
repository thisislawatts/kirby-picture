# Picture Plugin

## What is it?

The picture plugin makes it easy to include smaller, resized versions of your images in your pages without adding extra code or uploading smaller versions yourself. 

## Installation 

Put the picture/ directory in your site/plugins folder. Add the plugins folder if it isn't there yet. Add a pictures folder to the root dir of your site and make sure it is writable. If you want to change the location of your picture cache folder read more about the available default settings further down. 

Be sure to include the bundled version of picturefill.js in the head of your document.

	<?php echo js('site/plugins/kirby-picture/js/picturefill.min.js'); ?>

## How to use it?

	<?php picture($image); ?>

## Available settings

You can add the following config variables to your config file (site/config/config.php) to adjust the default settings of the thumb plugin:

```php
    c::set('picture.cache.root', c::get('root') . '/pictures');
    c::set('picture.cache.url', '/pictures');
    c::set('picture.quality', 100);
    c::set('picture.ajax', true);
    c::set('picture.breakpoints', array(
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
```


## Requirements

You must have GD Lib installed on your server for this plugin to work. 
	    
## Author
Luke Watts
<http://thisis.la>
