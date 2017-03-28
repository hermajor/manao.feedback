<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

class CFeedbackManao extends CBitrixComponent
{
	private $user = null;
	private $application = null;
	private $server = null;
	private $arErrors = array();
	private $isAjax = false;
	//конструктор
	public function init()
	{
		global $USER;
		$this->user = $USER;
		$this->server = Bitrix\Main\Context::getCurrent()->getServer();//$_SERVER
		$this->application = Bitrix\Main\Application::getInstance();
		$this->setParams();//заполняем $arParams
		$this->arResult["PARAMS_HASH"] = md5(serialize($this->arParams).$this->GetTemplateName());
		if ($this->isUseCaptcha()) {//если нужна капча - подключаем файл
			include_once($this->server->get("DOCUMENT_ROOT")."/bitrix/modules/main/classes/general/captcha.php");
			$this->cpt = new CCaptcha();
			
			$this->arResult["capCode"] = $this->getCaptchaCode();
		}
		$this->isAjax = ($this->server->get("HTTP_X_REQUESTED_WITH") == 'XMLHttpRequest') && ($this->request->get("ajax") == 'Y');
		//$class_methods = get_class_methods('CBitrixComponent');
		//echo '$class_methods <pre>'.print_r($class_methods, true).'</pre>';
		//echo '<pre>'.print_r($this->server, true).'</pre>';
		$this->arResult["CLASS_PATH"] = $this->GetPath();
	}
 
	public function setParams()
	{
		$this->arParams["USE_CAPTCHA"] = $this->isUseCaptcha() ? "Y" : "N";
		$this->arParams["EVENT_NAME"] = trim($this->arParams["EVENT_NAME"]);
		if($this->arParams["EVENT_NAME"] == '')
			$this->arParams["EVENT_NAME"] = "FEEDBACK_FORM";
		$this->arParams["EMAIL_TO"] = trim($this->arParams["EMAIL_TO"]);
		if($this->arParams["EMAIL_TO"] == '')
			$this->arParams["EMAIL_TO"] = COption::GetOptionString("main", "email_from");
		$this->arParams["OK_TEXT"] = trim($this->arParams["OK_TEXT"]);
		if($this->arParams["OK_TEXT"] == '') {
			$this->arParams["OK_TEXT"] = GetMessage("MF_OK_MESSAGE");
		}
	}
	
	public function isUseCaptcha()
	{
		return ($this->arParams["USE_CAPTCHA"] != "N" && !$this->user->IsAuthorized());
	}
	
	public function checkCaptcha()
	{
		$captcha_code = $this->request->get("captcha_sid");
		$captcha_word = $this->request->get("captcha_word");
		$captchaPass = COption::GetOptionString("main", "captcha_password", "");

		if (strlen($captcha_word) > 0 && strlen($captcha_code) > 0)
		{
			if (!$this->cpt->CheckCodeCrypt($captcha_word, $captcha_code, $captchaPass))
				//$this->arErrors["captcha_sid"] = GetMessage("MF_CAPTCHA_WRONG");
				$this->arErrors["captcha_word"] = GetMessage("MF_CAPTCHA_WRONG");
		}
		else
			$this->arErrors["captcha_word"] = GetMessage("MF_CAPTHCA_EMPTY");
	}
	
	public function getCaptchaCode()
    {
        $this->cpt->SetCode();
		
        return htmlspecialcharsbx($this->cpt->GetSID());
	}
	
	public function isSubmit()
	{
		return $this->server->get("REQUEST_METHOD") == "POST" && $this->request->get("submit") <> '' && ((!$this->request->get("PARAMS_HASH")) || $this->arResult["PARAMS_HASH"] === $this->request->get("PARAMS_HASH"));
	}
	
	public function isEmailValid()
	{
		return strlen($this->request->get("user_email")) > 1 && !check_email($this->request->get("user_email"));
	}
	
	public function checkRequiredFields()
	{
		if(empty($this->arParams["REQUIRED_FIELDS"]) || !in_array("NONE", $this->arParams["REQUIRED_FIELDS"]))
		{
			if((empty($this->arParams["REQUIRED_FIELDS"]) || in_array("NAME", $this->arParams["REQUIRED_FIELDS"])) && strlen($this->request->get("user_name")) <= 1)
				$this->arErrors["user_name"] = GetMessage("MF_REQ_NAME");			
			if((empty($this->arParams["REQUIRED_FIELDS"]) || in_array("EMAIL", $this->arParams["REQUIRED_FIELDS"])) && strlen($this->request->get("user_email")) <= 1)
				$this->arErrors["user_email"] = GetMessage("MF_REQ_EMAIL");
			if((empty($this->arParams["REQUIRED_FIELDS"]) || in_array("MESSAGE", $this->arParams["REQUIRED_FIELDS"])) && strlen($this->request->get("MESSAGE")) <= 3)
				$this->arErrors["MESSAGE"] = GetMessage("MF_REQ_MESSAGE");
		}	
	}
	
	public function isErrors()
	{
		return (empty($this->arErrors));
	}
	
	public function sendMessage()
	{
		$arFields = Array(
			"AUTHOR" => $this->request->get("user_name"),
			"AUTHOR_EMAIL" => $this->request->get("user_email"),
			"EMAIL_TO" => $this->arParams["EMAIL_TO"],
			"TEXT" => $this->request->get("MESSAGE"),
		);
		if(!empty($this->arParams["EVENT_MESSAGE_ID"]))
		{
			foreach($this->arParams["EVENT_MESSAGE_ID"] as $v)
				if(IntVal($v) > 0)
					CEvent::Send($this->arParams["EVENT_NAME"], SITE_ID, $arFields, "N", IntVal($v));
		}
		else
			CEvent::Send($this->arParams["EVENT_NAME"], SITE_ID, $arFields);
		$_SESSION["MF_NAME"] = htmlspecialcharsbx($this->request->get("user_name"));
		$_SESSION["MF_EMAIL"] = htmlspecialcharsbx($this->request->get("user_email"));
	}
	public function redirect()
	{
		$uriString = $this->application->getContext()->getRequest()->getRequestUri();
		$uri = new Bitrix\Main\Web\Uri($uriString);
		$uri->deleteParams(array("success"));
		$uri->addParams(array("success" => $this->arResult["PARAMS_HASH"]));
		LocalRedirect($uri->getUri());
	}
	
	public function formAutocomplete()
	{
		if (!$this->isErrors())
		{
			$this->arResult["MESSAGE"] = htmlspecialcharsbx($this->request->get("MESSAGE"));
			$this->arResult["AUTHOR_NAME"] = htmlspecialcharsbx($this->request->get("user_name"));
			$this->arResult["AUTHOR_EMAIL"] = htmlspecialcharsbx($this->request->get("user_email"));
		}
		else
		{
			if($this->user->IsAuthorized())
			{
				$this->arResult["AUTHOR_NAME"] = $this->user->GetFormattedName(false);
				$this->arResult["AUTHOR_EMAIL"] = htmlspecialcharsbx($this->user->GetEmail());
			}
			else
			{
				if(strlen($_SESSION["MF_NAME"]) > 0)
					$this->arResult["AUTHOR_NAME"] = htmlspecialcharsbx($_SESSION["MF_NAME"]);
				if(strlen($_SESSION["MF_EMAIL"]) > 0)
					$this->arResult["AUTHOR_EMAIL"] = htmlspecialcharsbx($_SESSION["MF_EMAIL"]);
			}
		}
	}
	
	public function validate()
	{
		$this->arErrors = array();
		
		if(!check_bitrix_sessid())
		{
			$this->arErrors["SESS_EXP"] = GetMessage("MF_SESS_EXP");
			return false;
		}
		$this->checkRequiredFields();
		
		if($this->isEmailValid()) {
			//$this->arErrors["EMAIL_NOT_VALID"] = GetMessage("MF_EMAIL_NOT_VALID");
			$this->arErrors["user_email"] = GetMessage("MF_EMAIL_NOT_VALID");
		}
		
		if($this->isUseCaptcha())
			$this->checkCaptcha();
		
		if ($this->isErrors())
		{
			return true;
		}
		else
		{
			$this->formAutocomplete();
			return false;
		}
	}

	public function executeComponent()
    {
		$this->init();//конструктор

		if($this->isSubmit() && $this->validate())
		{
			if ($this->isAjax) {
				$this->arResult["OK_MESSAGE"] = $this->arParams["OK_TEXT"];
				$this->sendMessage();
			}
			
			if (!$this->isAjax) {
				$this->sendMessage();
				$this->redirect();
			}
		}
		if(!$this->isSubmit() && $this->request->get("success") == $this->arResult["PARAMS_HASH"])
		{
			$this->arResult["OK_MESSAGE"] = $this->arParams["OK_TEXT"];
		}

		$this->formAutocomplete();

		if ($this->arErrors)
			$this->arResult["ERROR_MESSAGE"] = $this->arErrors;
		
		if (!$this->isAjax)
			$this->IncludeComponentTemplate();
		
		if ($this->isAjax) {
			$this->arResult["AJAX"] = array(
				"OK_MESSAGE" => $this->arParams["OK_TEXT"],
			);
			if ($this->isUseCaptcha()) {
				$this->arResult["AJAX"]["capCode"] = $this->arResult["capCode"];
			}
			if($this->arErrors){
				$this->arResult["AJAX"]["ERRORS"] = $this->arErrors;
			}
			//echo '<pre>'.print_r($this->arErrors, true).'</pre>';
			//echo 'arResult <pre>'.print_r($this->arResult, true).'</pre>';
		}
    }
}
?>