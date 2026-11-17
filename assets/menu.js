(function () {
	var secs = [].slice.call(document.querySelectorAll('.mn-sec'));

	secs.forEach(function (s) {
		var body = s.querySelector('.mn-sec-body');
		if (!body) return;
		[].slice.call(body.childNodes).forEach(function (n) {
			if (n.nodeType !== 3 || !n.textContent.trim()) return;
			var p = document.createElement('p');
			p.textContent = n.textContent.trim();
			body.replaceChild(p, n);
		});
	});
	if (window.jQuery && jQuery.fancybox) {
		jQuery('.mn-zoom').fancybox({ loop: false, buttons: ['close'] });
	}

})();
