<?php
/*
User form for initiating a bulk ingest.  User must have already uploaded ingestion folders to a server-accessible folder.
Author: Terry Brady, Georgetown University Libraries

License information is contained below.

Copyright (c) 2013, Georgetown University Libraries All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer. 
in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials 
provided with the distribution. THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, 
BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES 
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) 
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/
include '../web/header.php';

$CUSTOM = custom::instance();
$CUSTOM->getCommunityInit()->initCommunities();
$CUSTOM->getCommunityInit()->initCollections();

$ingestLoc =  $CUSTOM->getIngestLoc();

$status = "";

$hasPerm = $CUSTOM->isUserCollectionOwner();
if ($hasPerm) testArgs();
header('Content-type: text/html; charset=UTF-8');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<?php 
$header = new LitHeader("Bulk Ingest Zip File");
$header->litPageHeader();
?>
</head>
<body>
<?php $header->litHeaderAuth(array(), $hasPerm);?>
<div id="formIngestZip">
<form method="POST" action="" onsubmit="jobQueueUpload();return true;" enctype="multipart/form-data">
<p>This process will trigger a bulk ingest of content.</p>
<p>Assumptions:</p>
<ol>
<li>Content has been prepared into ingest folders </li>
<li>Ingest folder metadata has been validated</li>
<li>Ingest folders have been zipped into a single file</li>
</ol>
<div id="status"><?php echo $status?></div>
<?php 
collection::getCollectionWidget(util::getPostArg("community",""), util::getPostArg("collection",""));
$skipindex = (util::getPostArg("skipindex","") == "Y") ? "checked" : "";
?>
<p>
  <label for="skipindex">Skip <?php echo ($CUSTOM->ver < 4) ? "Full Text/" : ""; ?>Solr Index Update</label>
  <input type="checkbox" id="skipindex" name="skipindex" value="Y" {$skipindex}/>
</p>
<p>
<fieldset class="loc">
<legend>Upload Zip File * </legend>
<p>
  <label for="zip">File to upload, max size allowed: <?php echo ini_get("upload_max_filesize")?></label>
  <input type="file" name="zip" id="zip"/>
</p>
</fieldset>
</p>
<p align="center">
	<input id="ingestSubmit" type="submit" title="Submit Job" disabled/>
</p>
</form>
</div>

<?php $header->litFooter();?>
</body>
</html>
<?php 
function testArgs(){
	global $status;
	global $ingestLoc;
	$CUSTOM = custom::instance();
	$dspaceBatch = $CUSTOM->getDspaceBatch();
	$mroot =  $CUSTOM->getMapRoot();
	$bgindicator =  $CUSTOM->getBgindicator();
	
	$coll = util::getPostArg("collection","");
	$status = custom::instance()->validateCollection($coll);
	if ($status != "") return;
	
	if (count($_POST) == 0) {
		$status = "";
		return;
	}
	if ($_FILES["zip"]["error"]) {
		$name = isset($_FILES["zip"]["tmp_name"]) ? $_FILES["zip"]["tmp_name"] : $_FILES["zip"]["name"];
		$status = "File upload error: " . $_FILES["zip"]["error"] . " on file " . $name;
		return;
	}
	
	$loc = $ingestLoc . $_FILES["zip"]["name"];
	move_uploaded_file($_FILES["zip"]["tmp_name"], $loc);	

	$batch = date("Ymd_H.i.s");
	$mapfile = $mroot . $batch;
	
	$u = escapeshellarg($CUSTOM->getCurrentUser());
	$user = escapeshellarg($CUSTOM->getCurrentUserEmail());
	$coll = escapeshellarg($coll);
	$loc = escapeshellarg($loc);
	$mapfile = escapeshellarg($mapfile);
	
	$mode = (util::getPostArg("skipindex","") == "Y") ? "gu-ingest-zip-skipindex" : "gu-ingest-zip";

	$cmd = <<< HERE
{$u} {$mode} {$user} {$coll} {$loc} {$mapfile}
HERE;
    
    //echo($dspaceBatch . " " .$cmd);
    exec($dspaceBatch . " " . $cmd . " " . $bgindicator);
    header("Location: ../web/queue.php");
}
?>
