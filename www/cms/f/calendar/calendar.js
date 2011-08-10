/*** Общие функции (можно вынести в общий файл)
*********************************************************/
function element_position(ele) {
    this.x = ele.offsetLeft;
    this.y = ele.offsetTop;
    this.ele = ele;

    while (this.ele.offsetParent != null) {
        this.ele = this.ele.offsetParent;
        this.x += this.ele.offsetLeft;
        this.y += this.ele.offsetTop;
    }
}

function add_event(ele, type, func) {
	if (ele.addEventListener) {
		ele.addEventListener(type, func, false);

	} if (ele.attachEvent) {
		ele.attachEvent('on' + type, func);
	}
}

function remove_event(ele, type, func) {
	if (ele.removeEventListener) {
		ele.removeEventListener(type, func, false);

	} if (ele.detachEvent) {
		ele.detachEvent('on' + type, func);
	}
}

function cancel_event(e) {
	var evt = e ? e : window.event;
	evt.cancelBubble = true;
}


/*** Календарь
*********************************************************/
var calendars = new Array();

function get_calendar(name) {
	for (var i = 0; i < calendars.length; i++) {
		if (calendars[i].name == name) {
			return calendars[i];
		}
	}
	return false;
}

function calendar_init(name, lang) {
	calendars[calendars.length] = new calendar(name, lang);
}

function calendar_parse_input(name) {
	get_calendar(name).parse_input();
}

function calendar_remove() {
	for (var i = 0; i < calendars.length; i++) {
		calendars[i].remove();
	}
}

function calendar_switcher(name, e) {
	get_calendar(name).calendar(e);
}

function calendar_draw(name, y, m, d) {
	get_calendar(name).draw(y, m, d);
}

function calendar_set(name, y, m, d) {
	get_calendar(name).set(y, m, d);
}

function calendar(name, lang) {
	this.name = name;
	this.lang = lang != 'en' && lang != 'ru' ? 'ru' : lang;

	this.weekdays = this.lang == 'en'
		? new Array('M', 'T', 'W', 'T', 'F', 'S', 'S')
		: new Array('П', 'В', 'С', 'Ч', 'П', 'С', 'В');

	this.months = this.lang == 'en'
		? new Array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')
		: new Array('Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');

	this.get_ele_name = function() {
		return this.name + '_calendar_ele';
	}

	this.get_input_name = function() {
		return this.name + '_input';
	}

	this.get_ele = function() {
		return document.getElementById(this.get_ele_name());
	}

	this.get_date_ele = function() {
		return document.getElementById(this.name);
	}

	this.get_input_ele = function() {
		return document.getElementById(this.get_input_name());
	}

	this.string_to_date = function(string) {
		var regexp = /^\s*(\d+)[\s|\/|.|\-](.+)[\s|\/|.|\-](\d+)\s*$/i;
		var parse = regexp.exec(string);
		var now = new Date();
		var date = new Date(now.getFullYear(), now.getMonth(), now.getDate());

		if (parse) {
			if (parse[1].toString().length == 4) {
				date = new Date(parse[1], Number(parse[2]) - 1, Number(parse[3]));

			} else {
				if (isNaN(parse[2])) {
					date = new Date(parse[3], 0, parse[1]);

					for (var i = 0; i < this.months.length; i++) {
						if (this.months[i].substr(0, 3).toLowerCase() == parse[2].substr(0, 3).toLowerCase()) {
							date.setMonth(i);
							break;
						}
					}
				} else {
					date = this.lang == 'en'
						? new Date(parse[3], Number(parse[1]) - 1, parse[2])
						: new Date(parse[3], Number(parse[2]) - 1, parse[1]);
				}
			}
		}

		return date;
	}

	this.get_sql_date = function(date) {
		var m = (date.getMonth() + 1).toString();
		if (m.length == 1) m = '0' + m;

		var d = date.getDate().toString();
		if (d.length == 1) d = '0' + d;

		return date.getFullYear() + '-' + m + '-' + d;		
	}

	this.get_date = function() {
		return this.string_to_date(this.get_input_ele().value);
	}

	this.set_date = function(y, m, d) {
		var date = new Date(y, m, d);
		this.get_input_ele().value = date.getDate() + ' ' + this.months[date.getMonth()].substr(0, 3).toLowerCase() + ' ' + date.getFullYear();
		this.get_date_ele().value = this.get_sql_date(date);
	}

	this.clear_date = function() {
		this.get_input_ele().value = '';
		this.get_date_ele().value = '';
	}

	this.set = function(y, m, d) {
		this.set_date(y, m, d);
		this.remove();
	}

	this.calendar = function(e) {
		cancel_event(e);

		if (this.get_ele()) {
			this.remove();

		} else {
			calendar_remove();
			this.draw();
		}
	}

	this.remove = function() {
		if (this.get_ele()) {
			this.get_ele().parentNode.removeChild(this.get_ele());
			remove_event(document, 'click', calendar_remove);
		}
	}

	this.parse_input = function() {
		if (this.get_input_ele().value) {
			var date = this.get_date();
			this.set_date(date.getFullYear(), date.getMonth(), date.getDate());
		} else {
			this.clear_date();
		}
	}

	this.init = function() {
		if (this.get_date_ele().value && this.get_date_ele().value != '0000-00-00') {
			var date = this.string_to_date(this.get_date_ele().value);
			this.set_date(date.getFullYear(), date.getMonth(), date.getDate());
		}
	}

	this.draw = function(y, m, d) {
		this.remove();

		var body_ele = document.getElementsByTagName('body')[0];
		var ele = document.createElement('div');
		var pos = new element_position(this.get_input_ele());

		ele.className = 'calendar';
		ele.id = this.get_ele_name();
		ele.style.left = pos.x + 'px';
		ele.style.top = pos.y + this.get_input_ele().offsetHeight + 5 + 'px';

		body_ele.appendChild(ele);
		add_event(ele, 'click', cancel_event);
		add_event(document, 'click', calendar_remove);

		var now = this.get_date();
		var calendar = y ? new Date(y, m, d) : this.get_date();
		var y = calendar.getFullYear();
		var m = calendar.getMonth();
		var d = calendar.getDate();
		var prev_m = m == 0 ? new Date(y - 1, 11, 1) : new Date(y, m - 1, 1);
		var next_m = m == 11 ? new Date(y + 1, 0, 1) : new Date(y, m + 1, 1);

		var content = '<table><thead>';
		content += '<tr class="year"><td><a class="calc_arr_l" onclick="calendar_draw(\'' + this.name + '\',' + (y - 1) + ',' + m + ',' + d + '); return false;"><img src="/cms/f/calendar/left.gif" width="4" height="7" alt="" /></a></td>';
		content += '<td><a class="calc_arr_l" onclick="calendar_draw(\'' + this.name + '\',' + (y - 10) + ',' + m + ',' + d + '); return false;"><img src="/cms/f/calendar/left.gif" width="4" height="7" alt="" /><img src="/cms/f/calendar/left.gif" width="4" height="7" alt="" /></a></td>';
		content += '<td colspan="3">' + y + '</td>';
		content += '<td><a class="calc_arr_r" onclick="calendar_draw(\'' + this.name + '\',' + (y + 10) + ',' + m + ',' + d + '); return false;"><img src="/cms/f/calendar/right.gif" width="4" height="7" alt="" /><img src="/cms/f/calendar/right.gif" width="4" height="7" alt="" /></a></td>';
		content += '<td><a class="calc_arr_r" onclick="calendar_draw(\'' + this.name + '\',' + (y + 1) + ',' + m + ',' + d + '); return false;"><img src="/cms/f/calendar/right.gif" width="4" height="7" alt="" /></a></td></tr>';
		content += '<tr class="month"><td><a class="calc_arr_l" onclick="calendar_draw(\'' + this.name + '\',' + prev_m.getFullYear() + ',' + prev_m.getMonth() + ',' + prev_m.getDate() + '); return false;"><img src="/cms/f/calendar/left.gif" width="4" height="7" alt="" /></a></td>';
		content += '<td colspan="5">' + this.months[m] + '</td>';
		content += '<td><a class="calc_arr_r" onclick="calendar_draw(\'' + this.name + '\',' + next_m.getFullYear() + ',' + next_m.getMonth() + ',' + next_m.getDate() + '); return false;"><img src="/cms/f/calendar/right.gif" width="4" height="7" alt="" /></a></td></tr>';

		content += '<tr class="weekdays">';
		for (var i = 0; i < this.weekdays.length; i++) {
			content += '<td' + (i > 4 ? ' class="weekend"' : '') + '>' + this.weekdays[i] + '</td>';
		}
		content += '</tr></thead><tbody><tr>';

		while (!(calendar.getDay() == 1 && (calendar.getDate() == 1 || calendar.getMonth() != m))) {
			calendar.setDate(calendar.getDate() - 1);
		}

		var class_name = '';
		while (!(calendar.getDay() == 1 && calendar.getMonth() == next_m.getMonth())) {
			if (calendar.getMonth() != m) class_name += 'notnow';
			if (calendar.getDay() == 0 || calendar.getDay() == 6) class_name += (class_name ? ' ' : '') + 'weekend';
			if (calendar.valueOf() == now.valueOf()) class_name += (class_name ? ' ' : '') + 'selected';

			content += '<td' + (class_name ? ' class="' + class_name + '"' : '') + '>';
			content += '<a onclick="calendar_set(\'' + this.name + '\',' + calendar.getFullYear() + ',' + calendar.getMonth() + ',' + calendar.getDate() + '); return false;">' + calendar.getDate() + '</a></td>';

			if (calendar.getDay() == 0) content += '</tr><tr>';
			calendar.setDate(calendar.getDate() + 1);
			class_name = '';
		}

		content += '</tr></tbody></table>';
		ele.innerHTML = content;
	}

	this.init();
}