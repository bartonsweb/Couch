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
	
    ob_start();
    define( 'K_ADMIN', 1 );
    
    if ( !defined('K_COUCH_DIR') ) define( 'K_COUCH_DIR', str_replace( '\\', '/', dirname(realpath(__FILE__) ).'/') );
    require_once( K_COUCH_DIR.'header.php' );
    
    $AUTH->check_access( K_ACCESS_LEVEL_ADMIN, 1 );
    
    $response = '';
    if( isset($_GET['act']{0}) ){
        if( $_GET['act'] == 'crop' ){
            require_once( K_COUCH_DIR.'addons/thumbselect/timthumb.php' );
            
            $tpl_id = ( isset($_GET['tpl']) && $FUNCS->is_natural( $_GET['tpl'] ) ) ? (int)$_GET['tpl'] : null;
            $page_id = ( isset($_GET['p']) && $FUNCS->is_natural( $_GET['p']) ) ? (int)$_GET['p'] : null;
            $thumb_id = ( isset($_GET['tb']) ) ? $_GET['tb'] : null;
            $nonce = ( isset($_GET['nonce']) ) ? $_GET['nonce'] : null;
            $crop_x = ( isset($_GET['x']) ) ? $_GET['x'] : '0';
            $crop_y = ( isset($_GET['y']) ) ? $_GET['y'] : '0';
            $crop_w = ( isset($_GET['w']) ) ? $_GET['w'] : '150';
            $crop_h = ( isset($_GET['h']) ) ? $_GET['h'] : '150';
            
            if( $tpl_id && $page_id && $thumb_id && $nonce ){
                $FUNCS->validate_nonce( 'crop_image_' . $thumb_id );
                
                // create thumbnail
                $PAGE = new KWebpage( $tpl_id, $page_id );
                if( $PAGE->error ){
                    ob_end_clean();
                    die( 'ERROR: ' . $PAGE->err_msg );
                }
                
                for( $x=0; $x<count($PAGE->fields); $x++ ){
                    $tb = &$PAGE->fields[$x];
                    if( !$tb->system ){
                        if( $tb->k_type == 'imagethumb' && $tb->name==$thumb_id ){
                            // loop again to find the associated thumbnail
                            for( $t=0; $t<count($PAGE->fields); $t++ ){
                                $f = &$PAGE->fields[$t];
                                if( (!$f->system) && $f->k_type=='image' && $tb->assoc_field==$f->name ){
                                    
                                    if( extension_loaded('gd') && function_exists('gd_info') ){
                                        $src = $f->get_data();
                                        $pos = strpos( $src, $Config['k_append_url'] );
                                        if( $pos !== false ){
                                            $src = substr( $src, strlen($Config['k_append_url']) );
                                            $pos = strpos( $src, $Config['UserFilesPath'] );
                                            if( $pos !== false ){
                                                $src = substr( $src, strlen($Config['UserFilesPath']) );
                                                $src = $Config['UserFilesAbsolutePath'] . $src;
                                                
                                                // create thumbnail
                                                $dest = null;
                                                $w = $crop_w;
                                                $h = $crop_h;
						$targ_w = $tb->width;
						$targ_h = $tb->height;
						$targ_x = $crop_x;
						$targ_y = $crop_y;
                                                $crop = 1;
                                                $enforce_max = 0;
                                                $quality = $tb->quality;
                                                
                                                $thumbnail = k_resize_thumb( $src, $dest, $w, $h, $targ_w, $targ_h, $crop, $enforce_max, $quality, $targ_x, $targ_y );
                                                if( $FUNCS->is_error($thumbnail) ){
                                                    die( $thumbnail->err_msg );
                                                }
                                                
                                            }
                                        }
                                    }
                                    else{
                                        echo 'No GD image library installed';
                                        die();
                                    }
                                    
                                    // Job done. Exit.
                                    echo 'OK';
                                    die();
                                }
                                unset( $f );
                            }
                            
                        }
                        
                    }
                    unset( $tb );
                }
                
                //$response = '<img src="'.K_ADMIN_URL . 'theme/images/ok.jpg">';
                $response = 'OK';
            }
            else{
                die( 'Invalid parameters' );
            }
        }
    }
    
    echo $response;