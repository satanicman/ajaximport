<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

$ajaximport = Module::getInstanceByName('ajaximport');
echo $ajaximport->ajaxCall();
