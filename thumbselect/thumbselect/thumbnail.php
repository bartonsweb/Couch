<?php
    /*
    The contents of this file are subject to the Common Public Attribution License
    Version 1.0 (the "License"); you may not use this file except in compliance with
    the License. You may obtain a copy of the License at
    http://www.couchcms.com/cpal.html. The License is based on the Mozilla
    Public License Version 1.1 but Sections 14 and 15 have been added to cover use
    of software over a computer network and provide for limited attribution for the
    Original Developer. In addition, Exhibit A has been modified to be consistent with
    Exhibit B.
    
    Software distributed under the License is distributed on an "AS IS" basis, WITHOUT
    WARRANTY OF ANY KIND, either express or implied. See the License for the
    specific language governing rights and limitations under the License.
    
    The Original Code is the CouchCMS project.
    
    The Original Developer is the Initial Developer.
    
    The Initial Developer of the Original Code is Kamran Kashif (kksidd@couchcms.com). 
    All portions of the code written by Initial Developer are Copyright (c) 2009, 2010
    the Initial Developer. All Rights Reserved.
    
    Contributor(s):
    
    Alternatively, the contents of this file may be used under the terms of the
    CouchCMS Commercial License (the CCCL), in which case the provisions of
    the CCCL are applicable instead of those above.
    
    If you wish to allow use of your version of this file only under the terms of the
    CCCL and not to allow others to use your version of this file under the CPAL, indicate
    your decision by deleting the provisions above and replace them with the notice
    and other provisions required by the CCCL. If you do not delete the provisions
    above, a recipient may use your version of this file under either the CPAL or the
    CCCL.
    */
    
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly
    
    class ImageThumb extends KUserDefinedField{
        
        function handle_params( $params ){
            global $FUNCS, $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ) return;
            
            $attr = $FUNCS->get_named_vars(
                array(  'buttons'=>'',
                    'maxheight'=>'0',
		    'assoc_field'=>'',
		    'maxwidth'=>'0',
                  ),
                $params
            );
            $attr['maxheight'] = $FUNCS->is_non_zero_natural( $attr['maxheight'] ) ? intval( $attr['maxheight'] ) : 0;
            $attr['maxwidth'] = $FUNCS->is_non_zero_natural( $attr['maxwidth'] ) ? intval( $attr['maxwidth'] ) : 0;
            $attr['assoc_field'] = $attr['assoc_field']!='' ? trim( $attr['assoc_field'] ) : '';
                     
            return $attr;
        }

        function store_data_from_saved( $data ){ // just duplicating the default logic of KField.
            $this->data = $data;
        }

        function ImageThumb( $row, &$page, &$siblings ){
            global $FUNCS;
            
            // udf params
            $custom_params = $row['custom_params'];
            if( strlen($custom_params) ){
                $arr_params = $FUNCS->unserialize($custom_params);
                if( is_array($arr_params) && count($arr_params) ){
                    foreach( $arr_params as $k=>$v ){
                        $this->$k = $v;
                    }
                }
            }
            
            // call parent
            parent::KField( $row, $page, $siblings );
            
            if( !$FUNCS->is_core_type($this->k_type) ){
                $this->udf = 1;
            }
        }
 function resolve_dynamic_params(){
            if( !$this->system && $this->dynamic ){
                $arr_dynamic = array_map( "trim", explode( '|', $this->dynamic ) );
                foreach( $arr_dynamic as $dyn_param ){
                    if( in_array($dyn_param, array( 'desc', 'type', 'order', 'group', 'separator' )) ){
                        $dyn_param = 'k_'.$dyn_param;
                    }
                    
                    if( array_key_exists($dyn_param, $this) && $this->$dyn_param ){
                        if( defined('K_SNIPPETS_DIR') ){ // always defined relative to the site
                            $base_snippets_dir = K_SITE_DIR . K_SNIPPETS_DIR . '/';
                        }
                        else{
                            $base_snippets_dir = K_COUCH_DIR . 'snippets/';
                        }
                        $filepath = $base_snippets_dir . ltrim( trim($this->$dyn_param), '/\\' );
                        
                        if( file_exists($filepath) ){
                            $html = @file_get_contents($filepath);
                            if( strlen($html)  ){
                                $parser = new KParser( $html );
                                $this->$dyn_param = $parser->get_HTML();
                            }
                        }
                    }
                }
            }
        }



         function store_posted_changes( $post_val ){
            global $FUNCS;
            if( $this->deleted ) return; // no need to store
            
            if( is_null($this->orig_data) ) $this->orig_data = $this->data;
            $this->data = $FUNCS->cleanXSS( $post_val );
            $this->modified = ( strcmp( $this->orig_data, $this->data )==0 )? false : true; // values unchanged
        }


        function get_data_to_save(){ 
            return $this->data;
        }
        function get_search_data(){
            return $this->data;
        }

        function is_empty(){
            $data = trim( $this->get_data() );
            return ( strlen($data) ) ? false : true;
        }




        function get_data( $for_ctx=0 ){
             global $Config;
            if( !$this->data ){
                // make sure it is not numeric 0
                $data = ( is_numeric($this->data) )? (string)$this->data : $this->default_data;
            }
            else{
                $data = $this->data;
            }
            
            if( $this->search_type!='text' ){
                $pos = strpos( $data, ".00");
                if( $pos!==false ){
                    $data = substr( $data, 0, $pos );
                }
            }

                    if( $data{0}==':' ){ // if marker
                        $data = substr( $data, 1 );
                        $folder = ( $this->k_type=='imagethumb' ) ? 'image' : $this->k_type;
                        $domain_prefix = $Config['k_append_url'] . $Config['UserFilesPath'] . $folder . '/';
                        $data = $domain_prefix . $data;
                    }  
            
            return $data;
        }


        
//Not working currently, restored to original KK sample - unsure on problem and/or fix.
function validate(){
global $FUNCS, $Config;
$image_f = $this->page->_fields[$this->assoc_field]->get_data();

    if( $image_f->modified ){ // if a new value is set in the image field ..

        // get it to create a thumbnail 
        $path = $image_f;//->get_data();
        
        $domain_prefix = $Config['k_append_url'] . $Config['UserFilesPath'] . 'image/';
        
        if( strpos($path, $domain_prefix)===0 ){ // manipulate only if local file
            $path = substr( $path, strlen($domain_prefix) );
            if( $path ){
                $local_path = $Config['UserFilesAbsolutePath'] . 'image/' . $path;
                if( file_exists($local_path) ){

				for( $t=0; $t<count($this->fields); $t++ ){
                                $tb = &$this->fields[$t];
                                if( (!$tb->system) && $tb->k_type=='imagethumb' && $tb->assoc_field==$f->name ){
                                    if( $resized ){
                                        // create thumbnail
                                        $dest = null;
                                        $w = $tb->width;
                                        $h = $tb->height;
                                        $src = $local_path;
                                        // Make provision for enforce max. Make crop & enforce_max exclusive.
                                        $enforce_max = $tb->enforce_max;
                                        $crop = ( $enforce_max ) ? 0 : 1;
                                        $quality = $tb->quality;
                                        
                                        $thumbnail = k_resize_image( $src, $dest, $w, $h, $crop, $enforce_max, $quality );
                                        if( $FUNCS->is_error($thumbnail) ){
                                            //$tb->err_msg = $thumbnail->err_msg;
                                            //$errors++;
                                            // TODO: Non critical error. Will continue but have to report.
                                        }
                                        else{
                                            $tb->modified = 1;
                                            $path_parts = $FUNCS->pathinfo( $tb->get_data() );
                                            $img_path = $path_parts['dirname'] . '/';
                                            $img_path = substr( $img_path, strlen($domain_prefix) );
                                            if( $img_path ) $thumbnail = $img_path . $thumbnail;
                                            $tb->data = ':' . $thumbnail; // add marker
                                            $arr_custom_fields[$tb->id]['data'] = $tb->data;
                                            $arr_custom_fields[$tb->id]['type'] = $tb->search_type;
                                            $arr_custom_fields[$tb->id]['strip_domain'] = 1;
                                        }
                                    }
                                    else{
                                        $tb->data = '';
                                        $arr_custom_fields[$tb->id]['data'] = '';
                                        $arr_custom_fields[$tb->id]['type'] = $tb->search_type;
                                    }
                                }
                                unset( $tb );
                            }
                    
                }
            }
        }

    }
}


        function _render( $input_name, $input_id, $extra1='', $extra2=''){
            global $FUNCS, $CTX;
	    $assoc_image = $this->page->_fields[$this->assoc_field]->get_data();
	    $value = $this->get_data();
	    $tb_preview = $value ? $value : 'javascript:void()';
            $tb_preview_icon = $value ? $value : K_ADMIN_URL . 'theme/images/upload-image.gif';

            define( 'JCROP_URL', K_ADMIN_URL . 'addons/thumbselect/' );
	    $FUNCS->load_js( JCROP_URL . 'jquery.min.js' );
	    $FUNCS->load_js( JCROP_URL . 'jquery.bpopup.min.js' );
	    $FUNCS->load_js( JCROP_URL . 'jquery.Jcrop.min.js' );
	    $FUNCS->load_css( JCROP_URL . 'jquery.Jcrop.min.css' );
                if( $this->show_preview ){
                    $html .= '<a id="'.$input_id.'_preview" href="'.$tb_preview.'" rel="lightbox">';
                    $html .= '<img id="'.$input_id.'_tb_preview" name="'.$input_name.'_tb_preview" src="'.$this->get_data().'" ';
                    $html .= ( $this->preview_width ) ? 'width="'.$this->preview_width.'" ': '';
                    $html .= ( $this->preview_height ) ? 'height="'.$this->preview_height.'" ': '';
                    $html .= 'class="k_thumbnail_preview" >';
                    $html .= '</a><br>';
                }
            $html .= '<div id="'.$input_id .'-pop" class="BPopup">';
	    $html .= '<img src="'. $assoc_image .'" id="'. $input_id .'" />';

                $nonce = $FUNCS->create_nonce( 'crop_image_'.$this->name );
                $html .= '<a class="button" href="javascript:k_create_thumb('.$this->page->tpl_id.', '.$this->page->id.', \''.$this->name.'\', \''.$nonce.'\')"><span>'.$FUNCS->t('recreate').'</span></a>';


	    $html .='</div><a class="button" id="' .$input_id. '-pop-button"><span>Configure Thumbnail</span></a>';
	    $html .='   <input type="hidden" id="' .$this->name. '-x" name="x" />
			<input type="hidden" id="' .$this->name. '-y" name="y" />
			<input type="hidden" id="' .$this->name. '-w" name="w" />
			<input type="hidden" id="' .$this->name. '-h" name="h" />';

            if( $this->maxheight && $this->height && ($this->maxheight < $this->height) ){
                $this->maxheight = $this->height;
            }
            
            ob_start();
                ?>
<script type="text/javascript">
   jQuery.noConflict();

jQuery(function($) {
 var jcrop_api
    
 $('#<?php echo $input_id; ?>').Jcrop({
    onChange:   setCoords,
    onSelect:   setCoords,
    aspectRatio: 1/1,
    addClass: 'jcrop-light',
    bgColor: 'white',
    bgOpacity: .5,
    setSelect:   [ 100, 100, 50, 50 ],
    minSize: [200, 200],
    boxWidth: 700,
    boxHeight: 700 
  });
  function setCoords(c)
  {
    $('#<?php echo $this->name;?>-x').val(c.x);
    $('#<?php echo $this->name;?>-y').val(c.y);
    $('#<?php echo $this->name;?>-w').val(c.w);
    $('#<?php echo $this->name;?>-h').val(c.h);
  };
   jQuery.noConflict();

            $('#<?php echo $input_id; ?>-pop-button').bind('click', function(e) {

                // Prevents the default action to be triggered. 
                e.preventDefault();

                // Triggering bPopup when click event is fired
                $('#<?php echo $input_id; ?>-pop').bPopup();

            });


});

                        function k_create_thumb( tpl_id, page_id, field_id, nonce ){
                            var el_notice = 'k_notice_f_' + field_id;
                            var el_preview = 'f_'+field_id+'_preview';
                            var pos_x = $(field_id + '-x').value;
                            var pos_y = $(field_id + '-y').value;
                            var width = $(field_id + '-w').value;
                            var height = $(field_id + '-h').value;
                            var qs = '<?php echo K_ADMIN_URL; ?>ajax_thumb.php?act=crop&tpl='+tpl_id+'&p='+page_id+'&tb='+encodeURIComponent( field_id )+'&nonce='+ encodeURIComponent( nonce )+'&x='+encodeURIComponent(pos_x)+'&y='+encodeURIComponent(pos_y)+'&w='+encodeURIComponent(width)+'&h='+encodeURIComponent(height);
                            var requestHTMLData = new Request (
                                {
                                    url: qs,
                                    onComplete: function(response){
                                        if( response=='OK' ){
                                            var href = $(el_preview).get('href');
                                            if( href.indexOf('?') != -1 ){
                                                href = href.substr(0, href.indexOf('?'));
                                            }
                                            href = href + '?rand=' + Math.random();
                                            $(el_preview).set('href', href);
                                            try{
                                                $('f_'+field_id+'_tb_preview').set('src', href);
                                            }
                                            catch( e ){}
                                            
                                            alert('<?php echo $FUNCS->t('thumb_recreated'); ?>');
                                        }
                                        else{
                                            alert(response);
                                        }
                                    }
                                }
                            ).send();
                        }
</script>
                <?php
            $html .= ob_get_contents();
            ob_end_clean();
            return $html;
        }
    }

    
    // Register
    $FUNCS->register_udf( 'ImageThumb', 'imagethumb', 0/*repeatable*/ );
