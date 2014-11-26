<?php
/*
User form for initiating the move of a collection to another community.  Note: in order to properly re-index the repository, 
DSpace will need to be taken offline after running this operation.
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
function initFields($CUSTOM) {

$sql = <<< EOF
select 
  mfr.metadata_field_id, 
  msr.short_id, 
  mfr.element, 
  mfr.qualifier, 
  (msr.short_id || '.' || mfr.element || case when mfr.qualifier is null then '' else '.' || mfr.qualifier end) as name
from metadatafieldregistry mfr
inner join metadataschemaregistry msr on msr.metadata_schema_id=mfr.metadata_schema_id
order by msr.short_id, mfr.element, mfr.qualifier;
EOF;

$dbh = $CUSTOM->getPdoDb();
$stmt = $dbh->prepare($sql);

$result = $stmt->execute(array());

$result = $stmt->fetchAll();

if (!$result) {
    print($sql);
    print_r($dbh->errorInfo());
    die("Error in SQL query");
}       

$mfields = array();
foreach ($result as $row) {
    $mfi = $row[0];
    $mfn = $row[4];
    $mfields[$mfi] = $mfn;
}

return $mfields;
}

function sel($val,$test) {
    return ($val == $test) ? 'selected' : '';
} 

?>