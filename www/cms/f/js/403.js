function password_reminder(is_on) {
	var link = document.getElementById('auth_forgot');
	var reminder = document.getElementById('auth_reminder');

	link.style.display = is_on ? 'none' : 'block';
	reminder.style.display = is_on ? 'block' : 'none';
}
