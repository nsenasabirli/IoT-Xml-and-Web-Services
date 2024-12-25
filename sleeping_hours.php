<?php
$xmlFile = "sleeping_hours.xml";
$logFile = "all_logs.xml";

function showNotification($message, $type) {
    $alertClass = "";
    switch($type) {
        case "success":
            $alertClass = "alert-success";
            break;
        case "warning":
            $alertClass = "alert-warning";
            break;
        case "danger":
            $alertClass = "alert-danger";
            break;
    }
    echo "<div class='alert $alertClass'>$message</div>";
}

function addLogEntry($logFile) {
    if (!file_exists($logFile)) {
        // Create new log file if it doesn't exist
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $root = $doc->createElement('logs');
        $doc->appendChild($root);
        $doc->save($logFile);
    }

    $logXml = new DOMDocument();
    $logXml->load($logFile);
    
    // Create new log entry
    $logEntry = $logXml->createElement('logEntry');
    $logEntry->appendChild($logXml->createElement('timestamp', date('Y-m-d H:i:s')));
    
    // Get current values from sleeping_hours.xml
    $currentXml = new DOMDocument();
    $currentXml->load('sleeping_hours.xml');
    
    $elements = ['start', 'end', 'response', 'deactivation', 'deactivationcode', 'codematch'];
    foreach ($elements as $element) {
        $value = $currentXml->getElementsByTagName($element)->item(0)->nodeValue;
        $logEntry->appendChild($logXml->createElement($element, $value));
    }
    
    // Add new log entry to logs
    $logXml->documentElement->appendChild($logEntry);
    $logXml->save($logFile);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $xml = new DOMDocument();
    $xml->load($xmlFile);

    if ($action == "set_hours") {
        $start = intval($_POST['start']);
        $end = intval($_POST['end']);
        $code = str_pad($_POST['deactivationcode'], 4, "0", STR_PAD_LEFT);
        $xml->getElementsByTagName('start')->item(0)->nodeValue = $start;
        $xml->getElementsByTagName('end')->item(0)->nodeValue = $end;
        $xml->getElementsByTagName('deactivationcode')->item(0)->nodeValue = $code;
        $xml->getElementsByTagName('response')->item(0)->nodeValue = "none";
        $xml->getElementsByTagName('deactivation')->item(0)->nodeValue = "none";
        $xml->getElementsByTagName('codematch')->item(0)->nodeValue = "none";
        $xml->save($xmlFile);
        addLogEntry($logFile);
        showNotification("Sleeping hours and deactivation code updated successfully!", "success");
    } 
    elseif ($action == "response") {
        $response = $_POST['response'];
        $xml->getElementsByTagName('response')->item(0)->nodeValue = $response;
        if ($response == "yes") {
            $xml->getElementsByTagName('deactivation')->item(0)->nodeValue = "yes";
            $xml->getElementsByTagName('codematch')->item(0)->nodeValue = "yes";
        }
        $xml->save($xmlFile);
        addLogEntry($logFile);
        showNotification("Response updated successfully!", "warning");
    } 
    elseif ($action == "deactivation") {
        $inputCode = str_pad($_POST['code'], 4, "0", STR_PAD_LEFT);
        $storedCode = $xml->getElementsByTagName('deactivationcode')->item(0)->nodeValue;
        if ($inputCode === $storedCode) {
            $xml->getElementsByTagName('deactivation')->item(0)->nodeValue = "yes";
            $xml->getElementsByTagName('codematch')->item(0)->nodeValue = "yes";
            $xml->save($xmlFile);
            addLogEntry($logFile);
            showNotification("System deactivated successfully!", "success");
        } else {
            $xml->getElementsByTagName('codematch')->item(0)->nodeValue = "no";
            $xml->save($xmlFile);
            addLogEntry($logFile);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Security System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php
$xml = new DOMDocument();
$xml->load($xmlFile);
$response = $xml->getElementsByTagName('response')->item(0)->nodeValue;
$deactivation = $xml->getElementsByTagName('deactivation')->item(0)->nodeValue;
$codematch = $xml->getElementsByTagName('codematch')->item(0)->nodeValue;

if ($response == "none") {
    echo "<div class='notification-box'>
            <div class='notification-text'>There seems to be some movement in your house. Was this you?</div>
            <form method='POST'>
                <input type='hidden' name='action' value='response'>
                <div class='button-group'>
                    <button class='yes-btn' name='response' value='yes'>Yes</button>
                    <button class='no-btn' name='response' value='no'>No</button>
                </div>
            </form>
          </div>";
} 
elseif ($response == "no" && $deactivation == "none") {
    echo "<div class='notification-box'>
            <div class='notification-text'>Do you want to deactivate the system?</div>
            <form method='POST'>
                <input type='hidden' name='action' value='deactivation'>
                <label for='code'>Enter Deactivation Code:</label>
                <input type='text' id='code' name='code' maxlength='4' required>";
    
    if ($codematch == "no") {
        showNotification("Wrong deactivation code, please re-enter", "danger");
    }
    
    echo "<div class='button-group'>
            <button type='submit'>Submit</button>
          </div>
          </form>
          </div>";
} 
else {
    echo "<h1>Set Your Sleeping Hours and Deactivation Code</h1>
          <form method='POST'>
            <input type='hidden' name='action' value='set_hours'>
            <label for='start'>Start Hour (0-23):</label>
            <input type='number' id='start' name='start' min='0' max='23' required>
            
            <label for='end'>End Hour (0-23):</label>
            <input type='number' id='end' name='end' min='0' max='23' required>
            
            <label for='deactivationcode'>Enter 4-Digit Deactivation Code:</label>
            <input type='text' id='deactivationcode' name='deactivationcode' maxlength='4' required>
            
            <div class='button-group'>
                <button type='submit'>Save</button>
            </div>
          </form>";
}
?>
</body>
</html>