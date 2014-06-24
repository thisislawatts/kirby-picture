/*! jQuery Ajax Queue v0.1.2pre | (c) 2013 Corey Frang | Licensed MIT */
;(function(e){var r=e({});e.ajaxQueue=function(n){function t(r){u=e.ajax(n),u.done(a.resolve).fail(a.reject).then(r,r)}var u,a=e.Deferred(),i=a.promise();return r.queue(t),i.abort=function(o){if(u)return u.abort(o);var c=r.queue(),f=e.inArray(t,c);return f>-1&&c.splice(f,1),a.rejectWith(n.context||n,[i,o,""]),i},i}})(jQuery);

;(function($) {

	KirbyPicture = function() {

		if (window.KirbyPictures)
			this.init();
	}
	
	KirbyPicture.prototype.init = function() {
		var _self = this;

		for ( key in window.KirbyPictures ) {
			var url = window.KirbyPictures[key];
			_self.generateImage( key, url );
		};
	};

	KirbyPicture.prototype.generateImage = function(id, url) {

		var _self = this;

		return $.ajaxQueue({
			url: '/api', 
			type: 'POST',
			data: {
				action: 'kirby.picture',
				id: id,
				image: url
			},
			success: function(res) {
				var json = JSON.parse(res);
				
				console.log("Response", json );
				if (json.status === 'success')
					_self.updateImage(id, json.srcset );

			}
		})
	}

	KirbyPicture.prototype.updateImage = function( id, srcset ) {
		var $img = $('#' + id );
		$img.attr({
			'sizes' : '100vw',
			'srcset': srcset
		});

		$img.imagesLoaded().done(function(instance) {
			$(instance.elements[0]).parents('.images--block').addClass('s__loaded')
		})
		window.picturefill();
	}

	new KirbyPicture();


}(jQuery))