<?php 

$_min = null;
$_max = null;
$_int = null;

$_histogramValues = array();


function checkAndSetVars()
{
    $messages = array();
    $min = isset($_GET['min']) ? $_GET['min'] : null;
    $max = isset($_GET['max']) ? $_GET['max'] : null;
    $int = isset($_GET['int']) ? $_GET['int'] : null;
    if (!empty($min) && empty($max)) {
        $messages[] = "if you set a minimum, you also need a maximum";
    }
    if (empty($min) && !empty($max)) {
        $messages[] = "if you set a maximum, you also need a minimum";
    }
    if (!empty($min) && !empty($max)) {
        if ((float)$min > (float)$max) {
            $messages[] = "minimum must be less than maximum";
        }
    }
    if (!empty($int) && $int == "on") {
        if (!empty($min)) {
            if (!is_numeric($min) || ((int)$min != $min) ) {
                $messages[] = "if you only want to return integers, minimum must be an integer";
            }
        }
        if (!empty($max)) {
            if (!is_numeric($max) || ((int)$max != $max) ) {
                $messages[] = "if you only want to return integers, maximum must be an integer";
            }
        }
    } else {
        if (!empty($min)) {
            if (!is_numeric($min)) {
                $messages[] = "minimum must be a number";
            }
        }
        if (!empty($max)) {
            if (!is_numeric($max)) {
                $messages[] = "maximum must be a number";
            }
        }
    }
    
    global $_min, $_max, $_int;
    $_min = $min;
    $_max = $max;
    $_int = $int;
    
    if (!empty($messages)) {
        return $messages;
    } else {
        return false;
    }
}

function getRandomNumber() 
{
    $number = getXkcdRandom();
    global $_min, $_max, $_int;
    if (!empty($_min) && !empty($_max)) {
        $curMin = 1;
        $curMax = getCurrentMaxComicNum();
        $curTot = ($curMax - $curMin);
        $percent = ($number / $curTot);
        
        $tot = abs($_max - $_min);
        $number = ($tot * $percent);
        $number += $_min;
    }
    if (!empty($_int) && $_int=="on") {
        $number = round(floatval($number));
    }

    addHistogramCookieValue($number);

    return $number;
}

function getXkcdRandom()
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://dynamic.xkcd.com/random/comic/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    $data = curl_exec($ch);
    $url = curl_getinfo($ch);
    $url = $url['url'];
    curl_close($ch);
    return getNumFromXkcdUrl($url);
}

function getCurrentMaxComicNum()
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://www.xkcd.com/rss.xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    $xml = new SimpleXMLElement($data);
    $url = (string)$xml->channel[0]->item[0]->link;
    return getNumFromXkcdUrl($url);
}

function getNumFromXkcdUrl($url)
{
    $url = parse_url($url);
    $number = trim($url['path'], '/');
    return $number;
}

function getHistogramCookieValues()
{
    global $_histogramValues;

    $key = getHistogramCookieKey();
    if (isset($_COOKIE[$key])) {
        $_histogramValues = json_decode($_COOKIE[$key]);
    }
    return $_histogramValues;
}

function addHistogramCookieValue($value)
{
    global $_histogramValues;

    $key = getHistogramCookieKey();
    $values = getHistogramCookieValues();
    $values[] = $value;
    $_histogramValues[] = $value;

    setcookie($key, json_encode($values));
    return $values;
}

function resetHistogramCookie()
{
    $key = getHistogramCookieKey();
    setcookie($key, "", time() - 3600);
}

function getHistogramCookieKey()
{
    $keyBase = "histogram|";

    global $_min, $_max, $_int;
    $min = empty($_min) ? "0" : (string) $_min;
    $max = empty($_max) ? "0" : (string) $_max;
    $int = empty($_int) ? "0" : (string) $_int;

    return $keyBase . $min . ":" . $max . ":" . $int;
}

$randomNumber = getRandomNumber();
?>
<html>
    <head>
        <title>XKCD Random Number Generator</title>
        <style type="text/css">
            body {
                font-family: "Helvetica", "Arial", sans-serif;
            }
            #content {
                /*width: 900px;*/
                margin: 0 auto;
            }
            #number {
                font-weight: bold;
                font-size: 200px;
            }
            #errors {
                width: 500px;
                background-color: #CC3333;
                padding: 10px 10px 10px 10px;
            }
        </style>
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    </head>
    <body>
        <div id="content">
            <h1>XKCD Random Number Generator</h1>
            <h3>Idea: Duke Jonjon. Coding: Ariel. Numbers: Rand. M.</h3>
            <div id="wtf"><a href="xkcdrand-wtf.html">wait! how is this at all related to xkcd?</a><br><br></div>
            <?php $errors = checkAndSetVars(); ?>
            
            <div id="settings">
                <form action="xkcdrand.php" method="get">
                    
                    <label for="min">Minimum</label>
                    <input name="min" id="min" value="<?php echo $_min; ?>">
                    
                    <label for="max">Maximum</label>
                    <input name="max" id="max" value="<?php echo $_max; ?>">
                    
                    <label for="int">Integers Only</label>
                    <input name="int" id="int" type="checkbox" <?php if (empty($_int) || $_int=="on"): ?> checked="checked" <?php endif; ?>>
                    <input type="submit" id="submit" value="Go">
                </form>
            </div>
            
            
            <?php if(!$errors): ?>
                <div id="number"><?php echo $randomNumber; ?></div>
            <?php else: ?>
                <div id="errors">
                    <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            
            <script type="text/javascript">
              google.load("visualization", "1", {packages:["corechart"]});
              google.setOnLoadCallback(drawChart);
              function drawChart() {
                var data = google.visualization.arrayToDataTable([
                  ['Run', 'Value'],
                  <?php 
                      $i = 1; 
                      foreach ($_histogramValues as $value): 
                  ?>
                    ['<?php echo $i++; ?>', <?php echo $value; ?>],
                  <?php endforeach; ?>
                  ]);

                var options = {
                  title: 'Random numbers',
                  legend: { position: 'none' },
                };

                var chart = new google.visualization.Histogram(document.getElementById('chart_div'));
                chart.draw(data, options);
              }
            </script>
            <div id="chart_div" style="width: 700px; height: 500px;"></div>
        </div>
    </body>
</html>