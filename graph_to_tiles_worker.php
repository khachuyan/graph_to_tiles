<?php

if(!isset($argv[1])) die('$argv');

function error_handler($errno, $errstr, $errfile, $errline){
    switch ($errno) {
        case E_USER_ERROR:
            file_put_contents('./logs/worker_errors.log', date('Y-m-d H:i:s').' [ERROR] '.$errfile.':'.$errline.' "'.$errstr.'"'.PHP_EOL, FILE_APPEND);
            break;
    
        case E_USER_WARNING:
            file_put_contents('./logs/worker_errors.log', date('Y-m-d H:i:s').' [WARNING] '.$errfile.':'.$errline.' "'.$errstr.'"'.PHP_EOL, FILE_APPEND);
            break;
    
        case E_USER_NOTICE:
            file_put_contents('./logs/worker_errors.log', date('Y-m-d H:i:s').' [NOTICE] '.$errfile.':'.$errline.' "'.$errstr.'"'.PHP_EOL, FILE_APPEND);
            break;
    
        default:
            file_put_contents('./logs/worker_errors.log', date('Y-m-d H:i:s').' [UNKNOWN] '.$errfile.':'.$errline.' "'.$errstr.'"'.PHP_EOL, FILE_APPEND);
            break;
    }
}
set_error_handler("error_handler");

$pid = getmypid();

if($argv[1] == 'calculate_edge_coordinate'){
    calculate_edge_coordinate($argv[2], $argv[3], $argv[4], $argv[5], $argv[6], $argv[7], $argv[8], $argv[9], $argv[10], $argv[11], $argv[12], $argv[13], $argv[14], $argv[15]);
    file_put_contents('./process/'.$argv[15].'/'.$pid.'.pid', $pid);
    unlink('./process/'.$argv[15].'/'.$pid.'.pid');
}elseif($argv[1] == 'calculate_node_coordinate'){
    file_put_contents('./process/'.$argv[15].'/'.$pid.'.pid', $pid);
    calculate_node_coordinate($argv[2], $argv[3], $argv[4], $argv[5], $argv[6], $argv[7], $argv[8], $argv[9], $argv[10], $argv[11], $argv[12], $argv[13], $argv[14], $argv[15], $argv[16]);
    unlink('./process/'.$argv[15].'/'.$pid.'.pid');
}elseif($argv[1] == 'render_tile'){
    file_put_contents('./process/'.$argv[8].'/'.$pid.'.pid', $pid);
    render_tile($argv[2], $argv[3], $argv[4], $argv[5], $argv[6], $argv[7], $argv[8]);
    unlink('./process/'.$argv[8].'/'.$pid.'.pid');
}

function render_tile($z, $tile_x, $tile_y, $background_color, $tiles_path, $tile_size, $tile_set){

	if(is_file($tiles_path.$z.'/'.$tile_x.'/'.$tile_y.'.png')) return true;
    
    $tile = new Imagick(); 
    $tile->newImage($tile_size, $tile_size, "none");
    
    $fill_bckground = new ImagickDraw(); 
    $fill_bckground->setFillColor($background_color);
    $fill_bckground->rectangle(0, 0, $tile_size, $tile_size);
    $tile->drawImage($fill_bckground);
    
    $tile->setImageFormat("png");
    
    if(is_file('./graphics/'.$tile_set.'/'.$z.'/'.$tile_x.'/'.$tile_y.'_lines.txt')){
        foreach(file('./graphics/'.$tile_set.'/'.$z.'/'.$tile_x.'/'.$tile_y.'_lines.txt') as $json_line){
            $line = json_decode($json_line, TRUE);
            
            //1
            
            $l = sqrt(pow($line[2] - $line[0], 2) + pow($line[3] - $line[1], 2));
            
            $dx = $line[0]-$line[2];
            $dy = $line[1]-$line[3];
            
            $norm = sqrt($dx*$dx+$dy*$dy);
            $udx = $dx/$norm;
            $udy = $dy/$norm;
            
            $ax = $udx * sqrt(3)/2 - $udy * 1/2;
            $ay = $udx * 1/2 + $udy * sqrt(3)/2;
            
            $bx = $udx * sqrt(3)/2 + $udy * 1/2;
            $by =  - $udx * 1/2 + $udy * sqrt(3)/2;
            
            $h = $l*0.6;
            
            $x3 = $line[2] + $h * $ax;
            $y3 = $line[3] + $h * $ay;
            
            $x4 = $line[2] + $h * $bx;
            $y4 = $line[3] + $h * $by;
            
            $_curve = new ImagickDraw();
            $_curve->setStrokeColor($line[4]);
            $_curve->setFillColor($line[4]);
            $_curve->setStrokeWidth($line[5]);
            $_curve->setStrokeOpacity($line[6]);
            $_curve->setFillOpacity(0);
            $_curve->setStrokeAntialias(true);
            $_curve->bezier(array(
                                   array('x' => $line[0], 'y' => $line[1]),
                                   array('x' => $x3, 'y' => $y3),
                                   array('x' => $line[2], 'y' => $line[3]),
                                   ));
            
            $tile->drawImage($_curve);
            //
            ////2
            //
            ////$t = ($line[8]*0.95)/$l;
            //$t = ($l - ($line[7] + $line[7]*0.1))/$l;
            //$x9 = (1 - $t) * (1 - $t) * $line[0] + 2 * (1 - $t) * $t * $x3 + $t * $t * $line[2];
            //$y9 = (1 - $t) * (1 - $t) * $line[1] + 2 * (1 - $t) * $t * $y3 + $t * $t * $line[3];
            //
            //$l = sqrt(pow($x9 - $line[0], 2) + pow($y9 - $line[1], 2));
            //
            //$dx = $line[0]-$x9;
            //$dy = $line[1]-$y9;
            //
            //$norm = sqrt($dx*$dx+$dy*$dy);
            //$udx = $dx/$norm;
            //$udy = $dy/$norm;
            //
            //$ax = $udx * sqrt(3)/2 - $udy * 1/2;
            //$ay = $udx * 1/2 + $udy * sqrt(3)/2;
            //
            //$bx = $udx * sqrt(3)/2 + $udy * 1/2;
            //$by =  - $udx * 1/2 + $udy * sqrt(3)/2;
            //
            //$h = $l*0.6;
            //
            //$x3 = $x9 + $h * $ax;
            //$y3 = $y9 + $h * $ay;
            //
            //$x4 = $x9 + $h * $bx;
            //$y4 = $y9 + $h * $by;
            //
            //$_curve = new ImagickDraw();
            //$_curve->setStrokeColor($line[4]);
            //$_curve->setFillColor($line[4]);
            //$_curve->setStrokeWidth($line[5]);
            //$_curve->setStrokeOpacity($line[6]);
            //$_curve->setFillOpacity(0);
            //$_curve->setStrokeAntialias(true);
            //$_curve->bezier(array(
            //                       array('x' => $line[0], 'y' => $line[1]),
            //                       array('x' => $x3, 'y' => $y3),
            //                       array('x' => $x9, 'y' => $y9),
            //                       ));
            //
            //$tile->drawImage($_curve);
            //
            ////LINE
            //
            ////print_r($line);
            ////$_line = new ImagickDraw();
            ////$_line->setFillColor($line[4]);
            ////$_line->setStrokeColor($line[4]);
            ////$_line->setStrokeWidth($line[5]);
            ////$_line->setStrokeOpacity($line[6]);
            ////$_line->setFillOpacity(0);
            ////$_line->setStrokeAntialias(true);
            //
            ////DOT
            //
            //$t = ($line[8]*0.95)/$l;
            //$x5 = (1 - $t) * (1 - $t) * $line[0] + 2 * (1 - $t) * $t * $x3 + $t * $t * $x9;
            //$y5 = (1 - $t) * (1 - $t) * $line[1] + 2 * (1 - $t) * $t * $y3 + $t * $t * $y9;
            //
            //$_dot = new ImagickDraw();
            //$_dot->setStrokeColor($line[4]);
            //$_dot->setFillColor($line[4]);
            //$_dot->setStrokeWidth(1);
            //$_dot->setStrokeOpacity(1);
            //$_dot->setFillOpacity(1);
            //$_dot->setStrokeAntialias(true);
            ////$_dot->setFillAntialias(true);
            //$_dot->ellipse($x5, $y5, ($line[5]/2+($line[5]*0.1)/2)*1.3, ($line[5]/2+($line[5]*0.1)/2)*1.3, 0, 360);
            //$tile->drawImage($_dot);
            //
            ////ARROW
            //
            //$t = ($l - $line[7] - $line[7]*0.1)/$l;
            //$line[0] = (1 - $t) * (1 - $t) * $line[0] + 2 * (1 - $t) * $t * $x3 + $t * $t * $x9;
            //$line[1] = (1 - $t) * (1 - $t) * $line[1] + 2 * (1 - $t) * $t * $y3 + $t * $t * $y9;
            //
            //$l = sqrt(pow($line[2] - $line[0], 2) + pow($line[3] - $line[1], 2));
            //$o = ($l + ($line[7] + $line[7]*0.1))/$l;
            ////$o = 1;
            //
            //$line[2] = ($x9 - $line[0])*$o + $line[0];
            //$line[3] = ($y9 - $line[1])*$o + $line[1];
            //$dx = $line[0]-$line[2];
            //$dy = $line[1]-$line[3];
            //
            //$norm = sqrt($dx*$dx+$dy*$dy);
            //$udx = $dx/$norm;
            //$udy = $dy/$norm;
            //
            //$ax = $udx * sqrt(3)/2 - $udy * 1/2;
            //$ay = $udx * 1/2 + $udy * sqrt(3)/2;
            //$bx = $udx * sqrt(3)/2 + $udy * 1/2;
            //$by =  - $udx * 1/2 + $udy * sqrt(3)/2;
            //
            //$x2 = $line[2] + $line[5]*2 * $ax;
            //$y2 = $line[3] + $line[5]*2 * $ay;
            //$x3 = $line[2] + $line[5]*2 * $bx;
            //$y3 = $line[3] + $line[5]*2 * $by;
            //
            //$_arrow = new ImagickDraw();
            //$_arrow->setStrokeColor($line[4]);
            //$_arrow->setFillColor($line[4]);
            //$_arrow->setStrokeWidth(2);
            //$_arrow->setStrokeOpacity($line[6]);
            ////$_arrow->setFillOpacity(0.4);
            //$_arrow->setFillOpacity(1);
            //$_arrow->setStrokeAntialias(true);
            ////$_arrow->setFillAntialias(true);
            //$_arrow->polygon(array(array('x' => $line[2], 'y' => $line[3]),array('x' => $x2, 'y' => $y2), array('x' => $x3, 'y' => $y3)));
            //$tile->drawImage($_arrow);
            
        }
    }
    
    if(is_file('./graphics/'.$tile_set.'/'.$z.'/'.$tile_x.'/'.$tile_y.'_circles.txt')){
        $data = array();
        foreach(file('./graphics/'.$tile_set.'/'.$z.'/'.$tile_x.'/'.$tile_y.'_circles.txt') as $json_line){
            $circle = json_decode($json_line, TRUE);
            
            $_circle = new ImagickDraw();
            $_circle->setStrokeColor($circle[5]);
            $_circle->setFillColor($circle[3]);
            $_circle->setStrokeWidth($circle[2]*0.1);
            $_circle->setStrokeAntialias(true);
            $_circle->ellipse($circle[0], $circle[1], $circle[2]/2, $circle[2]/2, 0, 360);
            $tile->drawImage($_circle);
            
            $d = json_decode(file_get_contents('./stats/'.$tile_set.'/'.$circle[8].'.json'), TRUE);
            $tmp_data = array(array($circle[0] - $circle[2]/2, $circle[1] - $circle[2]/2, $circle[0] + $circle[2]/2, $circle[1] + $circle[2]/2),
                            $circle[4],
                            array(),
                            array(),
                            $d['description'],
                            $d['link'],
                            $d['color']
                            );
            
            $in = $out = array();
            
            foreach($d['in'] as $key => $value){
                $in[$value['color']][] = array($value['label'], $value['x'], $value['y'], $value['color'], $value['size']);
            }
            foreach($d['out'] as $key => $value){
                $out[$value['color']][] = array($value['label'], $value['x'], $value['y'], $value['color'], $value['size']);
            }
            
            ksort($in);
            ksort($out);
            
            foreach($in as $color => $items){
                foreach($items as $item){
                    $tmp_data[2][] = $item;
                }
            }
            foreach($out as $color => $items){
                foreach($items as $item){
                    $tmp_data[3][] = $item;
                }
            }
            
            $data[] = $tmp_data;
            //$text_parts = explode(' ', $circle[4]);
            //$font_size = array();
            //
            //$_text = new ImagickDraw();
            //$_text->setStrokeColor($circle[7]);
            //$_text->setFillColor($circle[7]);
            //$_text->setTextAntialias(true);
            //$_text->setStrokeAntialias(true);
            //$_text->setFont("./regular.ttf");
            //$_text->setTextAlignment(\Imagick::ALIGN_CENTER);
            //$font_size = 0;
            //$bbox = array();
            //for($f = 100; $f >= 0; $f -= 2){
            //    $_text->setFontSize($f);
            //    $_text->setStrokeWidth($f);
            //    $font_size[$text_parts[0]] = $f;
            //    $bbox = $tile->queryFontMetrics($_text, $circle[4]);
            //    if($bbox['textWidth'] <= $circle[2] && $bbox['textHeight'] <= $text_parts[0]) break;
            //}
            //$_text->setFontSize($font_size[$text_parts[0]]);
            //$_text->setStrokeWidth($font_size[$text_parts[0]]*0.7);
            //$_text->annotation($circle[0], ($circle[1]+$bbox['textHeight']/4)-$bbox['textHeight'], $text_parts[0]);
            //if($font_size[$text_parts[0]] >= 6) $tile->drawImage($_text);
            //
            //$_text = new ImagickDraw();
            //$_text->setStrokeColor($circle[7]);
            //$_text->setFillColor($circle[7]);
            //$_text->setTextAntialias(true);
            //$_text->setStrokeAntialias(true);
            //$_text->setFont("./regular.ttf");
            //$_text->setTextAlignment(\Imagick::ALIGN_CENTER);
            //$font_size = 0;
            //$bbox = array();
            //for($f = 100; $f >= 0; $f -= 2){
            //    $_text->setFontSize($f);
            //    $_text->setStrokeWidth($f);
            //    $font_size[$text_parts[1]] = $f;
            //    $bbox = $tile->queryFontMetrics($_text, $circle[4]);
            //    if($bbox['textWidth'] <= $circle[2] && $bbox['textHeight'] <= $text_parts[1]) break;
            //}
            //$_text->setFontSize($font_size[$text_parts[1]]);
            //$_text->setStrokeWidth($font_size[$text_parts[1]]*0.7);
            //$_text->annotation($circle[0], $circle[1]+$bbox['textHeight']/4, $text_parts[1]);
            //if($font_size[$text_parts[1]] >= 6) $tile->drawImage($_text);
            //
            //$_text = new ImagickDraw();
            //$_text->setStrokeColor($circle[7]);
            //$_text->setFillColor($circle[7]);
            //$_text->setTextAntialias(true);
            //$_text->setStrokeAntialias(true);
            //$_text->setFont("./regular.ttf");
            //$_text->setTextAlignment(\Imagick::ALIGN_CENTER);
            //$font_size = 0;
            //$bbox = array();
            //for($f = 100; $f >= 0; $f -= 2){
            //    $_text->setFontSize($f);
            //    $_text->setStrokeWidth($f);
            //    $font_size[$text_parts[2]] = $f;
            //    $bbox = $tile->queryFontMetrics($_text, $circle[4]);
            //    if($bbox['textWidth'] <= $circle[2] && $bbox['textHeight'] <= $text_parts[2]) break;
            //}
            //$_text->setFontSize($font_size[$text_parts[2]]);
            //$_text->setStrokeWidth($font_size[$text_parts[2]]*0.7);
            //$_text->annotation($circle[0], ($circle[1]+$bbox['textHeight']/4)+$bbox['textHeight'], $text_parts[2]);
            //if($font_size[$text_parts[2]] >= 6) $tile->drawImage($_text);
            //
            //$_text = new ImagickDraw();
            //$_text->setStrokeColor($circle[6]);
            //$_text->setFillColor($circle[6]);
            //$_text->setTextAntialias(true);
            //$_text->setStrokeAntialias(true);
            //$_text->setFont("./regular.ttf");
            //$_text->setTextAlignment(\Imagick::ALIGN_CENTER);
            //$_text->setFontSize($font_size);
            //$_text->setStrokeWidth(0);
            //$_text->annotation($circle[0], $circle[1]+$bbox['textHeight']/4, $circle[4]);
            //if($f >= 6) $tile->drawImage($_text);
            
            $_text = new ImagickDraw();
            $_text->setStrokeColor($circle[7]);
            $_text->setFillColor($circle[7]);
            $_text->setTextAntialias(true);
            $_text->setStrokeAntialias(true);
            $_text->setFont("./regular.ttf");
            $_text->setTextAlignment(\Imagick::ALIGN_CENTER);
            $font_size = 0;
            $bbox = array();
            for($f = 100; $f >= 0; $f -= 2){
                $_text->setFontSize($f);
                $_text->setStrokeWidth($f);
                $font_size = $f;
                $bbox = $tile->queryFontMetrics($_text, $circle[4]);
                if($bbox['textWidth'] <= $circle[2] && $bbox['textHeight'] <= $circle[2]) break;
            }
            $_text->setFontSize($font_size);
            $_text->setStrokeWidth($font_size*0.7);
            $_text->annotation($circle[0], $circle[1]+$bbox['textHeight']/4, $circle[4]);
            if($f >= 6) $tile->drawImage($_text);
            
            $_text = new ImagickDraw();
            $_text->setStrokeColor($circle[6]);
            $_text->setFillColor($circle[6]);
            $_text->setTextAntialias(true);
            $_text->setStrokeAntialias(true);
            $_text->setFont("./regular.ttf");
            $_text->setTextAlignment(\Imagick::ALIGN_CENTER);
            $_text->setFontSize($font_size);
            $_text->setStrokeWidth(0);
            $_text->annotation($circle[0], $circle[1]+$bbox['textHeight']/4, $circle[4]);
            if($f >= 6) $tile->drawImage($_text);
            
        }
            if(!is_dir('./data/'.$tile_set.'/'.$z.'/'.$tile_x.'/'.$tile_y)) mkdir('./data/'.$tile_set.'/'.$z.'/'.$tile_x.'/'.$tile_y, 0777, TRUE);
        file_put_contents('./data/'.$tile_set.'/'.$z.'/'.$tile_x.'/'.$tile_y.'.json', json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    
    if(!is_dir($tiles_path.$z.'/'.$tile_x.'/')) mkdir($tiles_path.$z.'/'.$tile_x.'/', 0777, TRUE);
    file_put_contents($tiles_path.$z.'/'.$tile_x.'/'.$tile_y.'.png', $tile);
    
}

function calculate_node_coordinate($z, $zoom, $offset_x, $offset_y, $x1, $y1, $size, $fill_color, $stroke_color, $label, $tile_size, $text_fill_color, $text_stroke_color, $tile_set, $node_id){
    
    $label = json_decode($label);
    $label = $label[0];
    
    //echo $label;die();
    
    if($z <= $zoom){
        $ratio = pow(2, $zoom-$z);
        
        $x = ($x1+$offset_x)/$ratio;
        $y = ($y1+$offset_y)/$ratio;
        $tile_x = floor($x/$tile_size);
        $tile_y = floor($y/$tile_size);
        
        $step = ceil(($size/$ratio)/$tile_size);
        
        for($_x = -1*$step; $_x <= $step; $_x++){
            for($_y = -1*$step; $_y <= $step; $_y++){
                
                if($tile_x+$_x < 0 || $tile_y+$_y < 0) continue;
                
                if(!is_dir('./graphics/'.$tile_set.'/'.$z.'/'.($tile_x+$_x).'/')) mkdir('./graphics/'.$tile_set.'/'.$z.'/'.($tile_x+$_x).'/', 0777, TRUE);
                //if(is_file('./graphics/'.$z.'/'.($tile_x+$_x).'/'.($tile_y+$_y).'.json')){
                //    $graphics_data = json_decode(file_get_contents('./graphics/'.$z.'/'.($tile_x+$_x).'/'.($tile_y+$_y).'.json'), TRUE);   
                //}else{
                //    $graphics_data = array();
                //}
                
                //$graphics_data['circles'][] = array(
                //                                                $x - ($tile_x+$_x)*$tile_size,
                //                                                $y - ($tile_y+$_y)*$tile_size,
                //                                                $size/$ratio,
                //                                                $fill_color,
                //                                                $label,
                //                                                $stroke_color
                //                                            );
                
                file_put_contents('./graphics/'.$tile_set.'/'.$z.'/'.($tile_x+$_x).'/'.($tile_y+$_y).'_circles.txt', json_encode(array(
                                                                $x - ($tile_x+$_x)*$tile_size,
                                                                $y - ($tile_y+$_y)*$tile_size,
                                                                $size/$ratio,
                                                                $fill_color,
                                                                $label,
                                                                $stroke_color,
                                                                $text_fill_color,
                                                                $text_stroke_color,
                                                                $node_id
                                                            )).PHP_EOL, FILE_APPEND);
            }
        }
    }else{
        $ratio = pow(2, $z-$zoom);
        
        $x = ($x1+$offset_x)*$ratio;
        $y = ($y1+$offset_y)*$ratio;
        $tile_x = floor($x/$tile_size);
        $tile_y = floor($y/$tile_size);
        
        $step = ceil(($size*$ratio)/$tile_size);
        
        for($_x = -1*$step; $_x <= $step; $_x++){
            for($_y = -1*$step; $_y <= $step; $_y++){
                
                if($tile_x+$_x < 0 || $tile_y+$_y < 0) continue;
                
                if(!is_dir('./graphics/'.$tile_set.'/'.$z.'/'.($tile_x+$_x).'/')) mkdir('./graphics/'.$tile_set.'/'.$z.'/'.($tile_x+$_x).'/', 0777, TRUE);
                //if(is_file('./graphics/'.$z.'/'.($tile_x+$_x).'/'.($tile_y+$_y).'.json')){
                //    $graphics_data = json_decode(file_get_contents('./graphics/'.$z.'/'.($tile_x+$_x).'/'.($tile_y+$_y).'.json'), TRUE);   
                //}else{
                //    $graphics_data = array();
                //}
                
                //$graphics_data['circles'][] = array(
                //                                                $x - ($tile_x+$_x)*$tile_size,
                //                                                $y - ($tile_y+$_y)*$tile_size,
                //                                                $size*$ratio,
                //                                                $fill_color,
                //                                                $label,
                //                                                $stroke_color
                //                                            );
                file_put_contents('./graphics/'.$tile_set.'/'.$z.'/'.($tile_x+$_x).'/'.($tile_y+$_y).'_circles.txt', json_encode(array(
                                                                $x - ($tile_x+$_x)*$tile_size,
                                                                $y - ($tile_y+$_y)*$tile_size,
                                                                $size*$ratio,
                                                                $fill_color,
                                                                $label,
                                                                $stroke_color,
                                                                $text_fill_color,
                                                                $text_stroke_color,
                                                                $node_id
                                                            )).PHP_EOL, FILE_APPEND);
            }
        }  
    }
}

function calculate_edge_coordinate($z, $zoom, $offset_x, $offset_y, $x1, $x2, $y1, $y2, $tile_size, $line_color, $weight, $arrow_offset, $opacity, $tile_set){
    //echo $z.PHP_EOL;
    //echo $offset_x.PHP_EOL;
    //echo $x1.PHP_EOL;
    //sleep(10);
    
    //$weight = intval($weight);
    //$weight = 20;
    //$opacity = 0.2;
    $arrow_offset = $arrow_offset/2;
    $dot_offset = $weight/2;
    $weight = $weight*0.1;
    if($weight < 1) $weight = 1;
    
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
    
    $x4 = $x2 + $h * $bx;
    $y4 = $y2 + $h * $by;
    
    if($z <= $zoom){
        
        $t = array();    
        $ratio = pow(2, $zoom-$z);
        $last_tile_x = $last_tile_y = -1;
        for($o = 0; $o <= 1; $o += 0.001){
            
            $x = (1 - $o) * (1 - $o) * ($x1+$offset_x)/$ratio + 2 * (1 - $o) * $o * ($x3+$offset_x)/$ratio + $o * $o * ($x2+$offset_x)/$ratio;
            $y = (1 - $o) * (1 - $o) * ($y1+$offset_y)/$ratio + 2 * (1 - $o) * $o * ($y3+$offset_y)/$ratio + $o * $o * ($y2+$offset_y)/$ratio;
            //$w = $weight*2;
            //
            //for($w1 = -1*$w; $w1 <= $w; $w1 += $w){    
            //    for($w2 = -1*$w; $w2 <= $w; $w2 += $w){
                    
            //$x = $x + $w1;
            //$y = $y + $w2;
                    
            for($dx = -1; $dx <= 1; $dx++){
                for($dy = -1; $dy <= 1; $dy++){
                    
                    $tile_x = floor($x/$tile_size);
                    $tile_y = floor($y/$tile_size);
                    
                    $tile_x += $dx;
                    $tile_y += $dy;
                    
                    if($tile_x < 0 || $tile_y < 0) continue;
                    if(isset($t[$z.'_'.$tile_x.'_'.$tile_y])) continue;
                    $t[$z.'_'.$tile_x.'_'.$tile_y] = 1;
                    
                    if(!is_dir('./graphics/'.$tile_set.'/'.$z.'/'.$tile_x.'/')) mkdir('./graphics/'.$tile_set.'/'.$z.'/'.$tile_x.'/', 0777, TRUE);
                    file_put_contents('./graphics/'.$tile_set.'/'.$z.'/'.$tile_x.'/'.$tile_y.'_lines.txt', json_encode(array(
                                                                        ($x1+$offset_x)/$ratio - $tile_x*$tile_size,
                                                                        ($y1+$offset_y)/$ratio - $tile_y*$tile_size,
                                                                        
                                                                        ($x2+$offset_x)/$ratio - $tile_x*$tile_size,
                                                                        ($y2+$offset_y)/$ratio - $tile_y*$tile_size,
                                                                        
                                                                        $line_color,
                                                                        $weight/$ratio,
                                                                        $opacity,
                                                                        $arrow_offset/$ratio,
                                                                        $dot_offset/$ratio
                    )).PHP_EOL, FILE_APPEND);
                }
            }
        }
    }else{
        
        $t = array();
        $ratio = pow(2, $z-$zoom);
        $last_tile_x = $last_tile_y = -1;
        for($o = 0; $o <= 1; $o += 0.001){
            
            $x = (1 - $o) * (1 - $o) * ($x1+$offset_x)*$ratio + 2 * (1 - $o) * $o * ($x3+$offset_x)*$ratio + $o * $o * ($x2+$offset_x)*$ratio;
            $y = (1 - $o) * (1 - $o) * ($y1+$offset_y)*$ratio + 2 * (1 - $o) * $o * ($y3+$offset_y)*$ratio + $o * $o * ($y2+$offset_y)*$ratio;
            //$w = $weight*2;
            //
            //for($w1 = -1*$w; $w1 <= $w; $w1 += $w){    
            //    for($w2 = -1*$w; $w2 <= $w; $w2 += $w){
                    
            //$x = $x + $w1;
            //$y = $y + $w2;
                    
            for($dx = -1; $dx <= 1; $dx++){
                for($dy = -1; $dy <= 1; $dy++){
                    
                    $tile_x = floor($x/$tile_size);
                    $tile_y = floor($y/$tile_size);
                    
                    $tile_x += $dx;
                    $tile_y += $dy;
                    
                    if($tile_x < 0 || $tile_y < 0) continue;
                    if(isset($t[$z.'_'.$tile_x.'_'.$tile_y])) continue;
                    $t[$z.'_'.$tile_x.'_'.$tile_y] = 1;
            
                    if(!is_dir('./graphics/'.$tile_set.'/'.$z.'/'.$tile_x.'/')) mkdir('./graphics/'.$tile_set.'/'.$z.'/'.$tile_x.'/', 0777, TRUE);
                    file_put_contents('./graphics/'.$tile_set.'/'.$z.'/'.$tile_x.'/'.$tile_y.'_lines.txt', json_encode(array(
                                                                        ($x1+$offset_x)*$ratio - $tile_x*$tile_size,
                                                                        ($y1+$offset_y)*$ratio - $tile_y*$tile_size,
                                                                        
                                                                        ($x2+$offset_x)*$ratio - $tile_x*$tile_size,
                                                                        ($y2+$offset_y)*$ratio - $tile_y*$tile_size,
                                                                        
                                                                        $line_color,
                                                                        $weight*$ratio,
                                                                        $opacity,
                                                                        $arrow_offset*$ratio,
                                                                        $dot_offset*$ratio
                    )).PHP_EOL, FILE_APPEND);
                }
            }
        }
    }
}

function imagelinethick($image, $x1, $y1, $x2, $y2, $color, $thick = 1)
{
    if ($thick == 1) {
        return imageline($image, $x1, $y1, $x2, $y2, $color);
    }
    $t = $thick / 2 - 0.5;
    if ($x1 == $x2 || $y1 == $y2) {
        return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
    }
    $k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
    $a = $t / sqrt(1 + pow($k, 2));
    $points = array(
        round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
        round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
        round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
        round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
    );
    imagefilledpolygon($image, $points, 4, $color);
    return imagepolygon($image, $points, 4, $color);
}
function imagettfstroketext(&$image, $size, $angle, $x, $y, &$textcolor, &$strokecolor, $fontfile, $text, $px) {
    for($c1 = ($x-abs($px)); $c1 <= ($x+abs($px)); $c1++)
        for($c2 = ($y-abs($px)); $c2 <= ($y+abs($px)); $c2++)
            $bg = imagettftext($image, $size, $angle, $c1, $c2, $strokecolor, $fontfile, $text);
   return imagettftext($image, $size, $angle, $x, $y, $textcolor, $fontfile, $text);
}
?>
