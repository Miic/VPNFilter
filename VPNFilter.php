<?php
require __DIR__ . '/vendor/autoload.php';
function getJson($url) {
    
    #Options
    $cacheFile = '/var/www/html/cache/' . md5($url) . '.json';
    $apikey = "xioax api key";
    $redirect = "exampleRedirect.html";
    
    $slackwebhook = "SLACK CLIENT HOOK URL";
    $channel = "#vpn-attempts";
    
    
    #Begin
    if (file_exists($cacheFile)) {
        $fh = fopen($cacheFile, 'r');
        $cacheTime = trim(fgets($fh));

        if ($cacheTime > strtotime('-60 minutes')) {
            return fread($fh, filesize($cacheFile));
        }

        fclose($fh);
        unlink($cacheFile);
    }
    $json = file_get_contents($url);

    $data = json_decode($json,true);
    if ($data['host-ip'] == true) {
        $settings = [
                'channel' => $channel,
                'allow_markdown' => true
        ];
        $client = new Maknz\Slack\Client($slackwebhook, $settings);
        $org =  $data['org'];
        if ($org == '') {
                $org = 'Unknown';
        }
        $client->send(':do_not_litter: IP caught in Website: ' .  $_SERVER['REMOTE_ADDR'] . '     from org: ' . $org . '     at '  . $data['city'] .  ', ' . $data['subdivision']['name'] . ', ' . $data['country']['name']);
    }

    $fh = fopen($cacheFile, 'w');
    fwrite($fh, time() . "\n");
    fwrite($fh, $json);
    fclose($fh);
    return $json;
}

function getUserIP()
{
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];
    if(filter_var($client, FILTER_VALIDATE_IP))
    {
        $ip = $client;
    }
    elseif(filter_var($forward, FILTER_VALIDATE_IP))
    {
        $ip = $forward;
    }
    else
    {
        $ip = $remote;
    }
    return $ip;
}

#added exception for bot
function botDetected()
{
  return (
    isset($_SERVER['HTTP_USER_AGENT'])
    && preg_match('/bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT'])
  );
}


$json = getJson('http://tools.xioax.com/networking/v2/json/' . $_SERVER['REMOTE_ADDR'] . '/' . $apikey);
$data = json_decode($json,true);
if ($data['host-ip'] == true && botDetected() == false) 
{
        header("Location: " . $redirect);
        die();
}
?>
