<?php

 if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

class Recaptcha extends KUserDefinedFormField{

        function handle_params( $params, $node )
	{
            global $FUNCS;
            $attr = $FUNCS->get_named_vars(
                        array(
			       'captcha_type' => '',
			       'theme' =>'',
			       'onload_callback' =>'',
                               'errMessage' =>'',
			       'lang' => '',
			       'callback' =>'',
			       'expired_callback' =>'',
			       'tabindex' =>'',
                              ),
                        $params);
            return $attr;
        }

        function _render( $input_name, $input_id, $extra='' )
	{
		//recaptcha script and check for optional callback and language params
		$html ='<script src="https://www.google.com/recaptcha/api.js';
		$html.=($this->onload_callback!="" ? "?onload=$this->onload_callback&render=explicit" : "");
		if(($this->onload_callback!="") && ($this->lang!="")) $html .= "&";
		$html.=($this->lang!="" ? "?hl=$this->lang" : "");
		$html.='"></script>';

		//g-recaptcha tag // refer to https://developers.google.com/recaptcha/docs/display#config
		$html.='<div class="g-recaptcha"';
		$html.=($this->theme!="" ? "data-theme='$this->theme'" : "");
		$html.=($this->captcha_type!="" ? "data-type='$this->captcha_type'" : "");
		$html.=($this->callback!="" ? "data-callback='$this->callback'" : "");
		$html.=($this->expired_callback!="" ? "data-expired-callback='$this->expired_callback'" : "");
		$html.=($this->tabindex!="" ? "data-tabindex='$this->tabindex'" : "");
		$html.=' data-sitekey="'.RECAPTCHA_SITE_KEY.'"></div>';

            return $this->wrap_fieldset( $html );
        }

	function validate()
	{
		$recaptcha=$_POST['g-recaptcha-response'];
		if(!empty($recaptcha))
		{
			$google_url="https://www.google.com/recaptcha/api/siteverify";
			$secret=RECAPTCHA_SECRET_KEY;
			$ip=$_SERVER['REMOTE_ADDR'];
			$url=$google_url."?secret=".$secret."&response=".$recaptcha."&remoteip=".$ip;
			$response=file_get_contents($url);
			$res= json_decode($response, true);
			if($res['success'])
			{
				return true;
			}
			else
			{
				$this->err_msg = $this->errMessage;
				return false;
			}
		}
		else
		{
			$this->err_msg = $this->errMessage;
			return false;
		}
	}

}
$FUNCS->register_udform_field( 'recaptcha', 'Recaptcha' );
?>