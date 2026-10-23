<?php
function requireAdminPost():void{
    if(session_status()!==PHP_SESSION_ACTIVE)session_start();
    if($_SERVER["REQUEST_METHOD"]!=="POST"||!isset($_SESSION["user_id"])||($_SESSION["role"]??"")!=="admin"){
        http_response_code(403);
        exit("Administrator access is required.");
    }
    if(!isset($_SESSION["csrf_token"],$_POST["csrf_token"])||!hash_equals($_SESSION["csrf_token"],(string)$_POST["csrf_token"])){
        http_response_code(403);
        exit("Your session has expired. Refresh the page and try again.");
    }
}

function saveAdminUpload(array $file):?array{
    if(($file["error"]??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return null;
    if(($file["error"]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK){
        throw new RuntimeException("A media file could not be uploaded. Check the file size and try again.");
    }
    $tmp=(string)($file["tmp_name"]??"");
    $size=(int)($file["size"]??0);
    if($size<=0||!is_uploaded_file($tmp)){
        throw new RuntimeException("The uploaded media file is invalid.");
    }

    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $imageTypes=[
        "image/jpeg"=>["jpg",8*1024*1024],
        "image/png"=>["png",8*1024*1024],
        "image/webp"=>["webp",8*1024*1024],
        "image/gif"=>["gif",8*1024*1024]
    ];
    $videoTypes=[
        "video/mp4"=>["mp4",30*1024*1024],
        "video/webm"=>["webm",30*1024*1024],
        "video/ogg"=>["ogv",30*1024*1024]
    ];
    $types=$imageTypes+$videoTypes;
    if(!isset($types[$mime])){
        throw new RuntimeException("Use JPG, PNG, WebP, or GIF images, or MP4, WebM, or OGG videos.");
    }
    [$extension,$maxSize]=$types[$mime];
    if($size>$maxSize){
        throw new RuntimeException(strpos((string)$mime,"video/")===0?"Videos must be 30 MB or smaller.":"Images must be 8 MB or smaller.");
    }
    if(strpos((string)$mime,"image/")===0){
        $imageInfo=getimagesize($tmp);
        if(!$imageInfo||($imageInfo["mime"]??"")!==$mime||(int)$imageInfo[0]>10000||(int)$imageInfo[1]>10000||(int)$imageInfo[0]*(int)$imageInfo[1]>40000000){
            throw new RuntimeException("The uploaded image is invalid.");
        }
    }

    $directory=__DIR__."/../uploads/store";
    if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory)){
        throw new RuntimeException("Unable to prepare the media upload directory.");
    }
    $filename=bin2hex(random_bytes(16)).".".$extension;
    if(!move_uploaded_file($tmp,$directory."/".$filename)){
        throw new RuntimeException("Unable to save the uploaded media file.");
    }
    return[
        "type"=>strpos((string)$mime,"video/")===0?"video":"image",
        "path"=>"uploads/store/".$filename,
        "size"=>$size
    ];
}

function saveAdminUploads(array $files):array{
    if(!isset($files["name"])||!is_array($files["name"]))return[];
    if(count($files["name"])>19)throw new RuntimeException("Upload no more than 19 additional images or videos at once.");
    $saved=[];
    $totalSize=0;
    try{
        foreach($files["name"] as $index=>$name){
            if($name==="")continue;
            $file=[
                "name"=>$name,
                "type"=>$files["type"][$index]??"",
                "tmp_name"=>$files["tmp_name"][$index]??"",
                "error"=>$files["error"][$index]??UPLOAD_ERR_NO_FILE,
                "size"=>$files["size"][$index]??0
            ];
            $media=saveAdminUpload($file);
            if($media){
                $totalSize+=$media["size"];
                $saved[]=$media;
                if($totalSize>35*1024*1024)throw new RuntimeException("Additional media uploads must total 35 MB or less per save.");
            }
        }
    }catch(Throwable $error){
        foreach($saved as $media)deleteAdminUpload($media["path"]);
        throw$error;
    }
    return$saved;
}

function deleteAdminUpload(string $path):void{
    $prefix="uploads/store/";
    if(strncmp($path,$prefix,strlen($prefix))!==0||basename($path)!==substr($path,strlen($prefix)))return;
    $file=__DIR__."/../".$path;
    if(is_file($file))unlink($file);
}
?>
