<?

define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("DisableEventsCheck", true);
define("BX_SECURITY_SHOW_MESSAGE", true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
Bitrix\Main\Localization\Loc::loadMessages(dirname(__FILE__)."/component.php");//подключаем файл локализации, что бы сообщения отрабатывали
CBitrixComponent::includeComponentClass("manao:main.feedback");

$component  = new CFeedbackManao();
$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$signer = new \Bitrix\Main\Security\Sign\Signer;
try
{
	//var_dump($request->get('signedParamsString'));
	$params = $signer->unsign($request->get('signedParamsString'), 'manao.main.feedback');
	$params = unserialize(base64_decode($params));
	$component->arParams = $params;
}
catch (\Bitrix\Main\Security\Sign\BadSignatureException $e)
{
	die();
}
//$params = $signer->unsign($request->get('signedParamsString'), 'manao.main.feedback');
//$params = unserialize(base64_decode($params));
//$component->arParams = $params;

$component->executeComponent();

echo json_encode($component->arResult["AJAX"]);