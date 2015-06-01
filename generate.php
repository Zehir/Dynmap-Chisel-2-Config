#!/usr/bin/php
<?php
$chiselGitDir = "Chisel-2";
$relativeAssetsDir = "assets/chisel/textures/blocks";
$chiselBlocksAssetsDir = "$chiselGitDir/src/main/resources/$relativeAssetsDir";
$chiselFeatures = "$chiselGitDir/src/main/java/com/cricketcraft/chisel/Features.java";

$chiselVersion = "2.3.7.34";

$modname = "chisel";

$headers = array(
    'version' => 1.7,
    'modname' => $modname,
    'cfgfile' => "config/chisel.cfg",
    'texturepath' => "assets/chisel/textures/blocks/"
);

$minecraftValues = array(
    'ItemDye.field_150921_b' => array(
        "black",
        "red",
        "green",
        "brown",
        "blue",
        "purple",
        "cyan",
        "silver",
        "gray",
        "pink",
        "lime",
        "yellow",
        "light_blue",
        "magenta",
        "orange",
        "white"
    )
);

$chiselValues = array(
    'sGNames' => array(
        "White",
        "Orange",
        "Magenta",
        "Light Blue",
        "Yellow",
        "Lime",
        "Pink",
        "Gray",
        "Light Gray",
        "Cyan",
        "Purple",
        "Blue",
        "Brown",
        "Green",
        "Red",
        "Black"
    ) ,
    'plank_names' => array(
        "oak",
        "spruce",
        "birch",
        "jungle",
        "acacia",
        "dark-oak"
    ) ,
    'plank_ucnames' => array(
        "Oak",
        "Spruce",
        "Birch",
        "Jungle",
        "Acacia",
        "Dark Oak"
    )
);

define("DEBUG", false);

echo "---------------------------------------------------------------------\n";

echo "# " . ucfirst($modname) . " $chiselVersion\n";
foreach ($headers as $key => $value) {
    echo "$key:$value\n";
}

echo "---------------------------------------------------------------------\n";

// # chisel 2.3.7.34
// version:1.7
// modname:chisel

// cfgfile:config/chisel.cfg

// texturepath:assets/chisel/textures/blocks/

// texture:id=chisel.stonebrick_fancy,filename=assets/chisel/textures/blocks/stonebrick/fancy.png

// block:id=%stonebricksmooth,data=9,stdrot=true,allfaces=0:chisel.stonebrick_fancy

// Loop through our array, show HTML source as HTML source; and line numbers too.

$currentID = null;
$texturesList = array();

foreach (array_slice(file($chiselFeatures

/*, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES*/) , 0, 40003) as $line_num => $line) {
    
    $line = trim($line);
    if ((substr($line, 0, 27) == "Carving.chisel.addVariation") or (substr($line, 0, 2) == "//")) {
        continue;
    }
    
    if (strpos($line, 'addVariation') !== false) {
        $newCurrentID = substr($line, 0, strpos($line, '.'));
        if ($currentID != $newCurrentID) {
            $currentID = $newCurrentID;
        }
        
        $functionArgs = str_replace(' ', '', substr($line, strlen($currentID) + 27, -2));
        $done = checkSpecial($currentID, $functionArgs);
        
        foreach ($minecraftValues as $key1 => $value1) {
            
            if (strpos($functionArgs, $key1)) {
                $done = true;
                foreach ($minecraftValues[$key1] as $key2 => $value2) {
                    $newArgs = str_replace("\"+" . $key1 . "[i]+\"", $value2, $functionArgs);
                    $newArgs = str_replace(",i,", ",$key2,", $newArgs);
                    addTexture($currentID, $newArgs);
                }
            }
        }
        
        if (!$done) {
            addTexture($currentID, $functionArgs);
        }
        
        // texture:id=chisel.stonebrick_fancy,filename=assets/chisel/textures/blocks/stonebrick/fancy.png
        // block:id=%stonebricksmooth,data=9,stdrot=true,allfaces=0:chisel.stonebrick_fancy
        
        // code...
        
        
    } 
    elseif (strpos($line, 'registerAll') !== false) {
        if (is_null($currentID)) {
            continue;
        }
        $done = checkSpecial($currentID);
        if (!$done) {
            addBlocID($currentID, substr($line, strlen($currentID) * 2 + 29, -3));
            $currentID = null;
        }
    } 
    else {
        continue;
    }
    
    // echo "$line_num:$line\n";
    
    
}

function addTexture($currentID, $args) {
    global $texturesList;
    
    $functionArgs = explode(",", $args);
    
    if (!array_key_exists($currentID, $texturesList)) {
        $texturesList[$currentID] = array();
    }
    
    if (!array_key_exists('variations', $texturesList[$currentID])) {
        $texturesList[$currentID]['variations'] = array();
    }
    
    $textureName = str_replace("\"", "", $functionArgs[2]);

    $texturesList[$currentID]['variations'][$functionArgs[1]] = $textureName;
    
    if (constant("DEBUG")) {
        ksort($texturesList[$currentID]['variations']);
        print ("Adding new Texture for $currentID:" . $functionArgs[1] . ":" . $functionArgs[2] . "\n");
    }
}

function addBlocID($currentID, $blocID) {
    global $texturesList;
    if ($currentID != $blocID) {
        $texturesList[$currentID]['bloc_id'] = $blocID;
    }
    if (constant("DEBUG")) {
        ksort($texturesList[$currentID]);
        print ("Adding bloc id for $currentID: $blocID\n");
    }
}

function checkSpecial($currentID, $args = null) {
    if (is_null($args) && in_array($currentID, array(
        'stainedGlass[glassId]',
        'stainedGlassPane[glassId]',
        'planks[i]'
    ))) {
        return true;
    }
    
    $functionArgs = explode(",", $args);
    global $texturesList;
    global $chiselValues;
    
    switch ($currentID) {
        case 'stainedGlass[glassId]':
            $texturesList[$currentID]['transparent'] = true;
            for ($i = 0; $i < count($chiselValues['sGNames']); $i++) {
                $oreName = "stainedGlass" . str_replace(" ", "", $chiselValues['sGNames'][$i]);
                $texName = "glassdyed/" . strtolower(str_replace(" ", "", $chiselValues['sGNames'][$i])) . "-" . str_replace('texName+', '', str_replace("\"", "", $functionArgs[2]));
                $glassPrefix = ($i & 3) << 2;
                $glassId = $i >> 2;
                $currentID = "stained_glass_" . strtolower(str_replace(" ", "", $chiselValues['sGNames'][$glassId * 4]));
                addTexture($currentID, implode(",", array(
                    $oreName,
                    $glassPrefix + preg_replace("/[^0-9]/", "", $functionArgs[1]) ,
                    $texName
                )));
            }
            
            break;

        case 'stainedGlassPane[glassId]':
            
            $texturesList[$currentID]['transparent'] = true;
            for ($i = 0; $i < count($chiselValues['sGNames']); $i++) {
                $oreName = "stainedGlassPane" . str_replace(" ", "", $chiselValues['sGNames'][$i]);
                $texName = "glasspanedyed/" . strtolower(str_replace(" ", "", $chiselValues['sGNames'][$i])) . "-" . str_replace('texName+', '', str_replace("\"", "", $functionArgs[2]));
                $glassPrefix = ($i & 1) << 3;
                $glassId = $i >> 1;
                $currentID = "stained_glass_pane_" . strtolower(str_replace(" ", "", $chiselValues['sGNames'][$glassId * 2]));
                addTexture($currentID, implode(",", array(
                    $oreName,
                    $glassPrefix + preg_replace("/[^0-9]/", "", $functionArgs[1]) ,
                    $texName
                )));
            }
            
            break;

        case 'planks[i]':
            
            for ($i = 0; $i < count($chiselValues['plank_names']); $i++) {
                $args = str_replace("\"+n+\"", $chiselValues['plank_names'][$i], $args);
                $args = str_replace("u+\"", $chiselValues['plank_ucnames'][$i], $args);
                $args = str_replace("\"", "", $args);
                addTexture(str_replace("-", "_", $chiselValues['plank_names'][$i]) . "_planks", $args);
            }
            break;

        case 'woolen_clay':
            for ($i = 0; $i < count($chiselValues['sGNames']); $i++) {
                addTexture($currentID, implode(",", array(
                    "tile.woolenClay.$i.desc",
                    $i,
                    "woolenClay/" . strtolower(str_replace(" ", "", $chiselValues['sGNames'][$i]))
                )));
            }
            
            break;

        case 'present':
            for ($i = 0; $i < 16; $i++) {
                addTexture($currentID, implode(",", array(
                    "tile.chisel.present.desc",
                    $i,
                    "present/presentChest$i"
                )));
                $texturesList[$currentID]['type'] = 'chest';
            }
            
            break;

        default:
            
            return false;
            break;
    }
    
    return true;
}

foreach ($texturesList as $id => $data) {
    if (!array_key_exists("bloc_id", $data)) {
        $data['bloc_id'] = $id;
    }
    
    foreach ($data['variations'] as $data_id => $textureFile) {
        $textureOptions = array();
        
        foreach (array(
            'any' => ".png",
            'ctmv' => "-ctmv.png",
            'ctmh' => "-ctmh.png",
            'side' => "-side.png",
            'top' => "-top.png",
            'bot' => "-bottom.png",
            'v9' => "-v9.png",
            'v4' => "-v4.png",
            'ctmx' => "-ctm.png",
            'r16' => "-r16.png",
            'r9' => "-r9.png",
            'r4' => "-r4.png",
        ) as $vc => $ext) {
            if (file_exists("$chiselBlocksAssetsDir/$textureFile$ext")) {
                $textureOptions[$vc] = true;
                print ("texture:id=$modname.{$data['bloc_id']}_$vc");
                print (",filename=$relativeAssetsDir/$textureFile$ext");
                print ("\n");
            }
        }
        
        if (array_key_exists('ctmh', $textureOptions) && array_key_exists('top', $textureOptions)) {
            $variation_kind = 5;
        } 
        else if (array_key_exists('ctmv', $textureOptions) && array_key_exists('top', $textureOptions)) {
            $variation_kind = 4;
        } 
        else if (array_key_exists('bot', $textureOptions) && array_key_exists('top', $textureOptions) && array_key_exists('side', $textureOptions)) {
            $variation_kind = 2;
        } 
        else if (array_key_exists('top', $textureOptions) && array_key_exists('side', $textureOptions)) {
            $variation_kind = 1;
        } 
        else if (array_key_exists('v9', $textureOptions)) {
            $variation_kind = 6;
        } 
        else if (array_key_exists('v4', $textureOptions)) {
            $variation_kind = 7;
        } 
        else if (array_key_exists('any', $textureOptions) && array_key_exists('ctmx', $textureOptions)) {
            $variation_kind = 8;
        } 
        else if (array_key_exists('r16', $textureOptions)) {
            $variation_kind = 9;
        } 
        else if (array_key_exists('r9', $textureOptions)) {
            $variation_kind = 10;
        } 
        else if (array_key_exists('r4', $textureOptions)) {
            $variation_kind = 11;
        } 
        else if (array_key_exists('any', $textureOptions)) {
            $variation_kind = 0;
        } 
        else {
            Print ("No valid textures found for chisel block variation '" . $data['bloc_id'] . "' ($textureFile)\n");
            continue;
        }
        
        switch ($variation_kind) {
            case 0:
            case 8:
                
                //Any
                print ("block:id=%{$data['bloc_id']},data=$data_id,stdrot=true");
                print (",allfaces=0:$modname.{$data['bloc_id']}_any");
                break;

            case 1:
                
                //Top Side
                print ("block:id=%{$data['bloc_id']},data=$data_id,stdrot=true");
                print (",allsides=0:$modname.{$data['bloc_id']}_side");
                print (",topbottom=0:$modname.{$data['bloc_id']}_top");
                break;

            case 2:
                
                // Top Bot Side
                print ("block:id=%{$data['bloc_id']},data=$data_id,stdrot=true");
                print (",allsides=0:$modname.{$data['bloc_id']}_side");
                print (",top=0:$modname.{$data['bloc_id']}_top");
                print (",bottom=0:$modname.{$data['bloc_id']}_bot");
                break;

            default:
                
                break;
        }
        print ("\n");
        
        // print ("texture:id={$headers['modname']}.{$data['bloc_id']}");
        
        // texture:id=chisel.stonebrick_fancy,filename=assets/chisel/textures/blocks/stonebrick/fancy.png
        
        
    }
}

// print_r(['texturesList' => $texturesList]);
