<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
//$this->props['not_show'] = array('img_model' => 1, 'language' => 1, 'common_language'=> 1, 'human_style' => 1);
include_once WaicFrame::_()->getModule('options')->getModDir() . 'views' . WAIC_DS . 'tpl' . WAIC_DS . 'adminOptionsTabApi.php';
