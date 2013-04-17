<?php
//[[getTvOptions? &tpl=`myChunk` &tvname=`myTv` &name=`cb1`]]
  
$tv = $modx->getObject('modTemplateVar',array('name'=>$tvname));
$options = explode('||',$tv->get('elements'));
$output = "";
  
foreach ($options as $option){
  $opt = explode ('==',$option);
  $ph['value'] = trim( count($opt)>1 ? $opt[1]: $opt[0] );
  $ph['text'] = trim( $opt[0] );

  if (empty($ph['text'])) continue;

  if (isset($name)) {
    $ph['name'] = !empty($asArray) ? $name.'[]' : $name;
    $ph['checked'] = ''; 
    $ph['selected'] = '';
     
    if ($name != '' && (is_array($_REQUEST[$name]) && in_array($ph['value'],$_REQUEST[$name])) || $_REQUEST[$name] == $ph['value']) 
    {
      $ph['checked'] = 'checked="checked"'; 
      $ph['selected'] = 'selected="selected"';      
    }
  }

  $output .= $modx->getChunk($tpl,$ph);
}
  
return $output;