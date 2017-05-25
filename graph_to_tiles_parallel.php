<?php

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
ini_set('memory_limit', '10000M');

$profile = json_decode(file_get_contents($argv[2]), TRUE);

//$styles = array(
//                'default' => array(
//                    'background_color' => array(255,255,255),
//                    'background_color_srgb' => array(255,255,255),
//                    'line_color' => 'auto',
//                    'line_opacity' => 0.3,
//                    'text_fill_color' => array(255,255,255),
//                    'text_color' => array(30,30,30),
//                    'text_stroke_color' => 'auto'
//                ),
//                'dark' => array(
//                    'background_color' => array(30,30,30),
//                    'background_color_srgb' => array(24,24,24),
//                    'line_color' => 'auto',
//                    'line_opacity' => 0.2,
//                    'text_fill_color' => array(220,220,220),
//                    'text_color' => array(220,220,220),
//                    'text_stroke_color' => 'auto'
//                )
//            );


$style = $profile['graphics'];

echo "Подготовка окружения".PHP_EOL;

$tile_size = $profile['render']['tile_size'];
$tile_set = $profile['meta']['tile_set'];
$tiles_path = './tiles/'.$tile_set.'/';
for($i = 0; $i <= 18; $i++){
    exec('rm -rf '.$tiles_path.$i.'/');
}

exec('rm -rf ./graphics/'.$tile_set.'/');
exec('rm -rf ./process/'.$tile_set.'/');
exec('rm -rf ./data/'.$tile_set.'/');
exec('rm -rf ./stats/'.$tile_set.'/');
mkdir('./graphics/'.$tile_set.'/', 0777, TRUE);
mkdir('./process/'.$tile_set.'/', 0777, TRUE);
mkdir('./data/'.$tile_set.'/', 0777, TRUE);
mkdir('./stats/'.$tile_set.'/', 0777, TRUE);

echo "Загрузка файла".PHP_EOL;

$content = file_get_contents($argv[1]);
$content = strtr($content, array('viz:size' => 'viz_size', 'viz:position' => 'viz_position', 'viz:color' => 'viz_color'));
//$content = strtr($content,array());
$content = preg_replace("|&#(.*);|isU", "", $content);
$data = json_decode(json_encode(simplexml_load_string($content)), TRUE);
//echo $content;
unset($content);
//print_R($data);die();

$background_color = array($style['background_color'][0], $style['background_color'][1], $style['background_color'][2]);
$background_color_srgb = array($style['background_color_srgb'][0], $style['background_color_srgb'][1], $style['background_color_srgb'][2]);

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
    
    //$node['attvalues']['attvalue'][] = array('@attributes' => array('for' => 'link', 'value' => 'https://vk.com/'.trim(trim(substr($node['@attributes']['label'],strpos($node['@attributes']['label'], '(')+1), ')'))));
    //$node['@attributes']['label'] = trim(substr($node['@attributes']['label'], 0, strpos($node['@attributes']['label'], '(')));
    
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
$i = 0;
foreach($data['graph']['edges']['edge'] as $edge){
    $i++;
    
    if(is_file('./stats/'.$tile_set.'/'.$edge['@attributes']['source'].'.json')){
        $source = json_decode(file_get_contents('./stats/'.$tile_set.'/'.$edge['@attributes']['source'].'.json'), TRUE);
    }else{
        $source = array('in' => array(), 'out' => array(), 'description' => $_nodes[$edge['@attributes']['source']][6], 'link' => $_nodes[$edge['@attributes']['source']][7], 'color' => 'rgb('.$_nodes[$edge['@attributes']['source']][4][0].','.$_nodes[$edge['@attributes']['source']][4][1].','.$_nodes[$edge['@attributes']['source']][4][2].')');
    }
    if(is_file('./stats/'.$tile_set.'/'.$edge['@attributes']['target'].'.json')){
        $target = json_decode(file_get_contents('./stats/'.$tile_set.'/'.$edge['@attributes']['target'].'.json'), TRUE);
    }else{
        $target = array('in' => array(), 'out' => array(), 'description' => $_nodes[$edge['@attributes']['target']][6], 'link' => $_nodes[$edge['@attributes']['target']][7], 'color' => 'rgb('.$_nodes[$edge['@attributes']['target']][4][0].','.$_nodes[$edge['@attributes']['target']][4][1].','.$_nodes[$edge['@attributes']['target']][4][2].')');
    }
    
    if(!isset($source['out'][$edge['@attributes']['target']])){
       
        $source['out'][$edge['@attributes']['target']] = array(
                                                                'label' => $_nodes[$edge['@attributes']['target']][0],
                                                                'x' => $_nodes[$edge['@attributes']['target']][1]+$offset_x,
                                                                'y' => $_nodes[$edge['@attributes']['target']][2]+$offset_y,
                                                                'color' => 'rgb('.$_nodes[$edge['@attributes']['target']][4][0].','.$_nodes[$edge['@attributes']['target']][4][1].','.$_nodes[$edge['@attributes']['target']][4][2].')',
                                                                'count' => 1,
                                                                'size' => $_nodes[$edge['@attributes']['target']][3]
                                                            );    
    }else{
        $source['out'][$edge['@attributes']['target']]['count']++;
    }
    if(!isset($target['in'][$edge['@attributes']['source']])){
            
        $target['in'][$edge['@attributes']['source']] = array(
                                                                'label' => $_nodes[$edge['@attributes']['source']][0],
                                                                'x' => $_nodes[$edge['@attributes']['source']][1]+$offset_x,
                                                                'y' => $_nodes[$edge['@attributes']['source']][2]+$offset_y,
                                                                'color' => 'rgb('.$_nodes[$edge['@attributes']['source']][4][0].','.$_nodes[$edge['@attributes']['source']][4][1].','.$_nodes[$edge['@attributes']['source']][4][2].')',
                                                                'count' => 1,
                                                                'size' => $_nodes[$edge['@attributes']['source']][3]
                                                            );
        
    }else{
        $target['in'][$edge['@attributes']['source']]['count']++;
    }
    
    file_put_contents('./stats/'.$tile_set.'/'.$edge['@attributes']['source'].'.json', json_encode($source, JSON_UNESCAPED_UNICODE));
    file_put_contents('./stats/'.$tile_set.'/'.$edge['@attributes']['target'].'.json', json_encode($target, JSON_UNESCAPED_UNICODE));
    
    $_edges[] = array($edge['@attributes']['source'], $edge['@attributes']['target'], array($style['line_color'][0], $style['line_color'][1], $style['line_color'][2],0));
}
//die();
unset($data);

$nodes_count = count($_nodes);
$edges_count = count($_edges);

echo "Вершин: ".$nodes_count.PHP_EOL;
echo "Ребер: ".$edges_count.PHP_EOL;

echo "Координаты X: ".$min_x." - ".$max_x.PHP_EOL;
echo "Координаты Y: ".$min_y." - ".$max_y.PHP_EOL;

$width = ceil($offset_x+$max_x);
$height = ceil($offset_y+$max_y);

echo "Размер изображения: ".$width." на ".$height.PHP_EOL;

$zoom = 0;
$min_zoom = $profile['render']['min_zoom'];
$double_zoom = $profile['render']['double_zoom'];

for($z = $min_zoom; $z <= 18; $z++){
    if($tile_size*pow(2, $z) >= max($width, $height)){
        $zoom = $z;
        break;
    }
}

$metadata = array(
                    'title' => $profile['meta']['title'],
                    'min_zoom' => $min_zoom,
                    'max_zoom' => min($profile['render']['max_zoom'], $zoom+$double_zoom),
                    'actual_zoom' => $zoom,
                    'background' => 'rgb('.$background_color_srgb[0].','.$background_color_srgb[1].','.$background_color_srgb[2].')',
                    'text_color' => 'rgb('.$style['text_color'][0].','.$style['text_color'][1].','.$style['text_color'][2].')',
                    'tile_set' => $tile_set,
                    
                    'nodes_count' => $nodes_count,
                    'edges_count' => $edges_count,
                    
                    'legends' => array(
                        'color' => array(
                            'title' => $profile['legends']['color']['title'],
                            'items' => array()
                        ),
                        'size' => array(
                            'title' => '',
                            'items' => array()
                        )
                    )
                );

echo "Максимальный уровень приближения: ".$zoom."/".($zoom+$double_zoom)."/".min($profile['render']['max_zoom'], $zoom+$double_zoom).PHP_EOL.PHP_EOL;

$graphics = array();
$i = 0;
$process_count = 0;
$m = 0;
for($z = min($profile['render']['max_zoom'], $zoom+$double_zoom); $z >= $min_zoom; $z--){
    foreach($_edges as $edge_id => $edge_data){
        $m++;
    }
}
for($z = min($profile['render']['max_zoom'], $zoom+$double_zoom); $z >= $min_zoom; $z--){
    foreach($_edges as $edge_id => $edge_data){
        
        print "\033[1A";
        print "\033[K";
        echo "Рассчет координат ребер: ".number_format($i, 0, ' ', ' ').' - '.ceil(($i/$m)*100)."%".PHP_EOL;
        $i++;
        
        if($i % 1000 == 0){
            $process_count = count(scandir('./process/'.$tile_set.'/')) - 2;
            while($process_count > 100){
                usleep(20000);
                $process_count = count(scandir('./process/'.$tile_set.'/')) - 2;
            }
        }
        
        //file_put_contents('./edges_commands.txt', 'nohup php5 graph_to_tiles_worker.php calculate_edge_coordinate '.$z.' '.$zoom.' '.$offset_x.' '.$offset_y.' '.$_nodes[$edge_data[0]][1].' '.$_nodes[$edge_data[1]][1].' '.$_nodes[$edge_data[0]][2].' '.$_nodes[$edge_data[1]][2].' '.$tile_size.' > /dev/null 2>&1 &'.PHP_EOL, FILE_APPEND);
        if($style['line_color'] == 'auto'){
            $line_color = $_nodes[$edge_data[0]][5];
        }else{
            $line_color = $style['line_color'];
        }
                                                               
        exec('nohup php5 graph_to_tiles_worker.php calculate_edge_coordinate \''.$z.'\' \''.$zoom.'\' \''.$offset_x.'\' \''.$offset_y.'\' \''.$_nodes[$edge_data[0]][1].'\' \''.$_nodes[$edge_data[1]][1].'\' \''.$_nodes[$edge_data[0]][2].'\' \''.$_nodes[$edge_data[1]][2].'\' \''.$tile_size.'\' \'rgb('.$line_color[0].','.$line_color[1].','.$line_color[2].')\' \''.$_nodes[$edge_data[0]][3].'\' \''.$_nodes[$edge_data[1]][3].'\' \''.$style['line_opacity'].'\' \''.$tile_set.'\' > /dev/null 2>&1 &');
    }
}

echo PHP_EOL;

$i = 0;
$process_count = 0;
$m = 0;
for($z = min($profile['render']['max_zoom'], $zoom+$double_zoom); $z >= $min_zoom; $z--){
    foreach($_nodes as $node_id => $node_data){
        $m++;
    }
}
for($z = min($profile['render']['max_zoom'], $zoom+$double_zoom); $z >= $min_zoom; $z--){
    foreach($_nodes as $node_id => $node_data){
        
        print "\033[1A";
        print "\033[K";
        echo "Рассчет координат для вершин: ".number_format($i, 0, ' ', ' ').' - '.ceil(($i/$m)*100)."%".PHP_EOL;
        $i++;
        
        if($i % 1000 == 0){
            $process_count = count(scandir('./process/'.$tile_set.'/')) - 2;
            while($process_count > 500){
                usleep(20000);
                $process_count = count(scandir('./process/'.$tile_set.'/')) - 2;
            }
        }
        
        //file_put_contents('./nodes_commands.sh', 'nohup php5 graph_to_tiles_worker.php calculate_node_coordinate '.$z.' '.$zoom.' '.$offset_x.' '.$offset_y.' '.$node_data[1].' '.$node_data[2].' '.$node_data[3].' "rgb('.$node_data[4][0].','.$node_data[4][1].','.$node_data[4][2].')" "rgb('.$node_data[5][0].','.$node_data[5][1].','.$node_data[5][2].')" \''.json_encode(array(str_replace("'",'`', $node_data[0]))).'\' '.$tile_size.'  > /dev/null 2>&1 &'.PHP_EOL, FILE_APPEND);
        if($style['text_stroke_color'] == 'auto'){
            $text_stroke_color = $node_data[5];
        }else{
            $text_stroke_color = $style['text_stroke_color'];
        }
        
        exec('nohup php5 graph_to_tiles_worker.php calculate_node_coordinate \''.$z.'\' \''.$zoom.'\' \''.$offset_x.'\' \''.$offset_y.'\' \''.$node_data[1].'\' \''.$node_data[2].'\' \''.$node_data[3].'\' \'rgb('.$node_data[4][0].','.$node_data[4][1].','.$node_data[4][2].')\' \'rgb('.$node_data[5][0].','.$node_data[5][1].','.$node_data[5][2].')\' \''.json_encode(array(str_replace("'",'`', $node_data[0]))).'\' \''.$tile_size.'\' \'rgb('.$style['text_fill_color'][0].','.$style['text_fill_color'][1].','.$style['text_fill_color'][2].')\' \'rgb('.$text_stroke_color[0].','.$text_stroke_color[1].','.$text_stroke_color[2].')\' \''.$tile_set.'\' \''.$node_id.'\'  > /dev/null 2>&1 &');
        
    }
}
$_color_legend = array();
foreach($color_legend as $key => $value){
    $_color_legend[$value['color']] = $value;
}
$color_legend_ranked = array();
foreach($_color_legend as $key => $value){
    $color_legend_ranked[$value['count']] = $value;
}
ksort($color_legend_ranked);

$color_legend_ranked = array_reverse($color_legend_ranked, TRUE);
foreach($color_legend_ranked as $key => $value){
    $value['label'] = strtr($value['label'], $profile['legends']['color']['dictionary']);
    $metadata['legends']['color']['items'][] = $value;
}

//die();
echo PHP_EOL;
$tiles_count = 0;
$max_tiles = 0;
foreach(scandir('./graphics/'.$tile_set.'/') as $z){
    if(in_array($z, array('.','..'))) continue;
    
    foreach(scandir('./graphics/'.$tile_set.'/'.$z.'/') as $tile_x){
        if(in_array($tile_x, array('.','..'))) continue;
        $__tiles = array();
        
        foreach(scandir('./graphics/'.$tile_set.'/'.$z.'/'.$tile_x.'/') as $tile_y){
            if(in_array($tile_y, array('.','..'))) continue;
            $tile_y = str_replace('_circles.txt','',$tile_y);
            $tile_y = str_replace('_lines.txt','',$tile_y);
            
            if(in_array($tile_y, $__tiles)) continue;
            array_push($__tiles, $tile_y);
            
            //echo $z.'/'.$tile_x.'/'.$tile_y.PHP_EOL;
            
            $max_tiles++;
            
        }
    }
}

$i = 0;
$process_count = 0;
foreach(scandir('./graphics/'.$tile_set.'/') as $z){
    if(in_array($z, array('.','..'))) continue;
    
    foreach(scandir('./graphics/'.$tile_set.'/'.$z.'/') as $tile_x){
        if(in_array($tile_x, array('.','..'))) continue;
        $__tiles = array();
        foreach(scandir('./graphics/'.$tile_set.'/'.$z.'/'.$tile_x.'/') as $tile_y){
            if(in_array($tile_y, array('.','..'))) continue;
            $tile_y = str_replace('_circles.txt','',$tile_y);
            $tile_y = str_replace('_lines.txt','',$tile_y);
            
            if(in_array($tile_y, $__tiles)) continue;
            array_push($__tiles, $tile_y);
            
            print "\033[1A";
            print "\033[K";
            echo "Отрисовка тайлов: ".number_format($i, 0, ' ', ' ').' - '.ceil(($i/$max_tiles)*100)."%".PHP_EOL;
            $i++;
            
            if($i % 50 == 0){
                $process_count = count(scandir('./process/'.$tile_set.'/')) - 2;
                while($process_count > 0){
                    usleep(20000);
                    $process_count = count(scandir('./process/'.$tile_set.'/')) - 2;
                }
            }
            
            exec('nohup php5 graph_to_tiles_worker.php render_tile '.$z.' '.$tile_x.' '.$tile_y.' \'rgb('.$background_color[0].','.$background_color[1].','.$background_color[2].')\' \''.$tiles_path.'\' '.$tile_size.' \''.$tile_set.'\'  > /dev/null 2>&1 &');
        
        }
    }
}
$process_count = count(scandir('./process/'.$tile_set.'/')) - 2;
if($process_count > 0) echo PHP_EOL;
while($process_count > 0){
    print "\033[1A";
    print "\033[K";
    echo "Завершение процессов (".$process_count.")".PHP_EOL;
    sleep(1);
    $process_count = count(scandir('./process/'.$tile_set.'/')) - 2;
}
print "\033[1A";
print "\033[K";
echo "Все процессы завершены".PHP_EOL;

file_put_contents('./'.$tile_set.'.json', json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Архивирование данных".PHP_EOL;
if(!isset($argv[3])) exec("cd ".$tiles_path." && zip -r ".$tile_set.".zip *");
if(!isset($argv[3])) exec("cd ./data/".$tile_set."/ && zip -r ".$tile_set.".zip *");
echo "Удаление старых тайлов".PHP_EOL;
if(!isset($argv[3])) exec("ssh root@144.76.155.226 -p22107 'rm -rf /var/www/tiles/".$tile_set."/'");
if(!isset($argv[3])) exec("ssh root@144.76.155.226 -p22107 'rm -rf /var/www/data/".$tile_set."/'");
if(!isset($argv[3])) exec("ssh root@144.76.155.226 -p22107 'mkdir /var/www/tiles/".$tile_set."/'");
if(!isset($argv[3])) exec("ssh root@144.76.155.226 -p22107 'mkdir /var/www/data/".$tile_set."/'");
echo "Копирование данных".PHP_EOL;
if(!isset($argv[3])) exec("scp -r -P 22107 ./tiles/".$tile_set."/".$tile_set.".zip root@144.76.155.226:/var/www/tiles/".$tile_set."/");
if(!isset($argv[3])) exec("scp -r -P 22107 ./data/".$tile_set."/".$tile_set.".zip root@144.76.155.226:/var/www/data/".$tile_set."/");
echo "Распаковка архива".PHP_EOL;
if(!isset($argv[3])) exec("ssh root@144.76.155.226 -p22107 'cd /var/www/tiles/".$tile_set."/ && unzip ".$tile_set.".zip'");
if(!isset($argv[3])) exec("ssh root@144.76.155.226 -p22107 'cd /var/www/data/".$tile_set."/ && unzip ".$tile_set.".zip'");
if(!isset($argv[3])) exec("ssh root@144.76.155.226 -p22107 'rm /var/www/tiles/".$tile_set."/".$tile_set.".zip'");
if(!isset($argv[3])) exec("ssh root@144.76.155.226 -p22107 'rm /var/www/data/".$tile_set."/".$tile_set.".zip'");
echo "Запись метаданных".PHP_EOL;
exec("scp -r -P 22107 ./".$tile_set.".json root@144.76.155.226:/var/www/metadata/".$tile_set.".json");
unlink('./'.$tile_set.'.json');

echo PHP_EOL."https://graphs.fubu.tech/".$tile_set.PHP_EOL.PHP_EOL;

if($profile['settings']['notification']) exec('curl -X POST -d "token=809opaklfasUJOPFLEJ39askldjasdio9e3300lkLKIFLKSAJFLKJEIJNfnf40449204i2ok4j&msg=Отрисовка графа завершена: https://graphs.fubu.tech/'.$tile_set.'" "http://admin.sdh.sexy/push_bot/public/proxy_send" --silent');

?>
