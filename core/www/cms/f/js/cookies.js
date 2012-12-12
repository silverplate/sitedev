function setCookie(name, value, expire, path) {
	var e = isNaN(expire) ? 0 : expire;
	var curDate = new Date();
	var cookieExpire = new Date(Date.parse(curDate.toUTCString()) + e * 24 * 60 * 60 * 1000);
	var cookiePath = path ? '; path=' + path : '';
	document.cookie = name + '=' + escape(value) + (e ? ('; expires=' + cookieExpire.toUTCString()) : '') + cookiePath;
}

function getCookie(name) {
	var search = name + '=';
	if (document.cookie.length > 0) {
		offset = document.cookie.indexOf(search);
		if (offset != -1) {
			offset += search.length;
			end = document.cookie.indexOf(';', offset);
			if (end == -1) end = document.cookie.length;
			return unescape(document.cookie.substring(offset, end));
		}
	}
	return '';
}

function removeFromCookieList(name, value, expire) {
	cookieList(name, value, expire);
}

function saveIntoCookieList(name, value, expire) {
	cookieList(name, value, expire, value);
}

function cookieList(name, value, expire, init) {
	var cookies = getCookie(name).split('|');
	var newCookies = new Array(init);

	for (var i = 0; i < cookies.length; i++) {
		if (cookies[i] != '' && cookies[i] != value) {
			newCookies.push(cookies[i]);
		}
	}

	if (newCookies.length > 0) {
		setCookie(name, newCookies.join('|'), expire);
	} else {
		setCookie(name, '', -1);
	}
}

function isCookie(name, value) {
	var cookies = getCookie(name).split('|');

	for (var i = 0; i < cookies.length; i++) {
		if (cookies[i] != '' && cookies[i] == value) {
			return true;
		}
	}

	return false;
}