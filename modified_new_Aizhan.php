<?php
/*
 * Work/School Note Form new.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Nikolai Vitsyn
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2004-2005 Nikolai Vitsyn
 * @copyright Copyright (c) Open Source Medical Software
 * @copyright Copyright (c) 2019 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */


require_once("../../globals.php");
require_once("$srcdir/api.inc");

use OpenEMR\Common\Csrf\CsrfUtils;

formHeader("Form: MedRec");
$returnurl = 'encounter_top.php';
$provider_results = sqlQuery("select fname, lname from users where username=?", array($_SESSION["authUser"]));
/* name of this form */
$form_name = "MedRec";
?>

<html><head>

<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
<link rel="stylesheet" href="<?php echo $GLOBALS['assets_static_relative']; ?>/jquery-datetimepicker/build/jquery.datetimepicker.min.css">

<!-- supporting javascript code -->
<script type="text/javascript" src="<?php echo $GLOBALS['assets_static_relative']; ?>/jquery/dist/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/textformat.js?v=<?php echo $v_js_includes; ?>"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['assets_static_relative']; ?>/jquery-datetimepicker/build/jquery.datetimepicker.full.min.js"></script>

<script language="JavaScript">
// required for textbox date verification
var mypcc = <?php echo js_escape($GLOBALS['phone_country_code']); ?>;
</script>
<style>
    table, th, td {
      border: 1px solid black;
    }
  </style>

<!-- start custom head content -->
<script language="JavaScript">

var queryPts = null;
var queryPubpid = null;
var queryAddMed = null;

function doFHIR() {	
	queryPubpid = new Queries();
	queryPubpid.callback = doPubpidCallback;
	queryPubpid.sql = "SELECT pubpid FROM patient_data where pid=<?php echo $pid ?>;";
	queryPubpid.params = queryPubpid.sql;
	queryPubpid.Execute();
}
function doPubpidCallback(query) {
	var pubpid = query.Values[0]['pubpid'];
	var type = 'MedicationOrder?patient=';
	var url = $('#url')[0].value + type + pubpid;
	alert(url);
    $.ajax({
        url: url,
        headers: {          
            Accept: "application/json+fhir",
            "Content-Type": "text/plain; charset=utf-8" 
        }
    }).then(function(data) {
		try {
            var strRslt = JSON.stringify(data,null,'\t');
            $('#txtRslt')[0].value = strRslt;
            
            processFHIR_JSON(data);
		}
		catch (e) {alert(e);}
    });
}
function processFHIR_JSON(data) {
    console.log("Hello");

    try {
        var strRslt = JSON.stringify(data, null, '\t');
        $('#txtRslt')[0].value = strRslt;

        $('#spnRsrc')[0].innerHTML = '';

		if (data.entry) {
        var items = data.entry;
        console.log(items);
        var numItems = items.length;
        if (numItems > 20) {
            alert('Found ' + numItems + '. Showing only first 20');
            numItems = 20;}
		}

        // creating the tableMeds
        var tableMeds = '<table>';
        tableMeds += '<tr><th>Drug Name</th><th>Timing</th><th>Route</th><th>Dosage</th></tr>';
        var tableData = ''; // storing data
        console.log(numItems);

        for (var i = 0; i < numItems; i++) {
                            var resource = items[i].resource;

                            if (resource.resourceType === 'MedicationOrder') {
                                var medication = resource.medicationCodeableConcept?.text || 'n/a';

                                var timing = 'n/a'; // initializing timing with 'n/a'
                                if (resource.dosageInstruction && resource.dosageInstruction[0].timing) {
                                    timing = resource.dosageInstruction[0].timing.code.text || 'n/a';
                                }

                                var route = 'n/a'; // initializing route with 'n/a'
                                if (resource.dosageInstruction && resource.dosageInstruction[0].route) {
                                    route = resource.dosageInstruction[0].route?.text || 'n/a';
                                }

                                var dose = 'n/a'; // initializing dose with 'n/a'
                                var unit = 'n/a'; // initializing unit with 'n/a'
                                if (resource.dosageInstruction && resource.dosageInstruction[0].doseQuantity) {
                                    dose = resource.dosageInstruction[0].doseQuantity.value || 'n/a';
                                    unit = resource.dosageInstruction[0].doseQuantity.unit || 'n/a';
                                }

                                var medicationString = medication + ' ' + dose + ' ' + unit;

                                tableData += '<tr><td>' + medicationString + '</td><td>' + timing + '</td><td>' + route + '</td><td>' + dose + ' ' + unit + '</td></tr>';
                            }
                        }

        if (tableData) {
            tableMeds += tableData;
        } else {
            // if not founded display the message
            tableMeds += '<tr><td colspan="4">The patient has no medication prescribed.</td></tr>';
        }

        tableMeds += '</table>';
        $('#medtable')[0].innerHTML = tableMeds;
    } catch (e) {
        alert(e);
        $('#medtable')[0].innerHTML = '<table><tr><td colspan="4">Error: Failed to process.</td></tr></table>';
    }
}



/*****  start read from / write to openEMR db directly ********/

function Queries() {
	this.callback = null;
	this.sql = null;
	this.params = null;
	this.Columns = new Array();
	this.Values = new Array();
	this.Execute = function() {
		var queriesObj = this;
		var request = new XMLHttpRequest();
		//request.open('POST', 'http://localhost/get_mysql_data.php', true);
		request.open('POST', 'https://hank.test/get_mysql_data.php', true);
		request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		request.onreadystatechange = function() {
			if (request.readyState == 4) {
				if (request.status == 200) {
					queriesObj.processResponse(request.responseText);
					queriesObj.callback(queriesObj);
				}
				else {
					alert('status: ' + request.status);
				}
			}
		};
		request.send('f=doSql&p=' + this.params);
	
	}
	this.processResponse = function(result) {
		try {
			var res = JSON.parse(result);
			if (res.status == 'error')
			{
				var response = '<rslt><Success>failed</Success></rslt>';
				return;
			}
			this.Values = res;
			if (res.length > 0)
			{
				this.Columns = Object.keys(res[0]);
			}
		}
		catch (e) {alert(e);}
	}
}
function doQuery() {
	queryPts = new Queries();
	queryPts.callback = doQueryCallback;
	queryPts.sql = "SELECT date, title FROM lists where pid=<?php echo $pid ?> and type='medication' order by title;";
	queryPts.params = queryPts.sql;
	queryPts.Execute();
}
function doQueryCallback(query) {
	try {
		if (query.Values.length == 0) {
			var s = 'No medications have been entered into openEMR';
		}
		else {
			var s = '<table border=0>';
			if (query.Columns.length > 0) {
				s += '<tr>';
				for (var j = 0; j < query.Columns.length; j++) 
					s += '<th>' + query.Columns[j] + '</th>';
				s += '</tr>';
			}
			if (query.Values.length > 0) {
				for (var i = 0; i < query.Values.length; i++) {
					s += '<tr>';					
					for (var j = 0; j < query.Columns.length; j++)
						s += '<td onclick="doSelPt(' + i + ');" >' + query.Values[i][query.Columns[j]] + '</td>';
					s += '</tr>';
				}
			}
			s += '</table>';
		}
		document.getElementById('tblMeds').innerHTML = s;
	}
	catch (e) {alert(e);}
}

function doAddMed() {
	queryAddMed = new Queries();
	queryAddMed.callback = doAddMedCallback;
	queryAddMed.sql = "insert into lists (date,type,title,begdate,pid,user) values ('" + $('#txtMedStart')[0].value + "','medication','" + $('#txtMed')[0].value + "','" + $('#txtMedStart')[0].value + "',<?php echo $pid ?>,'HIDS502-admin');";
	//alert(queryAddMed.sql);
	queryAddMed.params = queryAddMed.sql;
	queryAddMed.Execute();
}
function doAddMedCallback(query) {
	doQuery();
}

/***** end read from / write to openEMR db directly ****/

</script>
<!-- end custom head content -->

</head>

<body class="body_top">
<?php echo text(date("F d, Y", time())); ?>

<form method=post action="<?php echo $rootdir."/forms/".$form_name."/save.php?mode=new";?>" name="my_form" id="my_form">
<input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>" />

<span class="title"><?php echo xlt('Medication Reconciliation'); ?></span><br></br>

<!-- start custom body content -->

<!--input type='button' value='click me' onclick='doStuff();' /><br></br>
<div id='fillMe'></div-->

url root: <input type='text' id='url' style="width:500px;" value="https://fhir-open.cerner.com/dstu2/ec2458f2-1e24-41c8-b71b-0e701af7583d/" /><br></br>

<div style="display:none;">
patientID:
<select id='selPt'>
<option value='12507979'>Smith, Frank</option>
<option value='12742429'>Smart, Connie</option>
<option value="12743126">Smart, Freddie</option>
<option value="12724066">Smart, Nancy</option>
</select>
<select id='selType'>
<option value='Patient?_id='>Patient</option>
<option value='Encounter?patient='>Encounters</option>
<option value="CarePlan?category=assess-plan&patient=">Care Plan</option>
<option value='MedicationOrder?patient='>Meds</option>
<option value='Observation?patient='>Observations</option>
<option value='AllergyIntolerance?patient='>Allergies</option>
<option value='Condition?patient='>Conditions</option>
<option value='Procedure?patient='>Procedures</option>
<option value='Appointment?date=2017&patient='>Appointments</option>
</select>
</div>

<input type='button' value='Fetch openEMR medications' onclick='doQuery();' /><br></br>
<div id='tblMeds'></div><br /><br />
New medication to add to openEMR: <input type='text' id='txtMed' />&nbsp;&nbsp;&nbsp;&nbsp;
Start Date: <input type='text' id='txtMedStart' size='10' class='datepicker' value='<?php echo attr(date('Y-m-d', time())); ?>' />&nbsp;&nbsp;&nbsp;&nbsp;
<input type='button' value='Enter med into openEMR' onclick='doAddMed();' /><br /><br />

FHIR Medications:<br />
<input type='button' value='Get Medications via FHIR' onclick='doFHIR();'></input>&nbsp;&nbsp;
<div id='medtable'> </div>
<textarea id='txtRslt' rows=1 cols=15></textarea><br></br>
<span id='spnRsrc'></span>

<!-- end custom body content -->

<br></br><br></br>
Custom info:<br></br>
<textarea id='custom' name='custom' rows=4 cols=120></textarea><br></br><br></br>

<br>
<b><?php echo xlt('Signature:'); ?></b>
<br>

<table>
<tr><td>
<?php echo xlt('Doctor:'); ?>
<input type="text" name="doctor" id="doctor" value="<?php echo attr($provider_results["fname"]).' '.attr($provider_results["lname"]); ?>">
</td>

<td>
<span class="text"><?php echo xlt('Date'); ?></span>
   <input type='text' size='10' class='datepicker' name='date_of_signature' id='date_of_signature'
    value='<?php echo attr(date('Y-m-d', time())); ?>'
    title='<?php echo xla('yyyy-mm-dd'); ?>' />
</td>
</tr>
</table>

<div style="margin: 10px;">
<input type="button" class="save" value="    <?php echo xla('Save'); ?>    "> &nbsp;
<input type="button" class="dontsave" value="<?php echo xla('Don\'t Save'); ?>"> &nbsp;
</div>

</form>

</body>

<script language="javascript">

// jQuery stuff to make the page a little easier to use


$(function(){
    $(".save").click(function() { top.restoreSession(); $('#my_form').submit(); });
    $(".dontsave").click(function() { parent.closeTab(window.name, false); });
    //$("#printform").click(function() { PrintForm(); });

    $('.datepicker').datetimepicker({
        <?php $datetimepicker_timepicker = false; ?>
        <?php $datetimepicker_showseconds = false; ?>
        <?php $datetimepicker_formatInput = false; ?>
        <?php require($GLOBALS['srcdir'] . '/js/xl/jquery-datetimepicker-2-5-4.js.php'); ?>
        <?php // can add any additional javascript settings to datetimepicker here; need to prepend first setting with a comma ?>
    });
});

</script>

</html>
