<?php

  if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    class SocialShares extends KUserDefinedField
    {

        function Socials( $row, &$page, &$siblings ){
            // call parent
            parent::KUserDefinedField( $row, $page, $siblings );
        }



 function share_link_handler( $params, $node, $content=null )
	{

	global $FUNCS;

             extract( $FUNCS->get_named_vars(
                array(  'masterpage'=>'',
                    'platform'=>'', /* Platform to share */
                    'url'=>'', /*  Share URL  */
		    'show_count' => '0', /* Show share count */
                  ),
                $params)
            );
            $platform = trim( $platform );
	    $platform = ucwords(strtolower( trim($platform) ) );
            if( !strlen($platform) ) die("ERROR: TAG \"".$node->name."\" MISSING PARAMATER \"platform\".");
            if( $platform!='Facebook' && $platform!='Twitter' && $platform!='Linkedin' && $platform!='Reddit' && $platform!='Google' ) die("ERROR: TAG \"".$node->name."\" INCORRECT PARAMETER VALUE FOR \"platform\".");
            $url = strtolower( trim( $url ) );
            $show_count = ( trim( $show_count ) );


if($show_count!=0){
switch( $platform ){
                case 'Facebook':
			$urlfetch ="https://graph.facebook.com/?id=$url";
			$response = file_get_contents($urlfetch);
			$res = json_decode($response, true);
			$shareurl = "http://www.facebook.com/sharer.php?u=$url";
			$count = ($res['shares']!="" ? $res['shares'] : "0");
                break;
                case 'Google':
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, "https://clients6.google.com/rpc");
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"'.rawurldecode									($url).'","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
			$curl_results = curl_exec ($curl);
			curl_close ($curl);
			$json = json_decode($curl_results, true);
			$count = (isset($json[0]['result']['metadata']['globalCounts']['count'])?intval( $json[0]['result']['metadata']['globalCounts']['count'] ):0);
			$shareurl = "https://plus.google.com/share?url=$url";

                break;
		case 'Linkedin':
			$urlfetch ="https://www.linkedin.com/countserv/count/share?url=$url&format=json";
			$response = file_get_contents($urlfetch);
			$res = json_decode($response, true);
			$shareurl = "http://www.linkedin.com/shareArticle?mini=true&url=$url";
			$count = ($res['count']!="" ? $res['count'] : "0");
		break;
                case 'Reddit':
			$urlfetch ="http://www.reddit.com/api/info.json?url=$url";
			$response = file_get_contents($urlfetch);
			$res = json_decode($response, true);
			$shareurl = "http://reddit.com/submit?url=$url";
			$count = ($res['data']['score']!="" ? $res['data']['score'] : "0");
                break;
		case 'Twitter':
			$urlfetch ="https://cdn.api.twitter.com/1/urls/count.json?url=$url";
			$response = file_get_contents($urlfetch);
			$res = json_decode($response, true);
			$shareurl = "http://twitter.com/share?url=";
			$shareurl .= $url;
			$count = $res['count'];
		break;
		return;
            }
}




		
	    $html="<a href=\"".$shareurl."\" class=\"".strtolower($platform)." share--link\" onclick=\"window.open(this.href, 'mywin','left=20,top=20,width=500,height=500,toolbar=1,resizable=0'); return false;\" target=\"_blank\">";
	    foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }
	if($show_count!=0) $html .="<span class=\"share--count\">$count</span>";
	   $html.="</a>";
	return $html;

	}
        function is_empty(){
            $data = trim( $this->get_data() );
            return ( strlen($data) ) ? false : true;
        }

    }
 $FUNCS->register_tag( 'share_button', array('SocialShares', 'share_link_handler') );
?>