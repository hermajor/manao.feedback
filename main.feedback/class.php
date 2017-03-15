<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

class CFeedbackManao extends CBitrixComponent
{
	private $user = null;
	private $application = null;
	//конструктор
	public function localConstruct()
	{
		global $USER;
		global $APPLICATION;
		$this->user = $USER;
		$this->application = $APPLICATION;
		//$this->application = Bitrix\Main\Application::getInstance();
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
		if($this->arParams["OK_TEXT"] == '')
			$this->arParams["OK_TEXT"] = GetMessage("MF_OK_MESSAGE");
	}
	
	public function isUseCaptcha()
	{
		$capthaStatus = ($this->arParams["USE_CAPTCHA"] != "N" && !$this->user->IsAuthorized()) ? true : false;
		return $capthaStatus;
	}
	
	public function useCaptcha()
	{
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/captcha.php");
		$captcha_code = $_POST["captcha_sid"];
		$captcha_word = $_POST["captcha_word"];
		$cpt = new CCaptcha();
		$captchaPass = COption::GetOptionString("main", "captcha_password", "");
		if (strlen($captcha_word) > 0 && strlen($captcha_code) > 0)
		{
			if (!$cpt->CheckCodeCrypt($captcha_word, $captcha_code, $captchaPass))
				$this->arResult["ERROR_MESSAGE"][] = GetMessage("MF_CAPTCHA_WRONG");
		}
		else
			$this->arResult["ERROR_MESSAGE"][] = GetMessage("MF_CAPTHCA_EMPTY");
	}
	
	public function getCaptchaCode()
    {
		//$this->arResult["capCode"] =  htmlspecialcharsbx($this->application->CaptchaGetCode());
		return htmlspecialcharsbx($this->application->CaptchaGetCode());
	}
	
	public function isSubmit()
	{
		return $_SERVER["REQUEST_METHOD"] == "POST" && $_POST["submit"] <> '' && (!isset($_POST["PARAMS_HASH"]) || $this->arResult["PARAMS_HASH"] === $_POST["PARAMS_HASH"]);
	}
	
	public function isEmailValid()
	{
		return strlen($_POST["user_email"]) > 1 && !check_email($_POST["user_email"]);
	}
	
	public function checkRequiredFields()
	{
		if(empty($this->arParams["REQUIRED_FIELDS"]) || !in_array("NONE", $this->arParams["REQUIRED_FIELDS"]))
		{
			if((empty($this->arParams["REQUIRED_FIELDS"]) || in_array("NAME", $this->arParams["REQUIRED_FIELDS"])) && strlen($_POST["user_name"]) <= 1)
				$this->arResult["ERROR_MESSAGE"][] = GetMessage("MF_REQ_NAME");		
			if((empty($this->arParams["REQUIRED_FIELDS"]) || in_array("EMAIL", $this->arParams["REQUIRED_FIELDS"])) && strlen($_POST["user_email"]) <= 1)
				$this->arResult["ERROR_MESSAGE"][] = GetMessage("MF_REQ_EMAIL");
			if((empty($this->arParams["REQUIRED_FIELDS"]) || in_array("MESSAGE", $this->arParams["REQUIRED_FIELDS"])) && strlen($_POST["MESSAGE"]) <= 3)
				$this->arResult["ERROR_MESSAGE"][] = GetMessage("MF_REQ_MESSAGE");
		}	
	}
	
	public function isNoErrors()
	{
		return (empty($this->arResult["ERROR_MESSAGE"]));
	}
	
	public function sendMessage()
	{
		$arFields = Array(
			"AUTHOR" => $_POST["user_name"],
			"AUTHOR_EMAIL" => $_POST["user_email"],
			"EMAIL_TO" => $this->arParams["EMAIL_TO"],
			"TEXT" => $_POST["MESSAGE"],
		);
		if(!empty($this->arParams["EVENT_MESSAGE_ID"]))
		{
			foreach($this->arParams["EVENT_MESSAGE_ID"] as $v)
				if(IntVal($v) > 0)
					CEvent::Send($this->arParams["EVENT_NAME"], SITE_ID, $arFields, "N", IntVal($v));
		}
		else
			CEvent::Send($this->arParams["EVENT_NAME"], SITE_ID, $arFields);
		$_SESSION["MF_NAME"] = htmlspecialcharsbx($_POST["user_name"]);
		$_SESSION["MF_EMAIL"] = htmlspecialcharsbx($_POST["user_email"]);
	}
	
	public function executeComponent()
    {
		$this->localConstruct();//конструктор 
		$this->setParams();//заполняем $arParams
//echo '<pre>'.print_r($this->user, true).'</pre>';		
		$this->arResult["PARAMS_HASH"] = md5(serialize($this->arParams).$this->GetTemplateName());

		if($this->isSubmit())
		{
			$this->arResult["ERROR_MESSAGE"] = array();
			if(check_bitrix_sessid())
			{
echo 'arParams <pre>'.print_r($this->arParams, true).'</pre>';
				$this->checkRequiredFields();
				
				if($this->isEmailValid())
					$this->arResult["ERROR_MESSAGE"][] = GetMessage("MF_EMAIL_NOT_VALID");
				//капча 
				if($this->isUseCaptcha())
				{
					$this->useCaptcha();
				}
				//всё заебись, ошибок нет
				if($this->isNoErrors())
				{
					$this->sendMessage();
					//редиректит на текущую страницу +
					LocalRedirect($this->application->GetCurPageParam("success=".$this->arResult["PARAMS_HASH"], Array("success")));
				}
				
				$this->arResult["MESSAGE"] = htmlspecialcharsbx($_POST["MESSAGE"]);
				$this->arResult["AUTHOR_NAME"] = htmlspecialcharsbx($_POST["user_name"]);
				$this->arResult["AUTHOR_EMAIL"] = htmlspecialcharsbx($_POST["user_email"]);
			}
			else
				//ваша сессия истекла
				$this->arResult["ERROR_MESSAGE"][] = GetMessage("MF_SESS_EXP");
		}
		elseif($_REQUEST["success"] == $this->arResult["PARAMS_HASH"])
		{
			$this->arResult["OK_MESSAGE"] = $this->arParams["OK_TEXT"];
		}

		if($this->isNoErrors())
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

		if($this->isUseCaptcha())
			$this->arResult["capCode"] = $this->getCaptchaCode();

//var_dump($this->isUseCaptcha());
//var_dump($this->arParams["USE_CAPTCHA"] == "Y");
		$this->IncludeComponentTemplate();
echo 'arResult <pre>'.print_r($this->arResult, true).'</pre>';
//echo 'arParams <pre>'.print_r($this->arParams, true).'</pre>';
/*
echo '$_POST <pre>'.print_r($_POST, true).'</pre>';
echo '$_REQUEST <pre>'.print_r($_REQUEST, true).'</pre>';
*/
    }
}
?>