function openTest(e) {
	e.preventDefault();
	var xmlhttp = new XMLHttpRequest();
	var formData = new FormData(document.getElementById("feedback"));
	var errors;
	xmlhttp.open('POST', classPath, true);
	formData.set('ajax', 'Y'); // пишет в REQUEST
	formData.set('submit', 'Y');
	formData.set('signedParamsString', signedParams);
	xmlhttp.responseType = 'json';//приводим к типу json
	xmlhttp.setRequestHeader('X-REQUESTED-WITH', 'XMLHttpRequest'); // пишет в SERVER
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			//console.log(xmlhttp);
			if(xmlhttp.status == 200) {
				console.log(xmlhttp.response);
				// Сбрасиваем error класс с инпутов
				if (document.querySelectorAll('[class=error]').length > 0) {
					var clearErrors = document.querySelectorAll('[class=error]');
					for (var i = 0; i < clearErrors.length; i++) {
						clearErrors[i].className = "";
					}
				}
				// Сбрасываем сообщения об ошибках
				if (document.querySelectorAll('[class=error-message]').length > 0) {
					var clearMess = document.querySelectorAll('[class=error-message]');
					for (var i = 0; i < clearMess.length; i++) {
						clearMess[i].innerHTML = "";
					}
				}
				// Отображаем сообщения об ошибках + вешаем error класс на инпуты
				errors = xmlhttp.response.ERRORS;
				if (typeof errors != 'undefined') {
					for (var key in errors) {
						//console.log(key);
						document.querySelector('[name='+key).className = "error";
						document.querySelector('[name='+key).previousElementSibling.innerHTML = errors[key];
					}
				} else {
					// Пишем OK_MESSAGE + очищаем поле сообщения и каптчи
					document.querySelector('[class=mf-ok-text]').innerHTML = xmlhttp.response.OK_MESSAGE;
					var clearInputs = document.querySelectorAll('[data-id=clear-input]');
					for (var i = 0; i < clearInputs.length; i++) {
						clearInputs[i].value = "";
					}
				}
				// Обновляем каптчу
				if (typeof xmlhttp.response.capCode != 'undefined') {
					//console.log(xmlhttp.response.capCode);
					var captchaCode = xmlhttp.response.capCode;
					document.querySelector('[name=captcha_sid]').value=captchaCode;
					document.querySelector('[data-id=captcha_word]').src="/bitrix/tools/captcha.php?captcha_sid="+captchaCode;
				}
			}
		}
	};
	xmlhttp.send(formData);
	return false;
}
window.onload = function() {
	var form = document.querySelector("form[data-type=ajax]");
	if(form){
		form.addEventListener("submit", openTest, false);
	}
}