<?php

@mkdir('./metadata/', 0777, TRUE);
@mkdir('./logs/', 0777, TRUE);
ini_set('memory_limit', '10000M');

function error_handler($errno, $errstr, $errfile, $errline){
    switch ($errno) {
        case E_USER_ERROR:
            file_put_contents('./logs/main_errors.log', date('Y-m-d H:i:s').' [ERROR] '.$errfile.':'.$errline.' "'.$errstr.'"'.PHP_EOL, FILE_APPEND);
            break;
    
        case E_USER_WARNING:
            file_put_contents('./logs/main_errors.log', date('Y-m-d H:i:s').' [WARNING] '.$errfile.':'.$errline.' "'.$errstr.'"'.PHP_EOL, FILE_APPEND);
            break;
    
        case E_USER_NOTICE:
            file_put_contents('./logs/main_errors.log', date('Y-m-d H:i:s').' [NOTICE] '.$errfile.':'.$errline.' "'.$errstr.'"'.PHP_EOL, FILE_APPEND);
            break;
    
        default:
            file_put_contents('./logs/main_errors.log', date('Y-m-d H:i:s').' [UNKNOWN] '.$errfile.':'.$errline.' "'.$errstr.'"'.PHP_EOL, FILE_APPEND);
            break;
    }
}
set_error_handler("error_handler");

$content = file_get_contents($argv[1]);
$content = strtr($content, array('viz:size' => 'viz_size', 'viz:position' => 'viz_position', 'viz:color' => 'viz_color'));
$content = preg_replace("|&#(.*);|isU", "", $content);
$data = json_decode(json_encode(simplexml_load_string($content)), TRUE);
unset($content);
$profile = json_decode(file_get_contents($argv[2]), TRUE);

$min_x = $min_y = 9999;
$max_x = $max_y = 0;
$nodes_count = 0;
$_nodes = $_edges = array();

echo "Обработка вершин".PHP_EOL;
$i = 0;
$color_legend = array();

foreach($data['graph']['nodes']['node'] as $node){
    $i++;
    $node['viz_position']['@attributes']['x'] = $node['viz_position']['@attributes']['x']*$profile['render']['default_scale'];
    $node['viz_position']['@attributes']['y'] = $node['viz_position']['@attributes']['y']*$profile['render']['default_scale'];
    $node['viz_size']['@attributes']['value'] = $node['viz_size']['@attributes']['value']*$profile['render']['default_scale'];
    
    $min_x = min($min_x, $node['viz_position']['@attributes']['x']);
    $max_x = max($max_x, $node['viz_position']['@attributes']['x']);
    $min_y = min($min_y, $node['viz_position']['@attributes']['y']);
    $max_y = max($max_y, $node['viz_position']['@attributes']['y']);
    
    $description = $link = '';
    
    $node['@attributes']['label'] = str_replace('&quot;', '"', $node['@attributes']['label']);
    
    if($profile['legends']['color']['field'] == 'auto'){
        if(!isset($color_legend['rgb('.$node['viz_color']['@attributes']['r'].','.$node['viz_color']['@attributes']['g'].','.$node['viz_color']['@attributes']['b'].')'])){
            $color_legend['rgb('.$node['viz_color']['@attributes']['r'].','.$node['viz_color']['@attributes']['g'].','.$node['viz_color']['@attributes']['b'].')'] = array('color' => 'rgb('.$node['viz_color']['@attributes']['r'].','.$node['viz_color']['@attributes']['g'].','.$node['viz_color']['@attributes']['b'].')', 'label' => 'none', 'count' => 1);
        }else{
            $color_legend['rgb('.$node['viz_color']['@attributes']['r'].','.$node['viz_color']['@attributes']['g'].','.$node['viz_color']['@attributes']['b'].')']['count']++;
        }
    }else{
        if(isset($node['attvalues']['attvalue'])){
            if(isset($node['attvalues']['attvalue']['@attributes']) && $node['attvalues']['attvalue']['@attributes']['for'] == 'description') $description = $node['attvalues']['attvalue']['@attributes']['value'];
            if(isset($node['attvalues']['attvalue']['@attributes']) && $node['attvalues']['attvalue']['@attributes']['for'] == 'link') $link = $node['attvalues']['attvalue']['@attributes']['value'];
            if(isset($node['attvalues']['attvalue']['@attributes']) && $node['attvalues']['attvalue']['@attributes']['for'] == $profile['legends']['color']['field']){
                if(isset($color_legend[$node['attvalues']['attvalue']['@attributes']['value']])){
                    $color_legend[$node['attvalues']['attvalue']['@attributes']['value']]['count']++;
                }else{
                    $color_legend[$node['attvalues']['attvalue']['@attributes']['value']] = array('color' => 'rgb('.$node['viz_color']['@attributes']['r'].','.$node['viz_color']['@attributes']['g'].','.$node['viz_color']['@attributes']['b'].')', 'label' => $node['attvalues']['attvalue']['@attributes']['value'], 'count' => 1);   
                }
            }else{
                foreach($node['attvalues']['attvalue'] as $val){
                    if(isset($val['@attributes']['for'])){
                        
                        if($val['@attributes']['for'] == 'description') $description = $val['@attributes']['value'];
                        if($val['@attributes']['for'] == 'link') $link = $val['@attributes']['value'];
                        
                        if($val['@attributes']['for'] == $profile['legends']['color']['field']){
                            if(isset($color_legend[$val['@attributes']['value']])){
                                $color_legend[$val['@attributes']['value']]['count']++;
                            }else{
                                $color_legend[$val['@attributes']['value']] = array('color' => 'rgb('.$node['viz_color']['@attributes']['r'].','.$node['viz_color']['@attributes']['g'].','.$node['viz_color']['@attributes']['b'].')', 'label' => $val['@attributes']['value'], 'count' => 1);   
                            }
                        }
                    }
                }
            }
        }
    }
    
    $_nodes[$node['@attributes']['id']] = array(
                              $node['@attributes']['label'],
                              $node['viz_position']['@attributes']['x'],
                              $node['viz_position']['@attributes']['y'],
                              $node['viz_size']['@attributes']['value'],
                              
                              array($node['viz_color']['@attributes']['r'],$node['viz_color']['@attributes']['g'],$node['viz_color']['@attributes']['b']),
                              
                              array(
                                    ($node['viz_color']['@attributes']['r'] - 40) > 0 ? ($node['viz_color']['@attributes']['r'] - 40) : 0,
                                    ($node['viz_color']['@attributes']['g'] - 40) > 0 ? ($node['viz_color']['@attributes']['g'] - 40) : 0,
                                    ($node['viz_color']['@attributes']['b'] - 40) > 0 ? ($node['viz_color']['@attributes']['b'] - 40) : 0
                                ),
                                $description,
                                $link
                            );
}

$offset_x = abs($min_x)+500;
$offset_y = abs($min_y)+500;
echo "Обработка ребер".PHP_EOL;

foreach($data['graph']['edges']['edge'] as $edge){
    $_edges[] = array($edge['@attributes']['source'], $edge['@attributes']['target'], array($style['line_color'][0], $style['line_color'][1], $style['line_color'][2],0));
}

unset($data);

$nodes_count = count($_nodes);
$edges_count = count($_edges);

echo "Вершин: ".$nodes_count.PHP_EOL;
echo "Ребер: ".$edges_count.PHP_EOL;

echo "Координаты X: ".$min_x." - ".$max_x.PHP_EOL;
echo "Координаты Y: ".$min_y." - ".$max_y.PHP_EOL;

$width = ceil($offset_x+$max_x)+1000;
$height = ceil($offset_y+$max_y)+1000;

echo "Размер изображения: ".$width." на ".$height.PHP_EOL;

$svg = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="'.$width.'" height="'.$height.'" style="background: rgb('.$profile['graphics']['background_color'][0].','.$profile['graphics']['background_color'][1].','.$profile['graphics']['background_color'][2].')">';

foreach($_edges as $edge){
    
    $x1 = $_nodes[$edge[0]][1]+$offset_x;
    $y1 = $_nodes[$edge[0]][2]+$offset_y;
    
    $x2 = $_nodes[$edge[1]][1]+$offset_x;
    $y2 = $_nodes[$edge[1]][2]+$offset_y;
    
    $l = sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2));
    
    $dx = $x1-$x2;
    $dy = $y1-$y2;
    
    $norm = sqrt($dx*$dx+$dy*$dy);
    $udx = $dx/$norm;
    $udy = $dy/$norm;
    
    $ax = $udx * sqrt(3)/2 - $udy * 1/2;
    $ay = $udx * 1/2 + $udy * sqrt(3)/2;
    
    $bx = $udx * sqrt(3)/2 + $udy * 1/2;
    $by =  - $udx * 1/2 + $udy * sqrt(3)/2;
    
    $h = $l*0.6;
    
    $x3 = $x2 + $h * $ax;
    $y3 = $y2 + $h * $ay;
    
    $svg .= '<path d="M'.$x1.','.$y1.' Q'.$x3.','.$y3.' '.$x2.','.$y2.'" stroke="rgb('.$_nodes[$edge[0]][5][0].','.$_nodes[$edge[0]][5][1].','.$_nodes[$edge[0]][5][2].')" stroke-width="'.($_nodes[$edge[0]][3]*0.1).'" fill="transparent" opacity="0.3"/>';
}

foreach($_nodes as $node){
    $svg .= '<circle style="fill: rgb('.$node[4][0].','.$node[4][1].','.$node[4][2].');stroke: rgb('.$node[5][0].','.$node[5][1].','.$node[5][2].');stroke-width: '.($node[3]*0.1).'" cx="'.($node[1]+$offset_x).'" cy="'.($node[2]+$offset_y).'" r="'.ceil($node[3]/2).'"/><text text-anchor="middle" x="'.($node[1]+$offset_x).'" y="'.(($node[2]+$offset_y)+(($node[3]/2)*0.8)/3).'" font-family="Verdana" font-size="'.(($node[3]/2)*0.8).'" style="fill: #fff;stroke: rgb('.$node[5][0].','.$node[5][1].','.$node[5][2].');stroke-width: '.(($node[3]/2)*0.3).';paint-order: stroke;stroke-linecap: butt;stroke-linejoin: miter;">'.htmlentities($node[0]).'</text>';
}

$svg .= '</svg>';
file_put_contents($argv[3], $svg);

?>
