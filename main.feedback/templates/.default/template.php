<?
if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();
/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */
//echo '<pre>'.print_r($this->__folder, true).'</pre>';
$signer = new \Bitrix\Main\Security\Sign\Signer;
$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'manao.main.feedback');
//echo '<pre>'.print_r($arParams, true).'</pre>';
?>

<script type="text/javascript">
signedParams = '<?=CUtil::JSEscape($signedParams)?>';
classPath = '<?=$arResult["CLASS_PATH"]?>' + '/test.php';

</script>

<div class="mfeedback">
<?if(!empty($arResult["ERROR_MESSAGE"]))
{
	//echo '<pre>'.print_r($arResult["ERROR_MESSAGE"], true).'</pre>';
	foreach($arResult["ERROR_MESSAGE"] as $v)
		ShowError($v);
}
if(strlen($arResult["OK_MESSAGE"]) > 0)
{
	?><div class="mf-ok-text"><?=$arResult["OK_MESSAGE"]?></div><?
}
?>
<div class="mf-ok-text"></div>

<form <?=$arParams["USE_AJAX"] == "Y" ? "data-type='ajax'" : ''?> action="<?=POST_FORM_ACTION_URI?>" id="feedback" method="POST" enctype="multipart/form-data">
<?=bitrix_sessid_post()//Возвращает строку вида <input type="hidden" name="$varname" id="$varname" value="идентификатор сесии" />?>
	<div class="mf-name">
		<div class="mf-text">
			<?=GetMessage("MFT_NAME")?><?if(empty($arParams["REQUIRED_FIELDS"]) || in_array("NAME", $arParams["REQUIRED_FIELDS"])):?><span class="mf-req">*</span><?endif?>
		</div>
		<div class="error-message"></div>
		<input type="text" name="user_name" value="<?=$arResult["AUTHOR_NAME"]?>">
	</div>
	<div class="mf-email">
		<div class="mf-text">
			<?=GetMessage("MFT_EMAIL")?><?if(empty($arParams["REQUIRED_FIELDS"]) || in_array("EMAIL", $arParams["REQUIRED_FIELDS"])):?><span class="mf-req">*</span><?endif?>
		</div>
		<div class="error-message"></div>
		<input type="text" name="user_email" value="<?=$arResult["AUTHOR_EMAIL"]?>">
	</div>

	<div class="mf-message">
		<div class="mf-text">
			<?=GetMessage("MFT_MESSAGE")?><?if(empty($arParams["REQUIRED_FIELDS"]) || in_array("MESSAGE", $arParams["REQUIRED_FIELDS"])):?><span class="mf-req">*</span><?endif?>
		</div>
		<div class="error-message"></div>
		<textarea data-id="clear-input" name="MESSAGE" rows="5" cols="40"><?=$arResult["MESSAGE"]?></textarea>
	</div>

	<?if($arParams["USE_CAPTCHA"] == "Y"):?>
	<div class="mf-captcha">
		<div class="mf-text"><?=GetMessage("MFT_CAPTCHA")?></div>
		<input type="hidden" name="captcha_sid" value="<?=$arResult["capCode"]?>">
		<img data-id = "captcha_word" src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult["capCode"]?>" width="180" height="40" alt="CAPTCHA">
		<div class="error-message"></div>
		<input data-id="clear-input" type="text" name="captcha_word" size="30" maxlength="50" value="">
	</div>
	<?endif;?>
	
	<input type="hidden" name="PARAMS_HASH" value="<?=$arResult["PARAMS_HASH"]?>">
	<input type="submit" name="submit" value="<?=GetMessage("MFT_SUBMIT")?>">
</form>
</div>