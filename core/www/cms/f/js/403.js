function passwordReminder(_isOn)
{
	var link = document.getElementById("auth_forgot");
	var reminder = document.getElementById("auth_reminder");

	link.style.display = _isOn ? "none" : "block";
	reminder.style.display = _isOn ? "block" : "none";
}
