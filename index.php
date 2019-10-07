<?php
//{"lang":"","auth_pass":"d41d8cd98f00b204e9800998ecf8427e","error_reporting":1,"fm_root":""}
/*--------------------------------------------------
 | PHP FILE MANAGER
 +--------------------------------------------------
 | phpFileManager 0.9.9
 | By Fabricio Seger Kolling
 | Copyright (c) 2004-2016 Fabrício Seger Kolling
 | E-mail: dulldusk@gmail.com
 | URL: http://phpfm.sf.net
 | Last Changed: 2016-06-20
 +--------------------------------------------------
 | It is the AUTHOR'S REQUEST that you keep intact the above header information
 | and notify it only if you conceive any BUGFIXES or IMPROVEMENTS to this program.
 +--------------------------------------------------
 | LICENSE
 +--------------------------------------------------
 | Licensed under the terms of any of the following licenses at your choice:
 | - GNU General Public License Version 2 or later (the "GPL");
 | - GNU Lesser General Public License Version 2.1 or later (the "LGPL");
 | - Mozilla Public License Version 1.1 or later (the "MPL").
 | You are not required to, but if you want to explicitly declare the license
 | you have chosen to be bound to when using, reproducing, modifying and
 | distributing this software, just include a text file titled "LICENSE" in your version
 | of this software, indicating your license choice. In any case, your choice will not
 | restrict any recipient of your version of this software to use, reproduce, modify
 | and distribute this software under any of the above licenses.
 +--------------------------------------------------
 | CONFIGURATION AND INSTALATION NOTES
 +--------------------------------------------------
 | This program does not include any instalation or configuration
 | notes because it simply does not require them.
 | Just throw this file anywhere in your webserver and enjoy !!
 +--------------------------------------------------
*/
// +--------------------------------------------------
// | Header and Globals
// +--------------------------------------------------
if(!isset($_SERVER['PATH_INFO']) && isset($_SERVER["ORIG_PATH_INFO"])) {
    $_SERVER["PATH_INFO"] = $_SERVER["ORIG_PATH_INFO"];
}
if(!isset($_SERVER['DOCUMENT_ROOT'])) {
    if ( isset($_SERVER['SCRIPT_FILENAME']) ) $path = $_SERVER['SCRIPT_FILENAME'];
    elseif ( isset($_SERVER['PATH_TRANSLATED']) ) $path = str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']);
    $_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($path, 0, 0-strlen($_SERVER['PHP_SELF'])));
}
if (@get_magic_quotes_gpc()) {
    function stripslashes_deep($value){
        return is_array($value)? array_map('stripslashes_deep', $value):$value;
    }
    $_POST = array_map('stripslashes_deep', $_POST);
    $_GET = array_map('stripslashes_deep', $_GET);
    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
}
// Register Globals (its an old script..)
$blockKeys = array('_SERVER','_SESSION','_GET','_POST','_COOKIE');
foreach ($_GET as $key => $val) if (array_search($key,$blockKeys) === false) $$key=$val;
foreach ($_POST as $key => $val) if (array_search($key,$blockKeys) === false) $$key=$val;
foreach ($_COOKIE as $key => $val) if (array_search($key,$blockKeys) === false) $$key=$val;
// Server Vars
function curl_server_online_check(){
    @$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://phpfm.sf.net");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    @curl_exec($ch);
    $errnum = curl_errno($ch);
    @curl_close($ch);
    return ($errnum == "0");
}
function socket_get_lan_ip($dest='64.0.0.0', $port=80) {
    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_connect($socket, $dest, $port);
    socket_getsockname($socket, $addr, $port);
    socket_close($socket);
    return $addr;
}
function get_client_ip() {
    $ipaddress = '';
    if ($_SERVER['HTTP_CLIENT_IP']) $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if($_SERVER['HTTP_X_FORWARDED_FOR']) $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if($_SERVER['HTTP_X_FORWARDED']) $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if($_SERVER['HTTP_FORWARDED_FOR']) $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if($_SERVER['HTTP_FORWARDED']) $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if($_SERVER['HTTP_X_REAL_IP']) $ipaddress = $_SERVER['HTTP_X_REAL_IP'];
    else if($_SERVER['REMOTE_ADDR']) $ipaddress = $_SERVER['REMOTE_ADDR'];
    // proxy transparente não esconde o IP local, colocando ele após o IP da rede, separado por vírgula
    if (strpos($ipaddress, ',') !== false) {
        $ips = explode(',', $ipaddress);
        $ipaddress = trim($ips[0]);
    }
    if ($ipaddress == '::1') $ipaddress = 'localhost';
    return $ipaddress;
}
$ip = @get_client_ip();
$lan_ip = @socket_get_lan_ip();
$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
function getServerURL() {
    $url = ($_SERVER["HTTPS"] == "on")?"https://":"http://";
    $url .= $_SERVER["SERVER_NAME"]; // variável do servidor, $_SERVER["HTTP_HOST"] é equivalente
    if ($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443") $url .= ":".$_SERVER["SERVER_PORT"];
    return $url;
}
function getCompleteURL() {
    return getServerURL().$_SERVER["REQUEST_URI"];
}
$url = @getCompleteURL();
$url_info = parse_url($url);
$doc_root = rtrim(str_replace(DIRECTORY_SEPARATOR,'/',$_SERVER["DOCUMENT_ROOT"]),'/'); // ex: 'C:/htdocs'
$url_root = rtrim(@getServerURL(),'/'); // ex. 'http://www.site.com'
$fm_file = $doc_root.$_SERVER["PHP_SELF"];
$fm_url = $url_root.$_SERVER["PHP_SELF"];
$fm_path_info = pathinfo($fm_file);
$open_basedir = trim(str_replace(DIRECTORY_SEPARATOR,'/',@ini_get("open_basedir")));
$sys_lang = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
// +--------------------------------------------------
// | Config Class
// +--------------------------------------------------
function object_to_array( $var ) {
    if( !is_object( $var ) && !is_array( $var ) ) {
        return $var;
    }
    if( is_object( $var ) ) {
        $var = get_object_vars( $var );
    }
    return array_map( 'object_to_array', $var );
}
function array_to_object( $var ) {
    if( !is_object( $var ) && !is_array( $var ) ) {
        return $var;
    }
    $obj = new stdClass();
    foreach ($var as $key => $value) {
        if (strlen($key)) $obj->{$key} = array_to_object( $value );
    }
    return $obj;
}
class config {
    var $data;
    function config(){
        global $fm_file;
        $this->data = array(
            'lang'=>'',
            'auth_pass'=>md5(''),
            'error_reporting'=>1,
            'fm_root'=>''
            );
        $data = false;
        if (file_exists($fm_file)){
            $lines = file($fm_file);
            $config_string = trim(substr($lines[1],2));
            if (strlen($config_string)) $data = object_to_array(json_decode($config_string));
        }
        if (is_array($data) && count($data)) $this->data = $data;
        else $this->save();
    }
    function save(){
        global $fm_file;
        $config_string = "<?php".chr(13).chr(10)."//".json_encode($this->data).chr(13).chr(10);
        if (file_exists($fm_file)){
            $lines = file($fm_file);
            if ($fh = @fopen($fm_file, "w")){
                @fputs($fh,$config_string,strlen($config_string));
                for ($x=2;$x<count($lines);$x++) @fputs($fh,$lines[$x],strlen($lines[$x]));
                @fclose($fh);
            }
        }
    }
    function load(){
        foreach ($this->data as $key => $val) $GLOBALS[$key] = $val;
    }
}
// +--------------------------------------------------
// | Config
// +--------------------------------------------------
$charset = "UTF-8";
$version = '0.9.9';
$quota_mb = 0;
$upload_ext_filter = array();
$download_ext_filter = array();
$cookie_cache_time = 60*60*24*30; // 30 Days
$cfg = new config();
$cfg->load();
//@setlocale(LC_CTYPE, 'C');
//@ini_set('default_charset', $charset);
switch ($error_reporting){
    case 0: error_reporting(0); @ini_set("display_errors",0); break;
    case 1: error_reporting(E_ERROR | E_PARSE | E_COMPILE_ERROR); @ini_set("display_errors",1); break;
    case 2: error_reporting(E_ALL); @ini_set("display_errors",1); break;
}
if (!isset($current_dir)){
    $current_dir = $fm_path_info["dirname"]."/";
    if ($is_windows) $current_dir = ucfirst($current_dir);
    //@chmod($current_dir,0755);
} else {
    $current_dir = format_path($current_dir);
}
if (strlen($fm_root)) $fm_current_root = $fm_root;
else {
    if ($is_windows) {
        if (strpos($doc_root,":") !== false) $fm_current_root = ucfirst(substr($doc_root,0,strpos($doc_root,":")+1)."/");
        else $fm_current_root = ucfirst($doc_root."/");
    } else {
        $fm_current_root = "/"; // Linux default show root
    }
}
if (isset($set_fm_current_root)) {
    if ($is_windows) $fm_current_root = ucfirst($set_fm_current_root);
    else $fm_current_root = $set_fm_current_root;
}
if (strlen($open_basedir) && is_dir($open_basedir)){
    if (strpos($fm_current_root,$open_basedir) === false) $fm_current_root = $open_basedir;
}
if (!isset($resolve_ids)){
    setcookie("resolve_ids", 0, time()+$cookie_cache_time, "/");
} elseif (isset($set_resolve_ids)){
    $resolve_ids=($resolve_ids)?0:1;
    setcookie("resolve_ids", $resolve_ids, time()+$cookie_cache_time, "/");
}
if ($resolve_ids){
    @exec("cat /etc/passwd",$mat_passwd);
    @exec("cat /etc/group",$mat_group);
}
$fm_color = array();
$fm_color['Bg'] = "EEEEEE";
$fm_color['Text'] = "000000";
$fm_color['Link'] = "0A77F7";
$fm_color['Entry'] = "FFFFFF";
$fm_color['Over'] = "C0EBFD";
$fm_color['Mark'] = "A7D2E4";
// +--------------------------------------------------
// | BASE64 FILES
// | So that PHP File Manager can remain a single file script,
// | and still work normally on offline enviroments
// +--------------------------------------------------
function get_base64_file(){
    global $filename,$fm_path_info;
    //Total: 466.18 Kb
    //Total GZ: 163.17 Kb
    $base64_files = array();
    $base64_files['32px.png'] = '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';
    $base64_files['bitcoin.png'] = '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';
    $base64_files['file_sprite.png'] = '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';
    $base64_files['jquery-1.11.1.min.js'] = 'eJzdvXt348axL/r//hQiMlsGhi2KGts5O6AhXnvGEzvxK5lxbIeivfAiCYkvkdRIssh89lu/qu5G48Gxs/e56551nIwINPrd1dVV1fU4f945uf7bXb55PHl30bug/5/sT/w0OHnR73+s6O/FR+b769XdMot3xWqpTr5cpj3KeH2LL73VZno+L9J8uc1Pnp//R2dyt0yRz49VEjx5q+Q6T3deFO0e1/lqcrJYZXfz/PT0yIde/rBebXbbYfU1invZKr1b5MvdMKGaO/0gLBsKnoqJ3ymzBLvZZnV/sszvTz7fbFYb39Oj2OS3d8Um357EJ/fFMqM898VuRm+mpBcMNvnubrM8oVaCQ8h/fY/Gnk+KZZ55HdNdKT+Un3A3K7aqOvJ38eYkjUZjlUVpb4sZUjk9patlGu/UhB7Xd9uZmtID1ZE/fDtRs+jpoIpo1tut3uw2xXKqrullFm+/vV9+t1mt883uUd0g0zzyZME8tYiq7er+Y/CL3mRJlRc7/nJQy+j859HV9uru9eevX189fNofd/e192fnU7WibGeL7dm5WkfnZ/7oKovPfh0H59NC3bY3llCPv19T/17G29wPDgO0HC16681qt8KERU8CLeFc0QRsd5u7dLfahAu1zec5P3qemufL6W4W9tVu9elmEz+WK2wbynppPJ/7mG4azzTfVaDADP1uPu9E8bB/GQ+RcxR38dOT+sehpI3DamVYjTe7OL2pVIlVTGgki3wzzTlrzxmAH6i4hBgabv7uWwbriAEiQd5d/iCv5kUlB5XH6SxsncpFD9+4JSWrtojXbaPkKm2nfepivParcJio1GaPZbCUhEoDqpdhsmWOaxVnvXi9nj/qHm2mvE+2qGBSbLa7YxXkt36f8szj92Y5u6A8+W3LlDsrptKoG3d9LGcS9u181/qZXkb909PkMh2OeIHT8TgcjVH9Mjs6Srtg+31jbQFGGi7CidoSGgppI9OP2q556uiNH2iJCE/tqJ2Id5x+dtrEkGgxae4zlasJbXo7kaP+eL+nHT2LLmjr22Qz9OuoczGYAIUlq9U8j5clwpyenvrX0bRS2UxX1u0GqoFhp/v9oldsX5t+TYP93p8SOgmo9SgqqL6pAO7s7CwYFJezASoi3Co7ys8rLQUB+pWdFMuTPIij6Sgb00rl+Jl2oihF905P8YNWv5vHxVLmmk4Yahi7qtjyRqeEIBj6Cf2fhku4MT49LT/GwTDGSoY23a2Lv9KQ0Xxk1sG/pkmmSsN3qyI76evecBZKNQA0LRfOf6KDJiZUHuqjwuv68+7X8W7W2yB54QdBb5Ov53Ga++dXrwhLel6giu3f8zh7DDt9leOgqcBx/RCKgYFXq7ULjITu7Xq0bHLPJNEi0uCwjlyNnpqQ/5qJ2u9bKojxpVH6Bzm1juPO09M4ojNXTjeU+IaWfVOkLUU67kpRubN1vNnmr+erGItDmxLFP1+sd4+yYs29zvCdAI7iQNd5odeow6Wd9W4pzWf/fm/AveOMdb+Pe8tVlr+lVwF+GTl9KlvabR5BP8Tu5j897VwLwoyV56R7gfPFLVAedcqjDpuXbyde2dKBDn5C7RYnUwq1e9Nb3S+/IjQZNKbhxPYhCdxJMgAs0E2Lm+z3TtaDQtPHVpfWdRh3PS9s4AdMogNwJnU4GxW68mBcznNovtPem6+SeP75u3heNkonWoLdSnTMgl5o+8W01/L0Tbop1jsHVikjfaGyzgACH6NI40U+B0XRNpTYbseV8ohY8cr9uVa3vNWy/Buqof2YFbjAdwJZ+0xkzFere0PGYGKrKS0HN45YwCEh9qgP1GUw9zTClgd0pkyfToMnLOFgcpkPckGrGdUvh2s8ygl5BkQrRoQBg2STxzeHfE4kNcrksuy/s8TxtmSCUTBX+Pl97b2/lIFFAgMs9fugjmDOB+SVK7UEFgWFc5PXKD6HhCbgHo0Hdfzkb3x7AgRDQ6Clytsy5ezCL2i9mI4PGUlKJFugUsIry2abdjWxboleN3NcGAoqply8HTE7lkRJid5Mh3xkLOIHv6+ybhqEadgfZJfpIJVVSDGztC8SIk9oEu1GTw/ycHZBs4GRtM5E1zaXEazlFtYG97NiToO/zAJaoG53HCWjjH4Y+HD4BZLBnob0eVzLaveFVBnRWtPGpoVqzA9GbiCe2JsJdWVagv0s6qSD6eVkMKERZ1GHOKjRhHIR1FDDs9PTnGk2TrWILK9Tue6+ajSAfUW00ojHN2O06bRoGsTmEGjJTk8LaTQLBhbIJwLkv1nAdFHvOxpxAdbjrsjCC0VY/6EVakHm6aINiKT19wlRjJKxSqJYxRFNToUwI5rGTyPNnliSS70IaMablGyse5YIDasMj+nXKwhAtOc9dJ2m0/nB0YjfblflhmYCAr1v0s1d0DOv4h2t2PZuDd48vDmg+8y1eJ8JkXpC5EKSb06Eiz0xAzvhDcfFT/6eTz9/WJ/IHhYKyWN6eud7J0RaVed0NvJGcu6ceN2k6429cQM305407WxKPiIud6glCwYt1FVaow+GnYvwAlvUEhC0a4edfliSVFREH77eksdbWeLkEuzI2QWD2QGd2UYN4qXkCNRMFepa3ai5WqilWik6xdRGbdVO3UXetvj113nudc/M9Kt3jkhE3dMWeaB/j9E0IZ70V/n5VH4+a+fZY3SdIHEedfqBovV+GTlyDvUquvjkkw8v1OfEH9RFEK+x7/8cve6tV2v1BX4hyfjSPPyFHkTg8Vd60sKNKnVqcEhCnU5dXm+QXiaDRJAls3FJBU8mgxJPfhV56SxPb/JsL1IEeoi3j8t0H9/tVhOamy0/0VHzuAfvvVnNt3saYL7ZZ8U2TuZUYFZkWb7cF1vCP/s5Uef7xd18V6zn+Z4Gu9zTEZetlvPHvRYdUVspfaAJ+jryRldXDy/6V1e7q6vN1dXy6moy9tQ3kecPwyv6r7enDPdn4/3oZ8rY75/R37g/Drqe+jb6xh6C3r2nvPs/EMx/F3lXVyOv+3XXe+573W+6XkBV6ffR85+f7Tv/Gg+jQKcMww/8sqmf8fvBOHgefLC/8uofrjx8ufL2VO+3VG+w17VcXVGf/xbR0WwbvLryff/frzrY17/4AU3AeLz3ut9Rzc+DfY/yXaFp9fcIkCxIwPd+5r50uYKfdeFxYGqjkvL9GU3UlObpTUvh50p+6PPbts/+6LL7L3SFXgKb9ftK1shkpQ6MP6BxPR+6s8Rt/8Mt8bdA/VBvjGb3GeX7MXr68lVY+fYHPcX09eVXn755U/1KAy2/v/30z9Wv+FSDGOq/ZP707du/h7VefEfQ9Obz7199W/9AXX75xZdf1boW+gzkLNHZQ2azX+5m+HeGl+DMT4mAyParyRkQnAYSPVv5O9onqyyj1Rt1CdoD/+oqex4s9yWc6g/6nT53CQjs1DJAeAWNBDKO2rgB/1/ROJ/pLMs8z7YvRZJWHxuqk2UOy17lt/spjUlGVA6wOgZ6od2ZBUPuutMxfxiNfqa+P9NdPKifonP0qliu73Ya8ezRmZhQxT652+1Wy+DZeaH+SflmVxken0Hu+vPTuHv1dLV9fjVaxrviXX5ydX+ufpHa/uCPgCloWvyre/pLsKATqC4VJ9H5iIZ1rhJ6oj14dT5VaVKBPN5vtN2y+GwyfrpQfzzwKIZ7GSLtPR4BQDhLolZKK/L6D3S6nv3x448//KOhe0C1EYGQQvR2mQ3lRO9NNqvFy1m8eUlno591uUQQtn68vLzo7z/++MWf/qgu+i8+PM32H//xwxf94MCM95eaeHkd/UWolXc9BrVvqOw2UNW31yP33chz7QGt+euczrgvoyeuN3ytcw2rZ+AXhotSutmEaKNWmjt2SG5NZ8ejtCScg4ElmVM6lQ4HS4RMEp5dOt+lrgkd8XLAr/hgv1cPIGD9ZJhABJBvXunjfL9PwncBzfuSGGjqGVGJRGMsqQcZWCHF0g5NVNr7CHtEMutyQaX9GyKRzNwQA/4nSrvRuYR2Xp+ednJmcibRL8ydg5mi1+toMroY85c/RSiFpxlVN813n89zdPKzxy8z/zpQndl+35n11gT+yx3WpdKPWa8As3htE4WsnhEQWma1NnoiSdBSJa3ZLo1nR6zYjH5/qw0e3+jF2Hw3IJcpdzzbzx7fxlMIATAHinvP8/DhmNpIqzlfEgbZivAgOfLlN1uzOTEa6ip4td7tFqxt55bm9La3y7fM3fLsb6NNdEeEXkKEHq/J6WmsLuTBEX0lR2QZwdMqmoJv8jeyjJ/uCIAIXdFpUmREDwypAXvAJIkihPLs1AvCpLetZ1a0FbdE/dC0f+B1t13vg/GJp+bRqsqOzs/OgtVoPo623dvEx1MwuI/ixIzr9HSV0OI7kEOAT6Nb9a5XxdInbBVgUh4C4InGbN73+ELpjb4/+pT28APPoyCBx+DpQJQsbW0qS/XS0Bard3lt1LRddcWFXwqU/q68Zxc4jXjvlhsa1LSI8sF72uTET7HFLTfG0Jd2wcVcggUjIvUrnpfT04x6S7xPMop721kx2fkBsYAjzjuOctOXpGxylrgir9HdmIh1Yszt9yIpOZ1lL6UjaJdrEPO9rHjnBYNy9jqdGHK1phTSTJS7GIDs8k1P30sgX8FI4JQdZHedVJGm5uT2XtCUU+QEGFkvpqX4Il5m83yUjvIx4dOytptKbQlAPYMIv86QXUSRg+No5/yLwHV1t0nzL8F17PeviHT5V1xPw97OKnjKSEjSKO0t6TB/UyRzQq8sskEbgWE7rJxkeBESurc9nrsL5co29RCObEvDiTI1wTwl5p1vTGmkzvwu/kf1+04DdHYIlcJvwZH2lm57BIVukwZMoy4hYOcT8bLSnxxyUEhKUnN4EshMo0kVDKYEBrTyhGKn4zGtHaAg6vgZfvBMJzL+Z7u0quwFwv/64GtF4oQKXxLYHNJoQiAhkgpcnE/wXmx//PqrJjPOYsW4fhbHgeWzdSv2wnfoffH266+qaDfsQITHreY7U0sL459DDN9oK3xH85T3iEmNiQ39R5HfW/mT0ANA/bkD8Xm9c0N/GeVqFTU+qHXUmfg5LcTpKe7npgQpa9yK9eIs+5yo5d1XxXaXU3+GzSSoQMxXMeF+RyK0gEC8cxGEU2xmwnNchGp3X31vtTxSFmJYRgOMkbdR4cCSK+hPzUkZEQVO1E/t+LKfCZ+jxjZwOFo3HSf5MhO8lmsE+nK1EARKx6JurkkkgH3U8Nxs1Z7t0TM57fJjVMLp6bGeFUuad8BX5H1CmPyEhxl9EH9w+ck5vV9WEk8Kk+ypuMdcDo+pNncvGH8eIUPA3tRGBGLryNStKlNHm4SogTvVqQ0UFRMt05Lq37U1NvQz6v0y6335qia1gjxIy9Zq1KBs9XWJCmvEYnlfloKQKw+14SiFisDhoNDofJdvqs2WIkRDGaREEiS2utZla5JWOD8OhyD09elvR/i/oVkZsosJbeMYocaCjXTpWGVa3sXzu1x3Vekuvv30z1H7fqqxU0anorlAVYw8PEpxG6lzqyw9g7xRrteOVoBTnPYkCwufzGE+4SuOAGRC6iBNzSGkdgYyQ3pNDmbsLKGpj96h+/+NCbClNKTWZ6HcguU8qA3GfIs/whaUmKRO94IX8mubtIo+REpxstgSlbe6T+fFOvqA8MVqzeeqEZ5y2rkk0oMkMz5pUNreyKnrZyo4tqjj9PRWZteDwHIclbJKyA6vWGDVWqPpRlnVfm+qKqWiw5ABdS9CoSN1hVou3FJT+YnAvDZpsu3yOhktpFMwqHNCWGxiUkSCzD1xMWIS1LIvcUIp79Wx8eN7lLVNJJcU4ZaVAR8beb5kuXbbyM0n5YVG/H2klucqfKBPpqTqPQ89Pq4JFBfgHvKtyW/Acktsm/60369693lyU+y+rubFh8Xq15bUVVvObS0xaB6WaY9Gkq4I1AE5nD/aWr0N5n9U+T7adgCqPLaNHlsn8tTfAAu30a2deEeudquZ0T1ogQ2xyC15Nm6exMzIqpeuFjhsDHn33WpboOOB2kGe42Rb7uJiuQ2GLbiPef2S5RnGdbIuBGuUVLk1y6RELLvr+B36C5FQ5mjDdPzUNj0sH4kxCuNjXSeO64+nR79S0aYUje/RBR8nUYXbxxfnbqfTH1iGVH0WJcNGPbHDiZ3g2kr1ByK47Bzt01knOfbJIv9hRmdz1Eb5U4N18dR+nwTD41OQBOGFujjFrIuy4KscpHCeYYWOFeKGsiHGl0NlqdIgJb6DsOudioPh2UWYSK7kWC7q3kV4M/yrgP0NlTqzz9S7fvjRaYZ6LtqW6tgUp6xxAO2WcgGJU3HXU82iUTzGvXzCwsXOhObAKk/w2Gz3qYcTvEzf39UBSyaJVTG1aLnAII3iQcmwOzA1690tRbKSIlfSnqtwc0mOGXQDo6iAYkTW7ZawQU3im+Ivoc72Dp0vzPNFSOd1HoRLYi8TgwXbL11ZJgzRifyBllNZxOLUxrK0waaRD8csH7a04vfKiz54doETWdHGbyBsWpT1fr85Pd0I/kkCOiJw1ui3gMVtsq22jvoZRCb7fQvCBcBmVnZ7ATRTJpTyZ0tpaSF98HQo5yRRS5kQgiBzcl32eW4MXmqdz9+YF6P3TtWAzaxV8f7CDPZ0ILtyqprGAXRfiBL8XGbJzalqOYNhzncBnbUh8KoqfNTsZDgJXW4Y6zSssRO0J6A40iTnE5yNk952nafFpMiz4UTo+ZCldBg/q6dWmIyGjcSbR5rphxPOqU7ulps8XU2Xxa95dpI/rDf5dgsl1ROvG8uU3i0LIh3eQLDSFG84JDtvY8IlBDvE/qS7V3fQmiYKa6tuIo0l3+xAj4BVYcUBvw/CBB/8zwI1NwQ98USjCQh6PjdGE0iNsER8lE+CwJEvxlo3m8VJinCdwSAsuIRuUw4ZDc3kW+jntyhmRJ5nkZ6BYkZIvCPBUhCC+5P8XPArf2jqn/VwO8g3lsudRYduIuuSxZHLtw/iARJcUWTajVh911ybfChNf8QtV7Qa/4Gll3zlvPFdFddRcpIpcTsiqRLcsI2eHHF1+HFfCSn83Ta/y1bhLFGMTMIfVQnq0L0Gw4TfTT7nm83wybv0wqes2IReiXY9bTAAnV7vpOU7JXdt8iZ/V6zutnr0lbL/OpaJOHlKes08dfjE1+JtPProYhzhT42/VvHowzGRAfSXUMHoI/77MTReHY1FndX7V8Ss5ugFYJALetgZ9MCCf2UBWX1Eu0Vu3N/blwq+UN5yN5MG6JOp6cNgqHtnNjS99sfo+EfjqOvjZ4gu4/GPlO0iCF889z1chUtlH7L+bpaZtwBlP5ay/2tM3f+vRoYQP4Rcai0ejHpB287poHnazDQ7BtR+7PEc6Lsf1DHERgx5QEPkjKpTHqanp/+Q7BBSEwxP/RR2X/JijaZ84gKtnPksCc7MM6scU0MR/tg55GWmxlInxV2tD4mCBUALCEEN47cFM+0ifZFFDB2Zp1Vpb9dn/n0KydQ7UR5p9utxFPNNkpVPs5WFqyTg/2wVYCiraAZAtQGTisvwln7p67oWtJaWwhDnZb9vlUa1SaK06NYLeIsdaJ/UtqxyDZhssrlkiPSx7meOEZboGudDMHaYrjAZ+nkXuNyThCGEXGlovg/RM3r9Wb8S1PVZxG7AKw1C73n50f1wSUSg98z9JlBUgqA09S+dBYqN3ZzxQ72Wvdu5/T638Giq6l5wZV3vzAshcicoaqIVY2Gk9Q0ixiJMj5XgTVS7B/0XN/3sI1gAeVq7h3ti5hMHW6bnZNgEj07H5QgcwEZPCulHRakxmuDqYeg5J5vXgu1vq6zFBorDx+641DbqFKennRlO51tRZzAUwzp4mlsuYB7NR+sx+M7ZcH58i21Y73NeJ107F4NVtKZZWs5Z+zOmJlenp5WRHOwWp0ZW0Wg6vHUO9fC2h5nn5zGuYbbB0010O7ojhOfjh02xrqMbooJZ0WMZXQOBRdH96ek1nQRqUUl4MVZzkKu3jlLMaDm2o+126eOc/k+jphYW0TLqBxCtrFdrn/U8qgM9Pe12F5SdecAn9CIa3dOyLcYDMRCwtMeWTc/8RLqe6K4HoN7RMeligN5ejAcOIfJ7+vRvLo7uNHfJn0uH5k6HMIQFnVgyqqrNwuIsymlOWUiy+E8okfSJ+D/PYLR0aDnhHO1uUJ1MFW15sTII+4TwoIQam+Cc1x4xnnIZSVS1VCAUtb3mo64PmdLPDR90AWXwUaxiRVgsGSu3rZpmrh/X+Q73fjZ2deqZITlyK5tFfzWcHy5ncWRmuJpN8cMph6DtDEOdhLLwNQctJhMUPi1Xu3DWJmvFFbGYSM+a+heldB5zUh0I0ItVq5pGmeGqczUaA5fVNA5gz0ic0xRWi0wWzDCcBD+ToDoYaGOXhx/TDyoDg4rq2UaAWBYGVlyU0PTXR9ZyR+8y/4nD3+LmS7O3v7sWP3FZB8iBWNIPLobODXCC9myJcbagkXm8nB5p4AdNkfERfAxQuTyDqYp/g/pRbo95rwyy1QmrUeDeg2uq6x89LOYhPqAD9W+Sbs1kiG6rNgftjFj08EuSMMYxabjButixrjUSlGJHmqpdvKlYlbt6gKs0FiFo+Yz9N6vco8mJeiHGY0VGTNFq1WqlDonYikhN6Kkf+77sxSkYKi3rhWoYN/maldv35bMPCq7Twf5n4W7cm21y4g7/RQlxwoovbAnNUv928tPcCbBtFzGG+vW3MxNLpi9VWmnl36n4kqD/REdqjX7iQtbGNEN/MhdEB2We2vvm6jC5b7YCng5VVqgHkcNGtVLl7+LL2XTUANMnf2yzYJU+tNnO2lOkx62zee4sj7N80za2f+rNaucUxryYwLbMP7VkFsWf/+EyOepDBtycpOSgWDW7aadbr+pYm9QCaijrJ7AXsh6CjBp+YII1AAdhytRFb8ZPwdLBf2aSCL0zeqx9diSMo+TsAnny23qOkjUZwfAv7SZhyjmJ227WZgxe0qg/SNgeMHoRxPV76JjKE/f9vuIXv1F83hhKxWYvsn0dnJ2B0BmYarJKNdPfXU23m32StNfCmhUGwIkXiRxwv7UGzU+bOCtWsKHnzZ+sHvBMLHiO3zVxiPerTYbnYhFPkXgISuorGUfzxHfso5+2d8migKhIbXKilJr5F5Lf6JWtodZ5WCeOtxKjmLEte1whu5ipXiegngBuNznEpDXxsjUDLG27ol8Nhw67b2vTNOyHN1buOSDihY0biRHIelacZQiZ4MnvEIPo59EbUdieBSwRyVlteqarySHy0Dzofj8LlDZnnFC90LqClwWq4q2tAup3xPRqpVQ1kexPIkROxZ6cK3VotBO+Qy8btVyvrMUUhm9mIoMONfcjUY62xf3+ml4Js9MHPPk50n67F1OlLzSIxDzSOi6fUm3TbOd4pr+Hs2EpywrCX2mxisDO/qEEi9ukaajmGAN4njVVy7pszCki91KlpLRGTKpmFgkdnxs4AyJM54g/wYcQYf5Q3j4lcvKUfDfI3kkpDWczXs3GJc7Vrr1tiXWJGmM+lZ4wVII/mjBATpsVt9RMZ6quw97bVkXNxzsFzryIakyjIrDlu7XT05nlbWcQizpybfC60QziRFQBLuRacVqzL44O67aiMFqyU5W5sNKkFiVl9iwxysc6Y8vRHkK4WLa4S1owJa20sdse5JfZICOQEVaAPaY40nhbz13iCnNMXeB0CC/MqMqiBMXrSIyWE+uwhY2LhdMBrkj9CdcTsLKnvitR16wyIIYSjkcU24V3ThccjiijHQt+jOrKIspDs6JymJFIWo405A8qmsEaB1bkQRELOOjPKpqasaxxo0GTCJPa556alXoPNJ5wxszdbQT7l84E5rjrkGZqrRbEG6N6tYnSIYGaPxnG4Yq48mA4GofT8JZVvIk692FSyzlp2a8jKrxRS3rxrxUmFh9uousqINyAd5wTjrrhGd2MlvQE9vFWP80DtlOQ6x/Q3vKABqjSG6szUq1vI/Vdyxrc0htVNMiZ0hHlr2sYmv9Gcf86ys39+UTNg3CBdGL7YGI+ukY3p/hBH2WbbnjUuFQfbswd2UqZRoJwQ+s51N2Y0mwVQWiMLui1opB9X8WQik871wFG1jM3QiMWwgOBA36nEJjYTzgQCaChC9DHdLXrwwppOcMNgJofyfRX66GBOV+dmyDNFYFg6fX+hQclXGp3YD1EYIN7haCEuRudPZzrh+AwHkwui0GhnTlUB1joAQbUIPWPsM+CjtpABGBPOr8ch05uPbdyK4lUfbxRUdpSMr151O0WFWcebru5abci26J9WFwSGEg3+BEnmpUEF2cXgXEUoE9YWgi+9inOXkiVQ9qEoecdHN9HxjaG1vqyOD29L6ssgGIUdVJSrXDZpvKBGhwWhmo1ZzP3sISqh6rth5WWOHYkl7iVrSMXdSNlIGqGoLlPmMLre7SXCFXQdtpiT+3o4LgDkmEtUKPkCsVRKq7eRffdSFiNHUFjxf3Tft+7UA/RndmNWJcb8cAlSgPTYHBLTw+np9pb1jy6G92OKZXWjDHC6ek8eFpYM8IVTfUCt7KQFfvYbTOYesn0EC4QAgZt3EfvgkPKAs4IMuc57fz12ZmaQItDZ2cctO5Gt4oyoiPraluJtLXyYXqIpsq77PVlX2to3RJy2VCn9/st//XxE/1ZtlVB58QW2GMbHAxKKGALRl0EOt7a1aHeWVclALyKsgDVYy/jZWx0hO0Idx/sQcjCviCcmHyzaCLKU9THVipbaxuwN5JPHRq7QwOUW7ApW/WV8GQUg2jAk4jgFQ5ZCE4habT6BGFungZULbX3gLMNJ97EXplHsaM6XJSX6VULWz5B0VXHxUPU5msJlk6rCLNJHY6WthmcKhhQDtUvGiaIK2Nux2t4Ha1APa2cu046480avCBK88tX2N3+DV8hBJqxtor3Ylni2lKtsUMsmrnmO2pGM6zSF5WK+v6NwVkug6AvyqFeRKc2fVEdq1iWD5Z8iVuRy6kSZVwbFkBIajPOQxH92HPtv40lITFQtUO7oHVFPzHaAiJzO5B5dFNBl6ByF5EMBiaK7Jtl8d4xGRPGa3OmtZsyasPNa3PKFuoCgzTydkaIMKCN66aigBaVmzsXDV/+khg5AqZVQPQdqxLBHdP7jSmploNyFWwIfWnDPC8w6jVaeZXtFO5UU0kn6nQIXiFqregzHjELuXiPemi7gWKLInarDUx8ApFm9MEfoKUeX3rK+4OIiBxrl6psCPnBoxKrmYikaM9S0VleTGe7/X2R7WaeapfpEBIS1aywroOlPHtJWpU00YnxQiyQSi2u32fewyKx87rpTlV9nHeCx14Hvd8Yt2S1A9clj42TSG0RyXWOi+TKuTCWaaxidGzhtMOvWrdKpXPds68afRLXW40VECcwnf6wNuNQ2z2mDZc52nCZqw1H2Ds5QMFvwXs+2rITyPUm2pZ6UDppRNSPOJVcb6wcaKHPMspfHmqUyn5et0ajjB06/fj1V7QJKJEfKckqM27tI+sZ7kwjjHQqOI7IlfOfP2GvEPAdcT689IfhJ1fnVxeXe/iGeEefe6Ofwz9cja56avz82Xkpwrg380poqOJgKrE3KosefHxVVD8cArljvL8p6FhAU+LAx1DS0OtsqadKu5eFm/6wuIPvrAaqrVKIZeNxDQ5DyxRLRLyvZRr0UrumpJPoMupLLw6moiN+MHDt51zq0EnshcsVISxozcDoRGQbBo+zjkTJNghc1fVuWUtlSBx/SCxpNQs1rgeRtA2iavTMrnsdN7P+E2vftalh8cUquwSDRFOfj84CNH1H1H0Kw65PT7kjO2dJK5GWeemqaeHYD8CvGftULoUyWiToFpIpAGQhvyMFiWqdyC8vhmbPQRsn5ZPIkF+c2bwNK29d1sgLY/j60wpdLbNba+1e+zkWRxsXATuSbL1feW/BfsB+VtuufDo6Z1Oj6vS0pGgw9aHthbGUPIha8oN6dL1//So+ZAg9+J+Mru6vfhh3L4PRz5fj53vtV+Y5u5H5NLIOwdupaHGl6gJD634VAUdKB5ccROks3ny6I4KTqMzLSpJh2IjX5FtToUajD4cj4Xf5Vn0c/mp8jyjIqzop0Zqnp4Za7CS4FRb34UNIhh4CDTpB2PDZnNhvLPIxTkyI1Dwh2NzFy5RdzQ+xw8NEuX696YU91+JM5pIqMTpHvK1bnLQ88kKrO62miNaaDooT8ZMsnh+DChrWfqqDoX4QLkRGxZptqeKUgbMiB/bK+Vg3ck3h4ERlbNtY0oBPLCsoYCKbOh5QHuwkOZ7XogveteAirDmk67Y8elSV7RXF/H6wFI2dKb9SzFQq2auNBWHd72Gbq/2HHrywPQ71L+8Mf0Gnv1Wej22vCFvXemkfVaVbsXmC80LrgNSPNeo6DD517oOwb4j5X/iPsgE/kx0nM73dQ32NXr9f7or5ni0yz9XL6Im1sigHX22JvsYWz7g95qstKobbqUHphRpqze1nEjO4oISMfFoc+ri+AHzrDzgVc6/cNfda+HlAk82+ti9qXgQ0y5vDWUeONuwlhtrKNXdNC6q8jxyNW+7E63464g7fHKfa9acj8mbfkc5hBoWatrNsYVaGwynos8yF0uPHVNZ6TImrTBqre0wRtzRfbXPXZX11uNrxquucHndqahpZ1N3mmwnoXCWlj3mGu7BfXgcAQchOycYDSKoxYYO62RI8BFlNgwvIeKfDqWi9aEXSuqXzEVqEfeDSsOwFcuWyrHayTcqbE3sOQzrDygfUdKsWxrDFD29JjWmcoHDKMQ4wVJrB8xAXh3rFQ52bjkd5cE349doTJ+QH7M4fBqzmvISHFhVnWW01jxA7ZmyVYA+EZon15eUL2H8qVfdZPUiEWyF9943D41qMgbD2bmAWUlHXe+kr6Wa2IsrZ2fFEH1x03H3lXLVr/6tPLZomRsGjaR6aaHMt95DTFkMas7WNcoHbS5jTOleXgS3BOPCYCndbUagLCkZsaQszUVGwDQ6CNY/lrSsS67oJKN47lJYmfqvIkZZ+3/jd9ngCUNvvnLq6qjSKayR9ZL30V1gRlhOPOBGBI0MAR6XsifXeelzRAwqZo621kBEooO/FZMNuWob68F3uHNs7kyQeeC11G5rtSFg2dr0Bwkyjsq1xjkDh16Y6l7tOdJSkZDY8nvKKJvzHAd9spkxLNXBYxleclg3lW1WXrIGw23/JGsGcT2MU5PqslNRBikukzLt8wwpKugaH4wkMof95dH71pns+Va+jJ0c14c/lvn6NET9Z0bnGA7FgfP9zlr6680QQxb7HcdqphPDGS+LvE2q16pyYON4m/n7NA0PjYUmxHJS2laz4MsaZWMAse0UUN1+/XJf1z+VAhTrFIl+sNo+np3M6WKH0g6tBePvGIWs0NlRCnwbwHK59fEOBfzQxN2dzHCRz0N6sucg2hLvV+tvl63i+JRIYyi76fOPoIjN4yxgWlnu/9gsjbibmcoiuhzdGwZH1x26ip8ohIt4QDWFmujmwka9O2HW8XoqkFo5HCmlX1ASJFSfUGe0MgZnT0xsod3KolJk5pkPWMDUdL8kMWGjAlIQIGMfbt4I5iVV74QAoUaauQQC6Jz3M9uDbrSVGzgwEhACUdULeok1uFG0xLHOSp2qGmxKiSYKZNcWEABwup/NL9DjHDRY/Tc7OIDiV3tQpQEtRuMKcGVM7HV97nzQXBHUdznIkxhxV2tCr25qxiNJICOlq5pYgQp3ZQc1XLjFg6ylMHUSKO9Ckq0Sh1goL1lbMfyh2lSgTpbM+Gi9UMApWlUp5d7NxgFY8HhoFZIIVYmCHhQUdXnfdPJpo6fNNz7RdD/bklGvrdSc7WAx04wbVeQVn25tWxeDRyNvk29X8HYTa2WpJPw4ygtewND8R9ACRt86beWOFguzuUnmTmND3b5S7Zh1fLrdc7YrJo4dDdDWFQXOtrCk2xqR68OXCJ2wWPW138a5tylJCf/P7+HHb8g0e2Ja5syF76K7fmNXdzD1wjYdJm61E7WY2HY977WhGXzZOoyp3DWYHVplEUeYjOHgd+41mp+y+rS3g10AHXyrrA4O8KAjHDu2jH8ig055eMD1ovGMhQKPL1DMrg+UgSKXO9MddD5DnjbldxoVpWasE2JtKKA/bJfgHVLG4ogzKzCCn+PF9weEskELeHRIAE3ooz1FiiAvWA8XiqOYcx+Ucw7UsHXhwFDvIZFojdqCHk4Yf3FlOI8IZyeji53hM5QxioJQX/A7EQEw4T4hDyZQw1TpV+TAL23dsNT/1y+xwdvJlZgyXr2zECVl/rnK+N7yf5W2a4lCDzOqBJTiyoeWHWfRAFN1xeMlDHPJsND+MQweyodF5hAK2yTlIGKJ6NAdPT/WoacSpNroY5mwrUhDDLHDHeA4NhGdnk/1+aiDWphNI8HU937tfXjCTXrDmr5xCOWz1Kq83lVcr6oaUoTYXSCqnw30zOwh9uEEMM9lA0+YGQg7oiPEArHO4+khQh5o6m0OTll9wjEaRqEUt+2Qhn+rdig1klUjeBkG7UFzkh7jYhURnreaZfKgQlkNdMXJ1u6F+8zsIFrhpZNcuaTr9YefszCkZctQxrl6Cfz72khU9G5WifPe2WOSru52vC+G6TxdgF7odVHp6Wqn0sk+n6heV2XtUIxgP8lQRtTWd5trtAQSNEAfWUn2Pa/MgMqCPq8nEpoDJd7n8L2lXPzZ9b/qP2tduzf/mq2+/1jZfX63iDG68/oKbARW3ZxeHm5wlCKnOLK945OQ+8aGWzuLllA7fv6CqWi5dSeBoZ/2Feu03u73fS4tARkg3diFQBYDTQ3x4lJl+g1bBBX0JwYpeftYLqwBcCZNyPdj5AnT/F1EFWRyvvw0CjPVqs/tBM+l9M97M60y3qPs91n2gHpnxWi4z4+J0KdLR6dLI3KgzA22twR7rzsC0cx1iew/MKmSrN+lmNZ9DQcusYC78i7uBuA2bmUaTT3aecRRdogRnTnP1MW3YygoeDr7lK76waCMJDjyWv1aCy3zF14Ff4Upk4d8Yq1wbCQ96e7RBv1I3RPLPqchnOBe/wa34V/EjtQ8+btEgXkRdeZBWbkdcl61AER7rIomp3u4RPmf8hAq0aaao7OgHKdtLt1t2H+OttY5LGCeEPO52+SBZbWDF1R+wmgn9itYJPRBzSn8xx+HZn+i/9QMcWLi+A7Og5kpQWceS0u6vq9WCJuiv6Hy9K0RSIOJNKDM3WNARWCypQduhNYEvJPoX6wfdOTyhyvDCOz7lcfQh33oTTiNA+AEFQTX4qdOn6IJ9+rrOwjNW8GnQme0TO9CxRqOIeCb2lvq5hOUMnmoJEAkAbLVP1ZilKxpik5bcF4eDJhQZZuM0zde7V/EubnG8CqEVPo0c4znx2lBz/4S4tM4Vi3UOfiEBR3E/kyKOFF9h6hOn1QWGdRPLe+VrfZ/7JJe5z68O+6uReR7jJveb6NwffXr2TwRlLk+Ubx11j/IqqO413Qb7yGiIZ163dB32jfLOoNxbMwHku95at7OgeX+dakQSebvNHWPklONlTSCH0a8XoYdFkDd2ANNNu568dtPwa+PwZagvY//y5ttvWPDhuA1b9NBzPVZRajcc+8GyZXZSvisNEJ0ImtD64wlg5924HetVgpQKs8SmiCv0gbO12H/YZv5Wxvrge6YSwCDeN4bqRN2yog9gkm04zKIQJVnQmNn7UhgTIVkMYcIR4s/p6WzwH9BlhGuFG7aygPHUzZjnIdjv7TVo1nL3ZJUub4i4MfVG2oS9DPwWzoiapTopE34o39MhfJLBh9gQqzVsdRoxQ5PWmKEc7zMfck1lTF56g1s/2/XqJ05ibzxTNrZQMOSYcqp9YIOhaSQvgSoHztF9MX86YijhgHHUBqTJkP0AJGMleGbC+p6NsgGiDU/VxLEm+ntFmaplfTNt/mDXlHhDZ01n4HVGdvnHoX1k4ysY7LCqgsifh0gI8UfGCkbfhNlNgiF0Z7Xyvoi2octgR0C9ZzjPKB8NNeRYy+74lChEUAZ8Tpy4e4FrreSEa9Cuq0cJQjUwShh2voOv0MbOsXZSBx+GirqkHYn6TsbK5kg8PwgYyBMJ95HEB4Q15E0cTYeWXGIRD536eHoiCdN9OJQMicz4EzR3UG/45EGgke9OPNy6e/kiyTP9bGIKhoSHCQuHr178r5evPvvj52effv7HV2cXF+nk7E9//Oy/zj766KOPP/7w44/69J/HMkquuVXZLXZVwbgvI3fdsanLN9XpwJHMd2z5nVXrrLC/fzMIT4trX9Xz2px/114Pf/kd1Snmu375XVVy3vp9faMNq0zEu8Fc9E6x0SaOYuzAPaOE0XPuUfQ1C+P5iajbTZxbbYK5X+QbXyVu8wwn0xY8Fh0/06aK/3SUisUaHnrwp6z6rLRnnYjJYSiXP+5eybQ89WPaGd+KIR2MFQPwk6194Fky0hr90Iy0LFfDLFVySCM9YhHlwC1Ji2DjtwtyAOVwMkR3oVmoexkHjnPxtvWu3mM3mygLlT0UeDDAcHuX3+UNiKso9caI8c72fZMHxK/jIhCxmulkrzS4rOhkEpHTRIgfVvJUlHYQ6d36dCfqnE0O7GCzvNmv4IljaqETmtVa9DiXtF/qmLBkyxokowec7YvV6mZrPehUFiIv6zkMoFptZM3gjRmwywozgk2YT/roSKR1ZIxvWrcsZRW8N+HLLZUbvdwp21p2cImCDcb3HyzkgxjU6Wt7ZGU9/5zDKwXNZpZTJhDsm3qS65Xj8va6tJMKO1BDLZsFB9RUP6US7aWOX9qWTjr/wlziNrRtmKVDXF3MqsL+V/WN9Ek6NAtuUJT1vc+O2JAatm6EKrjYLTeogobeIUovLPXJXU7Wx+uzUNDAS7mlWgD2t7ZmSyU4Vjd/e289GlBlG8C2tk1w7hpZXai8Kn8RBA/tJ0chauZuiLOzDO7sXJnaRCH0M+2N4wsnC8EmQXqTOiGDLBqAJyoVV2A4EM6e4RS4vNtV+o1B0zF1nlH3c1dMoeWibxBq8Ww8BAOWPb/q7YOrrEsvo/zzMX+g131wrkNKqbfRyHu7WnvK+zvYe/r9bLXbrRb08BWkKGP1/bHwu4R/oAqCqy+CkAU4ePFaz/w7nCF1HH21mrNiVtf4RySE6HZbE5qbi3ht599mME4QyMq99mgq76SDpxzsNdilGajENDDNcO0zlRLdpXBdTy1Yn3AVYhwVVGNaZzCwnXKs4WtWXPNL2wITTywI/esoUc3wlMYNgJRg384pByTQyrXW9D1h315wCTDMQnMHgJSZMp+CEgbyYRxeD00/grAYJuwyFJcCk4P6Qfhw45Vkz35KENuzvOo/Ls/QUSPUUeFSaj+YNX29iaecQ9tYOCZCJyefzIvlzfnlJ2zHdfnJuf41VlHn8QeXMeyixJiII5FEH5iufwDjohsCgBiSnx9mBVFga2L5tUjHsSSyzMtNbwdxWdQ5EnHF24k0zUDVTW+2W8zf5JsinsMZSudoQQykXu7jl3PaB5H3SbiM39Ho+AdosjF59IEKp8jOxj4EUL0VUZQ8UUocBunA0DRwT1mXUoDItBa0iFo3CZLH5laV6f/EBHi9fKCZN8+Y0uWKe27Ks8VMpW/W42PFdKrWE3BkTYswvYgMdh+cmGF8oB8+OOGIIR/s9OJyskxjvQ/HemTGaofBcmhMVFKN6OUndUF1SlTxTSWiV72SCygYNTpCpXzat/8tIV/iCvmydiHfwW8IGxkfHhM4tngNEvk8+yCCa7ViyR6EUnj+9LqJ8m9GRMZ8dpfQ/tt64ygViRJY3arJXqo8IIBa9sxhgsCMaEaQdZeCQWZklHwW/fieUMMcYxixiG/yx3MONkw5F6u7bb5fr4olbYi9VjSm4d4Fe576cw5FTBn1yCRIOv+lPZTM7zaQLnJM4tHPvfFzDpLc83sI1+walsWJ687YJidOshMbMUWyE8TzserVrrxnYCaeoedpOl8l8RxMfF1/t+LitvStpObiYFbdqk3JKDAm3SBQzMxe2xVRSsi3MClE0xTWRoBDkd0VGdRr+CEyArJA0em1ke5t5Swzb+LGBp46pMqAJW3mre1yVRM8cD36V31Rrq/N5DaRfdIJLjNGkCYHyAM2EhQljZse9XzhXPwflCRFHBeCOS3Cl446oOdBXaEm45mxHbyOAQy/mKI4uIpuORKwWkccQlcq09KinrEhDtSKHSGYLrLhZTwfrcZQMqVKIArkvTqlDYjTJbzuJcR6szh6v1+po2XnpYzwid0/rdSKpojruBV5Sab0QoZ06tNShbJwyixpmFdjhrNW/BFjSx1ii/AToVY+IcO1to/uQcO3oHVeRlPqHdZYntiPjR3dy9UdgW9fXQMX3K3h4IUfSjvKtbqBJWXngmpoXiUOW24XVwTd8PkcV5Fy4/LQ61JO6JNeow4sCX5Ny/NAzQ3MGxivJkQyc5ABDpdGaa82NCKo+/AzszR+J5RZOdmxmBDC9AN92dJULvzdO1jL2FhjtrafaWg35V4EL38Ezq/rcH4tXp1mJahfO6A+06A+ex+ow6P0cUjPhvMqpM+rkL6Mbjg3+3GacfiAmoP2q6te4HUN2NEb4eDe8ytwIhCY+HiCy3Z4m4iW1eFBkXIaLYm/Up1c3IBMe2bD7PfMG2GJOV1gYAbP2QL3054F+4DVDCWfY1rlPX/uyT1Dp0znrWDAZQIdT7dMDX7OzggKBSZOT82TFWbAeRv1Z1mKAefUtXiTEe+D7ObZFFgri3P1llq46hBwl1DmMCIULIBmW3D6r3B+3wRmSaU4SnYBHAyrAOi6mPuGZYVSo2miIdTwBEQ9DiqgcXvVFVtjI+htEI1okh8BjdfGqZH2dTmUeL1hom6db3blOIN9cwA4lJjvs2gecSD5R/VhJSLY6el/1d47zwQu1t3G8YTBr0vpKVV/Cd/Z/m20dpqk3t9aWdet3kGQmDlFQy/4pI8Q5oS+1riXcITjMEehzbHoyWquVfP6CXaioJ23b6VrUT58EX6onCmIbksM7qbTSkXO67B1G97+5jYMJZATJBxE3ButX1ojdiQM3zP6EddRRpEj5XuXighT4cJR3TRQy1pQCyz4bsz8Q6VYP9p46KnsAPE5gs3fAUEudCdLzIutWFbgguZJdNluKqhqv18rveJFd41dDe+Kjv3ZgHjsasrKeJCjg3o2gIt1IoJrZrKP8E2iTws3sPF+P9dVSb/g4uawtB6LqPHVaAmXRdR7LDB8nMdT9rr8Zrci5ikjWNKxq5eXF8MivLGoFkOZRL45NmblRmSzl5EUG5cHC+WQLUwwIjcVPK0zKM9NoBQ7g6yp/IKSzhUgBxuxMFApbVPFOCFh+y9YvshM+IFm9nkca0bbPFz9/TvJjcFCJn5D/ZUPgAHzrNvTTvSpVeEmap3EpqWRZARSTYAgpIARzoHU6EHEMapJlq6FIaM6fKMgtOE7+XpGvRFshXN7K2Jm5MCq9UzMVlUE7R6YFA++Y11SsS1p6qBelwsuBvbVNRdyWo7e5jazX58O4mOSLZbjcoPIHuaKaRVoEV/pvmMhnFfplhbEypZ8mtnmNLm1dXOpa1DqFu4nRBYkGu7jY3D/FPfSuw02j+7YRPiAaVkPMR62udHUqfDLxSLPCoR1aqvZpzwujoRllvtuotCWpAJ71NdN0eEY5Zg2XNbncuGbRr5fn+7cUiVjMUST4hAV6z4HGqj1wArntp9dcphtlRqzH9Yvrm4tJaZAzjhhbmW9j9F22e7MusHgxn2vrKOKS5g1c3r81tO46UyqpA/LYOUs4IP49LRwjbdp3sWLNtQmWbrSMTygEXJ2IvHJQAyra8jHVrLa42qlysL6aWflo7aKtX9B9pzWH8y0hRVcoEzGrOntetNQ9oIklwtV/IBkcFio4YLOMu6RNkwuQBpojyf6k471V9hYf1B5R4W5dSKdWxJQuyx9AhiERTn9+cGu5OyTpD07N2ZLJPoudwaNtCnMWR4aysYO9WGd6LrmbXrioOhhrj2oGr58GOXjwZROzXpiNI3+aRhLub5lMY1cy/1U/XKTP0o6RApZxHrb660xJKZHo/WhvwRh+QmWEA61NAFWyar8QcL3J+xxJcbSTeiPvZktSRbzSBhku0m1mAYnufpQ5BL81YU0W8J+rHh26y3yXfzX/DGCH3/9rKbajHI4tYbQakJcLt9CrbehF893lO8kEdnZSQrvHXOA80m628zxqYIDT3jzf0d8Iy4kuY0TdvuWZzoDk6JIlj6e7IpF/mYXL9Yn74gggQfjdOY5yjDKrCLkUOXS6O7Bx8kJ/rykQZ7QZ/zDc62KmucZ5w7IaPVywzyL/GQc+vZM5cPykYBYt4IgbAflgJLpl2CRE/mhaZsXNC0/6t+fTiab1UIv6Ynocv6of386ITSZ/8h/fzrZpps8X/6of3862a10qd8enqsBkmisxtFinbYHtTngpo3vTMiNudesj2Fhqk5Zsl+jmgo0Iy1cTShdZ1lZF04ziCViLWfczmmO2E3oB2eSS8o4udwEDk/J02Rr/6lS+9vVulI5v9fqLvM47/An04l7Fbhlss2vJRJWKbciO0YzAaGn6BwDklENjDhSpAWvi9MJfNXR3w/Dj+jvi7AvwKRP5/AJaugI2yPsA0ddlDglTw0m1qru4GBOQKUK/kP2wBH6lql0LncuXKGvqghMPC2b9g4KAunWJp06o0qzKKH9z+CRm3I1QRrtrO52Hi7L6Vh8X0uOHbvQlvp6jxhXe9VEPeGGxbOmuPpAvcYfjtx9VDpkCPjfMp3Xy6y82IMkIcnp1M7vlrJMLtVSNdgq3eMI9QKZIeiuYhnPzc1OLaUnrfMllS0H9Tq1LRZ384oVpBbllTb2WlLrnEJQGmFpRayK7RtdA8dyqLRK+PUQDLJhjY3wcxMmuSn+1oIP2Kod4ZbSBj0IfOnKh6JWW5th/fa51cQGc9lmecNKdfXY2qXht1wiDSqmNvBZpDUgJKT0XzlGVWa4sKpdDlh9dgEnQzjq2qTi8EoyD30289uVTpoqq2BcNDErqukcTmjOb2Q9QdokB9k0P8pslZAFkn0YJ2GSaApG2oR1uAUj8dagvSPYozqSIZhXSPyWxMMKeyDKPCUJB1F0UJEhiWqkfnM8PD01x0idU23sl6S/h4tChiroNc17m7M/ODrVCdtZ1NmbYZPfCauTDDBUNQbo3+lJc2RlX2rVGq8LFVYLd+9Ms8kxErGuKXK1zd2/07H3zL3uofSmLd/7vmn7/OZIGHGIhx0muLAwm9DjZ8IAG0/osHkev8tNMh0PSt/E6uz6TQroF13EfOLTqO7WpCapGEdPlYMsUUbsRY/C7DTsdtPSE2SNjmCVdcu8a6rM74AVF10eVwnJhJHQahYTy8UT1WWlDe3m40Y1A9qdMIRUNz25ctdX4wgGWR2p/h498QXa7zmU6VRceAGsTBgVmfqg+SUZ+Azu/SIVg1znsOYmwVP1aUsshQUXZGVziSUABP+UyToQFoTw6I2J9p46Ssup8iojZ61jt682Q3vHYpP8S2L2FWtNHqldnFFCt+8YnVCvsLzYqH/RnmqcAGssUdKy9/Ia29AKvmcGUCuoYu0j09zo/A+WV9/YSB47ZYHAmChzHIcx+X4cxn4U/py7bwOpDX2/nezb7z3W1qkk1pdXOrrWITulA71fjFWouzCeVtARn5pVOs2UlwDM0sFfru+2O11Txui2FOE2NkFbg81a6ivc2tBF2Uy58qZ+LeTk3jjUb7NfQtWyaghV0N7B6qYc6NUpIwMHzj6j3VhZ/cY+sxna2+rUYBaSUIeIlde35kbm/RPQAH1nxzb6aXfsEWTuTIBD74kh2/u6qGGTPb4Jk1JCsU0cOqfB+zG65WLeu4db96ieGgKH1v0lW1fzgXbvmhNYOFHLJgqX6HJzbdri7hw2lipRFs1Xrx54pQb1EzgZt+ALIe+FZqmKKfS5a5Y7g7J4jgg4DUWPWAxylJNVwdivH3QvjuDKf6vZs4sBbJLKynO4UG9lY5yuODfaqITdGFSV9CshJRv6HVU149KHcNNUkaPQpfs9VIC1Cjgr6E1ExU5GueTwB6mKIaHOG75x9QWrSJLgRcbPIjgQtTWG+gPUGVqsAzPWDjN59avTIYhl+VaHqk2sS4FOVvGa7HjuzrXfrazVyYYvPiJi6Ey0bjJEUGTdHKOv4U9remmaZm0aBdTwK3SPUrYNOqjVso2NdyU1zkoC9KiT7QytyrG8THFXuRGkWERiJidzaU141azdr8lsZM6tfGZJzK7X87rOp7D8pMprCno0F0hKLkvagY+vPVywonZzGhfCKVVhSlPESSQKJsesXStAwq5m+FKKkpPfWCEXN8Z2iRqyqIbr0mP1GeEJlokvYpzqtJuSVoMabQhThl9oq5CxgsH8rjuTLCmPp9xalO5Z0T1+r6J7WtXM1aGIzI1OUPuMICccKr70IczipyTy4iTZ7OPNrkjn+T7eFnRkx3d04u2TrNgTJ/ou3u7ZnBh/5oTp9pCrFPPtflJM05jjDePxbpPvJ6sVVGglFu9+NiXWbL1fxJub/SLHh2X8bk+nDRRzjVXPfpvzVOy3dwvK+biHkGL/jrqxIsIiic5Prv8G57ZXWTfy/CHjoT29BN75VE2TyFVA+YS+e9086XrB6Opqe3459ojl8BBVLzr/+WrbPVcFPVG2DpSB9wm0fed7Nm3dzzb7YjHdi9owtO3R53hPJEi8CHx4hA/HXXEQH1ydX55PC3XNlekv5+oGr6zgf16oOV72p38YXt13B+dqIe2G23RTrHd7dv7ArQSUd0kfNdEKd/TDcPRzNN5H9GyUzXvItsIonu2vzinHdfwu3ufpIg6kRvq8xmc4EaAMvefUn1sZ9fNPOlBIHr189enbT69G+7OzYI+E8dUYz5eU4xnN5SaJniR6dDi6UN4nghtO6LDfFWvilz4wTx8giswn5/L90hsrwkV0oEmpSZHPMzrmJU/5NlaYccmziNfymR/GiqdYPgnOka/mGXERCKAkgxhw8Hf9SJ834eiF/SYroLPwo5OVlrslr81InxlmpbR9c9uifnzYKL/b6PY2ly2NWnl1zdpjOOorD8Fpxjy2Hz/JindSDz+MD2qbRIQhHgkVJtE2qRg/tGvm0/5OerSK3O9InmlFaXG1YQoesEvxYMbHz7KR+Ttmm0vM+DUr0dVdUruqiuB91JwPrZYrcLgybP+kA1YGoa2A3Vgbf9uf4k6LC9eTbUEtJ5CYZUzzROza0XV5S+T8QMeWI7KEDyrEA4R3EYdXBo02nJiLdOs+d6JoyEy/2QiB1tqRKC7w7o6r3gQeOLTf3XisOBibnbt3jOp/MPHFDY9rpb4NY5pazEjnIKv6B2Y484JKX5oeql17JSqzYX/C77VRolMNrFDF9qdKLtdBUBfEbbTt+kM1nCxLs3xZj0j7WeegDbHR1Ay63rnX1YJ0p6JH56hcJzr2hUyjdc09NOKy0cU4NDcNjbjnbq2/Ji2+6g28ECDBbJ2dzTuSItHZ/vxdPCemMymNfTkyrfvVdQH2qW6oEeMX61ZqbVevgUsNbjWNSs53wg7/tCb3QNzqGg8PRql2WppZDGwkjRlvEvHGP4MdTS3AbpXPTxXyYAYO4juEQ+FWfZA8HZT2KxI4Xj8+q+OJQWPYOhpKKYSo+s9Rnapl1Ompq+IKW0s7HcLtZBhfbrTbqwrN7GLGKDQPkgZc2IqDgyfHqSc+gBKOTyWKP9DS8R8QBL2nQ2FwoKlHpAShYxGaDish5VjH0draQSJlnoOKGR9If2vMxgIZ6t7CNWgUdcnS4M3Jjx6IbFU6XkUz6FANyyTWwM813uOAXzxcfuL23ulLTf7lka41IU8jtfUKduaKt+Yxrn8MfaeThMKNVRa/y+hcc7+oGjgtqHgrmTe5McehjKiFR++1BOZw8s4CiOMEiT7GESk6Ux0o0PvE67pOrS4JeeICoGKmF/q76uKU9pU7A3KCRenEdLEx5Ht+HdrrNpISmsSNDnJRTaj1PtA7/Q4xQQlb4OQOWNFR4zYggCnhtm53GmQcU/4zjtPJidCt5cAZXMssmu33UgHrwEudLXVNCYV8WtbCbD7jvYlF0dIjZbYZnMKV8U9/xVnbKU5Pmc6wedDsLMr5XldNDiq5o1kz3FALR25wuYUDdQ3zgNKWewWqKoGiPVELtxjH5S0HnEVwV8L5t3R27/esABK0WntPgsAc9GtV+lgZjibjcFK6TJxr+KHsTzyJq6r9ajsJR3DrX+sDbiI2OaARA44lWsGRiOg8KuiUJjLNEJlq5gAhHLx3J9ZVWZEQcfnsgmjTZy8IhrtzOOHNI/iCd7wWQVvdWrsybdViA316OrODOz1dC+FkBgSXerwtZnYYiJ7KSJ1pBFqjSNMtBMTFfn9jKxtaGp++oP+Vj/1wFs5cSiYXFzkludf0wuSQRdeRm5VoQWVoFkK4181aULW7c6/hcNOs+8yNq6BmfBxo15eRZ3wvuJ0NZpXKKp8GM0SmNbMudjbHZpXACY79V9XaGmbZ7KYM8fE42rs1rX+XBAzz2vYpWo9udThldh5zdsFgbpzGT+CPnoVxDi6d1HCpwTBV8J4Ezjaf8u6eQc0eh3fZ/GyUc4Rls8bmRsgLbOSjSamyKjhgJY46li2ul8zeL2Mq9PkcMO7jriPtXUrN64bUhCFqMuuSb4AThIB9JWCemOar2AhIANwMUXPZadM1nHUwiWPosMDKzmzKkgBwWJNnQQQcVqkXDGVqqRfUXF414k3Nh9bRWDE2zFRWp3KYl2qk+kUQophMrJ3wFnH17ki0mX+IDK7lS6nZMpRAn5xTq66wexHfuOz0jUOZMlxRwyCmvg1iDjMoEfHqnmoQcogrPqL/1stWi6/jZbFujdFgqXR7waJdVbak/ameZHgU4tm0HvwgqTlQOBwkeM3/QR2kbZ1vdp/xZSJ2UiVYDbor94z/zd42LsBrCfXmrQA2nuyO6jD+f9FoJR7bIWg1xK0yi/FwUSpil/AN0UjJRmZW7JAIIZdW6LbSmR8h0ZSdwVb5B/CIFvumNewLyplwK4q6NJNbR+WkSIOqqL4Z/sOML2YLGz0KUS2CvR27Z3+qR6urDSNmJ9omCJcLTnGlN5VPg1gLrLY1yYonTIUnEhOdx0RF7NciszR5A8d/kIky1rkIY+1AB56r4lDE/uwWsu1Glit1fcUdFFiH34kRZcdp1MamiSkdTJnrAarm28+5DKuE07TEnaXqJgniW7uysJZYtQRmNuo5G0VWJJGnp9Pq1/fQe8hBZKclUeP3kag4BGNWnmqlQNksjq10sst0kGJ/yCSlMkl1SYlfA7KEgaziBQbYLOo7jm8Pid75tRMnfu/poTtcDSnTiDLiXPz8JkZKMBN1rYbaeBh/6LASugeaVTAoUSsSSj/Zysxgdcf5mqYl2CkaVGCP+jKzZtusZGh6X9s6MEmTW1YEE9NO1lVWMlcEyfOKL7OlaM2tovnZBTFZ7BzqthpWZc1M5u1+P0f0r8aV8potcUvnPKenSw1+6yA4fp1nFYaJbLlFnKhb1gnuj6O1Y5mW4rqTwJ+jiGXOovG+Rr/m7PBk0atwmhq/N2gTgkEdZBP+URzeRCzLWpgKOFMRShiwP9Vx1mj9C4difkjYiNZgh/nl9eCajcxoxukEWRmnm4yXYG3PmgcTIE7hUKYiuy6PBPYsZGYBVvqZuubhTjSbPx2Z5s4u6oOUPk7VYwKb0f5gYvszRVWGfs9c+r1Tak1UxKKV4wwRrzAWGE1BzSKnLN9v5mxlrJ/lI+jishYfTdFhDZUQh/PCq0UG4o3C4J5bxpOwHI7EorxyaojSF6vKCIJ4uwo9efIMrYYk/egpl3oItRqWSf2UyRaPqRfP4BLERvQcvNKis1oLxRc3Y6hy1FR25TaLysUazC4jkeASrUFQN3OQgQAIa6X4Uwhz6Qj3YaCtNzW8anG8zgpV0BpY72WiXiWVmHqfJ74reKOeNS6eA4Nx367oI3P/rD1LbWqlh5erBbGmefZGRxbIjn/1cxYkEB9jHPaLB0AkO04Aras6bQMA91aldPq1c5PwSJv2FQLj2ItxjhH2uQiwtaNBsZ7Fl5dJRP/oMPW9TyQu4wn/lTAB0Qf9D044OAA/SewCPJ7TWedMQ1I3v2KnQy8RXrsaz3G/rySazRiUUcyT3v2Gzmhfew1jsVDZ/ZeJnYBA8TAZ8RwanvgGN73tjFDwzQ+beM1BDLauP0qtIdQp6RJEL+5clDaeZSD6fyeMxPD/sigSZ/d5clPszpLVw9m2+BXxIvTSIWlwtlj9euzbkWQD5gnW5N8OS/G7roz1IARuvY8xJXH0YacWtaIZoaL0/Xowrtf+DI0D6eS5+qKqnPGz73XfdL3AH3bWD8EoPvv1P8fdZ1pB48tE/SVRf0Vxn9Zov8F67RN2CrrHcsGRGmOFCjoY+l8mbRpi9XtKx4FGow7aKeIMnXrQcKFZFSXFMlUlrBNK+FIL1tMho9HvtIIx31gQ9Oz3CFMVmrCGUJ8DOpky23f8boI9pS2kOYmiTNOp/Q3Rafln/ZyI6+2ZLB5H/1wUS4kyMsFL/CAvZbqTaspFU/Rf12HSMrdMrpxScJ1sGZXpcBpOu553CMJGXB3jbMHg9WNr5Wb7n6+CM93C3MEPCVyRIJwYzeqMXca4s9n5a306AXFi9HG3FKMpapBmlO1Q+SMiIMhjVB0Ap0EczN+8Ca3xG+Kw2E/10LvIF16I6Z711sVDzuazXQ9bThfI3JpbZ5qImvhut/JcNbKvKvoBTwSHdaYljWLfBorp0Clg9dW4AS1A5JMfpW0Y7ygJjqhYHhqnSCWWLd8hHcPh/9u9tOLesf3YifWZA9VVfXTAsaFF2ZP5Kt6FmO7BihjdYvcY9j6GZ1D9Fnl9eucA8ToF9hHb7WuUizqd1L4ocxzAxzcUawjvzot15Dn43Gs492wvwq5J4YuauR8GrGo9zBi3laWC9P0NnySRxIgpE0AQc8rXq18/ayT+wIeXTS9d1N8ogqx5gVX5osiyfPktnwtt4Tvtlit89hZhm/67ruBomYmUmRwU74zvzKl+LH8u+XPw6lL113zwsEfpo6VmUmpW0cIsKgBc/F8ZC+v30CrSYiup4nxqT20jVM7Q0Yv/HOgfPSAQKE2y5SOk1ufAw/0eh5/m8IONs5t9ZnsX/4kj1W9+Jt6Ej3ZxloMIAJPIo4YA7u/PLn3ivFRSTsYi+l3klCpqM/1/EJXY92zvFuVmiYoqCdj3LLTolAs+oaIOh+xgZOe3zV/hTrfTQFB31Gy0Jzf0LwPCz/hhJ09ajVJrehZHUfsugz9H8Ee1CX7PBNipYsYOSgwQ0rBMh3HaF7zHcF3pOzXrQsDLBWSctdT31tUW2u0g8dS29/G64YNeOwZgh0had4stOZJgOpqMDfWDe7/ykR0PDRAjQ87qWEfzcEs7uVGRZZF19DQiveP5ehZf+aOfg/HzKygcf0OJ+tC72j6HPrJ8DM7Vt0yqY+B7Xiii6s/SUR6Pgx5Uob87Qvv3ngeG5P9bPQviBASRzqkz/T2JnixW8Eq08K7YFkkxx2ntzfhI8pRZWY93gXdQb6gwkTW7fPMGg6DlB2iDIvtBMKn3EccleptEI08OP2r2W/pHxyP9XWy9cXlIfF8q8OlwaEbrUMdCEXczn+78PnDN94QptBC8a1woXeBASAinvW0JFsX00ltEiuqmqt6C0Z4phRj/aCoussogC4j6TCbrJmaXU9bMwS32dGxOHlxYw8OcI6JbzTMrQREvVi6YKwQBoxJELlihiLjvrm2RQDE9Uftwevo9+9J7X6vqdeJnpUFbgCCseYRyyqfz1m23kzsObmu15MNUi4UyRyYEyZ+IW/vHpyRxWmkMwGtJbZmBZKinyQulrlKy5iie/pBUXVx8p69TklJVavh1vGO+y++rjNDOGUKD9YOg62fiapewchAmZZ0/Jq4hmYGMSZSyi8mhJ+gQYbvk7PCC4Uehxzhe+JMLjjDdH3zEHs2iF4FGpVqr0J92bcSNtPuWHeb2YdyusqFvKzV5z8roHBr9epUypu5OM7/uKGdHGG50UBcjkHB60VZzmdhp9Pq9NZuJn5Yz+lNtlZAzcmcsdmUkYVw9RSaR4UsdopzgqCSbKjFMbBYPVwoTZtf6lwgKrileRj559Bfp1IR2Rf/S+crUkEHzLpOb2zuTfJCBOPed/hjCHHENc77TLSvII+fEh3tjGysy7xpgg6iiDbRwQxQwb+uqbW632u2WYbaqzKrFsIZr5bF6OrMV7OrYl96FF8ItwkFRvd/cLRI6659SOiIWS/YdyNEIivn8W90WXuf5w583q3vz/IalnhK2wJ4L9IZgrl/Yt1VZgVAU/ECn5XKLR4KH1T0//folvPfxE0RwcAFFXfuOXYw9Ca/phSX/OPTME80cT7u8wOHH47zN2lDMBj+s6YL+V+09NoyuowZZD2ZYWEEKnzwMhdzR0WzMJv/Oe/Q97qZmgaihm0UkEGExlnmdjUtphRUwAN5pib2CH61zJYLUKV87QE8AOmbDPCyoQr6OsrFRnUiUHHMyj/6m0WQaiPmgn0PF8SJ4nhNK7DrQanYVRGdE9S8ZOjyJodFhcb7GU+ZTh919cTEBJZmHtBsxnm0y5MDwchj1WVhqnTOXDDk8XfgYVkTjn+UbeFhQHR9zsq3NCVB0yn7Dd2xGnOHAYldgXN6Ecr3GjTYD1TGPUq2rbS0kfnudNVT8ntWuri0igpol7eN2s4QGiReq8RYUnulg3CziuV5XpnbeJJzpTcKYx9M64unQryChibYS7Yu+M60UzWoKT5Cw+A7Z5Ka8yht5wjMTMSc4e9y4cnOGF9VxkWvfmw6/1di0GXOKQFEcODlnwVDIe8r390Q1pRE/mbk4BKF9tr4Bti39EHdh2ekpnylmQZmMoO9DjY4z9d84auAOLAj7gfYwo7GdAQ6eHisOa8HXuivf6PkBEVWViQ5rIlLt/lBDmn4N+LZ22OtfPHfWW9iD3rMLOkq8MGGU73mNGbLnhYHerCaWZZv+El6SYOgxu+NbMV/3ot9/DtNRNEAog+WF0jUCQ/vkeYNUR+1WfnIZXWjxGWTHnlYRh91GqXX9tdw7Q15UU77kOOANIyVpyZDRCccU6GTlLPmmM9HXpWJ0pTlazAk8z3ZzHRrQrqLL9X+VEBnQIjtTraublAD9ZDktCXp+ZhiuvyRqFBu6TiK7lREly/t1zZ7TLBu2nB41q65JsubVuN2ncRc7VfR5SxBIHdVfvih/OgDtN6JtD1PHD2cIR64fadunnGp+O8rGqH5Cv/s9/T17wb99h10+qD87mlB+rWfA39EPSVDXnq0j7OPKYrUrDwwEJDnHTy5jGctwM01gOtGHc83aTEYJcSFju+XxJketGcrE0HKli+ihc+WkWOHCHKIHaL01FKUu2VfajMipJob7h1YvZdUi4tHbJL86DyztV9PpvC3oIqGqFVSk3DCwOhAsGva1OjEaMM91E//vpZHhQn5NOfMqRTmKZsnt/7PCShkRcn5PH0r/d3TkFzsnHxG6b+/zfBn9M1FuvoiI0iWB4h1H1qGPKNfi/IMIZtFalVBE2gcOnc1RapwhbCHWB+mxvWfsLW4YRB8ySowjtnizMzq/9/KQsltPqWSZRZk83lE/2MmpS/ak4yFYVxA9/0GUxt2mqQAng1sL2Ntelk6RcQDAwSr/1VrfbpnSB7/9flCbu2Vl/bVE/rcak5QVjT5yJ6OX3W3YDx007HnaRs4Ujo2+bz3381j11UX7tyA0SsI0q76Zy7NyzgM6P8q3aiXbXb7WiohuUqmQJR7bTf1Go4xvGWgm+e97Z9J+V6LPpBqQ6gCk+0259UVP1lq9dtLrBal7IEavR7EsCfQR8W7EKVpnxEkzOYeG4/BjHYqL03FQwpul3HCCqh4mRJeE1Wbqpz9rTj3wdNr6GymIVFntnd/SPYca1uWqRK9ODEpEWek+eLD7bsz7qt5rZtDuD3Zh9PpZT8dRSzKuh9lrUs3zHPfYZfc4oaIz3ta48cjISOQJR3e8adMNjWmGgV5avvU+PmOpVLqipXvOj999GZy/4JonD1ED6JRdCUizAULPEvULHSWpxNQTxL8HTt4DEyOUXpLWRMPDUEuH94GRJIsCSSlOTqm+MqQtVZKl0ahIxypPoyfvuReOjnlvMXYlwNy+DWQtCJMO1tTI5fT9/+jDcQ1hxiXCpKO69knEdB3mc7oQgJoKBfxTDT8BFPouiCl/wUc9ke5TagjFYCtHKBqv1J2cI3FMoy4sQgfZ6oTtCj1cWU/Po5kycGnqVdPuxGjhz8B3z8zYztEZ2CLMTk/Pzooy/DprSaT6FOlO93u0hSigclogNDCdIOC7h9Nuhf8Ou/gLBbOxoyM4ScsDn0D5bbGAm0/3gH5mXAAR1fQsibRv2lIKN02rEZWf9E0owSnMLDgapUgviZrLB3k3enGWBGn0FhZ+2cgIGrsIuzAqRYb0GlvGmFVQDTOQ6YsvWLyW3ZilhjpzrMwiP0+ZNR6NAxNmgFII4saAGMhTTVSGwVQHiqAFxgROxoL9U1BWVkTnNFikDXKwYtCqtayJQlxbxue2Igj6HtShE9kRkbWx1byAuBjeLhywqB6gncPMa/WZGa07f4Tvr/KF7epmTuh05b64yoNlmf2+AKGlypRuVy178fw+fty6ANGWVhY6O1Nl3HnuqtWHn1VjubPzxLqhim+EArgWo70jkgG+I2OGDJ5mJ3M64kdr+6zKxx+d55/GYlBYkweoeWQjVF8Pncl3b1j2e+ihlrcd4bXS/BSKzc1FhMu1i/wwYPmtZGVVzW8QSOSr+JF2FQdM5zpoY1frH6412xquy8uaCgPHRkNmaByqzE6FvWRr6olCbty2Xk7pslZoTjhT6X65cL/85H55MT4EjpMHtkyHj49sTKeIINNcjC1NyFt8YqzpyfGCWYQIgPDf7RBjySFv5X3A5eSZpgwBwzaOT+4NFKYh0i6Wd/ngFk5jV3Axvjk93TCPWDJMmY6Odx2Vljj18HerIHDW2HeApA4LEirOrNO12JQ/bYZmIWgeNhw5btOTlCBs2+YKEVEgfTPZos5toG6H0CA3fNCyl0FD3PWJhq+GLVLN70IINsL2WdRiwgOfrAKXoVxBsndwVnIVTCNCqrdDzGXYJxZoSQgXX2gNfCRCIMq09C37HsHBY1L0r3MbQ/jFbG284TyoeCW5rh4jgk5L5yix4GVXcJoyBSDQhNAvqmTBJ3LdwnFe5WMEOQFtIR3MnV3RT0z4RGSoyVMzEaCKJINlqDjqp9pUGZJOWzYbl92cBNJbhKYw0WiIu6enXEMgOhzl5bhvmscIn0tZagx2cAK8yif5ZoMgAC2bWfekYKriwFE0K9rheWACGA/M2ZhEzwi74vAnHs65ybyWdQMZ0L22vNWZkF3nZQpojkl0cZbxfXYRXfd2oNBKczkt6DDpo+kY6pp+6WtiRhtqV0weYWxBMzC6pvXGKlxcQtdsmIY+DPK3q/m73GYZs7PdA6H1GQjZRUFQIFGRYh1gx3V9Q50mZs5J6/TVkzYe/5zJbMRoQEA744ZY6wcX+TZMbOK3whCGqbJTE9rJM/MRpnZqlIw4hK+rknathMg0F5ZaIkFjuwbfuWVRgzz2Kh1lGkZ/EA7BzqOdee0di2gidkjvNGjoadg0JsPaUoWMC/OKr08JoNXpW8M/u4ypLOOF49WpuUyKMEmIZKDVSqoy1jw3BDG8YtwW7f2b1nEHVaosSy1Zdk3UsylTUmemV2IPdaNmKSLRVWzbTDMsEkAs4/LVhGDlIsQXQbl441vwKZSBtXhZLIgiYCon1DXwy4EjFRvviaYt8x4IrtapeDaDTleLNfZw0JvExdzkwLPd7zpN3iDM+pQ6wQBX+nu6QWgSLFTDHWZlCmL4HEIowEgo4RBGoKXgdVA3a4pL8aWxYwLWI+aNEVvKBqEj/U6EoIRHTcSMvjWaUjLMUpuReP9M+zaItUicgCDP2jXNESyj6f106G58qvDJzGgIdRQ4i5IrKWcOiDMvt2+sZFuFkjXR0SRfl3uI0g9W+cNu9ogBZTWZDPuhuau0vSqzDcvHsHzESSEMOIa7HTrPozIXgqra9NKTjPYKnBlGQT/w9ZuY7Mm7cAvEPc0zBJjU06LKR/ewqIyZywS4aKHfUhSGyrhqqPhkuZD6zOzodBirHqoC9klM9P3qN/zzakP97wOmqK1ageobYggUBg5ABnzC/ObiPzkouYY7KP3tPQF9qjRfHLAjN55cCV4MOqBOTUV0SPMIq1AGLRA/L13McRZvUiyL7YzvlBIOu+Gzx21z496T79EU8W8n5aJdDEvx+FTLymVudSY1DeqYvbIvmhbtEhVkYAMf4E3Bh4HpTdMS3Xr9BXYwnn9xZdgR579Ot2I4Dn7w1Gh8xBmwdIKVcrQwj+rpeiUv6/HcM5bdOi70GMjkUJqOENEWf7nzBIv+VLw1mFDX4rSlmTFNjXZNWUiOtYnFZmdng2CCIkDrHXHEIQo73Ff+xL2FAgdRdJwACJNlRSRcqD9PTKDwXMGpAhzQdNKgsTtidksgq98q1+uYoJ+Rntr3zSsH73DmC/TZyMyuB2FU+SqTPa7OdjbMSgqA6VcDmbRihoPXHhWwxBIJjudWfh2bbdivJuXEJjKxiUys9nCC+UzGFt5jVjZM3PlkC2kzlwnPpQiP+kQFJOzXImM7IfzVna28ODjKAL0Z1CGoKB5oxlMJa6mE6ayrHwjRxHa/yXigf91DqXL7JBJ3WriWK7H0SLQBXl6DzqYpPCYgbJ1cVzl3sts59e8V3ORTJs0aK078fs1J3H+d9FYu65Csh0nTSvj3y2Wp1yV1HDj927ud84Frkg+6ovKbru7w26bRTeRuRpkYVM3DE2iEWqwmuNKbOvKFHxALtqkWKJZyyEFqA6UypRpH4LZUDMEHnmTrJgas0rMzAqxBYmVSWgDOQaZLSadD/9XCLEhHDLWCdsxtRoxI8GGZA1XqijgQ0rt4Hl18qMrc7kh/IaTh/5JE23z3pc7s2ympVhKYWtFrtw7WgLKlfyH25xdxoWLyMwURETSt7sM/9omBi7e78AU92Fulj/p9fXLT/okf2yOxxUz0VKgVSNJjdtmS6FPBOSgcDRvnuHKEzRCwDtL28Zg82tFREzjkcnbwHms2aslR4Ejn8XYLOQ5t+93/T9Zuja4aBzpKWwkcN2PR/j3hh+RoO9qZHNpqWDKxtQ8MRm5Q+I0zNfRNQqbYGYI8kY3bzqnUuXEhMa1MJ2dAd256mKFvWHOs+JWod+88Fk3zagHkEoU9TNq3S46fy+5LWbmp9FWaW1+l9CGnZcdUdzqNGZEQSiaHSm2AaBxkXOUrm5DbjzR9jZqME764BjLcO4+vPyHapUxiuxfXhib5UF7cstKENupiJ8dKh61Bffz0D5M/su5djdX2PI3Orzbn00GFoKYsbdfx7PVb+0EZsDpq3XmO9fRZ44baSA29V/NBw3EZS9cyaI45fluMygawD9xM6yuKfEgj85rcEaV38aGU2mklaWHe8zandcY/FBWL2a454NOBWnQUEHTU+3pyuyvjMd9oa33PxNGBTeydvcqVWVoTClvWNw+0RxorPal0Jm/pSf7ebkyb3WAvzNDDKDsRQDiW0v6QXdOqRmX0zeYpu5/SEWQwb6nxHuiAktX6Zga1XbWg7g9cd8bVOOiwRoBWsdNOBdn1tJKd3KzZiiMVe2szyhcsczC7n5W2WV2ME860VD7Wvmz6lzk4tiE6EHKM+gkB1kVoqFtVRJRlSCT3MIeZ5mUxKESelIpLyI7fSW1jp6eFXDT4FcwxLLFKqN2jp7XNb77T+kAj0XHvZr5UvKa5OaBCL0EBYAjzJBrftPtkJxGXaS2NdCT4xN4HT48qW+rI7mZGJ7ytbnKjoubefMoF9FTMnHD3OWVZunYyWkKvrozBMUO/LqM+K0FnpVvpFLcwog0Nv+daVUKU9UUA7mTuXLiOXWpLHp1dwID4UCHcBW+qMsjXWFXEF5UtP65rZ1S89JfqekPrZdvxrcrXLLIACQZqvWmURxdrGlRbZKv8o4jr2IkxRAjd0CD+QDv0WaRqmapVKg5SN7zzJNiNWmv9DN3tvZm44Nl5oW7TqHG4qw0S+eyqHiSo9L26jwtu97iCYakhe9SRWTOqT1nI4Y0rWKnRr+oNSXllPCiNLiZia0E/L1g3w/TBiYBRUjtwuLpgGbTRp2Sfneyiq+JoHNoLNdfOgBNeDat67+s1WnAgZ7CAxmnFcJmGi4q+fTrMSlyfGUUsGF1kWkM/YYsLPgpdnJsE9kiVysI8sOiIK93aSl1bjszaLeiKa0QJjb4L4ia1cQyd9eHeHFnmZrSRBEIvngT/c/FednpaveLXsZDSaCK+hTGXWIXXxQPLkVN1ZCrTYLhJT09vIdBdpzaNb/LgL3Lk3g16mqM587ppMI5MpoWZSj4WVTMIxS0dniELEs3yhhLsuwWTiPPtkn6j+beRMuvBRwxtWWrC8163OpmtVGLCKoa+ISchbzoIPlymTdSGDWLJEBYo1taRlrd1Aqttd25T9L26JFT0N+Y3RWBqhME1uLq5itvV3SbNNXScX913z6dBq8hllWpzFgv8A06KGt1HWBh3CkozGyuqh9xOalRSSW7lkakuM6zt71D8WHPuCc6gY/Ocmn14dHKSYNxaOxbRjMZ3kIle6vctbitccUf8akiGCDHmsZip3v+oBQ1Lo0dASF+yVJAl+0NJA2dOq0DD35l+awlAU4JWyh4dZYQJaH9DRYo1UdI8IAk8k/LgXaW9IqO15IB5+E1Xq022bb8nGtTXyG8dVRIE1naYOzPUv6GRnFjqR+Igt9nYmD3dUrvtBusK425zUjA9KY24NkWyTgflgoM22MyzggUR7UtmF1hsUuA9V5a6pJtEDUMZDYympZV7nDVascCnTTubB4joBJdHSB9oKjAChBqMi1ChwQyUuNAVWRh9nzZCt70EwRaxhpqI2mpKSSLHCZ20N6FN9rKke7lOZOJpp/PHe2RAUpVeAl78DXpJlI3fTy99V62mIlszqJeFau1XACC6mc2Px1pkqRwnUZSqSfAEBok1ykpXD4vXFWyC4Wz1NT0pEY3BStiKyA6qMd6awmM7JTYVSmwqlNjUUGKT6ELcq3UqNBbrQCXOyIH9EzYKK20LoD3g2MLkJbWTu9SOyI1Kaicj3AwLzTAvaa68pLmQvaS5JLMM2hz+cSJWw7+PK6bsbGnqOQoSbDD3JUc6uugH4TY14X+sRtl+v2smcrTRTY6b5bOLg7b+q8rYbADhkYjVlLfdpC2b253F9+y7aXVPf1SaHFqpHO9kRxHecHJHZid2uFxXmTepcffVkEzuWzWjEMDVqxy9QBCq5XH27XL+CO8g8cNXvOcA1vl8rn2M6LfvtJIxFVnd06cl0ldz/XS3zb+O1/TAzkM/E9t5ZWznP9eouM55GtgVEVRFtsOyM55JLaS0s0gFrGjTo4cVd0sw1x1hotHV7mpztbyajOsCQBrBS2zTY1JAJ76G6/z5uml1h1tZow7pCATf48gZMKVlfaYbvisK1JZOFo0YMdl1IPd5fHXsBZZPgPLIoLicmRAeOm7rbEyM1kXk+uNnFWBb7xDqKl0nAWaVpUvhOyLyYUeIP1DeM2FNoDQ4YdajtAqHOSaX/qQP3YluJG+DqbEYzaB+axsS3Ob2hZBc1c+99ir031ojtleuHR6wKvvfuXJO//6PXrwjayevbSsIoRRUXUxN9ovSK8oBT2RNYdv67y2r3IHWl9WxWdFLYzQsGrfBietQHUHaRO+j3EiB6ya+mqYZq6oqlyuuN4vr9NKvCumriwsWMwkc/kYrXJf9MwArmmC6fhbAVABABziGbS5WB/H8ttI8sWh5ZTDEleTlcBNRi/XhN+CvldtzCTpX7W/pVUh0bX75xX765RevDrm196j6SsSVsMl8B3G8VjF955ASekztIuwIgBYzoDXDRxhFSicyDHTlXLsHKm2S378nLMyzXNLo9vYHRsnXOR69ZH63OZkQQ7aVv1BRxu/qbncyX8XZySbfEiVxImLak7slJ6bzIr05yZK5PCxWdCRmxNvJ091afrGk8gRjAP1E9fIDmBadRutJGdNZvJxSQxK4eHuXLIrdyU3+yPXS7xoaknig6vPNZkWbCQfuw46Q4J3nqCe2KR9U9TJKXrlBgfdlt/FVtI4vkerNZUKCJ0EjutIMA3xP2PJyxBAal4OGKhAiWYAmPMbOm/7IdZnw6Opu2ShSKzCZ2BIcSUJCzv+Ghh0P26jK3S2PlLJlLlqOoaHTvPec4wObBKoakX+feyJmYCLmXWoUNdQ9LjKH5+qBfn0V7P2r0f6Jfg77cbD3OK62d3UFcme8v7oa4fk8mSw3O7zeja6y+Gzy6dnr8dNHh+C5d7V9Hg73CKi9n8SEQFhta3829Ied/lUWXGVdRNHu0e8+QN3552MYKQ45gYkpJsv/8ubbbyL3eAQP00MqyG/8Sj7rWd1J88Fn2ogbEqMrN/SCfCut9kzE0Nzu5oe06leADcxLaQHbvNH2Vn02l4j5yg9sEREnnclZJ1eMlYKhPQs8XRieJVgThbeR7325fEfsQnaCnocnkElBMMBDIBasMno9Fo5Tk5QUh9UPTMw8YLAcHybuvfr26+9Q12ZI/YVlqE1gN3XiLmSzWrzhuiArwK4+f1ggBC9GhTKfUhfe5T9qrUzv64Lw0XY12fXAJH77NSQLvXj7uEwjj5cbxzZQFX2GCKeMKJMaFR5X7lJzfU2rkR5RneDebnjiHMO1xlRSszKTKhWz2cdU/ZqqTwm0/9B7/uxcfQYgHw1Px8Ev0ejn0/Hzc/WSJQu958MgHJ1c7cZw18jQ/jy42gyfnU8X6pURPiSER/fxeo1/Z9vdahNP832ve8YIaQv7iwmdt3tCmfv7IqOhBCE1+rku/ufP3+6/+PzTV7DWfY20q/Or83P1Z/48urqnisbdENsCH3jnXZ0P/zB+/v/QXpHnkHpFH0Kf9kuwp/+dqy9S2DJ+yX//Quvw/NwzJpUI983Q8GsazVcpqy0zq6rX5a+EU35tUXuJaV1/TTknpIGURb8dHtPoz9p6jJIq7JQQHNZ+5qvU4WGrikZN8LXqrdxjYyTKFxfVRmrkbY3GTgNDf5pbDK8rui7WySQ2Q2Y9S3JEdCVWRzGbpsEg1SjAp9gHtW98z5u6BlJfp3VlZvaiAiLmy7ScjynuXfG9sNgHvqNYqZNpgphdS40qUgJd5DqaaSXoYwrC1/v9ZL/PR9fj4WTY8Yvo2gj8QgTOIBoK5MzWDu06UFP8gdlOoAp7be1mhmEWfEayMcLp6ZTBqRz3N3XDMFy+XccPb/Ldjvq27U3m8U4b6cC5rWuLWGpz0MTS4vs5/RIulTgiT4Q+MesJxxEvEYZrMAT6wA1J/m271S8cGol8YMu+2+zgNGVMQ9LuZ4OiJ1Pj3Ajm2jngoljosHOs8/H3fLumQeVf5HFGpIWno+icvZVQ7KJ5wl4zJXw4Yo1zsGD8tc4Fnwq7GtNgkNAOvDlQTvSFSqXBhLslJoy2slSutwodZp7GRhQQ0Tfb0ZR9F+HDmC3idI2QDGXgltiyMzMLPRn6k44M/PS07AgchSEIpxEW2+n9rg7kTiArAvcbd2r17pJQ0aOLcTkVboeD69G0Lo+pDoiQSXRjFsWEPg2YENjoFXhd5PNsK8E901FLOgFRwMGRM9AN6OJrtmZgwaabACrJDoHDCU+U0zxHNGZYmWBhbLBiSsM0inrKRGKXRtejghdjAuMw2j38qDrTMprpNcMERKIl+TyjqdJe38oqZlhPWwu/EeRM2ZpkiGz5OMQf6Kj32RUc8qgbu6KoNXDAa8o5A/GWEI+83Wyzut964yCJprgW4YHhyJB3fVDMbZSE7Q6EaeU8VvwTTofz0PtmdSJLiMPwZELkBYCShrJbYRYOh0O1nu1dmhJ/4SlMfZi4wcpjJj3CvkKA369XGV/QhARs+S6GhaBykU34dLeZh3TU86WwRyetp4rtV3TmzcNXWob7mGIulETDgrfJ9WaFxjkgLlAK6Bg8aIzxlquChnghR+f5w9n9/f0ZFBvPqDmWC+bZACzUBh6svn/7+uy/PCUxbuG68rkX/oW6hBiwQlwRhVksPYmBKCl49NQD3istLebqxNJj6nrLnpydDEjROa7jd7EOV3YwfafWUSdKn0tz3NK51MSlzyELc7eLFPFMItFSnu67ScItjumMSQP1Ku3qjYtxc8e8UEhLISxPeKSYXnlFLWDwLbmv0zHesCSD6SgtjxFZZbNCD/AyerBwcHfk/icZ0knFh1X1fIKEJfwGLkXcVKi8cI3fWRM5omS+wGFDiW838ZKGvdkh8UudWGu2aQQnyMY13IE6tnbs4kgc2UXFTXmM3q21uSyiLush7/c3alm+UtVzJyrqvHd9e5dvHuG2a86sBoIhq1XFUlmt6fVlPJ/D5SYMupZpfrLIF6sNvDDcAunR5rzbvqRqOaDkBih+iz87osnuIi+NqQh06tS76AnS/cc3vJ37qnE6tvhCItzzArIWOcqugyccIVZS9TI10c/phEiasTGJKngxPiSE+uLal4PDA+G6lTUPkwP69Ol8Xu1WWxQO7tRwoi+YtxgJTeZ21xiIe7Vc6YL1rgWtuQgio0iicsYKXm1wFom0EnKLTZHlX2vColVFi5UdDekRxaZsuTjtc8sW+S8ud4F1KBAHt5DEjPBX4SZNqIqTd8aGNR6902s+rkW5JY5n03qBtN/fmZw4/XqcEfqLsFY34zygPytrEv4uKI0b1xA1KmpWDoDondjdvhOOjl5hZItzbDOPfMi2+XG//zWFT8tS9vYpawzZ19epYjTf9c7PWY2br3CS3iLfzVYZ6De557mxKZKFclr6xYgKyiRmE4LjnIjnjbUGGG1MYpC3r1YLQvTM1Rh2iftf45hUJXsEjVeEvmI6gIdBTDJBu7y9ECKHHSl5s91uHbIwFh6FvP/qe6H30UcfEvUJFxmPjWyPjXzcOgZ4enrTc07CUgxumQuTT89IxNg5NhOESYZ+FQAEVjDqa+BLdUPk4rtAyU43PhMHM6hA8gGsZuL/FTHhcdB3u0zgc1h3I/LzGBHCJqdcS/mpxgrABWy81WR41PlcH/iSFcYbPPmVXLy59LD0927k31vrxKF3SlM19IKuHqW+x5c3Xjli2sRClGcGgPpZWbyUJ31GEPrs4pfI676DpDnMu63NeDYHWzNMDMXD8XtdEogtKt/16vjJ976cnJk8Z28KwtCeapRkCTTRT++r5BvaiPBXls68Mjf1yi/hpZxHvDn0EttKYps5aUF7SxWuSVVqCVRbgU+ZrvLcrcrsy01PU1yj6pfx8OiXribcq8lDTxGR+pe06w1ObqN+r89uc4OwrIbN8ktGliZCTpOgpb8wj9GfmY8FN9STiKdviMAVf/L2VS6D5uqdugkimUTZOnbvaBQbDOgA5kfPduRJY9HwQtPiF8ra2V8cgnfUPnFiuhNF9DVoF71BCaH3yhM8usC2XNY2IEduHVHPxgBNJpMx6zsxAbvsM8fS7qTM9NrTmb0Aetr6JRDh1A4u3Gj+iOjfqAfDctwLecAnWcBcysn94ME/u1DwyMfnF7+B5bBkmec4I32ocqzXaqO26k7dq4coGUD9BcTTLnoBfzIVk7YpuD+tvTNh/zhE57iTFF/2hx8RsXNNT9GLPo3/w37/ks6oD/sfQTTP2qF30bfwnvGOfXrfRd/h5Y5erwN1PfRrO/yeDrwWwcJXtHntniYUeN+GDKJ7+tBeHnvXFtMbmbITVpaO0tAgj8QBIbhy+AAfXCYaQajHI6m7helI6D9Ed0ww5EQb3gl+3NIDAx9NS2cLydk2elA4uTsPuDCkOjS7SFPF1tR9nEGG8KBZM4+sK4Yrmgcc9TRbq4onk7kaEZiod+MgXLm+TOYA0Qe1HZeVgkjy4brJLGcFuK+HAt6aBQ357XPpI6CdWt6EqG7NntGcRigNAalr++Sl3nJ2r5ydmcONRdVtR9uKTUusT2EmVvk+4KiS6dSojCqPWaiAy7xh/u+YSYWU0XOQlMGtKwo7UMJSiKjWooZYv8irmFjXRLB8oMLxBUzfVWqZHmFrfGbhYuHTE2XQb5iLBCBVBpVlNdPwkhRQ5dyp2sS7S1hZXFVisqYaVv2isu1yTnfGBNduy90yQBFE2EGaiTeihgsrjCA8piUx4GmDmpuO+028RiBst9H/rn6JrquqW2KUSbSJolWi849Ebw8QKb4flGGyByZbVV/MDfdtK1cJGxLWTYb58l2zgbETEd4YKpQppc1CXEkvAwaZQH7aS3KFpcEEfLlc5r9hF3NUnaM2l1xVYzbr3p8Tq6LBIa+0kAZm8ub+Ni1XJghNzDxfnFPgS6uOY6WLg+PrnzbXP6laiQahNHW3rDZWnRlZXXh8aZgOWeV30dWQmI+I+W3UmmwId607ghUDnND6aA8yRh92vdE+Z7bG/16rWqQTN+KTqA/5rhtHiJLo6Cl99VdicPqB4ynSBO+wEWDKMFUtUSsO9S5yVLV53tLHTutgsKYs+flbGp3/54v++VT9HVfwo6vxs3P1hs2Kh1dLSn6r7w1FKcMoRRcLXDrSiZjv+LaR1aO/f6869U3+OM2XwXlRUkf/qAv0G77yNeateAnARe5+/3ejFBsMCUIROAG1db2RR3R1XfqVDxMQ0V1v7KlcVBwCKz6nykyBDgeBoDJoO2PUXPMmkwS2mZyrQ9g3qVFzpFGLjIZOIbjAqn2qbZ4kGCa+McdNoHlER9PImKqOIxH1fv/3L3HUENgsMfiuRxxby5ckYLmHvUVKtBa1K2YEr1u5LHNZaMdhIgzdYi3U076vvoMM2bpJMssUu1qvsgnZHEOVdtF24h3njTSjqWInjVjc0o/W9apY+sSblpKVvxHB0fXqJxOh94J1nlswhhETiHN3k1HGhX1UTTqGclqPCm0gKKgm1+oCpV4HNAlLo1qtpUdYRnuyaj15GPYqKHTJim0dg8GKre+FpTXx6en3ehtUrNgRGfut3R9WUU7bg+47P5hPAUf7dMbm+rZxLfarvuNTEWuWEJIGQzHMT1sM858wBjpNGBLEsiW2K/qGVhSKDkwHhi1Z0/asTE5qZF2B4YfZJrI3u3GvojsybCxvh2dGX9KcnhL6onr3oD/3YJv3QGQidNlrY2lgOmfKdyJb+GcKnzLP6O8h/GfKmPUHKPr9yBoRP6X1a2nqJU1qrXu0H6EiBm0d0bXz3P1kdAljbJof0+DHdBSP9QbnUB0sD1pttlGn8xNiLt7TMfdykxO23xGIb2Hc8FOKvtxwXzib+ik1WMDyr36NwuvEroAQwkq0Ug0q8ATO2Ynp4lzR0iLwaOH6vPtDynafvdUaZ5BIPWOWi8XC0+ON9iQDAAwEttv71SaDNSZVIldE5Q1mJRFspZNAr4Py0vz0dNKry7vb0vyyCNqsjDsdeT+eafFKnp2BivA4zlhbeuT9+PVXX+x2a/1Be07M5f68tFVhGdikKbihUwrxRKEnNhGxRFwReMk9sTgkY/9a5dTr4HF8k8MR4sAM7ffgoieOBIGvk7VAkWBpStjXChwIBjkjs9einMmqeqs11f5Rp1oR+m/EQqwowLfxM8ojDHDTCcWk517mUfeu2R9EVE0XwUxh60GalsvcEGBGnneYETKLzfZlXYRywYYXL158GLGTfH8Wveh/FISzSBoavuj3w4/6Hx2u4f9NrrwmvdYrGj4kNGwO61M4dP0UBWHrtPHURkmYwOFT7SaDSABiUNw9fKiGskm1OZcTxSbuVQHLNeayBZ+1FnyfHt0Xb99+5wVuZZUbQHudLMyjvjcuL33VSeVe+Eh6ni5a0x/Oyi+V62PdGtTQUOce2QJJPK9d9/K9ra6ijalaaJH/5zjP4JzrUB4e9prVt8xxhdExRFQs8nY2uxbJe+eihib4m5jsMNsd62YjUXKqo9rW9li7xKmz9Or3yMJcMDQeHtiN037/WNdhbEfKLC5pcYVlBDHEEosWJQdsl9SXolXAVllGw6D2jb1obXANCTSe0B7A0RUlbZuhqocNyruTOPtpvz9HWaJRjORYO5ZKKniLe/O+ZkT1+bjZmBvUG0YOUoB6k/vweGYVQrD306r8gFbBYfaPbWndu8bOxjr+koIXoK6f+1FwNfSH0en+WbC/Gl4NzweVTQfR2jr0Un1LLkoPa3Np3ozt9EsqjuVY0MeuyFdd7xe50HGJStwCY41bNwDaYL2ItVdxzVaPUpn0OJP1S5mZlSIoCIYe/eXAqFXEn+jLm45fuZwRoyFrrfOb2i4gfMsG+S6M6B38luQ3bKJkGOB+qlcssBTqVSazxolVP8IHQCXBh3CkkqJmwwSalPhjCdY4w6UbNK7D2mwltas+M2vmHk5nF+YOG8xRSdP7VXRXxlGTY5mWOsmwEbmPtyfL1e4EYMQC/ClNwUFVpyQSOS77w89xP59Xap6Wqv4HlbV4mZcCzAzz8KqTm9Ym6xft0Rk6blPxqWLnHt75J/6UZXTTaFKGl7F4yg1LyBo67J6vLqtmwrVFSz2uaKm3mmBpNWDgdlaSeRRlYNwt4BKdo/bBbfTIRjLLhqOkhlYR9jsYhxyWILkjXPF6E0/5C20/ZnTFa6pw9yD9c2N352Nki3wzzf0RvPQ5UiottUkydj3KWu4D+9Q2B21efJPMOo3K2p2PDhz9VYlVAyVWx5yu3GaXERtFGn2FWCtezpRxDM7eCE1yn4PT1sUeesK1kD5MWpx4S4hZ77tv37wFCFuTHcO9VCTeE0faLbptWrMvqAXEoMM2L0GbqkVuPxvS+fpJVry79KwM1wE18M1s2A0bQQgsrUIJQt1XWempCEZS+HN2VENBysIHAIJqaI2amphOu0TN2mX8U0IxvnV86kqFrasAID0J+aDnSmt6wdGFIRcadIMlILOs0l6x/YHGu7qHtC0O/1Tx2TMsHYsU+T2IcDlppUTYuSBKUmSi7DpChJ/v8xrg6KCZUPGrLYulOD4PeGS1hLaWhzO/SPnmkOkuEZya3JG3yecxSF4otkZz3QtfvHrrqvnySxVlwjyfgBa6jugcSrar+d2O5bM3cF1aPBACxQs7cDbOwMS9hRpNVDEOLs8ucLWaUXOmH8z60mkF5imCOG+yC0J/Wo2NjJgZlXDJBZIaW4WleImJRZDybtLe9Xrsfhn3gau1vJ3N8Lc7LbOgbc6DB/1OufCDc8aDDeCUvQzSkccvpqllEM55ipbiOsQRv63qKxq8z7lkSUyztVhb3E++D5NaexZijLvpxHhAE7eAT/BZ2lcYQdg/lA4udci36o0Ryx5Kd4yTOvwj3i5RJURyb1nOPPSN/BjM4WeIC05T8nJeUN6/E3qi4/yvjPyOfId2VRplHBGGO8pQ0GX/ftP8JxkYOyA2oQSDM1BGXAG9MQTw0ARsTMkf6yURbdApileUPQRhdlAGDmuGvJVrNt6F9cnMrLdQbfBhN4DslszdmcNEPJS1zULoa/GmLOp3+gJHJW4qn3ql9yKsoSBvDpxsQ3TznPI0mj3LOSUIMc2ZRCFmX9mpTFpLPkxQmVEvjuwZrvvMDlAC0L3FNW/HLIbeN1J9PSuq9jTFr9zx/luy5cZs7fdpZu4lWQbv+nmSaWKlOYMQW3AnX1S6dVpJNSo/VJ2KW7CCmr2FOE9ZSJV0DcONqM8iQj7/yfBvwaDhB7w0P20GUs6MEGsS8Uk0qCOPfDgZslrpZAhH8eHk6JmG+FSwnGKq0afsdrP5KZ3zk8DZQgSpuUqHeeikvwVHFXAVCNPEDtjruI3xa92JPBQD+DB5f+R6juW9po01/06vlKpyydZVFW2CvyTi6e+LxHpoY5FxedRQlV2ErQxLZ0uBs65yLRkaV0+KN0GoPUA1XbdLIRvrm51he93YSGhCxEcPvdXdjpOd8swu8pJn7pKXy1qfQlDdjoW/JVc55EbK9kOwGSEWXcxOTGTIUO/okiKtQ1PlfrFJ0eDW7TjweIJQaXBjJnuSkuxhJrJ+gNhoWUkPl8/MrBEQobzKK28mg2xIk6H6VjYemNVke1/Z3BjYFPr1EqdN/IYgDkiiJsMsNLJcC5zGcB02/S1so+ORQB/w8TJ7k88nwmoQDHwG3s0zJZ1QNjlRw3Taym8vXmTm2ffk1hCBQFSzyYU+ynMQpdd/Q041wfOzcqGWq5er5YSYh13URuf2ngHZMfX3LJpkEo9E12W/6NecPi8OynAUkZze9jNqWCBHMPiP/xcWQEVU';
    $base64_files['jquery.mousewheel-min.js'] = 'eJyNk11r2zAUhu/7K7JcFGkoarLd1YhR6KCFdmHrLtqFME6sI1uZIhtLzgeJ//tke6mdEkYvbKTznvfo0ZFEVGljrzNLYro/jgcJAbpfQzFYCDgcNtrKbMNxjdYzLWZz7oyOkcdgDIEiKVdBcGxCGYoxS8OnxDgCEbcWrvSWLGgE3O9yFMNVVjrcpIhmGGlFFrwZ36LxQFH0p1eTT+M2RaIHbYI8Oo6vPkdKYKvCVrsPQpRWotIW5eVlGxMilLub/rj/Nf328+bh983z/VPYZYBLxWjyEas3y7/0i1B1wvLSwXTB5xNDU/REbj28tC7VyhNgyFKmaFSgL4vQ8n8Nktrl4OP0cDhGUrDSIOWQ52ZHfKod07SqT0SK2fB2+vhYN/EpLjJjhqzf0nnN2Ov8XZb9cVRlBandSyG5QZv4NFpG9G3aTM5Go+V8Li6OSlO40aJjyOUYazC8W1PsXdhPfv16leg+MNTQHKT8WrsetPNosXgFgQ4EIno2l9Q0MGcJU2Ac0gjDf9CkZra3elIxj1CEK2rPIRS4ytb4booz6e8FsaUxVRUapSzHbbBKsu/kji28rfYCDOBLU2URHhjpHyIDet0ovtBJEgj6Iq1Yaf9ft/GW9lzdqqIVJcvvJRY7Gl38BZFKbus=';
    $base64_files['jquery.terminal.min.css'] = 'eJzNF9uK4zb0vV+hsizMbuPYzlygchkK7UsLfWiZx0CQbXmsjSwZWZ6byb/3SL4piZLtDMNQ4zg695t0JIVff/wBfUX9s9nACw/yPZvh8RIdrkldiMIQAXto1Q6aNwNgkAYIJ9xmJG1GR6wKqw7GVpv7GjJ8Bxgd4KwDFofWA2k9aAuRR9lmM2sZlc24WWA9qg1NXEbd2vJZ79fzGL6gYd3H4OKGXzhDILTeWMm5EuZZh/up7XWcT/8DVQ2TAkXLGN6VUVhqXeMw1FRVTBC+/Ja1KcuWNQeiod+VrEEF4xTBf02URrJA3/5uqXpGd6PQwPubrJ8Vuy81usi+oFUUxwF8btCfZNum8BVbRh9Z9oJ+GaxOxm6N+D+UU9LQHLUipwrpkqK//rhDnGVUNHSw8TvRFINX7QIZza1A1kT0M15d4ShGP0XwAGO4HENC0yiQra5bjZaFVBXRi2VW5QdArWRV7wMoZw+LM9qAbH5dzpqak2fMBGeCBimX2XY3y5Wxo6RcucClC1y5wLUL3DhArah1squIumcCR+9rqbMhlNQUE8fLFa12fUoyzupUEpV3tWyYhsmESdpI3mqajOw39VPCaaFxYEaPLNclvoZRSrLtvZJQXawVEQ3MJip0kkoF5cZRkkku1R4JMmwcAVpN8pyJexgp2rAXioUUNHkJGEyVJ8BKmNoFl4+4ZHlOhZONJVVKqq5Xrmg+k7pRaRyBd1M8inKi2QOddZJWSxt/N7sx5eaSVseyrn0mQI+m+TCpDsCsVY1UyxTi3HZzhoLe30+EkCEvn2Be7zwywSNNt0wHRDCYxsaJaYJaBhQ3iImCCaYpajStm4t40WhYyV+SoJIvbxRs3iT3Bpndr2OEW/pcKFLRBu0Ld9HnRRxFnz3pg5wlcyZ3116mNE3dHINBiO4DjUENPszaRxn6fr/c67FT87RdM9nrPlewOIfxtBAnOVifRGPTbWaTfWMspNBBQSrGn3ElhYSeklEnkMQfqRWzHSZemT524MnuMJxb0/oB4WfyRw+uiBPbhYnNkn2BjToMw+IIS/qMTqnd50I6P9w+3nXDGBeplm1WBhnhHNzCZkEzwpOR2jZUBQ3s85lD25a64n6SWRt+QuPFe3DuvDhI2XEOTTH9kdj9xhdGTziOoccfBjBgm2PkIWLoDLnM2gr2QtQqHkDZCvZ08aV7n5j+D9Vx1oomKaddfxwwS5KTuqF4HLiM+cCF4/oJwemD5cj2JndKY2z9683BjrM3xc8RL88Rr84Rr88Rb84Qoa5nqDo/QzzVXl8rYrrFG8wgciw0dCEv4dZvxz1u+zj+w8lor/Snqn6q4KdqfarMpyp8srin6vq6kr62Mq+svq+G3vKdrdybiubzqj+5f8f1nsk2OfeIz3hO5isKNE2aJ1rWcHC3N5NouJaYI890i4hje8KGSyhcF8wXp1KXjpOkG30vbqKicCm4NLcFx4V/ATVu+ho=';
    $base64_files['jquery.terminal.min.js'] = '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';
    $base64_files['jstree.min.js'] = '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';
    $base64_files['jstree.style.min.css'] = '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';
    $base64_files['paypal.png'] = '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';
    $base64_files['throbber.gif'] = 'eJx902tMU2cYB/CensPp23LaHkqFA6i0WykHRVIQsApzLTdLsVwEZkHQFgQKVik3BUTTUq1QqyDTCRtBpJHVeRmgLrhFU0AFvKKBqNO4ijodbgvMOPWDspLMb90+vN+e5P/L/3mfVYkJ4uVqnIJTXlEos7Oz09PTZrPZ4XAkJCTU1tZSKBSlUikQCE6ePHnx4kUul9ve3o4gSGRkpEaj4c+6J8dnpMdKU+PDQkQw5Jzmf/CNLS9QVxZs5G0rrtTw1CXqam2pemNI8ZbCUgr/HUJnOKeCnY8yF0pBynAZ/iKqqkQ0Bkyhqd+EE7KxhgEb06oCi9sToVDjjbK67uuSu6n9OkkO77F4Mpxa35TWhZxOoxqyf1x/yjy4xEYHXkjRNXEQjyiEtM2dPcuthtgDKuxpiidEc6NgGbAuAHb7dA+HkR/AYGR6uFLonApNzbmby0dAQyiW1JdofKy/gS96AABqDLZItQMGmvvwZdWIBqUdwjrf1gkNksGKOzJOUwy0tSsOtgw9GTi18wJsyuQ8U7Dh9Y4gD5zjuCSL/PwKjQ0zqhXaaDdstbB+k7bETO4LTAp0iSh3IkhPJ+IRQDhIcl9ibIV+Uk0+oIkSOEIUwnSpBwX1EocIQ9Anf/aKMZTM5iTQyYW+jYO8VtKEvSiOS+beHA89v0K2McCq4M3YR+AwurWz5++71b5hq1L8a0ohTJTCrSnfEpVXFhia55ox1wWZ72TIAW1mwVUhc7832pSW7G8PltsIdkTq4FJk1+BuFRBhe+WBr6OCcPTwU+u0Fu3fIBmQCbZ2D+fd5uUSDsXiqU4EZTCLJLcwpPXGt0GPP0z5b/UNXWcoNdYyonbV1SpCtwiFzUKXiMK5hQQ5EcnANOPjRJi8UffzOyRSk6U3Fa4KZrzIWdwGxhKZ4XD8iL25jtvBvFqGYcS99tHep7mMnxWbEnsXLpNP3TdPyA7y/3gttg2OFDx8XmG6OFXzxRbP+hqTcJfR02V0tTPatubcTZ81IGZm3pDQbXcg4K0pn5jGXvaADB9ylBJ2uD5/D28jl2oRisTE/O9v2c/QccL/N3sRVcSi1at+mtAS4NKkVL0zEqZZH3YndaMwf+raTi9Ugbr7+OxYgNIo0Zk5pQBl5GZtpsAolpuZ5OcSUzF3HoQTkwGwV6FDQoPyk7C40UwjboMPKL0iF3u/qtx1ZPeb5cyFe40vPe1aahNS8d19wcImo7aEKgCsVYY7wckorXim4jiLixrHwpZE46ht4MCoygulovmlKSv0cHVIymom6s1clhtIq+O6dpR9dOhS5bhgSLiDePtZwFomXxzNOFQ1WRUQvw0sa+pO13Gzu54KeSDgiFDfJVFER/hdfjJ9puar8ILTxvk87MKXwyocANBt0MFgDCBWpn4zqoXd3SEWusSPjvKNC0BEWgCU4/PfbWj6K6e2D/fLj6VtcLRZSHX3O7qxoOxsS2tMyaHJy9GWe9hSOlJ81kPF0zxqsUrVX/eQuJlpOSMgsIEJfQ+eQWUa81USEYdd7zFjE/VRjfbHMv3hGI4WQHUQjWnwpDdCgCVEsuRF6UW+/+u49s7m+7rKSJUrReuvqrJOvGFa6BWaZvDDsY6DXs97yZUxemnecR3RgQj2DaTf9jqdqO+VczouXBsasCG4abithzufao492qKjYxZY2itrCIdRFlQLubGyfBnAH0EWsRv9uFRqoWtH6UfH9olxh1tXJKZem2dQAytBHpv0PRYx9ksm0bjv978khD/PniyNCvfaG1PehjdykHFB6jOSffph/LQsg8qK5UvEIp+mLEPaUYiwD+Y7QMtqd8TJwLLSsyA2bWU2rwir9HF9Lv92UXLuyvtWlHR+qo5IzK4WbDC7NfAxk8eG9RHjaEJM3wklwdz9KxcXA8TQ0Jc9D1ceB5nvSdbJRMntEwjDtH+mBSevh0gdacoAgjzadDkNTLGD+XEstsINojekw4Y93ph6HZcumnNE/QMdUh5G';
    if (isset($base64_files[$filename])) {
        $headers = @apache_request_headers();
        $fm_mtime = filemtime(__FILE__);
        // Checking if the client is validating his cache and if it is current.
        if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == $fm_mtime)) {
            // Client's cache IS current, so we just respond '304 Not Modified'.
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', $fm_mtime).' GMT', true, 304);
        } else {
            // Image not cached or cache outdated, we respond '200 OK' and output the image.
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', $fm_mtime).' GMT', true, 200);
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($extension == 'jpg') header("Content-Type: image/jpeg");
            elseif ($extension == 'gif') header("Content-Type: image/gif");
            elseif ($extension == 'png') header("Content-Type: image/png");
            elseif ($extension == 'js')  header("Content-Type: text/javascript");
            elseif ($extension == 'css') header("Content-Type: text/css");
            header("Content-Disposition: inline; filename=\"".basename($filename)."\"");
            $data = gzuncompress(base64_decode($base64_files[$filename]));
            if ($filename == 'jstree.style.min.css') {
                $data = str_replace('32px.png', $fm_path_info["basename"].'?action=99&filename=32px.png', $data);
                $data = str_replace('throbber.gif', $fm_path_info["basename"].'?action=99&filename=throbber.gif', $data);
            }
            echo $data;
        }
    } else {
        header('HTTP/1.1 404 Not Found');
    }
    die();
}
// +--------------------------------------------------
// | File Manager Actions
// +--------------------------------------------------
if ($action != '99') {
    header("Pragma: no-cache");
    header("Cache-Control: no-store");
    header("Content-Type: text/html; charset=".$charset);
}
if ($auth_pass == md5('') || $loggedon==$auth_pass){
    switch ($frame){
        case 1: break; // Empty Frame
        case 2: frame2(); break;
        case 3: frame3(); break;
        default:
            switch($action){
                case 1: logout(); break;
                case 2: config_form(); break;
                case 3: download(); break;
                case 4: view_form(); break;
                case 5: server_info_form(); break;
                case 6: break;
                case 7: edit_file_form(); break;
                case 8: chmod_form(); break;
                case 9: shell_form(); break;
                case 10: upload_form(); break;
                case 11: execute_file(); break;
                case 12: portscan_form(); break;
                case 99: get_base64_file(); break;
                default: frameset();
            }
    }
} elseif (strlen($pass)) {
    login();
} else {
    login_form();
}
// +--------------------------------------------------
// | File System
// +--------------------------------------------------
function total_size($arg) {
    $total = 0;
    if (file_exists($arg)) {
        if (is_dir($arg)) {
            $handle = opendir($arg);
            while($aux = readdir($handle)) {
                if ($aux != "." && $aux != "..") $total += total_size($arg."/".$aux);
            }
            @closedir($handle);
        } else $total = filesize($arg);
    }
    return $total;
}
function total_delete($arg) {
    if (file_exists($arg)) {
        @chmod($arg,0755);
        if (is_dir($arg)) {
            $handle = opendir($arg);
            while($aux = readdir($handle)) {
                if ($aux != "." && $aux != "..") total_delete($arg."/".$aux);
            }
            @closedir($handle);
            rmdir($arg);
        } else unlink($arg);
    }
}
function total_copy($orig,$dest) {
    $ok = true;
    if (file_exists($orig)) {
        if (is_dir($orig)) {
            mkdir($dest,0755);
            $handle = opendir($orig);
            while(($aux = readdir($handle))&&($ok)) {
                if ($aux != "." && $aux != "..") $ok = total_copy($orig."/".$aux,$dest."/".$aux);
            }
            @closedir($handle);
        } else $ok = copy((string)$orig,(string)$dest);
    }
    return $ok;
}
function total_move($orig,$dest) {
    // Just why doesn't it has a MOVE alias?!
    return rename((string)$orig,(string)$dest);
}
function download(){
    global $current_dir,$filename;
    $file = $current_dir.$filename;
    if(file_exists($file)){
        $is_denied = false;
        foreach($download_ext_filter as $key=>$ext){
            if (eregi($ext,$filename)){
                $is_denied = true;
                break;
            }
        }
        if (!$is_denied){
            $size = filesize($file);
            header("Content-Type: application/save");
            header("Content-Length: $size");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Content-Transfer-Encoding: binary");
            if ($fh = fopen("$file", "rb")){
                fpassthru($fh);
                fclose($fh);
            } else alert(et('ReadDenied').": ".$file);
        } else alert(et('ReadDenied').": ".$file);
    } else alert(et('FileNotFound').": ".$file);
}
function execute_file(){
    global $current_dir,$filename;
    header("Content-type: text/plain");
    $file = $current_dir.$filename;
    if(file_exists($file)){
        echo "# ".$file."\n";
        exec($file,$mat);
        if (count($mat)) echo trim(implode("\n",$mat));
    } else alert(et('FileNotFound').": ".$file);
}
function save_upload($temp_file,$filename,$dir_dest) {
    global $upload_ext_filter;
    $filename = remove_special_chars($filename);
    $file = $dir_dest.$filename;
    $filesize = filesize($temp_file);
    $is_denied = false;
    foreach($upload_ext_filter as $key=>$ext){
        if (eregi($ext,$filename)){
            $is_denied = true;
            break;
        }
    }
    if (!$is_denied){
        if (!check_limit($filesize)){
            if (file_exists($file)){
                if (unlink($file)){
                    if (copy($temp_file,$file)){
                        @chmod($file,0755);
                        $out = 6;
                    } else $out = 2;
                } else $out = 5;
            } else {
                if (copy($temp_file,$file)){
                    @chmod($file,0755);
                    $out = 1;
                } else $out = 2;
            }
        } else $out = 3;
    } else $out = 4;
    return $out;
}
function zip_extract(){
  global $cmd_arg,$current_dir;
  $zip = zip_open($current_dir.$cmd_arg);
  if ($zip) {
    while ($zip_entry = zip_read($zip)) {
        if (zip_entry_filesize($zip_entry)) {
            $complete_path = $path.dirname(zip_entry_name($zip_entry));
            $complete_name = $path.zip_entry_name($zip_entry);
            if(!file_exists($complete_path)) {
                $tmp = '';
                foreach(explode('/',$complete_path) AS $k) {
                    $tmp .= $k.'/';
                    if(!file_exists($tmp)) {
                        @mkdir($current_dir.$tmp, 0755);
                    }
                }
            }
            if (zip_entry_open($zip, $zip_entry, "r")) {
                if ($fd = fopen($current_dir.$complete_name, 'w')){
                    fwrite($fd, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));
                    fclose($fd);
                } else echo "fopen($current_dir.$complete_name) error<br>";
                zip_entry_close($zip_entry);
            } else echo "zip_entry_open($zip,$zip_entry) error<br>";
        }
    }
    zip_close($zip);
  }
}
// +--------------------------------------------------
// | Data Formating
// +--------------------------------------------------
function html_encode($str){
    global $charSet;
    $str = preg_replace(array('/&/', '/</', '/>/', '/"/'), array('&amp;', '&lt;', '&gt;', '&quot;'), $str);  // Bypass PHP to allow any charset!!
    $str = htmlentities($str, ENT_QUOTES, $charSet, false);
    return $str;
}
function rep($x,$y){
  if ($x) {
    $aux = "";
    for ($a=1;$a<=$x;$a++) $aux .= $y;
    return $aux;
  } else return "";
}
function str_zero($arg1,$arg2){
    if (strstr($arg1,"-") == false){
        $aux = intval($arg2) - strlen($arg1);
        if ($aux) return rep($aux,"0").$arg1;
        else return $arg1;
    } else {
        return "[$arg1]";
    }
}
function replace_double($sub,$str){
    $out=str_replace($sub.$sub,$sub,$str);
    while ( strlen($out) != strlen($str) ){
        $str=$out;
        $out=str_replace($sub.$sub,$sub,$str);
    }
    return $out;
}
function remove_special_chars($str){
    $str = trim($str);
    $str = strtr($str,"¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ!@#%&*()[]{}+=?",
                      "YuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy_______________");
    $str = str_replace("..","",str_replace("/","",str_replace("\\","",str_replace("\$","",$str))));
    return $str;
}
function format_path($str){
    global $is_windows;
    $str = trim($str);
    $str = str_replace("..","",str_replace("\\","/",str_replace("\$","",$str)));
    $done = false;
    while (!$done) {
        $str2 = str_replace("//","/",$str);
        if (strlen($str) == strlen($str2)) $done = true;
        else $str = $str2;
    }
    $tam = strlen($str);
    if ($tam){
        $last_char = $tam - 1;
        if ($str[$last_char] != "/") $str .= "/";
        if ($is_windows) $str = ucfirst($str);
    }
    return $str;
}
function array_csort() {
  $args = func_get_args();
  $marray = array_shift($args);
  $msortline = "return(array_multisort(";
   foreach ($args as $arg) {
       $i++;
       if (is_string($arg)) {
          foreach ($marray as $row) {
               $sortarr[$i][] = $row[$arg];
           }
       } else {
          $sortarr[$i] = $arg;
       }
       $msortline .= "\$sortarr[".$i."],";
   }
   $msortline .= "\$marray));";
   eval($msortline);
   return $marray;
}
function show_perms( $P ) {
   $sP = "<b>";
   if($P & 0x1000) $sP .= 'p';            // FIFO pipe
   elseif($P & 0x2000) $sP .= 'c';        // Character special
   elseif($P & 0x4000) $sP .= 'd';        // Directory
   elseif($P & 0x6000) $sP .= 'b';        // Block special
   elseif($P & 0x8000) $sP .= '&minus;';  // Regular
   elseif($P & 0xA000) $sP .= 'l';        // Symbolic Link
   elseif($P & 0xC000) $sP .= 's';        // Socket
   else $sP .= 'u';                       // UNKNOWN
   $sP .= "</b>";
   // owner - group - others
   $sP .= (($P & 0x0100) ? 'r' : '&minus;') . (($P & 0x0080) ? 'w' : '&minus;') . (($P & 0x0040) ? (($P & 0x0800) ? 's' : 'x' ) : (($P & 0x0800) ? 'S' : '&minus;'));
   $sP .= (($P & 0x0020) ? 'r' : '&minus;') . (($P & 0x0010) ? 'w' : '&minus;') . (($P & 0x0008) ? (($P & 0x0400) ? 's' : 'x' ) : (($P & 0x0400) ? 'S' : '&minus;'));
   $sP .= (($P & 0x0004) ? 'r' : '&minus;') . (($P & 0x0002) ? 'w' : '&minus;') . (($P & 0x0001) ? (($P & 0x0200) ? 't' : 'x' ) : (($P & 0x0200) ? 'T' : '&minus;'));
   return $sP;
}
function format_size($arg) {
    if ($arg>0){
        $j = 0;
        $ext = array(" bytes"," Kb"," Mb"," Gb"," Tb");
        while ($arg >= pow(1024,$j)) ++$j;
        return round($arg / pow(1024,$j-1) * 100) / 100 . $ext[$j-1];
    } else return "0 bytes";
}
function get_size($file) {
    return format_size(filesize($file));
}
function check_limit($new_filesize=0) {
    global $fm_current_root;
    global $quota_mb;
    if($quota_mb){
        $total = total_size($fm_current_root);
        if (floor(($total+$new_filesize)/(1024*1024)) > $quota_mb) return true;
    }
    return false;
}
function get_user($arg) {
    global $mat_passwd;
    $aux = "x:".trim($arg).":";
    for($x=0;$x<count($mat_passwd);$x++){
        if (strstr($mat_passwd[$x],$aux)){
            $mat = explode(":",$mat_passwd[$x]);
            return $mat[0];
        }
    }
    return $arg;
}
function get_group($arg) {
    global $mat_group;
    $aux = "x:".trim($arg).":";
    for($x=0;$x<count($mat_group);$x++){
        if (strstr($mat_group[$x],$aux)){
            $mat = explode(":",$mat_group[$x]);
            return $mat[0];
        }
    }
    return $arg;
}
function uppercase($str){
    global $charset;
    return mb_strtoupper($str, $charset);
}
function lowercase($str){
    global $charset;
    return mb_strtolower($str, $charset);
}
// +--------------------------------------------------
// | Interface
// +--------------------------------------------------
function html_header($header=""){
    global $charset,$fm_color;
    echo "
    <!DOCTYPE HTML PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
    <html xmlns=\"http://www.w3.org/1999/xhtml\">
    <head>
    <meta http-equiv=\"content-type\" content=\"text/html; charset=".$charset."\" />
    <title>...:::: ".et('FileMan')."</title>
    <script language=\"Javascript\" type=\"text/javascript\">
    <!--
        function Is(){
            this.appname = navigator.appName;
            this.appversion = navigator.appVersion;
            this.platform = navigator.platform;
            this.useragent = navigator.userAgent.toLowerCase();
            this.ie = ( this.appname == 'Microsoft Internet Explorer' );
            if (( this.useragent.indexOf( 'mac' ) != -1 ) || ( this.platform.indexOf( 'mac' ) != -1 )){
                this.sisop = 'mac';
            } else if (( this.useragent.indexOf( 'windows' ) != -1 ) || ( this.platform.indexOf( 'win32' ) != -1 )){
                this.sisop = 'windows';
            } else if (( this.useragent.indexOf( 'inux' ) != -1 ) || ( this.platform.indexOf( 'linux' ) != -1 )){
                this.sisop = 'linux';
            }
        }
        var is = new Is();
        function enterSubmit(keypressEvent,submitFunc){
            var kCode = (is.ie) ? keypressEvent.keyCode : keypressEvent.which
            if( kCode == 13) eval(submitFunc);
        }
        function getCookieVal (offset) {
            var endstr = document.cookie.indexOf (';', offset);
            if (endstr == -1) endstr = document.cookie.length;
            return unescape(document.cookie.substring(offset, endstr));
        }
        function getCookie (name) {
            var arg = name + '=';
            var alen = arg.length;
            var clen = document.cookie.length;
            var i = 0;
            while (i < clen) {
                var j = i + alen;
                if (document.cookie.substring(i, j) == arg) return getCookieVal (j);
                i = document.cookie.indexOf(' ', i) + 1;
                if (i == 0) break;
            }
            return null;
        }
        function setCookie (name, value, expires) {
            var argv = setCookie.arguments;
            var argc = setCookie.arguments.length;
            var expires = (argc > 2) ? argv[2] : null;
            var path = (argc > 3) ? argv[3] : null;
            var domain = (argc > 4) ? argv[4] : null;
            var secure = (argc > 5) ? argv[5] : false;
            document.cookie = name + '=' + escape (value) +
            ((expires == null) ? '' : ('; expires=' + expires.toGMTString())) +
            ((path == null) ? '' : ('; path=' + path)) +
            ((domain == null) ? '' : ('; domain=' + domain)) +
            ((secure == true) ? '; secure' : '');
        }
        function delCookie (name) {
            var exp = new Date();
            exp.setTime (exp.getTime() - 1);
            var cval = getCookie (name);
            document.cookie = name + '=' + cval + '; expires=' + exp.toGMTString();
        }
        var frameWidth, frameHeight;
        function getFrameSize(){
            if (self.innerWidth){
                frameWidth = self.innerWidth;
                frameHeight = self.innerHeight;
            }else if (document.documentElement && document.documentElement.clientWidth){
                frameWidth = document.documentElement.clientWidth;
                frameHeight = document.documentElement.clientHeight;
            }else if (document.body){
                frameWidth = document.body.clientWidth;
                frameHeight = document.body.clientHeight;
            }else return false;
            return true;
        }
        getFrameSize();
    //-->
    </script>
    ".$header."
    </head>
    <script language=\"Javascript\" type=\"text/javascript\">
    <!--
        var W = screen.width;
        var H = screen.height;
        var FONTSIZE = 0;
        switch (W){
            case 640:
                FONTSIZE = 8;
            break;
            case 800:
                FONTSIZE = 10;
            break;
            case 1024:
                FONTSIZE = 12;
            break;
            default:
                FONTSIZE = 14;
            break;
        }
    ";
    echo replace_double(" ",str_replace(chr(13),"",str_replace(chr(10),"","
        document.writeln('
        <style type=\"text/css\">
        html {
            width: 100%;
            margin-left: 0 !important;
        }
        body {
            font-family : Arial;
            font-size: '+FONTSIZE+'px;
            font-weight : normal;
            color: #".$fm_color['Text'].";
            background-color: #".$fm_color['Bg'].";
        }
        table {
            font-family : Arial;
            font-size: '+FONTSIZE+'px;
            font-weight : normal;
            color: #".$fm_color['Text'].";
            cursor: default;
        }
        input {
            font-family : Arial;
            font-size: '+FONTSIZE+'px;
            font-weight : normal;
            color: #".$fm_color['Text'].";
        }
        textarea {
            font-family : Courier;
            font-size: 12px;
            font-weight : normal;
            color: #".$fm_color['Text'].";
        }
        a {
            font-family : Arial;
            font-size : '+FONTSIZE+'px;
            font-weight : bold;
            text-decoration: none;
            color: #".$fm_color['Text'].";
        }
        a:link {
            color: #".$fm_color['Text'].";
        }
        a:visited {
            color: #".$fm_color['Text'].";
        }
        a:hover {
            color: #".$fm_color['Link'].";
        }
        a:active {
            color: #".$fm_color['Text'].";
        }
        tr.entryUnselected {
            background-color: #".$fm_color['Entry'].";
        }
        tr.entryUnselected:hover {
            background-color: #".$fm_color['Over'].";
        }
        tr.entrySelected {
            background-color: #".$fm_color['Mark'].";
        }
        </style>
        ');
    ")));
    echo "
    //-->
    </script>
    ";
}
function reloadframe($ref,$frame_number,$Plus=""){
    global $current_dir,$fm_path_info;
    echo "
    <script language=\"Javascript\" type=\"text/javascript\">
    <!--
        ".$ref.".frame".$frame_number.".location.href='".$fm_path_info["basename"]."?frame=".$frame_number."&current_dir=".$current_dir.$Plus."';
    //-->
    </script>
    ";
}
function alert($arg){
    echo "
    <script language=\"Javascript\" type=\"text/javascript\">
    <!--
        alert('$arg');
    //-->
    </script>
    ";
}
define ('UTF32_BIG_ENDIAN_BOM'   , chr(0x00).chr(0x00).chr(0xFE).chr(0xFF));
define ('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF).chr(0xFE).chr(0x00).chr(0x00));
define ('UTF16_BIG_ENDIAN_BOM'   , chr(0xFE).chr(0xFF));
define ('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF).chr(0xFE));
define ('UTF8_BOM'               , chr(0xEF).chr(0xBB).chr(0xBF));
function get_encoding($text){
    $first2 = mb_substr($text, 0, 2);
    $first3 = mb_substr($text, 0, 3);
    $first4 = mb_substr($text, 0, 4);
    if ($first3 == UTF8_BOM) return 'UTF-8'; // WITH BOM
    elseif ($first4 == UTF32_BIG_ENDIAN_BOM) return 'UTF-32BE';
    elseif ($first4 == UTF32_LITTLE_ENDIAN_BOM) return 'UTF-32LE';
    elseif ($first2 == UTF16_BIG_ENDIAN_BOM) return 'UTF-16BE';
    elseif ($first2 == UTF16_LITTLE_ENDIAN_BOM) return 'UTF-16LE';
    elseif (mb_detect_encoding($text, 'UTF-8', true) == true) return 'UTF-8'; // WITHOUT BOM
    elseif (mb_detect_encoding($text, 'ISO-8859-1', true) == true) return 'ISO-8859-1';
    else return mb_detect_encoding($text);
}
function utf8_convert($str){
    $str_chatset = get_encoding($str);
    if ($str_chatset == "UTF-8") return $str;
    return iconv($str_chatset, "UTF-8//TRANSLIT", $str);
}
function convert_charset($str,$charset){
    $str_chatset = get_encoding($str);
    if ($str_chatset == $charset) return $str;
    else return iconv($str_chatset, $charset."//TRANSLIT", $str);
}
function fs_encode($str){
    global $is_windows;
    if ($is_windows) {
        if (extension_loaded('mbstring') && extension_loaded('iconv')) {
            return convert_charset($str,'ISO-8859-1');
        }
    }
    return $str;
}
class fs
{
    protected $base = null;

    protected function real($path) {
        $temp = realpath(fs_encode($path));
        if(!$temp) { throw new Exception('Path does not exist: ' . $path); }
        if($this->base && strlen($this->base)) {
            if(strpos($temp, $this->base) !== 0) { throw new Exception('Path is not inside base ('.$this->base.'): ' . $temp); }
        }
        return $temp;
    }
    protected function path($id) {
        $id = str_replace('/', DIRECTORY_SEPARATOR, $id);
        $id = trim($id, DIRECTORY_SEPARATOR);
        $id = $this->real($this->base . DIRECTORY_SEPARATOR . $id);
        return $id;
    }
    protected function id($path) {
        $path = $this->real($path);
        $path = substr($path, strlen($this->base));
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        $path = trim($path, '/');
        return strlen($path) ? $path : '/';
    }

    public function __construct($base) {
        $this->base = $this->real($base);
        if(!$this->base) { throw new Exception('Base directory does not exist'); }
    }
    public function lst($id, $with_root = false) {
        global $is_windows;
        $dir = $this->path($id);
        $lst = @scandir($dir);
        if(!$lst) { throw new Exception('Could not list path: ' . $dir); }
        $res = array();
        foreach($lst as $item) {
            if($item == '.' || $item == '..' || $item === null) { continue; }
            if(is_dir($dir . DIRECTORY_SEPARATOR . $item)) {
                $res[] = array('text' => utf8_encode($item), 'children' => true,  'id' => utf8_encode($this->id($dir . DIRECTORY_SEPARATOR . $item)), 'icon' => 'folder');
            }
        }
        if($with_root && $this->id($dir) === '/') {
            $text = utf8_encode(basename($this->base));
            $res = array(array('text' => $text, 'children' => $res, 'id' => '/', 'icon'=>'folder', 'state' => array('opened' => true, 'disabled' => true)));
        }
        return $res;
    }
    public function data($id) {
        if(strpos($id, ":")) {
            $id = array_map(array($this, 'id'), explode(':', $id));
            return array('type'=>'multiple', 'content'=> 'Multiple selected: ' . implode(' ', $id));
        }
        $dir = $this->path($id);
        if(is_dir($dir)) {
            return array('type'=>'folder', 'content'=> $id);
        }
        throw new Exception('Not a valid selection: ' . $dir);
    }
}
function frame2(){
    global $fm_current_root,$fm_path_info,$setflag,$is_windows,$cookie_cache_time,$current_dir,$auth_pass;
    if(isset($_GET['operation'])) {
        $fs = new fs($fm_current_root);
        try {
            $rslt = null;
            switch($_GET['operation']) {
                case 'get_node':
                    $node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
                    $rslt = $fs->lst($node, true);
                    break;
                default:
                    throw new Exception('Unsupported operation: ' . $_GET['operation']);
                    break;
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($rslt);
        }
        catch (Exception $e) {
            header($_SERVER["SERVER_PROTOCOL"] . ' 500 Server Error');
            header('Status:  500 Server Error');
            echo $e->getMessage();
        }
        die();
    }
    html_header("
    <script language=\"Javascript\" type=\"text/javascript\">
    <!--
        function saveFrameSize(){
            if (getFrameSize()){
                var exp = new Date();
                exp.setTime(exp.getTime()+$cookie_cache_time);
                setCookie('leftFrameWidth',frameWidth,exp);
            }
        }
        window.onresize = saveFrameSize;
    //-->
    </script>");
    echo "<body marginwidth=\"0\" marginheight=\"0\">
    <script language=\"Javascript\" type=\"text/javascript\">
    <!--
        // Disable text selection, binding the onmousedown, but not for some elements, it must work.
        function disableTextSelection(e){
            var type = String(e.target.type);
            return (type.indexOf('select') != -1 || type.indexOf('button') != -1 || type.indexOf('input') != -1 || type.indexOf('radio') != -1);
        }
        function enableTextSelection(){return true}
        if (is.ie) document.onselectstart=new Function('return false')
        else {
            document.body.onmousedown=disableTextSelection
            document.body.onclick=enableTextSelection
        }
        var flag = ".(($setflag)?"true":"false")."
        function set_flag(arg) {
            flag = arg;
        }
        function go_dir(arg) {
            var setflag;
            setflag = (flag)?1:0;
            document.location.href='".addslashes($fm_path_info["basename"])."?frame=2&setflag='+setflag+'&current_dir=".addslashes($current_dir)."&ec_dir='+arg;
        }
        function go(arg) {
            if (flag) {
                parent.frame3.set_dir_dest(arg+'/');
                flag = false;
            } else {
                parent.frame3.location.href='".addslashes($fm_path_info["basename"])."?frame=3&current_dir='+arg+'/';
            }
        }
        function set_fm_current_root(arg){
            document.location.href='".addslashes($fm_path_info["basename"])."?frame=2&set_fm_current_root='+escape(arg);
        }
        function refresh_tree(){
            document.location.href='".addslashes($fm_path_info["basename"])."?frame=2';
        }
        function logout(){
            document.location.href='".addslashes($fm_path_info["basename"])."?action=1';
        }
    //-->
    </script>
    ";
    echo "<table width=\"100%\" height=\"100%\" border=0 cellspacing=0 cellpadding=5>\n";
    echo "<tr valign=top height=10><td>";
    echo "<form style=\"display:inline-block;margin-top:-2px;\" action=\"".$fm_path_info["basename"]."\" method=\"post\" target=\"_parent\">";
        if ($is_windows){
            $drives=array();
            $aux="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            for($x=0;$x<strlen($aux);$x++){
                if ($handle = opendir($aux[$x].":/")){
                    @closedir($handle);
                    $is_sel="";
                    if (strpos(uppercase($fm_current_root),$aux[$x].":/") !== false) $is_sel="selected";
                    $drives[] = "<option ".$is_sel." value=\"".$aux[$x].":/\">".$aux[$x].":/</option>";
                }
            }
            if (count($drives)>1) "<select name=drive onchange=\"set_fm_current_root(this.value)\">".implode("\n",$drives)."</select>&nbsp;";
        }
        echo "<input type=button value=".et('Refresh')." onclick=\"refresh_tree()\">";
        if ($auth_pass != md5('')) echo "&nbsp;<input type=button value=\"".et('Leave')."\" onclick=\"logout()\">";
    echo "</form>";
    echo "</td></tr>";
    echo "<tr valign=top><td>";
    ?>
        <script type="text/javascript" src="<?php echo $fm_path_info["basename"]; ?>?action=99&filename=jquery-1.11.1.min.js"></script>
        <script type="text/javascript" src="<?php echo $fm_path_info["basename"]; ?>?action=99&filename=jstree.min.js"></script>
        <link rel="stylesheet" type="text/css" href="<?php echo $fm_path_info["basename"]; ?>?action=99&filename=jstree.style.min.css" media="screen" />
        <style>
            #tree { float:left; overflow:auto; padding:0; margin-bottom: 20px;}
            #tree .folder { background:url('<?php echo $fm_path_info["basename"]; ?>?action=99&filename=file_sprite.png') right bottom no-repeat; }
            #tree .file { background:url('<?php echo $fm_path_info["basename"]; ?>?action=99&filename=file_sprite.png') 0 0 no-repeat; }
            #tree .file-pdf { background-position: -32px 0 }
            #tree .file-as { background-position: -36px 0 }
            #tree .file-c { background-position: -72px -0px }
            #tree .file-iso { background-position: -108px -0px }
            #tree .file-htm, #tree .file-html, #tree .file-xml, #tree .file-xsl { background-position: -126px -0px }
            #tree .file-cf { background-position: -162px -0px }
            #tree .file-cpp { background-position: -216px -0px }
            #tree .file-cs { background-position: -236px -0px }
            #tree .file-sql { background-position: -272px -0px }
            #tree .file-xls, #tree .file-xlsx { background-position: -362px -0px }
            #tree .file-h { background-position: -488px -0px }
            #tree .file-crt, #tree .file-pem, #tree .file-cer { background-position: -452px -18px }
            #tree .file-php { background-position: -108px -18px }
            #tree .file-jpg, #tree .file-jpeg, #tree .file-png, #tree .file-gif, #tree .file-bmp { background-position: -126px -18px }
            #tree .file-ppt, #tree .file-pptx { background-position: -144px -18px }
            #tree .file-rb { background-position: -180px -18px }
            #tree .file-text, #tree .file-txt, #tree .file-md, #tree .file-log, #tree .file-htaccess { background-position: -254px -18px }
            #tree .file-doc, #tree .file-docx { background-position: -362px -18px }
            #tree .file-zip, #tree .file-gz, #tree .file-tar, #tree .file-rar { background-position: -416px -18px }
            #tree .file-js { background-position: -434px -18px }
            #tree .file-css { background-position: -144px -0px }
            #tree .file-fla { background-position: -398px -0px }
        </style>
        <div id="container" role="main">
            <div id="tree"></div>
        </div>
        <script>
        var tree_loaded = false;
        var tree_auto_load_nodes = <?php echo json_encode(explode("/",trim(str_replace($fm_current_root,'',$current_dir),'/'))); ?>;
        var tree_auto_load_node_curr = 0;
        //console.log(tree_auto_load_nodes);
        function tree_auto_load(){
            if (tree_auto_load_node_curr > tree_auto_load_nodes.length) return;
            //console.log('tree_auto_load()');
            var node_id = tree_auto_load_nodes.slice(0, tree_auto_load_node_curr+1).join("/");
            var node = $('#tree').find("[id='"+node_id+"']:eq(0)");
            //console.log(node_id);
            //console.log(node);
            tree_auto_load_node_curr++;
            if (tree_auto_load_node_curr == tree_auto_load_nodes.length) {
                if (node.length) {
                    $("#tree").jstree(true).open_node(node, function(){
                        $('#tree').jstree(true).select_node(node,true);
                        tree_loaded = true;
                    }, false);
                } else {
                    tree_loaded = true;
                }
            } else {
                if (node.length) {
                    $("#tree").jstree(true).open_node(node, tree_auto_load, false);
                } else {
                    tree_auto_load();
                }
            }
        }
        $(function () {
            $('#tree')
                .jstree({
                    'core' : {
                        'data' : {
                            'url' : '?frame=2&operation=get_node',
                            'data' : function (node) {
                                return { 'id' : node.id };
                            }
                        },
                        'check_callback' : function(o, n, p, i, m) {
                            if(m && m.dnd && m.pos !== 'i') { return false; }
                            if(o === "move_node" || o === "copy_node") {
                                if(this.get_node(n).parent === this.get_node(p).id) { return false; }
                            }
                            return true;
                        },
                        'force_text' : true,
                        'themes' : {
                            'responsive' : false,
                            'variant' : 'small',
                            'stripes' : false
                        },
                        'expand_selected_onload' : true
                    },
                    'sort' : function(a, b) {
                        return this.get_type(a) === this.get_type(b) ? (this.get_text(a) > this.get_text(b) ? 1 : -1) : (this.get_type(a) >= this.get_type(b) ? 1 : -1);
                    },
                    'types' : {
                        'default' : { 'icon' : 'folder' },
                        'file' : { 'valid_children' : [], 'icon' : 'file' }
                    },
                    'unique' : {
                        'duplicate' : function (name, counter) {
                            return name + ' ' + counter;
                        }
                    },
                    'massload' : {
                        'url' : '?frame=2&operation=get_node',
                        'data' : function (nodes) {
                            return { 'ids' : nodes.join(',') };
                        }
                    },
                    'plugins' : ['sort','types','unique'] // 'state', 'massload'
                })
            //.on('changed.jstree', function (e, data) {
            .on('select_node.jstree', function (e, data) {
                if (!tree_loaded) return;
                if (data && data.selected && data.selected.length) {
                    //console.log('select_node.jstree()');
                    //console.log(data);
                    go('<?php echo $fm_current_root; ?>/'+data.selected[0]);
                }
            })
            .on('loaded.jstree', function (e, data) {
                //console.log('loaded.jstree()');
                //console.log(e);
                //console.log(data);
                tree_auto_load();
            });
            //$('#tree').jstree(true).clear_state();
        });
        </script>
    <?php
    echo "</td></tr>";
    echo "</table>\n";
    echo "</body>\n</html>";
}
function getmicrotime(){
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}
function dir_list_form() {
    global $fm_current_root,$current_dir,$quota_mb,$resolve_ids,$order_dir_list_by,$is_windows,$cmd_name,$ip,$lan_ip,$fm_path_info;
    $ti = getmicrotime();
    clearstatcache();
    $out = "<style>
        #modalDiv {
            background: #000;
            opacity: 0.5;
            width: 100%;
            height: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 30000;
            display: none;
        }
        #modalIframeWrapper {
            background: #FFF;
            border: 1px solid #ccc;
            box-shadow: 0 0 3px rgba(0, 0, 0, 0.15);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 32000;
            display: none;
        }
        #modalIframe {
            background: #FFF;
            width: 640px;
            height: 480px;
            overflow-y: scroll;
            overflow-x: auto;
            border: 1px solid #ccc;
            box-shadow: 0 0 3px rgba(0, 0, 0, 0.15);
        }
    </style>
    <div id=\"modalDiv\"></div>
    <div id=\"modalIframeWrapper\">
        <table border=0 cellspacing=1 cellpadding=4>
            <tr><td id=\"modalIframeWrapperTitle\" style=\"font-weight:bold;\">Title</td><td align=right width=10><nobr><a style=\"margin-right:2px;\" href=\"JavaScript:closeModalWindow()\">".et('Close')."</a></nobr></td></tr>
            <tr><td colspan=2><iframe id=\"modalIframe\" src=\"\" scrolling=\"yes\" frameborder=\"0\"></iframe></td></tr>
        </table>
    </div>
    <script language=\"Javascript\" type=\"text/javascript\">
    <!--
        var modalWindowReloadOnClose = false;
        function openModalWindow(url,title,w,h,reloadOnClose){
            if (typeof(title) == 'undefined') title = '';
            if (typeof(w) == 'undefined') w = '640';
            if (typeof(h) == 'undefined') h = '480';
            if (typeof(reloadOnClose) != 'undefined') modalWindowReloadOnClose = reloadOnClose;
            document.getElementById(\"modalIframe\").src = url;
            document.getElementById(\"modalIframe\").style.width = w+'px';
            document.getElementById(\"modalIframe\").style.height = h+'px';
            document.getElementById(\"modalDiv\").style.display = ('block');
            document.getElementById(\"modalIframeWrapper\").style.display = ('block');
            document.getElementById(\"modalIframeWrapperTitle\").innerHTML = title;
            document.getElementById(\"modalIframe\").focus();
        }
        function closeModalWindow(){
            document.getElementById(\"modalIframe\").src = '';
            document.getElementById(\"modalDiv\").style.display=('none');
            document.getElementById(\"modalIframeWrapper\").style.display=('none');
            if (modalWindowReloadOnClose) {
                window.top.frame3.location.href='".$fm_path_info["basename"]."?frame=3&current_dir=".$current_dir."';
            }
        }
    -->
    </script>
    <table border=0 cellspacing=1 cellpadding=4 width=\"100%\" bgcolor=\"#eeeeee\">\n";
    if ($opdir = @opendir(fs_encode($current_dir))) {
        $has_files = false;
        $entry_count = 0;
        $total_size = 0;
        $entry_list = array();
        while ($file = readdir($opdir)) {
          if (($file != ".")&&($file != "..")){
            $entry_list[$entry_count]["size"] = 0;
            $entry_list[$entry_count]["sizet"] = 0;
            $entry_list[$entry_count]["type"] = "none";
            if (is_file($current_dir.$file)){
                $ext = lowercase(strrchr($file,"."));
                $entry_list[$entry_count]["type"] = "file";
                // Função filetype() returns only "file"...
                $entry_list[$entry_count]["size"] = filesize($current_dir.$file);
                $entry_list[$entry_count]["sizet"] = format_size($entry_list[$entry_count]["size"]);
                if (strstr($ext,".")){
                    $entry_list[$entry_count]["ext"] = $ext;
                    $entry_list[$entry_count]["extt"] = $ext;
                } else {
                    $entry_list[$entry_count]["ext"] = "";
                    $entry_list[$entry_count]["extt"] = "&nbsp;";
                }
                $has_files = true;
            } elseif (is_dir($current_dir.$file)) {
                // Recursive directory size disabled
                // $entry_list[$entry_count]["size"] = total_size($current_dir.$file);
                $entry_list[$entry_count]["size"] = 0;
                $entry_list[$entry_count]["sizet"] = "&nbsp;";
                $entry_list[$entry_count]["type"] = "dir";
            }
            $entry_list[$entry_count]["name"] = $file;
            $entry_list[$entry_count]["date"] = date("Ymd", filemtime($current_dir.$file));
            $entry_list[$entry_count]["time"] = date("his", filemtime($current_dir.$file));
            $entry_list[$entry_count]["datet"] = date("d/m/y h:i", filemtime($current_dir.$file));
            if (!$is_windows && $resolve_ids){
                $entry_list[$entry_count]["p"] = show_perms(fileperms($current_dir.$file));
                $entry_list[$entry_count]["u"] = get_user(fileowner($current_dir.$file));
                $entry_list[$entry_count]["g"] = get_group(filegroup($current_dir.$file));
            } else {
                $entry_list[$entry_count]["p"] = base_convert(fileperms($current_dir.$file),10,8);
                $entry_list[$entry_count]["p"] = substr($entry_list[$entry_count]["p"],strlen($entry_list[$entry_count]["p"])-3);
                $entry_list[$entry_count]["u"] = fileowner($current_dir.$file);
                $entry_list[$entry_count]["g"] = filegroup($current_dir.$file);
            }
            $total_size += $entry_list[$entry_count]["size"];
            $entry_count++;
          }
        }
        @closedir($opdir);

        if($entry_count){
            $or1="1A";
            $or2="2D";
            $or3="3A";
            $or4="4A";
            $or5="5A";
            $or6="6D";
            $or7="7D";
            switch($order_dir_list_by){
                case "1A": $entry_list = array_csort ($entry_list,"type",SORT_STRING,SORT_ASC,"name",SORT_STRING,SORT_ASC); $or1="1D"; break;
                case "1D": $entry_list = array_csort ($entry_list,"type",SORT_STRING,SORT_ASC,"name",SORT_STRING,SORT_DESC); $or1="1A"; break;
                case "2A": $entry_list = array_csort ($entry_list,"type",SORT_STRING,SORT_ASC,"p",SORT_STRING,SORT_ASC,"g",SORT_STRING,SORT_ASC,"u",SORT_STRING,SORT_ASC); $or2="2D"; break;
                case "2D": $entry_list = array_csort ($entry_list,"type",SORT_STRING,SORT_ASC,"p",SORT_STRING,SORT_DESC,"g",SORT_STRING,SORT_ASC,"u",SORT_STRING,SORT_ASC); $or2="2A"; break;
                case "3A": $entry_list = array_csort ($entry_list,"type",SORT_STRING,SORT_ASC,"u",SORT_STRING,SORT_ASC,"g",SORT_STRING,SORT_ASC); $or3="3D"; break;
                case "3D": $entry_list = array_csort ($entry_list,"type",SORT_STRING,SORT_ASC,"u",SORT_STRING,SORT_DESC,"g",SORT_STRING,SORT_ASC); $or3="3A"; break;
                case "4A": $entry_list = array_csort ($entry_list,"type",SORT_STRING,SORT_ASC,"g",SORT_STRING,SORT_ASC,"u",SORT_STRING,SORT_DESC); $or4="4D"; break;
                case "4D": $entry_list = array_csort ($entry_list,"type",SORT_STRING,SORT_ASC,"g",SORT_STRING,SORT_DESC,"u",SORT_STRING,SORT_DESC); $or4="4A"; break;
                case "5A": $entry_list = array_csort ($entry_list,"type",SORT_STRING,SORT_ASC,"size",SORT_NUMERIC,SORT_ASC); $or5="5D"; break;
                case "5D": $entry_list = array_csort ($entry_list,"type",SORT_STRING,SORT_ASC,"size",SORT_NUMERIC,SORT_DESC); $or5="5A"; break;
                case "6A": $entry_list = array_csort ($entry_list,"type",SORT_STRING,SORT_ASC,"date",SORT_STRING,SORT_ASC,"time",SORT_STRING,SORT_ASC,"name",SORT_STRING,SORT_ASC); $or6="6D"; break;
                case "6D": $entry_list = array_csort ($entry_list,"type",SORT_STRING,SORT_ASC,"date",SORT_STRING,SORT_DESC,"time",SORT_STRING,SORT_DESC,"name",SORT_STRING,SORT_ASC); $or6="6A"; break;
                case "7A": $entry_list = array_csort ($entry_list,"type",SORT_STRING,SORT_ASC,"ext",SORT_STRING,SORT_ASC,"name",SORT_STRING,SORT_ASC); $or7="7D"; break;
                case "7D": $entry_list = array_csort ($entry_list,"type",SORT_STRING,SORT_ASC,"ext",SORT_STRING,SORT_DESC,"name",SORT_STRING,SORT_ASC); $or7="7A"; break;
            }
        }
        $out .= "
        <script language=\"Javascript\" type=\"text/javascript\">
        <!--
        function go(arg) {
            document.location.href='".addslashes($fm_path_info["basename"])."?frame=3&current_dir=".addslashes($current_dir)."'+arg+'/';
        }
        function resolve_ids() {
            document.location.href='".addslashes($fm_path_info["basename"])."?frame=3&set_resolve_ids=1&current_dir=".addslashes($current_dir)."';
        }
        var entry_list = new Array();
        // Custom object constructor
        function entry(name, type, size, selected){
            this.name = name;
            this.type = type;
            this.size = size;
            this.selected = false;
        }
        // Declare entry_list for selection procedures";
        foreach ($entry_list as $i=>$data){
            $out .= "\nentry_list['entry$i'] = new entry('".addslashes($data["name"])."', '".$data["type"]."', ".$data["size"].", false);";
        }
        $out .= "
        // Select/Unselect Rows OnClick/OnMouseOver
        var lastRows = new Array(null,null);
        function selectEntry(Row, Action){
            if (multipleSelection){
                // Avoid repeated onmouseover events from same Row ( cell transition )
                if (Row != lastRows[0]){
                    if (Action == 'over') {
                        if (entry_list[Row.id].selected){
                            if (unselect(entry_list[Row.id])) {
                                Row.className = 'entryUnselected';
                            }
                            // Change the last Row when you change the movement orientation
                            if (lastRows[0] != null && lastRows[1] != null){
                                var LastRowID = lastRows[0].id;
                                if (Row.id == lastRows[1].id){
                                    if (unselect(entry_list[LastRowID])) {
                                        lastRows[0].className = 'entryUnselected';
                                    }
                                }
                            }
                        } else {
                            if (select(entry_list[Row.id])){
                                Row.className = 'entrySelected';
                            }
                            // Change the last Row when you change the movement orientation
                            if (lastRows[0] != null && lastRows[1] != null){
                                var LastRowID = lastRows[0].id;
                                if (Row.id == lastRows[1].id){
                                    if (select(entry_list[LastRowID])) {
                                        lastRows[0].className = 'entrySelected';
                                    }
                                }
                            }
                        }
                        lastRows[1] = lastRows[0];
                        lastRows[0] = Row;
                    }
                }
            } else {
                if (Action == 'click') {
                    var newClassName = null;
                    if (entry_list[Row.id].selected){
                        if (unselect(entry_list[Row.id])) newClassName = 'entryUnselected';
                    } else {
                        if (select(entry_list[Row.id])) newClassName = 'entrySelected';
                    }
                    if (newClassName) {
                        lastRows[0] = lastRows[1] = Row;
                        Row.className = newClassName;
                    }
                }
            }
            return true;
        }
        // Disable text selection and bind multiple selection flag
        var multipleSelection = false;
        if (is.ie) {
            document.onselectstart=new Function('return false');
            document.onmousedown=switch_flag_on;
            document.onmouseup=switch_flag_off;
            // Event mouseup is not generated over scrollbar.. curiously, mousedown is.. go figure.
            window.onscroll=new Function('multipleSelection=false');
            window.onresize=new Function('multipleSelection=false');
        } else {
            if (document.layers) window.captureEvents(Event.MOUSEDOWN);
            if (document.layers) window.captureEvents(Event.MOUSEUP);
            window.onmousedown=switch_flag_on;
            window.onmouseup=switch_flag_off;
        }
        // Using same function and a ternary operator couses bug on double click
        function switch_flag_on(e) {
            if (is.ie){
                multipleSelection = (event.button == 1);
            } else {
                multipleSelection = (e.which == 1);
            }
            var type = String(e.target.type);
            return (type.indexOf('select') != -1 || type.indexOf('button') != -1 || type.indexOf('input') != -1 || type.indexOf('radio') != -1);
        }
        function switch_flag_off(e) {
            if (is.ie){
                multipleSelection = (event.button != 1);
            } else {
                multipleSelection = (e.which != 1);
            }
            lastRows[0] = lastRows[1] = null;
            update_sel_status();
            return false;
        }
        var total_dirs_selected = 0;
        var total_files_selected = 0;
        function unselect(Entry){
            if (!Entry.selected) return false;
            Entry.selected = false;
            sel_totalsize -= Entry.size;
            if (Entry.type == 'dir') total_dirs_selected--;
            else total_files_selected--;
            return true;
        }
        function select(Entry){
            if(Entry.selected) return false;
            Entry.selected = true;
            sel_totalsize += Entry.size;
            if(Entry.type == 'dir') total_dirs_selected++;
            else total_files_selected++;
            return true;
        }
        function is_anything_selected(){
            var selected_dir_list = new Array();
            var selected_file_list = new Array();
            for(var x=0;x<".(integer)count($entry_list).";x++){
                if(entry_list['entry'+x].selected){
                    if(entry_list['entry'+x].type == 'dir') selected_dir_list.push(entry_list['entry'+x].name);
                    else selected_file_list.push(entry_list['entry'+x].name);
                }
            }
            document.form_action.selected_dir_list.value = selected_dir_list.join('<|*|>');
            document.form_action.selected_file_list.value = selected_file_list.join('<|*|>');
            return (total_dirs_selected>0 || total_files_selected>0);
        }
        function format_size (arg) {
            var resul = '';
            if (arg>0){
                var j = 0;
                var ext = new Array(' bytes',' Kb',' Mb',' Gb',' Tb');
                while (arg >= Math.pow(1024,j)) ++j;
                resul = (Math.round(arg/Math.pow(1024,j-1)*100)/100) + ext[j-1];
            } else resul = 0;
            return resul;
        }
        var sel_totalsize = 0;
        function update_sel_status(){
            var t = total_dirs_selected+' ".et('Dir_s')." ".et('And')." '+total_files_selected+' ".et('File_s')." ".et('Selected_s')." = '+format_size(sel_totalsize);
            //document.getElementById(\"sel_status\").innerHTML = t;
            window.status = t;
        }
        // Select all/none/inverse
        function selectANI(Butt){
            cancel_copy_move();
            for(var x=0;x<". (integer)count($entry_list).";x++){
                var Row = document.getElementById('entry'+x);
                var newClassName = null;
                switch (Butt.value){
                    case '".et('SelAll')."':
                        if (select(entry_list[Row.id])) newClassName = 'entrySelected';
                    break;
                    case '".et('SelNone')."':
                        if (unselect(entry_list[Row.id])) newClassName = 'entryUnselected';
                    break;
                    case '".et('SelInverse')."':
                        if (entry_list[Row.id].selected){
                            if (unselect(entry_list[Row.id])) newClassName = 'entryUnselected';
                        } else {
                            if (select(entry_list[Row.id])) newClassName = 'entrySelected';
                        }
                    break;
                }
                if (newClassName) {
                    Row.className = newClassName;
                }
            }
            if (Butt.value == '".et('SelAll')."'){
                for(var i=0;i<2;i++){
                    document.getElementById('ANI'+i).value='".et('SelNone')."';
                }
            } else if (Butt.value == '".et('SelNone')."'){
                for(var i=0;i<2;i++){
                    document.getElementById('ANI'+i).value='".et('SelAll')."';
                }
            }
            update_sel_status();
            return true;
        }
        function download(arg){
            parent.frame1.location.href='".addslashes($fm_path_info["basename"])."?action=3&current_dir=".addslashes($current_dir)."&filename='+escape(arg);
        }
        function upload_form(){
            var w = 400;
            var h = 250;
            openModalWindow('".addslashes($fm_path_info["basename"])."?action=10&current_dir=".addslashes($current_dir)."','".et('Upload')."',w,h,true);
        }
        function decompress(arg){
            if(confirm('".uppercase(et('Decompress'))." \\' '+arg+' \\' ?')) {
                document.form_action.action.value = 72;
                document.form_action.cmd_arg.value = arg;
                document.form_action.submit();
            }
        }
        function execute_file(arg){
            if(arg.length>0){
                if(confirm('".et('ConfExec')." \\' '+arg+' \\' ?')) {
                    openModalWindow('".addslashes($fm_path_info["basename"])."?action=11&current_dir=".addslashes($current_dir)."&filename='+escape(arg),'".et('Exec')." '+escape(arg),800,600);
                }
            }
        }
        function edit_file_form(arg){
            openModalWindow('".addslashes($fm_path_info["basename"])."?action=7&current_dir=".addslashes($current_dir)."&filename='+escape(arg),'".et('Edit')." '+escape(arg),1024,768);
        }
        function config_form(){
            openModalWindow('".addslashes($fm_path_info["basename"])."?action=2','".et('FileMan')." - ".et('Configurations')."',640,300);
        }
        function server_info_form(arg){
            openModalWindow('".addslashes($fm_path_info["basename"])."?action=5','".et('ServerInfo')."',800,600);
        }
        function shell_form(){
            openModalWindow('".addslashes($fm_path_info["basename"])."?action=9','".et('Shell')."',800,600);
        }
        function portscan_form(){
            openModalWindow('".addslashes($fm_path_info["basename"])."?action=12','".et('Portscan')."',800,600);
        }
        function view_form(arg){
            if(confirm('".uppercase(et('View'))." \\' '+arg+' \\' ?')) openModalWindow('".addslashes($fm_path_info["basename"])."?action=4&current_dir=".addslashes($current_dir)."&filename='+escape(arg),'".et("View")." '+escape(arg),800,600);
        }
        function rename(arg){
            var nome = '';
            if (nome = prompt('".uppercase(et('Ren'))." \\' '+arg+' \\' ".et('To')." ...')) document.location.href='".addslashes($fm_path_info["basename"])."?frame=3&action=3&current_dir=".addslashes($current_dir)."&old_name='+escape(arg)+'&new_name='+escape(nome);
        }
        function set_dir_dest(arg){
            document.form_action.dir_dest.value=arg;
            if (document.form_action.action.value.length>0) test(document.form_action.action.value);
            else alert('".et('JSError').".');
        }
        function sel_dir(arg){
            document.form_action.action.value = arg;
            document.form_action.dir_dest.value='';
            if (!is_anything_selected()) alert('".et('NoSel').".');
            else {
                if (!getCookie('sel_dir_warn')) {
                    //alert('".et('SelDir').".');
                    document.cookie='sel_dir_warn'+'='+escape('true')+';';
                }
                set_sel_dir_warn(true);
                parent.frame2.set_flag(true);
            }
        }
        function set_sel_dir_warn(b){
            try {
                document.getElementById(\"sel_dir_warn\").style.display=(b?'':'none');
            } catch (err) {}
        }
        function cancel_copy_move(){
            set_sel_dir_warn(false);
            parent.frame2.set_flag(false);
        }
        function chmod_form(){
            cancel_copy_move();
            document.form_action.dir_dest.value='';
            document.form_action.chmod_arg.value='';
            if (!is_anything_selected()) alert('".et('NoSel').".');
            else openModalWindow('".addslashes($fm_path_info["basename"])."?action=8','".et('Perms')."',280,180);
        }
        function set_chmod_arg(arg){
            cancel_copy_move();
            if (!is_anything_selected()) alert('".et('NoSel').".');
            else {
                document.form_action.dir_dest.value='';
                document.form_action.chmod_arg.value=arg;
                test(9);
            }
        }
        function test_action(){
            if (document.form_action.action.value != 0) return true;
            else return false;
        }
        function test_prompt(arg){
            cancel_copy_move();
            var erro='';
            var conf='';
            if (arg == 1){
                document.form_action.cmd_arg.value = prompt('".et('TypeDir').".');
            } else if (arg == 2){
                document.form_action.cmd_arg.value = prompt('".et('TypeArq').".');
            } else if (arg == 71){
                if (!is_anything_selected()) erro = '".et('NoSel').".';
                else document.form_action.cmd_arg.value = prompt('".et('TypeArqComp')."');
            }
            if (erro!=''){
                document.form_action.cmd_arg.focus();
                alert(erro);
            } else if(document.form_action.cmd_arg.value.length>0) {
                document.form_action.action.value = arg;
                document.form_action.submit();
            }
        }
        function strstr(haystack,needle){
            var index = haystack.indexOf(needle);
            return (index==-1)?false:index;
        }
        function valid_dest(dest,orig){
            return (strstr(dest,orig)==false)?true:false;
        }
        // ArrayAlert - Selection debug only
        function aa(){
            var str = 'selected_dir_list:\\n';
            for (x=0;x<selected_dir_list.length;x++){
                str += selected_dir_list[x]+'\\n';
            }
            str += '\\nselected_file_list:\\n';
            for (x=0;x<selected_file_list.length;x++){
                str += selected_file_list[x]+'\\n';
            }
            alert(str);
        }
        function test(arg){
            cancel_copy_move();
            var erro='';
            var conf='';
            if (arg == 4){
                if (!is_anything_selected()) erro = '".et('NoSel').".\\n';
                conf = '".et('RemSel')." ?\\n';
            } else if (arg == 5){
                if (!is_anything_selected()) erro = '".et('NoSel').".\\n';
                else if(document.form_action.dir_dest.value.length == 0) erro = '".et('NoDestDir').".';
                else if(document.form_action.dir_dest.value == document.form_action.current_dir.value) erro = '".et('DestEqOrig').".';
                else if(!valid_dest(document.form_action.dir_dest.value,document.form_action.current_dir.value)) erro = '".et('InvalidDest').".';
                conf = '".et('CopyTo')." \\' '+document.form_action.dir_dest.value+' \\' ?\\n';
            } else if (arg == 6){
                if (!is_anything_selected()) erro = '".et('NoSel').".';
                else if(document.form_action.dir_dest.value.length == 0) erro = '".et('NoDestDir').".';
                else if(document.form_action.dir_dest.value == document.form_action.current_dir.value) erro = '".et('DestEqOrig').".';
                else if(!valid_dest(document.form_action.dir_dest.value,document.form_action.current_dir.value)) erro = '".et('InvalidDest').".';
                conf = '".et('MoveTo')." \\' '+document.form_action.dir_dest.value+' \\' ?\\n';
            } else if (arg == 9){
                if (!is_anything_selected()) erro = '".et('NoSel').".';
                else if(document.form_action.chmod_arg.value.length == 0) erro = '".et('NoNewPerm').".';
                //conf = '".et('AlterPermTo')." \\' '+document.form_action.chmod_arg.value+' \\' ?\\n';
            }
            if (erro!=''){
                document.form_action.cmd_arg.focus();
                alert(erro);
            } else if(conf!='') {
                if(confirm(conf)) {
                    document.form_action.action.value = arg;
                    document.form_action.submit();
                } else {
                    set_sel_dir_warn(false);
                }
            } else {
                document.form_action.action.value = arg;
                document.form_action.submit();
            }
        }
        //-->
        </script>";
        $out .= "
            <tr>
            <td bgcolor=\"#DDDDDD\" colspan=50><nobr>
            <form style=\"display:inline-block;margin-top:-2px;\" action=\"".$fm_path_info["basename"]."\" method=\"post\" onsubmit=\"return test_action();\">
                <input type=button onclick=\"config_form()\" value=\"".et('Config')."\">
                <input type=button onclick=\"server_info_form()\" value=\"".et('ServerInfo')."\">
                <input type=button onclick=\"test_prompt(1)\" value=\"".et('CreateDir')."\">
                <input type=button onclick=\"test_prompt(2)\" value=\"".et('CreateArq')."\">
                <input type=button onclick=\"upload_form()\" value=\"".et('Upload')."\">
                <input type=button onclick=\"shell_form()\" value=\"".et('Shell')."\">
                <input type=button onclick=\"portscan_form()\" value=\"".et('Portscan')."\">
            </form>
            <style type=\"text/css\">
                .paypal {
                    background: url('".$fm_path_info["basename"]."?action=99&filename=paypal.png') 0 0 no-repeat;
                    text-indent: -9999px;
                    width: 105px;
                    height: 24px;
                    border: none;
                    cursor: hand;
                }
                .paypal:hover {
                    background-position: 0 -24px;
                }
                .bitcoin {
                    display: inline-block;
                    position: absolute;
                    margin-left: 3px;
                    background: url('".$fm_path_info["basename"]."?action=99&filename=bitcoin.png') 0 0 no-repeat;
                    text-indent: -9999px;
                    width: 105px;
                    height: 24px;
                    border: none;
                    cursor: hand;
                }
                .bitcoin:hover {
                    background-position: 0 -24px;
                }
            </style>
            <form style=\"display:inline-block;\" action=\"https://www.paypal.com/cgi-bin/webscr\" method=\"post\" target=\"_blank\">
                <input type=\"hidden\" name=\"cmd\" value=\"_xclick\">
                <input type=\"hidden\" name=\"business\" value=\"dulldusk@gmail.com\">
                <input type=\"hidden\" name=\"lc\" value=\"BR\">
                <input type=\"hidden\" name=\"item_name\" value=\"Donation for PHP File Manager\">
                <input type=\"hidden\" name=\"button_subtype\" value=\"services\">
                <input type=\"hidden\" name=\"currency_code\" value=\"USD\">
                <input type=\"hidden\" name=\"tax_rate\" value=\"0.000\">
                <input type=\"hidden\" name=\"shipping\" value=\"0.00\">
                <input type=\"hidden\" name=\"bn\" value=\"PP-BuyNowBF:btn_buynowCC_LG.gif:NonHostedGuest\">
                <input type=\"hidden\" name=\"buyer_credit_promo_code\" value=\"\">
                <input type=\"hidden\" name=\"buyer_credit_product_category\" value=\"\">
                <input type=\"hidden\" name=\"buyer_credit_shipping_method\" value=\"\">
                <input type=\"hidden\" name=\"buyer_credit_user_address_change\" value=\"\">
                <input type=\"hidden\" name=\"tax\" value=\"0\">
                <input type=\"hidden\" name=\"no_shipping\" value=\"1\">
                <input type=\"hidden\" name=\"return\" value=\"http://phpfm.sf.net\">
                <input type=\"hidden\" name=\"cancel_return\" value=\"http://phpfm.sf.net\">
                <input type=\"submit\" class=\"paypal\" value=\"donate with paypal\">
            </form>
            <a class=\"bitcoin\" href=\"http://phpfm.sf.net/#donation\" target=\"_blank\">donate with bitcoin</a>
            </nobr>
            </td>
            </tr>";
        $out .= "
        <form name=\"form_action\" action=\"".$fm_path_info["basename"]."\" method=\"post\" onsubmit=\"return test_action();\">
            <input type=hidden name=\"frame\" value=3>
            <input type=hidden name=\"action\" value=0>
            <input type=hidden name=\"dir_dest\" value=\"\">
            <input type=hidden name=\"chmod_arg\" value=\"\">
            <input type=hidden name=\"cmd_arg\" value=\"\">
            <input type=hidden name=\"current_dir\" value=\"$current_dir\">
            <input type=hidden name=\"dir_before\" value=\"$dir_before\">
            <input type=hidden name=\"selected_dir_list\" value=\"\">
            <input type=hidden name=\"selected_file_list\" value=\"\">";
        $uplink = "";
        if ($current_dir != $fm_current_root){
            $mat = explode("/",$current_dir);
            $dir_before = "";
            for($x=0;$x<(count($mat)-2);$x++) $dir_before .= $mat[$x]."/";
            $uplink = "<a href=\"".$fm_path_info["basename"]."?frame=3&current_dir=$dir_before\"><<</a> ";
        }
        if($entry_count){
            $out .= "
                <tr bgcolor=\"#DDDDDD\"><td colspan=50><nobr>$uplink <a href=\"".$fm_path_info["basename"]."?frame=3&current_dir=$current_dir\">$current_dir</a></nobr>
                <tr>
                <td bgcolor=\"#DDDDDD\" colspan=50><nobr>
                    <input type=\"button\" style=\"width:80\" onclick=\"selectANI(this)\" id=\"ANI0\" value=\"".et('SelAll')."\">
                    <input type=\"button\" style=\"width:80\" onclick=\"selectANI(this)\" value=\"".et('SelInverse')."\">
                    <input type=\"button\" style=\"width:80\" onclick=\"test(4)\" value=\"".et('Rem')."\">
                    <input type=\"button\" style=\"width:80\" onclick=\"sel_dir(5)\" value=\"".et('Copy')."\">
                    <input type=\"button\" style=\"width:80\" onclick=\"sel_dir(6)\" value=\"".et('Move')."\">
                    <input type=\"button\" style=\"width:100\" onclick=\"test_prompt(71)\" value=\"".et('Compress')."\">";
            if (!$is_windows) $out .= "
                    <input type=\"button\" style=\"width:100\" onclick=\"resolve_ids()\" value=\"".et('ResolveIDs')."\">";
            $out .= "
                    <input type=\"button\" style=\"width:100\" onclick=\"chmod_form()\" value=\"".et('Perms')."\">";
            $out .= "
                </nobr></td>
                </tr>
                <tr>
                <td bgcolor=\"#DDDDDD\" colspan=50 id=\"sel_dir_warn\" style=\"display:none\"><nobr><font color=\"red\">".et('SelDir')."...</font></nobr></td>
                </tr>";
            $file_count = 0;
            $dir_count = 0;
            $dir_out = array();
            $file_out = array();
            $max_opt = 0;
            foreach ($entry_list as $ind=>$dir_entry) {
                $file = $dir_entry["name"];
                if ($dir_entry["type"]=="dir"){
                    $dir_out[$dir_count] = array();
                    $dir_out[$dir_count][] = "
                        <tr ID=\"entry$ind\" class=\"entryUnselected\" onmouseover=\"selectEntry(this, 'over');\" onmousedown=\"selectEntry(this, 'click');\">
                        <td><nobr><a href=\"JavaScript:go('".addslashes($file)."')\">".utf8_encode($file)."</a></nobr></td>";
                    $dir_out[$dir_count][] = "<td>".$dir_entry["p"]."</td>";
                    if (!$is_windows) {
                        $dir_out[$dir_count][] = "<td><nobr>".$dir_entry["u"]."</nobr></td>";
                        $dir_out[$dir_count][] = "<td><nobr>".$dir_entry["g"]."</nobr></td>";
                    }
                    $dir_out[$dir_count][] = "<td><nobr>".$dir_entry["sizet"]."</nobr></td>";
                    $dir_out[$dir_count][] = "<td><nobr>".$dir_entry["datet"]."</nobr></td>";
                    if ($has_files) $dir_out[$dir_count][] = "<td>&nbsp;</td>";
                    // Opções de diretório
                    if ( is_writable($current_dir.$file) ) $dir_out[$dir_count][] = "
                        <td align=center><a href=\"JavaScript:if(confirm('".et('ConfRem')." \\'".addslashes($file)."\\' ?')) document.location.href='".addslashes($fm_path_info["basename"])."?frame=3&action=8&cmd_arg=".addslashes($file)."&current_dir=".addslashes($current_dir)."'\">".et('Rem')."</a>";
                    if ( is_writable($current_dir.$file) ) $dir_out[$dir_count][] = "
                        <td align=center><a href=\"JavaScript:rename('".addslashes($file)."')\">".et('Ren')."</a>";
                    if (count($dir_out[$dir_count])>$max_opt){
                        $max_opt = count($dir_out[$dir_count]);
                    }
                    $dir_count++;
                } else {
                    $file_out[$file_count] = array();
                    $file_out[$file_count][] = "
                        <tr ID=\"entry$ind\" class=\"entryUnselected\" onmouseover=\"selectEntry(this, 'over');\" onmousedown=\"selectEntry(this, 'click');\">
                        <td><nobr><a href=\"JavaScript:download('".addslashes($file)."')\">".utf8_encode($file)."</a></nobr></td>";
                    $file_out[$file_count][] = "<td>".$dir_entry["p"]."</td>";
                    if (!$is_windows) {
                        $file_out[$file_count][] = "<td><nobr>".$dir_entry["u"]."</nobr></td>";
                        $file_out[$file_count][] = "<td><nobr>".$dir_entry["g"]."</nobr></td>";
                    }
                    $file_out[$file_count][] = "<td><nobr>".$dir_entry["sizet"]."</nobr></td>";
                    $file_out[$file_count][] = "<td><nobr>".$dir_entry["datet"]."</nobr></td>";
                    $file_out[$file_count][] = "<td>".$dir_entry["extt"]."</td>";
                    // Opções de arquivo
                    if ( is_writable($current_dir.$file) ) $file_out[$file_count][] = "
                                <td align=center><a href=\"javascript:if(confirm('".uppercase(et('Rem'))." \\'".addslashes($file)."\\' ?')) document.location.href='".addslashes($fm_path_info["basename"])."?frame=3&action=8&cmd_arg=".addslashes($file)."&current_dir=".addslashes($current_dir)."'\">".et('Rem')."</a>";
                    else $file_out[$file_count][] = "<td>&nbsp;</td>";
                    if ( is_writable($current_dir.$file) ) $file_out[$file_count][] = "
                                <td align=center><a href=\"javascript:rename('".addslashes($file)."')\">".et('Ren')."</a>";
                    else $file_out[$file_count][] = "<td>&nbsp;</td>";
                    if ( is_readable($current_dir.$file) && (strpos(".wav#.mp3#.mid#.avi#.mov#.mpeg#.mpg#.rm#.iso#.bin#.img#.dll#.psd#.fla#.swf#.class#.ppt#.tif#.tiff#.pcx#.jpg#.gif#.png#.wmf#.eps#.bmp#.msi#.exe#.com#.rar#.tar#.zip#.bz2#.tbz2#.bz#.tbz#.bzip#.gzip#.gz#.tgz#", $dir_entry["ext"]."#" ) === false)) $file_out[$file_count][] = "
                                <td align=center><a href=\"javascript:edit_file_form('".addslashes($file)."')\">".et('Edit')."</a>";
                    else $file_out[$file_count][] = "<td>&nbsp;</td>";
                    if ( is_readable($current_dir.$file) && (strpos(".txt#.sys#.bat#.ini#.conf#.swf#.php#.php3#.asp#.html#.htm#.jpg#.gif#.png#.bmp#", $dir_entry["ext"]."#" ) !== false)) $file_out[$file_count][] = "
                                <td align=center><a href=\"javascript:view_form('".addslashes($file)."');\">".et('View')."</a>";
                    else $file_out[$file_count][] = "<td>&nbsp;</td>";
                    if ( is_readable($current_dir.$file) && strlen($dir_entry["ext"]) && (strpos(".tar#.zip#.bz2#.tbz2#.bz#.tbz#.bzip#.gzip#.gz#.tgz#", $dir_entry["ext"]."#" ) !== false)) $file_out[$file_count][] = "
                                <td align=center><a href=\"javascript:decompress('".addslashes($file)."')\">".et('Decompress')."</a>";
                    else $file_out[$file_count][] = "<td>&nbsp;</td>";
                    if ( is_readable($current_dir.$file) && strlen($dir_entry["ext"]) && (strpos(".exe#.com#.sh#.bat#", $dir_entry["ext"]."#" ) !== false)) $file_out[$file_count][] = "
                                <td align=center><a href=\"javascript:execute_file('".addslashes($file)."')\">".et('Exec')."</a>";
                    else $file_out[$file_count][] = "<td>&nbsp;</td>";
                    if (count($file_out[$file_count])>$max_opt){
                        $max_opt = count($file_out[$file_count]);
                    }
                    $file_count++;
                }
            }
            if ($dir_count){
                $out .= "
                <tr>
                      <td bgcolor=\"#DDDDDD\"><nobr><a href=\"".$fm_path_info["basename"]."?frame=3&or_by=$or1&current_dir=$current_dir\">".et('Name')."</a></nobr></td>
                      <td bgcolor=\"#DDDDDD\"><nobr><a href=\"".$fm_path_info["basename"]."?frame=3&or_by=$or2&current_dir=$current_dir\">".et('Perm')."</a></nobr></td>";
                if (!$is_windows) $out .= "
                      <td bgcolor=\"#DDDDDD\"><nobr><a href=\"".$fm_path_info["basename"]."?frame=3&or_by=$or3&current_dir=$current_dir\">".et('Owner')."</a></td>
                      <td bgcolor=\"#DDDDDD\"><nobr><a href=\"".$fm_path_info["basename"]."?frame=3&or_by=$or4&current_dir=$current_dir\">".et('Group')."</a></nobr></td>";
                $out .= "
                      <td bgcolor=\"#DDDDDD\"><nobr><a href=\"".$fm_path_info["basename"]."?frame=3&or_by=$or5&current_dir=$current_dir\">".et('Size')."</a></nobr></td>
                      <td bgcolor=\"#DDDDDD\"><nobr><a href=\"".$fm_path_info["basename"]."?frame=3&or_by=$or6&current_dir=$current_dir\">".et('Date')."</a></nobr></td>";
                if ($file_count) $out .= "
                      <td bgcolor=\"#DDDDDD\"><nobr><a href=\"".$fm_path_info["basename"]."?frame=3&or_by=$or7&current_dir=$current_dir\">".et('Type')."</a></nobr></td>";
                $out .= "
                      <td bgcolor=\"#DDDDDD\" colspan=50>&nbsp;</td>
                </tr>";

            }
            foreach($dir_out as $k=>$v){
                while (count($dir_out[$k])<$max_opt) {
                    $dir_out[$k][] = "<td>&nbsp;</td>";
                }
                $out .= implode($dir_out[$k]);
                $out .= "</tr>";
            }
            if ($file_count){
                $out .= "
                <tr>
                      <td bgcolor=\"#DDDDDD\"><nobr><a href=\"".$fm_path_info["basename"]."?frame=3&or_by=$or1&current_dir=$current_dir\">".et('Name')."</a></nobr></td>
                      <td bgcolor=\"#DDDDDD\"><nobr><a href=\"".$fm_path_info["basename"]."?frame=3&or_by=$or2&current_dir=$current_dir\">".et('Perm')."</a></nobr></td>";
                if (!$is_windows) $out .= "
                      <td bgcolor=\"#DDDDDD\"><nobr><a href=\"".$fm_path_info["basename"]."?frame=3&or_by=$or3&current_dir=$current_dir\">".et('Owner')."</a></td>
                      <td bgcolor=\"#DDDDDD\"><nobr><a href=\"".$fm_path_info["basename"]."?frame=3&or_by=$or4&current_dir=$current_dir\">".et('Group')."</a></nobr></td>";
                $out .= "
                      <td bgcolor=\"#DDDDDD\"><nobr><a href=\"".$fm_path_info["basename"]."?frame=3&or_by=$or5&current_dir=$current_dir\">".et('Size')."</a></nobr></td>
                      <td bgcolor=\"#DDDDDD\"><nobr><a href=\"".$fm_path_info["basename"]."?frame=3&or_by=$or6&current_dir=$current_dir\">".et('Date')."</a></nobr></td>
                      <td bgcolor=\"#DDDDDD\"><nobr><a href=\"".$fm_path_info["basename"]."?frame=3&or_by=$or7&current_dir=$current_dir\">".et('Type')."</a></nobr></td>
                      <td bgcolor=\"#DDDDDD\" colspan=50>&nbsp;</td>
                </tr>";

            }
            foreach($file_out as $k=>$v){
                while (count($file_out[$k])<$max_opt) {
                    $file_out[$k][] = "<td>&nbsp;</td>";
                }
                $out .= implode($file_out[$k]);
                $out .= "</tr>";
            }
            $out .= "
                <tr>
                <td bgcolor=\"#DDDDDD\" colspan=50><nobr>
                      <input type=\"button\" style=\"width:80\" onclick=\"selectANI(this)\" id=\"ANI1\" value=\"".et('SelAll')."\">
                      <input type=\"button\" style=\"width:80\" onclick=\"selectANI(this)\" value=\"".et('SelInverse')."\">
                      <input type=\"button\" style=\"width:80\" onclick=\"test(4)\" value=\"".et('Rem')."\">
                      <input type=\"button\" style=\"width:80\" onclick=\"sel_dir(5)\" value=\"".et('Copy')."\">
                      <input type=\"button\" style=\"width:80\" onclick=\"sel_dir(6)\" value=\"".et('Move')."\">
                      <input type=\"button\" style=\"width:100\" onclick=\"test_prompt(71)\" value=\"".et('Compress')."\">";
            if (!$is_windows) $out .= "
                      <input type=\"button\" style=\"width:100\" onclick=\"resolve_ids()\" value=\"".et('ResolveIDs')."\">";
            $out .= "
                      <input type=\"button\" style=\"width:100\" onclick=\"chmod_form()\" value=\"".et('Perms')."\">";
            $out .= "
                </nobr></td>
                </tr>";
            $out .= "
            </form>";
            $out .= "
                <tr><td bgcolor=\"#DDDDDD\" colspan=50><b>$dir_count ".et('Dir_s')." ".et('And')." $file_count ".et('File_s')." = ".format_size($total_size)."</td></tr>";
            if ($quota_mb) {
                $out .= "
                <tr><td bgcolor=\"#DDDDDD\" colspan=50><b>".et('Partition').": ".format_size(($quota_mb*1024*1024))." ".et('Total')." - ".format_size(($quota_mb*1024*1024)-total_size($fm_current_root))." ".et('Free')."</td></tr>";
            } else {
                $out .= "
                <tr><td bgcolor=\"#DDDDDD\" colspan=50><b>".et('Partition').": ".format_size(disk_total_space($current_dir))." ".et('Total')." - ".format_size(disk_free_space($current_dir))." ".et('Free')."</td></tr>";
            }
            $tf = getmicrotime();
            $tt = ($tf - $ti);
            $out .= "
                <tr><td bgcolor=\"#DDDDDD\" colspan=50><b>".et('RenderTime').": ".substr($tt,0,strrpos($tt,".")+5)." ".et('Seconds')."</td></tr>";
            $out .= "
            <script language=\"Javascript\" type=\"text/javascript\">
            <!--
                update_sel_status();
            //-->
            </script>";
        } else {
            $out .= "
            <tr>
            <td bgcolor=\"#DDDDDD\" width=\"1%\">$uplink<td bgcolor=\"#DDDDDD\" colspan=50><nobr><a href=\"".$fm_path_info["basename"]."?frame=3&current_dir=$current_dir\">$current_dir</a></nobr>
            <tr><td bgcolor=\"#DDDDDD\" colspan=50>".et('EmptyDir').".</tr>";
        }
    } else $out .= "<tr><td><font color=red>".et('IOError').".<br>$current_dir</font>";
    $out .= "</table>";
    echo $out;
}
function upload_form(){
    global $_FILES,$current_dir,$dir_dest,$quota_mb,$fm_path_info;
    html_header();
    echo "<body marginwidth=\"0\" marginheight=\"0\">";
    if (count($_FILES)==0){
        echo "
        <table height=\"100%\" border=0 cellspacing=0 cellpadding=2 style=\"padding:5px;\">
        <form name=\"upload_form\" action=\"".$fm_path_info["basename"]."\" method=\"post\" ENCTYPE=\"multipart/form-data\">
        <input type=hidden name=dir_dest value=\"$current_dir\">
        <input type=hidden name=action value=10>
        <tr><td colspan=2 align=left><nobr><b>".et('Destination').": $current_dir</b></nobr></td></tr>
        <tr><td width=1 align=right><b>".et('File_s').":<td><nobr><input type=\"file\" id=\"upfiles\" name=\"upfiles[]\" multiple onchange=\"upfiles_update(this);\"></nobr></td></tr>
        <tr><td>&nbsp;<td><input type=button value=\"".et('Send')."\" onclick=\"upfiles_send()\"></nobr></td></tr>
        <tr><td colspan=2 align=left><div id=\"upfileslist\"></div></td></tr>
        </form>
        </table>
        <script language=\"Javascript\" type=\"text/javascript\">
        <!--
            foi = false;
            function upfiles_update(fileinput){
                var files = document.getElementById(\"upfiles\").files;
                var text = '';
                if (files.length > 1) {
                    for (var i = 0; i < files.length; ++i) {
                        text += '<nobr>' + (i+1) + ' - ' + files[i].name + '</nobr><br>';
                    }
                }
                document.getElementById(\"upfileslist\").innerHTML = text;
            }
            function upfiles_send(){
                if(true){
                    if (foi) alert('".et('SendingForm')."...');
                    else {
                        foi = true;
                        document.upload_form.submit();
                    }
                } else alert('".et('NoFileSel').".');
            }
        //-->
        </script>";
    } else {
        $out = "<tr><th colspan=2>".et('UploadEnd')."</th></tr>
                <tr><td colspan=2 align=left><nobr><b>".et('Destination').": $current_dir</b></nobr></td></tr>";
        $files = array();
        if (is_array($_FILES['upfiles'])){
            // Check and re-arrange multi-upload array()
            if (is_array($_FILES['upfiles']['name'])){
                for($i=0;$i<count($_FILES['upfiles']['name']);$i++){
                    if ($_FILES['upfiles']['error'][$i] === 0) $files[] = array(
                        'name' => $_FILES['upfiles']['name'][$i],
                        'tmp_name' => $_FILES['upfiles']['tmp_name'][$i],
                        'size' => $_FILES['upfiles']['size'][$i],
                        'type' => $_FILES['upfiles']['type'][$i],
                        'error' => $_FILES['upfiles']['error'][$i]
                    );
                }
            } else {
                foreach ($_FILES['upfiles'] as $file){
                    if ($file['error'] === 0) $files[] = $file;
                }
            }
        }
        $i=1;
        foreach ($files as $file) {
            $filename = $file["name"];
            $temp_file = $file["tmp_name"];
            if (strlen($filename)) {
                $resul = save_upload($temp_file,$filename,$dir_dest);
                switch($resul){
                    case 1:
                        $out .= "<tr><td align=right>".$i." - <font color=green>".et('FileSent')."</font>:</td><td>".$filename."</td></tr>\n";
                        break;
                    case 2:
                        $out .= "<tr><td align=right>".$i." - <font color=red>".et('IOError')."</font>:</td><td>".$filename."</td></tr>\n";
                        break;
                    case 3:
                        $out .= "<tr><td align=right>".$i." - <font color=red>".et('SpaceLimReached')." ($quota_mb Mb)</font>:</td><td>".$filename."</td></tr>\n";
                        break;
                    case 4:
                        $out .= "<tr><td align=right>".$i." - <font color=red>".et('InvExt')."</font>:</td><td>".$filename."</td></tr>\n";
                        break;
                    case 5:
                        $out .= "<tr><td align=right>".$i." - <font color=red>".et('FileNoOverw')."</font>:</td><td>".$filename."</td></tr>\n";
                        break;
                    case 6:
                        $out .= "<tr><td align=right>".$i." - <font color=green>".et('FileOverw')."</font>:</td><td>".$filename."</td></tr>\n";
                        break;
                    default:
                        $out .= "<tr><td align=right>".$i." - <font color=green>".et('FileIgnored')."</font>:</td><td>".$filename."</td></tr>\n";
                }
                $i++;
            }
        }
        echo "<table height=\"100%\" border=0 cellspacing=0 cellpadding=2 style=\"padding:5px;\">".$out."</table>";
    }
    echo "</body>\n</html>";
}
function chmod_form(){
    html_header("
    <script language=\"Javascript\" type=\"text/javascript\">
    <!--
    function octalchange() {
        var val = document.chmod_form.t_total.value;
        var stickybin = parseInt(val.charAt(0)).toString(2);
        var ownerbin = parseInt(val.charAt(1)).toString(2);
        while (ownerbin.length<3) { ownerbin=\"0\"+ownerbin; };
        var groupbin = parseInt(val.charAt(2)).toString(2);
        while (groupbin.length<3) { groupbin=\"0\"+groupbin; };
        var otherbin = parseInt(val.charAt(3)).toString(2);
        while (otherbin.length<3) { otherbin=\"0\"+otherbin; };
        document.chmod_form.sticky.checked = parseInt(stickybin.charAt(0));
        document.chmod_form.owner4.checked = parseInt(ownerbin.charAt(0));
        document.chmod_form.owner2.checked = parseInt(ownerbin.charAt(1));
        document.chmod_form.owner1.checked = parseInt(ownerbin.charAt(2));
        document.chmod_form.group4.checked = parseInt(groupbin.charAt(0));
        document.chmod_form.group2.checked = parseInt(groupbin.charAt(1));
        document.chmod_form.group1.checked = parseInt(groupbin.charAt(2));
        document.chmod_form.other4.checked = parseInt(otherbin.charAt(0));
        document.chmod_form.other2.checked = parseInt(otherbin.charAt(1));
        document.chmod_form.other1.checked = parseInt(otherbin.charAt(2));
        calc_chmod(1);
    };
    function calc_chmod(nototals) {
      var users = new Array(\"owner\", \"group\", \"other\");
      var totals = new Array(\"\",\"\",\"\");
      var syms = new Array(\"\",\"\",\"\");

        for (var i=0; i<users.length; i++)
        {
            var user=users[i];
            var field4 = user + \"4\";
            var field2 = user + \"2\";
            var field1 = user + \"1\";
            var symbolic = \"sym_\" + user;
            var number = 0;
            var sym_string = \"\";
            var sticky = \"0\";
            var sticky_sym = \" \";
            if (document.chmod_form.sticky.checked){
                sticky = \"1\";
                sticky_sym = \"t\";
            }
            if (document.chmod_form[field4].checked == true) { number += 4; }
            if (document.chmod_form[field2].checked == true) { number += 2; }
            if (document.chmod_form[field1].checked == true) { number += 1; }

            if (document.chmod_form[field4].checked == true) {
                sym_string += \"r\";
            } else {
                sym_string += \"-\";
            }
            if (document.chmod_form[field2].checked == true) {
                sym_string += \"w\";
            } else {
                sym_string += \"-\";
            }
            if (document.chmod_form[field1].checked == true) {
                sym_string += \"x\";
            } else {
                sym_string += \"-\";
            }

            totals[i] = totals[i]+number;
            syms[i] =  syms[i]+sym_string;

      };
        if (!nototals) document.chmod_form.t_total.value = sticky + totals[0] + totals[1] + totals[2];
        document.chmod_form.sym_total.value = syms[0] + syms[1] + syms[2] + sticky_sym;
    }
    function sticky_change() {
        document.chmod_form.sticky.checked = !(document.chmod_form.sticky.checked);
    }
    function apply_chmod() {
        if (confirm('".et('AlterPermTo')." \\' '+document.chmod_form.t_total.value+' \\' ?\\n')){
            window.top.frame3.set_chmod_arg(document.chmod_form.t_total.value);
            window.top.frame3.closeModalWindow();
        }
    }
    window.onload=octalchange
    //-->
    </script>");
    echo "<body marginwidth=\"0\" marginheight=\"0\">
    <form name=\"chmod_form\">
    <table border=\"0\" cellspacing=\"0\" cellpadding=\"4\" align=center style=\"padding:5px;\">
    <tr align=\"left\" valign=\"middle\">
    <td><input type=\"text\" name=\"t_total\" value=\"0755\" size=\"4\" onKeyUp=\"octalchange()\"> </td>
    <td><input type=\"text\" name=\"sym_total\" value=\"\" size=\"12\" readonly=\"1\"></td>
    </tr>
    </table>
    <table cellpadding=\"2\" cellspacing=\"0\" border=\"0\" align=center>
    <tr bgcolor=\"#333333\">
    <td width=\"60\" align=\"left\"> </td>
    <td width=\"55\" align=\"center\" style=\"color:#FFFFFF\"><b>".et('Owner')."
    </b></td>
    <td width=\"55\" align=\"center\" style=\"color:#FFFFFF\"><b>".et('Group')."
    </b></td>
    <td width=\"55\" align=\"center\" style=\"color:#FFFFFF\"><b>".et('Other')."
    <b></td>
    </tr>
    <tr bgcolor=\"#DDDDDD\">
    <td width=\"60\" align=\"left\" nowrap bgcolor=\"#FFFFFF\">".et('Read')."</td>
    <td width=\"55\" align=\"center\" bgcolor=\"#EEEEEE\">
    <input type=\"checkbox\" name=\"owner4\" value=\"4\" onclick=\"calc_chmod()\">
    </td>
    <td width=\"55\" align=\"center\" bgcolor=\"#FFFFFF\"><input type=\"checkbox\" name=\"group4\" value=\"4\" onclick=\"calc_chmod()\">
    </td>
    <td width=\"55\" align=\"center\" bgcolor=\"#EEEEEE\">
    <input type=\"checkbox\" name=\"other4\" value=\"4\" onclick=\"calc_chmod()\">
    </td>
    </tr>
    <tr bgcolor=\"#DDDDDD\">
    <td width=\"60\" align=\"left\" nowrap bgcolor=\"#FFFFFF\">".et('Write')."</td>
    <td width=\"55\" align=\"center\" bgcolor=\"#EEEEEE\">
    <input type=\"checkbox\" name=\"owner2\" value=\"2\" onclick=\"calc_chmod()\"></td>
    <td width=\"55\" align=\"center\" bgcolor=\"#FFFFFF\"><input type=\"checkbox\" name=\"group2\" value=\"2\" onclick=\"calc_chmod()\">
    </td>
    <td width=\"55\" align=\"center\" bgcolor=\"#EEEEEE\">
    <input type=\"checkbox\" name=\"other2\" value=\"2\" onclick=\"calc_chmod()\">
    </td>
    </tr>
    <tr bgcolor=\"#DDDDDD\">
    <td width=\"60\" align=\"left\" nowrap bgcolor=\"#FFFFFF\">".et('Exec')."</td>
    <td width=\"55\" align=\"center\" bgcolor=\"#EEEEEE\">
    <input type=\"checkbox\" name=\"owner1\" value=\"1\" onclick=\"calc_chmod()\">
    </td>
    <td width=\"55\" align=\"center\" bgcolor=\"#FFFFFF\"><input type=\"checkbox\" name=\"group1\" value=\"1\" onclick=\"calc_chmod()\">
    </td>
    <td width=\"55\" align=\"center\" bgcolor=\"#EEEEEE\">
    <input type=\"checkbox\" name=\"other1\" value=\"1\" onclick=\"calc_chmod()\">
    </td>
    </tr>
    </table>
    <table border=\"0\" cellspacing=\"0\" cellpadding=\"4\" align=center>
    <tr><td colspan=2><input type=checkbox name=sticky value=\"1\" onclick=\"calc_chmod()\"> <a href=\"JavaScript:sticky_change();\">".et('StickyBit')."</a><td colspan=2 align=right><input type=button value=\"".et('Apply')."\" onClick=\"apply_chmod()\"></tr>
    </table>
    </form>
    </body>\n</html>";
}
function get_mime_type($ext = ''){
    $mimes = array(
      'hqx'   =>  'application/mac-binhex40',
      'cpt'   =>  'application/mac-compactpro',
      'doc'   =>  'application/msword',
      'bin'   =>  'application/macbinary',
      'dms'   =>  'application/octet-stream',
      'lha'   =>  'application/octet-stream',
      'lzh'   =>  'application/octet-stream',
      'exe'   =>  'application/octet-stream',
      'class' =>  'application/octet-stream',
      'psd'   =>  'application/octet-stream',
      'so'    =>  'application/octet-stream',
      'sea'   =>  'application/octet-stream',
      'dll'   =>  'application/octet-stream',
      'oda'   =>  'application/oda',
      'pdf'   =>  'application/pdf',
      'ai'    =>  'application/postscript',
      'eps'   =>  'application/postscript',
      'ps'    =>  'application/postscript',
      'smi'   =>  'application/smil',
      'smil'  =>  'application/smil',
      'mif'   =>  'application/vnd.mif',
      'xls'   =>  'application/vnd.ms-excel',
      'ppt'   =>  'application/vnd.ms-powerpoint',
      'pptx'  =>  'application/vnd.ms-powerpoint',
      'wbxml' =>  'application/vnd.wap.wbxml',
      'wmlc'  =>  'application/vnd.wap.wmlc',
      'dcr'   =>  'application/x-director',
      'dir'   =>  'application/x-director',
      'dxr'   =>  'application/x-director',
      'dvi'   =>  'application/x-dvi',
      'gtar'  =>  'application/x-gtar',
      'php'   =>  'application/x-httpd-php',
      'php4'  =>  'application/x-httpd-php',
      'php3'  =>  'application/x-httpd-php',
      'phtml' =>  'application/x-httpd-php',
      'phps'  =>  'application/x-httpd-php-source',
      'js'    =>  'application/x-javascript',
      'swf'   =>  'application/x-shockwave-flash',
      'sit'   =>  'application/x-stuffit',
      'tar'   =>  'application/x-tar',
      'tgz'   =>  'application/x-tar',
      'xhtml' =>  'application/xhtml+xml',
      'xht'   =>  'application/xhtml+xml',
      'zip'   =>  'application/zip',
      'mid'   =>  'audio/midi',
      'midi'  =>  'audio/midi',
      'mpga'  =>  'audio/mpeg',
      'mp2'   =>  'audio/mpeg',
      'mp3'   =>  'audio/mpeg',
      'aif'   =>  'audio/x-aiff',
      'aiff'  =>  'audio/x-aiff',
      'aifc'  =>  'audio/x-aiff',
      'ram'   =>  'audio/x-pn-realaudio',
      'rm'    =>  'audio/x-pn-realaudio',
      'rpm'   =>  'audio/x-pn-realaudio-plugin',
      'ra'    =>  'audio/x-realaudio',
      'rv'    =>  'video/vnd.rn-realvideo',
      'wav'   =>  'audio/x-wav',
      'bmp'   =>  'image/bmp',
      'gif'   =>  'image/gif',
      'jpeg'  =>  'image/jpeg',
      'jpg'   =>  'image/jpeg',
      'jpe'   =>  'image/jpeg',
      'png'   =>  'image/png',
      'tiff'  =>  'image/tiff',
      'tif'   =>  'image/tiff',
      'css'   =>  'text/css',
      'html'  =>  'text/html',
      'htm'   =>  'text/html',
      'shtml' =>  'text/html',
      'txt'   =>  'text/plain',
      'text'  =>  'text/plain',
      'log'   =>  'text/plain',
      'rtx'   =>  'text/richtext',
      'rtf'   =>  'text/rtf',
      'xml'   =>  'text/xml',
      'xsl'   =>  'text/xml',
      'mpeg'  =>  'video/mpeg',
      'mpg'   =>  'video/mpeg',
      'mpe'   =>  'video/mpeg',
      'qt'    =>  'video/quicktime',
      'mov'   =>  'video/quicktime',
      'avi'   =>  'video/x-msvideo',
      'movie' =>  'video/x-sgi-movie',
      'doc'   =>  'application/msword',
      'docx'  =>  'application/msword',
      'word'  =>  'application/msword',
      'xl'    =>  'application/excel',
      'xls'   =>  'application/excel',
      'xlsx'  =>  'application/excel',
      'eml'   =>  'message/rfc822'
    );
    return (!isset($mimes[lowercase($ext)])) ? 'application/octet-stream' : $mimes[lowercase($ext)];
}
function view_form(){
    global $doc_root,$fm_path_info,$url_info,$current_dir,$is_windows,$filename,$passthru;
    if (intval($passthru)){
        $file = $current_dir.$filename;
        if(file_exists($file)){
            $is_denied = false;
            foreach($download_ext_filter as $key=>$ext){
                if (eregi($ext,$filename)){
                    $is_denied = true;
                    break;
                }
            }
            if (!$is_denied){
                if ($fh = fopen("$file", "rb")){
                    fclose($fh);
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    $ctype = get_mime_type($ext);
                    if ($ctype == "application/octet-stream") $ctype = "text/plain";
                    header("Pragma: public");
                    header("Expires: 0");
                    header("Connection: close");
                    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                    header("Cache-Control: public");
                    header("Content-Description: File Transfer");
                    header("Content-Type: ".$ctype);
                    header("Content-Disposition: inline; filename=\"".pathinfo($file, PATHINFO_BASENAME)."\";");
                    header("Content-Transfer-Encoding: binary");
                    header("Content-Length: ".filesize($file));
                    @readfile($file);
                    exit();
                } else alert(et('ReadDenied').": ".$file);
            } else alert(et('ReadDenied').": ".$file);
        } else alert(et('FileNotFound').": ".$file);
        echo "
        <script language=\"Javascript\" type=\"text/javascript\">
        <!--
            window.close();
        //-->
        </script>";
    } else {
        html_header();
        echo "<body marginwidth=\"0\" marginheight=\"0\">";
        $is_reachable_thru_webserver = (stristr($current_dir,$doc_root)!==false);
        if ($is_reachable_thru_webserver){
            $url = $url_info["scheme"]."://".$url_info["host"];
            if (strlen($url_info["port"])) $url .= ":".$url_info["port"];
            // Malditas variaveis de sistema!! No windows doc_root é sempre em lowercase... cadê o str_ireplace() ??
            $url .= str_replace($doc_root,"","/".$current_dir).$filename;
        } else {
            $url = addslashes($fm_path_info["basename"])."?action=4&current_dir=".addslashes($current_dir)."&filename=".addslashes($filename)."&passthru=1";
        }
        echo "
        <script language=\"Javascript\" type=\"text/javascript\">
        <!--
            document.location.href='$url';
        //-->
        </script>
        </body>\n</html>";
    }
}
function edit_file_form(){
    global $current_dir,$filename,$file_data,$save_file,$fm_path_info;
    $file = $current_dir.$filename;
    if ($save_file){
        $fh=fopen($file,"w");
        fputs($fh,$file_data,strlen($file_data));
        fclose($fh);
    }
    $fh=fopen($file,"r");
    $file_data=fread($fh, filesize($file));
    fclose($fh);
    html_header();
    echo "<body marginwidth=\"0\" marginheight=\"0\">
    <table border=0 cellspacing=0 cellpadding=5 align=center style=\"padding:5px;\">
    <form name=\"edit_form\" action=\"".$fm_path_info["basename"]."\" method=\"post\">
    <input type=hidden name=action value=\"7\">
    <input type=hidden name=save_file value=\"1\">
    <input type=hidden name=current_dir value=\"$current_dir\">
    <input type=hidden name=filename value=\"$filename\">
    <tr><th colspan=2>".$file."</th></tr>
    <tr><td colspan=2><textarea name=file_data style='width:1000px;height:680px;border: 1px solid #ccc;'>".html_encode($file_data)."</textarea></td></tr>
    <tr><td><input type=button value=\"".et('Refresh')."\" onclick=\"document.edit_form_refresh.submit()\"></td><td align=right><input type=button value=\"".et('SaveFile')."\" onclick=\"go_save()\"></td></tr>
    </form>
    <form name=\"edit_form_refresh\" action=\"".$fm_path_info["basename"]."\" method=\"post\">
    <input type=hidden name=action value=\"7\">
    <input type=hidden name=current_dir value=\"$current_dir\">
    <input type=hidden name=filename value=\"$filename\">
    </form>
    </table>
    <script language=\"Javascript\" type=\"text/javascript\">
    <!--
        function go_save(){";
    if (is_writable($file)) {
        echo "
        document.edit_form.submit();";
    } else {
        echo "
        if(confirm('".et('ConfTrySave')." ?')) document.edit_form.submit();";
    }
    echo "
        }
        window.focus();
    //-->
    </script>
    </body>\n</html>";
}
function config_form(){
    global $cfg;
    global $current_dir,$fm_file,$doc_root,$fm_path_info,$fm_current_root,$lang,$error_reporting,$version,$sys_lang;
    global $config_action,$newpassvar,$newlang,$newerror,$newfm_root;
    $reload = false;
    switch ($config_action){
        case 2:
            if ($cfg->data['lang'] != $newlang){
                $cfg->data['lang'] = $newlang;
                $lang = $newlang;
            }
            if ($cfg->data['error_reporting'] != $newerror){
                $cfg->data['error_reporting'] = $newerror;
                $error_reporting = $newerror;
            }
            $newfm_root = format_path($newfm_root);
            if ($cfg->data['fm_root'] != $newfm_root){
                $cfg->data['fm_root'] = $newfm_root;
                if (strlen($newfm_root)) $current_dir = $newfm_root;
                else {
                    if ($is_windows) $current_dir = ucfirst($fm_path_info["dirname"]."/");
                    else $current_dir = $doc_root."/";
                }
            }
            if (isset($GLOBALS[$newpassvar])){
                $cfg->data['auth_pass'] = md5($GLOBALS[$newpassvar]);
                setcookie("loggedon", $cfg->data['auth_pass'], 0 , "/");
            }
            $cfg->save();
            $reload = true;
        break;
    }
    html_header('<script type="text/javascript" src="'.$fm_path_info["basename"].'?action=99&filename=jquery-1.11.1.min.js"></script>');
    echo "<body marginwidth=\"0\" marginheight=\"0\">\n";
    if ($reload){
        echo "
        <script language=\"Javascript\" type=\"text/javascript\">
        <!--
            window.setTimeout(function(){
                window.top.document.location.href='".$fm_path_info["basename"]."';
            },500);
        //-->
        </script>";
    } else {
        $newpassvar = "newpass".time();
        echo "
        <form name=\"config_form\" action=\"".$fm_path_info["basename"]."\" method=\"post\">
        <input type=hidden name=\"newpassvar\" value=\"".$newpassvar."\">
        <table border=0 cellspacing=0 cellpadding=5 align=center width=\"100%\" style=\"padding:5px;\">
        <tr><td align=right width=\"1%\">".et('Version').":<td>$version (".get_size($fm_file).")</td></tr>
        <tr><td align=right>".et('Website').":<td><a href=\"http://phpfm.sf.net\" target=\"_blank\">http://phpfm.sf.net</a></td></tr>
        <input type=hidden name=action value=2>
        <input type=hidden name=config_action value=0>
        <tr><td align=right width=1><nobr>".et('DocRoot').":</nobr><td>".$doc_root."</td></tr>
        <tr><td align=right><nobr>".et('FLRoot').":</nobr><td><input type=text placeholder=\"".$fm_current_root."\" style=\"width:400px\" name=newfm_root value=\"".$cfg->data['fm_root']."\" onkeypress=\"enterSubmit(event,'test_config_form(2)')\"></td></tr>
        <tr><td align=right>".et('Lang').":<td>
        <select name=newlang style=\"width:406px\">
            <option value=''>System Default
            <option value='ca'>Catalan - by Pere Borràs AKA @Norl
            <option value='nl'>Dutch - by Leon Buijs
            <option value='en'>English - by Fabricio Seger Kolling
            <option value='fr'>French - by Jean Bilwes
            <option value='fr2'>French - by Sharky
            <option value='fr3'>French - by Michel Lainey
            <option value='de'>German - by Guido Ogrzal
            <option value='de2'>German - by AXL
            <option value='de3'>German - by Mathias Rothe
            <option value='it'>Italian - by Valerio Capello
            <option value='it2'>Italian - by Federico Corrà
            <option value='it3'>Italian - by Luca Zorzi
            <option value='it4'>Italian - by Gianni
            <option value='ko'>Korean - by Airplanez
            <option value='pt'>Portuguese - by Fabricio Seger Kolling
            <option value='es'>Spanish - by Sh Studios
            <option value='ru'>Russian - by Евгений Рашев
            <option value='tr'>Turkish - by Necdet Yazilimlari
        </select></td></tr>
        <tr><td align=right>".et('ErrorReport').":<td><select name=newerror style=\"width:406px\">
        <option value=\"0\">Disabled
        <option value=\"1\">Show Errors
        <option value=\"2\">Show Errors, Warnings and Notices
        </select></td></tr>";
        if ($cfg->data['auth_pass'] == md5('')) {
            echo "
            <tr><td align=right>".et('Pass').":<td><input type=button value=\"".et('SetPass')."\" onclick=\"$(this).hide(); $('#".$newpassvar."').show(); $('#".$newpassvar."').val(''); $('#".$newpassvar."').focus();\">
            <input type=password style=\"display:none; width:400px\" name=\"".$newpassvar."\" id=\"".$newpassvar."\" autocomplete=\"off\" value=\"\" onkeypress=\"enterSubmit(event,'test_config_form(2)')\">
            </td></tr>";
        } else {
            echo "
            <tr><td align=right>".et('Pass').":<td><input type=button value=\"".et('ChangePass')."\" onclick=\"$(this).hide(); $('#".$newpassvar."').show(); $('#".$newpassvar."').val(''); $('#".$newpassvar."').focus();\">
            <input type=password style=\"display:none; width:400px\" name=\"".$newpassvar."\" id=\"".$newpassvar."\" autocomplete=\"off\" value=\"\" onkeypress=\"enterSubmit(event,'test_config_form(2)')\">
            </td></tr>";
        }
        echo "
        <tr><td>&nbsp;<td><input type=button value=\"".et('SaveConfig')."\" onclick=\"test_config_form(2)\"></td></tr>
        </form>
        </table>
        <script language=\"Javascript\" type=\"text/javascript\">
        <!--
            function set_select(sel,val){
                for(var x=0;x<sel.length;x++){
                    if(sel.options[x].value==val){
                        sel.options[x].selected=true;
                        break;
                    }
                }
            }
            set_select(document.config_form.newlang,'".$cfg->data['lang']."');
            set_select(document.config_form.newerror,'".$cfg->data['error_reporting']."');
            function test_config_form(arg){
                document.config_form.config_action.value = arg;
                document.config_form.submit();
            }
        //-->
        </script>";
    }
    echo "
    </body>\n</html>";
}
function ping($host) {
    global $g_icmp_error;
    $g_icmp_error = "No Error";
    $timeout = 1;
    $port = 0;
    $datasize = 64;
    $ident = array(ord('J'), ord('C'));
    $seq   = array(rand(0, 255), rand(0, 255));
    $packet = '';
    $packet .= chr(8); // type = 8 : request
    $packet .= chr(0); // code = 0
    $packet .= chr(0); // checksum init
    $packet .= chr(0); // checksum init
    $packet .= chr($ident[0]); // identifier
    $packet .= chr($ident[1]); // identifier
    $packet .= chr($seq[0]); // seq
    $packet .= chr($seq[1]); // seq
    for ($i = 0; $i < $datasize; $i++)
    $packet .= chr(0);
    $chk = icmp_checksum($packet);
    $packet[2] = $chk[0]; // checksum init
    $packet[3] = $chk[1]; // checksum init
    $sock = socket_create(AF_INET, SOCK_RAW,  getprotobyname('icmp'));
    if ($sock === false) {
        $g_icmp_error = socket_strerror(socket_last_error());
        return false;
    }
    $time_start = microtime();
    socket_sendto($sock, $packet, strlen($packet), 0, $host, $port);
    $read   = array($sock);
    $write  = NULL;
    $except = NULL;
    $select = socket_select($read, $write, $except, 0, $timeout * 1000);
    if ($select === NULL) {
        $g_icmp_error = "Select Error";
        socket_close($sock);
        return -1;
    } elseif ($select === 0) {
        $g_icmp_error = "Timeout";
        socket_close($sock);
        return -1;
    }
    $recv = '';
    $time_stop = microtime();
    socket_recvfrom($sock, $recv, 65535, 0, $host, $port);
    $recv = unpack('C*', $recv);
    if ($recv[10] !== 1) // ICMP proto = 1
    {
    $g_icmp_error = "Not ICMP packet";
    socket_close($sock);
    return -1;
    }
    if ($recv[21] !== 0) // ICMP response = 0
    {
    $g_icmp_error = "Not ICMP response";
    socket_close($sock);
    return -1;
    }
    if ($ident[0] !== $recv[25] || $ident[1] !== $recv[26])
    {
    $g_icmp_error = "Bad identification number";
    socket_close($sock);
    return -1;
    }
    if ($seq[0] !== $recv[27] || $seq[1] !== $recv[28])
    {
    $g_icmp_error = "Bad sequence number";
    socket_close($sock);
    return -1;
    }
    $ms = ($time_stop - $time_start) * 1000;
    if ($ms < 0)
    {
    $g_icmp_error = "Response too long";
    $ms = -1;
    }
    socket_close($sock);
    return number_format((float)$ms, 2, '.', '');
}
function icmp_checksum($data) {
    $bit = unpack('n*', $data);
    $sum = array_sum($bit);
    if (strlen($data) % 2) {
        $temp = unpack('C*', $data[strlen($data) - 1]);
        $sum += $temp[1];
    }
    $sum = ($sum >> 16) + ($sum & 0xffff);
    $sum += ($sum >> 16);
    return pack('n*', ~$sum);
}
function portscan($host,$ports=false){
    $resul = '';
    $timeout = 1;
    if (!is_array($ports)) {
        $ports = array();
        $ports[] = array(80,"HTTPD");
        //$ports[] = array(8080,"HTTPD");
        $ports[] = array(21,"FTP");
        $ports[] = array(22,"SSH");
        $ports[] = array(25,"SMTP");
        $ports[] = array(110,"POP3");
        $ports[] = array(137,"NETBIOS-NS");
        //$ports[] = array(138,"NETBIOS-DGM");
        //$ports[] = array(139,"NETBIOS-SSN");
        $ports[] = array(143,"IMAP");
        //$ports[] = array(389,"NETBIOS-LDAP");
        //$ports[] = array(445,"NETBIOS-CIFS");
        $ports[] = array(5900,"VNC");
        $ports[] = array(3306,"MYSQL");
        $ports[] = array(3389,"REMOTE DESKTOP");
    }
    for($i=0;$i<count($ports);$i++) {
        $port_service = '';
        if (is_array($ports[$i])) {
            $port = $ports[$i][0];
            $port_service = ' = '.$ports[$i][1];
        } else {
            $port = $ports[$i];
        }
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if($fp){
            $resul .= "&nbsp;&nbsp;&nbsp;&nbsp;Port: ".$port.$port_service."<br>\n";
            fclose($fp);
        }
    }
    return $resul;
}
function portscan_form(){
    global $cfg;
    global $current_dir,$fm_file,$doc_root,$fm_path_info,$fm_current_root;
    global $ip,$lan_ip;
    global $portscan_action, $g_icmp_error;
    switch ($portscan_action){
        case 1:
            html_header();
            echo "<body marginwidth=\"5\" marginheight=\"5\" style=\"background-color:#fff\">";
            $hosts_found = array();
            $hosts_miss = array();
            $m = explode(".",$lan_ip);
            $inet = $m[0].".".$m[1].".".$m[2].".";
            $max_hip = 254;
            echo "Searching hosts from ".$inet."1 to ".$inet.".".$max_hip."<br>\n";
            for ($hip=1;$hip<=$max_hip;$hip++){
                $host = $inet.$hip;
                $pingTime = ping($host);
                if ($pingTime>0) {
                    $hosts_found[] = "Ping: ".$host." = ".$pingTime."ms";
                    $hosts_found[] = portscan($host)."<br>\n";
                } else {
                    $hosts_miss[] = "Ping: ".$host." = ".$g_icmp_error;
                }
            }
            if (count($hosts_found)) echo implode($hosts_found,"<br>\n");
            else {
                echo "<br>\n";
                echo "Result: No hosts found.<br>Maybe the server does not allow local network access using PHP sockets.<br>Good, this was major security problem on older PHP versions.<br>\n";
                echo "<br>\n";
                if (count($hosts_miss)) echo implode($hosts_miss,"<br>\n");
            }
            echo "</body>\n</html>";
            die();
        break;
    }
    html_header();
    echo "<body marginwidth=\"0\" marginheight=\"0\">
    <style>
        #portscanIframe {
            background: #FFF;
            width: 100%;
            height: 485px;
            overflow-y: scroll;
            overflow-x: auto;
            border: 1px solid #ccc;
            box-shadow: 0 0 3px rgba(0, 0, 0, 0.15);
        }
    </style>
    <table border=0 cellspacing=0 cellpadding=5 align=center width=\"100%\" height=\"100%\" style=\"padding:5px;\">
    <form name=\"portscan_form\" action=\"".$fm_path_info["basename"]."\" method=\"post\" target=\"portscanIframe\">
    <input type=hidden name=action value=12>
    <input type=hidden name=portscan_action value=0>
    <tr><td align=right width=1><nobr>Your IP:</nobr><td>".$ip."</td></tr>";
    if (strlen($lan_ip)) echo "<tr><td align=right width=1><nobr>Server Lan IP:</nobr><td>".$lan_ip."</td></tr>";
    echo "
    <tr><td>&nbsp;</td><td><input type=button value=\"".et('Exec')."\" onclick=\"test_portscan_form(1)\"></td></tr>
    <tr><td colspan=2><iframe id=\"portscanIframe\" name=\"portscanIframe\" src=\"\" scrolling=\"yes\" frameborder=\"0\"></iframe></td></tr>
    </form>
    </table>
    <script language=\"Javascript\" type=\"text/javascript\">
    <!--
        function test_portscan_form(arg){
            document.portscan_form.portscan_action.value = arg;
            document.portscan_form.submit();
        }
    //-->
    </script>
    ";
    echo "</body>\n</html>";
}
// +--------------------------------------------------
// | Shell Form Functions
// +--------------------------------------------------
function error_handler($err, $message, $file, $line) {
    global $stop;
    $stop = true;
    $content = explode("\n", file_get_contents($file));
    header('Content-Type: application/json');
    $id = extract_id(); // don't need to parse
    $error = array(
       "code" => 100,
       "message" => "Server error",
       "error" => array(
          "name" => "PHPErorr",
          "code" => $err,
          "message" => $message,
          "file" => $file,
          "at" => $line,
          "line" => $content[$line-1]));
    ob_end_clean();
    echo response(null, $id, $error);
    exit();
}
class JsonRpcExeption extends Exception {
    function __construct($code, $message) {
        $this->code = $code;
        Exception::__construct($message);
    }
    function code() {
        return $this->code;
    }
}
function json_error() {
    switch (json_last_error()) {
    case JSON_ERROR_NONE:
        return 'No error has occurred';
    case JSON_ERROR_DEPTH:
        return 'The maximum stack depth has been exceeded';
    case JSON_ERROR_CTRL_CHAR:
        return 'Control character error, possibly incorrectly encoded';
    case JSON_ERROR_SYNTAX:
        return 'Syntax error';
    case JSON_ERROR_UTF8:
        return 'Malformed UTF-8 characters, possibly incorrectly encoded';
    }
}
function get_raw_post_data() {
    if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
        return $GLOBALS['HTTP_RAW_POST_DATA'];
    } else {
        return file_get_contents('php://input');
    }
}
function has_field($object, $field) {
    return array_key_exists($field, get_object_vars($object));
}
function get_field($object, $field, $default) {
    $array = get_object_vars($object);
    if (isset($array[$field])) {
        return $array[$field];
    } else {
        return $default;
    }
}
function response($result, $id, $error) {
    if ($error) {
        $error['name'] = 'JSONRPCError';
    }
    return json_encode(array("jsonrpc" => "2.0",
                             'result' => $result,
                             'id' => $id,
                             'error'=> $error));
}
function extract_id() {
    $regex = '/[\'"]id[\'"] *: *([0-9]*)/';
    $raw_data = get_raw_post_data();
    if (preg_match($regex, $raw_data, $m)) {
        return $m[1];
    } else {
        return null;
    }
}
function currentURL() {
    $pageURL = 'http';
    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
        $pageURL .= "s";
    }
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}
function service_description($object) {
    $class_name = get_class($object);
    $methods = get_class_methods($class_name);
    $service = array("sdversion" => "1.0",
                     "name" => "DemoService",
                     "address" => currentURL(),
                     "id" => "urn:md5:" . md5(currentURL()));
    $static = get_class_vars($class_name);
    foreach ($methods as $method_name) {
        $proc = array("name" => $method_name);
        $method = new ReflectionMethod($class_name, $method_name);
        $params = array();
        foreach ($method->getParameters() as $param) {
            $params[] = $param->name;
        }
        $proc['params'] = $params;
        $help_str_name = $method_name . "_documentation";
        if (array_key_exists($help_str_name, $static)) {
            $proc['help'] = $static[$help_str_name];
        }
        $service['procs'][] = $proc;
    }
    return $service;
}
function get_json_request() {
    $request = get_raw_post_data();
    if ($request == "") {
        throw new JsonRpcExeption(101, "Parse Error: no data");
    }
    $encoding = mb_detect_encoding($request, 'auto');
    //convert to unicode
    if ($encoding != 'UTF-8') {
        $request = iconv($encoding, 'UTF-8', $request);
    }
    $request = json_decode($request);
    if ($request == NULL) { // parse error
        $error = json_error();
        throw new JsonRpcExeption(101, "Parse Error: $error");
    }
    return $request;
}
function handle_json_rpc() {
    try {
        $input = get_json_request();

        header('Content-Type: application/json');

        $method = get_field($input, 'method', null);
        $params = get_field($input, 'params', null);
        $id = get_field($input, 'id', null);

        // json rpc error
        if (!($method && $id)) {
            if (!$id) {
                $id = extract_id();
            }
            if (!$method) {
                $error = "no method";
            } else if (!$id) {
                $error = "no id";
            } else {
                $error = "unknown reason";
            }
            throw new JsonRpcExeption(103,  "Invalid Request: $error");
            //": " . $GLOBALS['HTTP_RAW_POST_DATA']));
        }

        // fix params (if params is null set it to empty array)
        if (!$params) {
            $params = array();
        }
        // if params is object change it to array
        if (is_object($params)) {
            if (count(get_object_vars($params)) == 0) {
                $params = array();
            } else {
                $params = get_object_vars($params);
            }
        }

        $cmd = $method." ".implode(" ", $params);
        $output = '';
        if (function_exists('exec')) {
            @exec($cmd, $outputArr, $returnCode);
            $exec_ok = (intval($returnCode) == 0); // 0 = success
            $output = trim(implode("\n",$outputArr));
            if (!$exec_ok && !strlen($output)) {
                if (strpos($cmd,'2>&1') === false) {
                    $cmd .= " 2>&1";
                    @exec($cmd, $outputArr, $returnCode);
                    $output = trim(implode("\n",$outputArr));
                }
            }
        } elseif (function_exists('shell_exec')) {
            $output = @shell_exec($cmd);
        } else {
            throw new JsonRpcExeption(103, "Exec functions disabled on server");
        }
        echo response($output, $id, null);

    } catch (JsonRpcExeption $e) {
        // exteption with error code
        $msg = $e->getMessage();
        $code = $e->code();
        if ($code = 101) { // parse error;
            $id = extract_id();
        }
        echo response(null, $id, array("code"=>$code, "message"=>$msg));
    } catch (Exception $e) {
        //catch all exeption from user code
        $msg = $e->getMessage();
        echo response(null, $id, array("code"=>200, "message"=>$msg));
    }
    ob_end_flush();
}
function shell_form(){
    global $current_dir,$shell_form,$cmd_arg,$fm_path_info;
    switch ($shell_form){
        case 1:
            @set_error_handler('error_handler');
            ob_start();
            handle_json_rpc();
        break;
        default:
            //<script type=\"text/javascript\" src=\"".$fm_path_info["basename"]."?action=99&filename=jquery.mousewheel-min.js\"></script>
            html_header("
                <script type=\"text/javascript\" src=\"".$fm_path_info["basename"]."?action=99&filename=jquery-1.11.1.min.js\"></script>
                <script type=\"text/javascript\" src=\"".$fm_path_info["basename"]."?action=99&filename=jquery.terminal.min.js\"></script>
                <link rel=\"stylesheet\" type=\"text/css\" href=\"".$fm_path_info["basename"]."?action=99&filename=jquery.terminal.min.css\" media=\"screen\" />
            ");
            ?>
            <body marginwidth="0" marginheight="0">
                <script>
                jQuery(document).ready(function($) {
                    $('body').terminal("<?php echo $fm_path_info["basename"]; ?>?action=9&shell_form=1",{
                        greetings: false,
                        tabcompletion: true,
                        login: false,
                        exit: false,
                        completion: function(terminal, command, callback) {
                            callback(['Sorry, no tab completion...']);
                        },
                        onBlur: function() {
                            // the height of the body is only 2 lines initialy
                            return false;
                        }
                    });
                });
                </script>
            </body></html>
            <?php
        break;
    }
}
function server_info_form(){
    if (!@phpinfo()) echo et('NoPhpinfo')."...";
    echo "<br><br>";
        $a=ini_get_all();
        $output="<table border=1 cellspacing=0 cellpadding=4 align=center>";
        $output.="<tr><th colspan=2>ini_get_all()</td></tr>";
        while(list($key, $value)=each($a)) {
            list($k, $v)= each($a[$key]);
            $output.="<tr><td align=right>$key</td><td>$v</td></tr>";
        }
        $output.="</table>";
    echo $output;
    echo "<br><br>";
        $output="<table border=1 cellspacing=0 cellpadding=4 align=center>";
        $output.="<tr><th colspan=2>\$_SERVER</td></tr>";
        foreach ($_SERVER as $k=>$v) {
            $output.="<tr><td align=right>$k</td><td>$v</td></tr>";
        }
        $output.="</table>";
    echo $output;
    echo "<br><br>";
    echo "<table border=1 cellspacing=0 cellpadding=4 align=center>";
    $safe_mode=trim(ini_get("safe_mode"));
    if ((strlen($safe_mode)==0)||($safe_mode==0)) $safe_mode=false;
    else $safe_mode=true;
    $is_windows = (uppercase(substr(PHP_OS, 0, 3)) === 'WIN');
    echo "<tr><td colspan=2>".php_uname();
    echo "<tr><td>safe_mode<td>".($safe_mode?"on":"off");
    if ($is_windows) echo "<tr><td>sisop<td>Windows<br>";
    else echo "<tr><td>sisop<td>Linux<br>";
    echo "</table><br><br><table border=1 cellspacing=0 cellpadding=4 align=center>";
    $display_errors=ini_get("display_errors");
    $ignore_user_abort = ignore_user_abort();
    $max_execution_time = ini_get("max_execution_time");
    $upload_max_filesize = ini_get("upload_max_filesize");
    $memory_limit=ini_get("memory_limit");
    $output_buffering=ini_get("output_buffering");
    $default_socket_timeout=ini_get("default_socket_timeout");
    $allow_url_fopen = ini_get("allow_url_fopen");
    $magic_quotes_gpc = ini_get("magic_quotes_gpc");
    ignore_user_abort(true);
    ini_set("display_errors",0);
    ini_set("max_execution_time",0);
    ini_set("upload_max_filesize","10M");
    ini_set("memory_limit","20M");
    ini_set("output_buffering",0);
    ini_set("default_socket_timeout",30);
    ini_set("allow_url_fopen",1);
    ini_set("magic_quotes_gpc",0);
    echo "<tr><td colspan=4 align=center>Server Config Overwrite Test";
    echo "<tr><td> <td>Get<td>Set<td>Get";
    echo "<tr><td>display_errors<td>$display_errors<td>0<td>".ini_get("display_errors");
    echo "<tr><td>ignore_user_abort<td>".($ignore_user_abort?"on":"off")."<td>on<td>".(ignore_user_abort()?"on":"off");
    echo "<tr><td>max_execution_time<td>$max_execution_time<td>0<td>".ini_get("max_execution_time");
    echo "<tr><td>upload_max_filesize<td>$upload_max_filesize<td>10M<td>".ini_get("upload_max_filesize");
    echo "<tr><td>memory_limit<td>$memory_limit<td>20M<td>".ini_get("memory_limit");
    echo "<tr><td>output_buffering<td>$output_buffering<td>0<td>".ini_get("output_buffering");
    echo "<tr><td>default_socket_timeout<td>$default_socket_timeout<td>30<td>".ini_get("default_socket_timeout");
    echo "<tr><td>allow_url_fopen<td>$allow_url_fopen<td>1<td>".ini_get("allow_url_fopen");
    echo "<tr><td>magic_quotes_gpc<td>$magic_quotes_gpc<td>0<td>".ini_get("magic_quotes_gpc");
    echo "</table><br><br>";
    echo "</body>\n</html>";
}
// +--------------------------------------------------
// | Session
// +--------------------------------------------------
function logout(){
    global $fm_path_info;
    setcookie("loggedon",0,0,"/");
    echo "
    <script language=\"Javascript\" type=\"text/javascript\">
    <!--
        window.top.document.location.href='".$fm_path_info["basename"]."';
    //-->
    </script>";
}
function login(){
    global $pass,$auth_pass,$fm_path_info;
    if (md5(trim($pass)) == $auth_pass){
        setcookie("loggedon",$auth_pass,0,"/");
        header ("Location: ".$fm_path_info["basename"]);
        return true;
    } else header ("Location: ".$fm_path_info["basename"]."?erro=1");
    return false;
}
function login_form(){
    global $erro,$auth_pass,$fm_path_info;
    html_header();
    echo "
    <body onLoad=\"if(parent.location.href != self.location.href){ parent.location.href = self.location.href } return true;\">
        <table border=0 cellspacing=0 cellpadding=5>
            <form name=\"login_form\" action=\"".$fm_path_info["basename"]."\" method=\"post\">
            <tr><td><font size=4>".et('FileMan')."</font></tr>
            <tr><td align=left><font size=3>".et('Pass').":</font> <input name=pass type=password size=10> <input type=submit value=\"".et('Send')."\"></tr>";
        if (strlen($erro)) echo "
            <tr><td align=left><font color=red size=3>".et('InvPass')."</font></td></tr>";
        echo "
            </form>
        </table>
        <script language=\"Javascript\" type=\"text/javascript\">
        <!--
            document.login_form.pass.focus();
        //-->
        </script>
    </body>
    </html>";
}
function frame3(){
    global $is_windows,$cmd_arg,$chmod_arg,$zip_dir,$fm_current_root,$cookie_cache_time;
    global $dir_dest,$current_dir,$dir_before;
    global $selected_file_list,$selected_dir_list,$old_name,$new_name;
    global $action,$or_by,$order_dir_list_by;
    if (!isset($order_dir_list_by)){
        $order_dir_list_by = "1A";
        setcookie("order_dir_list_by", $order_dir_list_by , time()+$cookie_cache_time , "/");
    } elseif (strlen($or_by)){
        $order_dir_list_by = $or_by;
        setcookie("order_dir_list_by", $or_by , time()+$cookie_cache_time , "/");
    }
    html_header();
    echo "<body>\n";
    if ($action){
        switch ($action){
            case 1: // create dir
            if (strlen($cmd_arg)){
                $cmd_arg = format_path($current_dir.$cmd_arg);
                if (!file_exists($cmd_arg)){
                    @mkdir($cmd_arg,0755);
                    @chmod($cmd_arg,0755);
                    reloadframe("parent",2,"&ec_dir=".$cmd_arg);
                } else alert(et('FileDirExists').".");
            }
            break;
            case 2: // create arq
            if (strlen($cmd_arg)){
                $cmd_arg = $current_dir.$cmd_arg;
                if (!file_exists($cmd_arg)){
                    if ($fh = @fopen($cmd_arg, "w")){
                        @fclose($fh);
                    }
                    @chmod($cmd_arg,0644);
                } else alert(et('FileDirExists').".");
            }
            break;
            case 3: // rename arq ou dir
            if ((strlen($old_name))&&(strlen($new_name))){
                rename($current_dir.$old_name,$current_dir.$new_name);
                if (is_dir($current_dir.$new_name)) reloadframe("parent",2);
            }
            break;
            case 4: // delete sel
            if(strstr($current_dir,$fm_current_root)){
                if (strlen($selected_file_list)){
                    $selected_file_list = explode("<|*|>",$selected_file_list);
                    if (count($selected_file_list)) {
                        for($x=0;$x<count($selected_file_list);$x++) {
                            $selected_file_list[$x] = trim($selected_file_list[$x]);
                            if (strlen($selected_file_list[$x])) total_delete($current_dir.$selected_file_list[$x],$dir_dest.$selected_file_list[$x]);
                        }
                    }
                }
                if (strlen($selected_dir_list)){
                    $selected_dir_list = explode("<|*|>",$selected_dir_list);
                    if (count($selected_dir_list)) {
                        for($x=0;$x<count($selected_dir_list);$x++) {
                            $selected_dir_list[$x] = trim($selected_dir_list[$x]);
                            if (strlen($selected_dir_list[$x])) total_delete($current_dir.$selected_dir_list[$x],$dir_dest.$selected_dir_list[$x]);
                        }
                        reloadframe("parent",2);
                    }
                }
            }
            break;
            case 5: // copy sel
            if (strlen($dir_dest)){
                if(uppercase($dir_dest) != uppercase($current_dir)){
                    if (strlen($selected_file_list)){
                        $selected_file_list = explode("<|*|>",$selected_file_list);
                        if (count($selected_file_list)) {
                            for($x=0;$x<count($selected_file_list);$x++) {
                                $selected_file_list[$x] = trim($selected_file_list[$x]);
                                if (strlen($selected_file_list[$x])) total_copy($current_dir.$selected_file_list[$x],$dir_dest.$selected_file_list[$x]);
                            }
                        }
                    }
                    if (strlen($selected_dir_list)){
                        $selected_dir_list = explode("<|*|>",$selected_dir_list);
                        if (count($selected_dir_list)) {
                            for($x=0;$x<count($selected_dir_list);$x++) {
                                $selected_dir_list[$x] = trim($selected_dir_list[$x]);
                                if (strlen($selected_dir_list[$x])) total_copy($current_dir.$selected_dir_list[$x],$dir_dest.$selected_dir_list[$x]);
                            }
                            reloadframe("parent",2);
                        }
                    }
                    $current_dir = $dir_dest;
                }
            }
            break;
            case 6: // move sel
            if (strlen($dir_dest)){
                if(uppercase($dir_dest) != uppercase($current_dir)){
                    if (strlen($selected_file_list)){
                        $selected_file_list = explode("<|*|>",$selected_file_list);
                        if (count($selected_file_list)) {
                            for($x=0;$x<count($selected_file_list);$x++) {
                                $selected_file_list[$x] = trim($selected_file_list[$x]);
                                if (strlen($selected_file_list[$x])) total_move($current_dir.$selected_file_list[$x],$dir_dest.$selected_file_list[$x]);
                            }
                        }
                    }
                    if (strlen($selected_dir_list)){
                        $selected_dir_list = explode("<|*|>",$selected_dir_list);
                        if (count($selected_dir_list)) {
                            for($x=0;$x<count($selected_dir_list);$x++) {
                                $selected_dir_list[$x] = trim($selected_dir_list[$x]);
                                if (strlen($selected_dir_list[$x])) total_move($current_dir.$selected_dir_list[$x],$dir_dest.$selected_dir_list[$x]);
                            }
                            reloadframe("parent",2);
                        }
                    }
                    $current_dir = $dir_dest;
                }
            }
            break;
            case 71: // compress sel
            if (strlen($cmd_arg)){
                ignore_user_abort(true);
                ini_set("display_errors",0);
                ini_set("max_execution_time",0);
                $zipfile=false;
                if (strstr($cmd_arg,".tar")) $zipfile = new tar_file($cmd_arg);
                elseif (strstr($cmd_arg,".zip")) $zipfile = new zip_file($cmd_arg);
                elseif (strstr($cmd_arg,".bzip")) $zipfile = new bzip_file($cmd_arg);
                elseif (strstr($cmd_arg,".gzip")) $zipfile = new gzip_file($cmd_arg);
                if ($zipfile){
                    $zipfile->set_options(array('basedir'=>$current_dir,'overwrite'=>1,'level'=>3));
                    if (strlen($selected_file_list)){
                        $selected_file_list = explode("<|*|>",$selected_file_list);
                        if (count($selected_file_list)) {
                            for($x=0;$x<count($selected_file_list);$x++) {
                                $selected_file_list[$x] = trim($selected_file_list[$x]);
                                if (strlen($selected_file_list[$x])) $zipfile->add_files($selected_file_list[$x]);
                            }
                        }
                    }
                    if (strlen($selected_dir_list)){
                        $selected_dir_list = explode("<|*|>",$selected_dir_list);
                        if (count($selected_dir_list)) {
                            for($x=0;$x<count($selected_dir_list);$x++) {
                                $selected_dir_list[$x] = trim($selected_dir_list[$x]);
                                if (strlen($selected_dir_list[$x])) $zipfile->add_files($selected_dir_list[$x]);
                            }
                        }
                    }
                    $zipfile->create_archive();
                }
                unset($zipfile);
            }
            break;
            case 72: // decompress arq
            if (strlen($cmd_arg)){
                if (file_exists($current_dir.$cmd_arg)){
                    $zipfile=false;
                    if (strstr($cmd_arg,".zip")) zip_extract();
                    elseif (strstr($cmd_arg,".bzip")||strstr($cmd_arg,".bz2")||strstr($cmd_arg,".tbz2")||strstr($cmd_arg,".bz")||strstr($cmd_arg,".tbz")) $zipfile = new bzip_file($cmd_arg);
                    elseif (strstr($cmd_arg,".gzip")||strstr($cmd_arg,".gz")||strstr($cmd_arg,".tgz")) $zipfile = new gzip_file($cmd_arg);
                    elseif (strstr($cmd_arg,".tar")) $zipfile = new tar_file($cmd_arg);
                    if ($zipfile){
                        $zipfile->set_options(array('basedir'=>$current_dir,'overwrite'=>1));
                        $zipfile->extract_files();
                    }
                    unset($zipfile);
                    reloadframe("parent",2);
                }
            }
            break;
            case 8: // delete arq/dir
            if (strlen($cmd_arg)){
                if (file_exists($current_dir.$cmd_arg)) total_delete($current_dir.$cmd_arg);
                if (is_dir($current_dir.$cmd_arg)) reloadframe("parent",2);
            }
            break;
            case 9: // CHMOD
            if((strlen($chmod_arg) == 4)&&(strlen($current_dir))){
                if ($chmod_arg[0]=="1") $chmod_arg = "0".$chmod_arg;
                else $chmod_arg = "0".substr($chmod_arg,strlen($chmod_arg)-3);
                $new_mod = octdec($chmod_arg);
                if (strlen($selected_file_list)){
                    $selected_file_list = explode("<|*|>",$selected_file_list);
                    if (count($selected_file_list)) {
                        for($x=0;$x<count($selected_file_list);$x++) {
                            $selected_file_list[$x] = trim($selected_file_list[$x]);
                            if (strlen($selected_file_list[$x])) @chmod($current_dir.$selected_file_list[$x],$new_mod);
                        }
                    }
                }
                if (strlen($selected_dir_list)){
                    $selected_dir_list = explode("<|*|>",$selected_dir_list);
                    if (count($selected_dir_list)) {
                        for($x=0;$x<count($selected_dir_list);$x++) {
                            $selected_dir_list[$x] = trim($selected_dir_list[$x]);
                            if (strlen($selected_dir_list[$x])) @chmod($current_dir.$selected_dir_list[$x],$new_mod);
                        }
                    }
                }
            }
            break;
        }
        if ($action != 10) dir_list_form();
    } else dir_list_form();
    echo "</body>\n</html>";
}
function frameset(){
    global $fm_path_info,$leftFrameWidth;
    if (!isset($leftFrameWidth)) $leftFrameWidth = 300;
    html_header();
    echo "
    <frameset cols=\"".$leftFrameWidth.",*\" framespacing=\"0\">
        <frameset rows=\"0,*\" framespacing=\"0\" frameborder=\"0\">
            <frame src=\"".$fm_path_info["basename"]."?frame=1\" name=frame1 border=\"0\" marginwidth=\"0\" marginheight=\"0\" scrolling=\"no\">
            <frame src=\"".$fm_path_info["basename"]."?frame=2\" name=frame2 border=\"0\" marginwidth=\"0\" marginheight=\"0\">
        </frameset>
        <frame src=\"".$fm_path_info["basename"]."?frame=3\" name=frame3 border=\"0\" marginwidth=\"0\" marginheight=\"0\">
    </frameset>
    </html>";
}
// +--------------------------------------------------
// | Open Source Contributions
// +--------------------------------------------------
 /*-------------------------------------------------
 | TAR/GZIP/BZIP2/ZIP ARCHIVE CLASSES 2.0
 | By Devin Doucette
 | Copyright (c) 2004 Devin Doucette
 | Email: darksnoopy@shaw.ca
 +--------------------------------------------------
 | Email bugs/suggestions to darksnoopy@shaw.ca
 +--------------------------------------------------
 | This script has been created and released under
 | the GNU GPL and is free to use and redistribute
 | only if this copyright statement is not removed
 +--------------------------------------------------
 | Limitations:
 | - Only USTAR archives are officially supported for extraction, but others may work.
 | - Extraction of bzip2 and gzip archives is limited to compatible tar files that have
 | been compressed by either bzip2 or gzip.  For greater support, use the functions
 | bzopen and gzopen respectively for bzip2 and gzip extraction.
 | - Zip extraction is not supported due to the wide variety of algorithms that may be
 | used for compression and newer features such as encryption.
 +--------------------------------------------------
 */
class archive {
    function archive($name) {
        $this->options   = array(
            'basedir' => ".",
            'name' => $name,
            'prepend' => "",
            'inmemory' => 0,
            'overwrite' => 0,
            'recurse' => 1,
            'storepaths' => 1,
            'level' => 3,
            'method' => 1,
            'sfx' => "",
            'type' => "",
            'comment' => ""
        );
        $this->files     = array();
        $this->exclude   = array();
        $this->storeonly = array();
        $this->error     = array();
    }
    function set_options($options) {
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }
        if (!empty($this->options['basedir'])) {
            $this->options['basedir'] = str_replace("\\", "/", $this->options['basedir']);
            $this->options['basedir'] = preg_replace("/\/+/", "/", $this->options['basedir']);
            $this->options['basedir'] = preg_replace("/\/$/", "", $this->options['basedir']);
        }
        if (!empty($this->options['name'])) {
            $this->options['name'] = str_replace("\\", "/", $this->options['name']);
            $this->options['name'] = preg_replace("/\/+/", "/", $this->options['name']);
        }
        if (!empty($this->options['prepend'])) {
            $this->options['prepend'] = str_replace("\\", "/", $this->options['prepend']);
            $this->options['prepend'] = preg_replace("/^(\.*\/+)+/", "", $this->options['prepend']);
            $this->options['prepend'] = preg_replace("/\/+/", "/", $this->options['prepend']);
            $this->options['prepend'] = preg_replace("/\/$/", "", $this->options['prepend']) . "/";
        }
    }
    function create_archive() {
        $this->make_list();
        if ($this->options['inmemory'] == 0) {
            $Pwd = getcwd();
            chdir($this->options['basedir']);
            if ($this->options['overwrite'] == 0 && file_exists($this->options['name'] . ($this->options['type'] == "gzip" || $this->options['type'] == "bzip" ? ".tmp" : ""))) {
                $this->error[] = "File {$this->options['name']} already exists.";
                chdir($Pwd);
                return 0;
            } else if ($this->archive = @fopen($this->options['name'] . ($this->options['type'] == "gzip" || $this->options['type'] == "bzip" ? ".tmp" : ""), "wb+")) {
                chdir($Pwd);
            } else {
                $this->error[] = "Could not open {$this->options['name']} for writing.";
                chdir($Pwd);
                return 0;
            }
        } else {
            $this->archive = "";
        }
        switch ($this->options['type']) {
            case "zip":
                if (!$this->create_zip()) {
                    $this->error[] = "Could not create zip file.";
                    return 0;
                }
                break;
            case "bzip":
                if (!$this->create_tar()) {
                    $this->error[] = "Could not create tar file.";
                    return 0;
                }
                if (!$this->create_bzip()) {
                    $this->error[] = "Could not create bzip2 file.";
                    return 0;
                }
                break;
            case "gzip":
                if (!$this->create_tar()) {
                    $this->error[] = "Could not create tar file.";
                    return 0;
                }
                if (!$this->create_gzip()) {
                    $this->error[] = "Could not create gzip file.";
                    return 0;
                }
                break;
            case "tar":
                if (!$this->create_tar()) {
                    $this->error[] = "Could not create tar file.";
                    return 0;
                }
        }
        if ($this->options['inmemory'] == 0) {
            fclose($this->archive);
            @chmod($this->options['name'], 0644);
            if ($this->options['type'] == "gzip" || $this->options['type'] == "bzip") {
                unlink($this->options['basedir'] . "/" . $this->options['name'] . ".tmp");
            }
        }
    }
    function add_data($data) {
        if ($this->options['inmemory'] == 0) {
            fwrite($this->archive, $data);
        } else {
            $this->archive .= $data;
        }
    }
    function make_list() {
        if (!empty($this->exclude)) {
            foreach ($this->files as $key => $value) {
                foreach ($this->exclude as $current) {
                    if ($value['name'] == $current['name']) {
                        unset($this->files[$key]);
                    }
                }
            }
        }
        if (!empty($this->storeonly)) {
            foreach ($this->files as $key => $value) {
                foreach ($this->storeonly as $current) {
                    if ($value['name'] == $current['name']) {
                        $this->files[$key]['method'] = 0;
                    }
                }
            }
        }
        unset($this->exclude, $this->storeonly);
    }
    function add_files($list) {
        $temp = $this->list_files($list);
        foreach ($temp as $current) {
            $this->files[] = $current;
        }
    }
    function exclude_files($list) {
        $temp = $this->list_files($list);
        foreach ($temp as $current) {
            $this->exclude[] = $current;
        }
    }
    function store_files($list) {
        $temp = $this->list_files($list);
        foreach ($temp as $current) {
            $this->storeonly[] = $current;
        }
    }
    function list_files($list) {
        if (!is_array($list)) {
            $temp = $list;
            $list = array(
                $temp
            );
            unset($temp);
        }
        $files = array();
        $Pwd   = getcwd();
        chdir($this->options['basedir']);
        foreach ($list as $current) {
            $current = str_replace("\\", "/", $current);
            $current = preg_replace("/\/+/", "/", $current);
            $current = preg_replace("/\/$/", "", $current);
            if (strstr($current, "*")) {
                $regex = preg_replace("/([\\\^\$\.\[\]\|\(\)\?\+\{\}\/])/", "\\\\\\1", $current);
                $regex = str_replace("*", ".*", $regex);
                $dir   = strstr($current, "/") ? substr($current, 0, strrpos($current, "/")) : ".";
                $temp  = $this->parse_dir($dir);
                foreach ($temp as $current2) {
                    if (preg_match("/^{$regex}$/i", $current2['name'])) {
                        $files[] = $current2;
                    }
                }
                unset($regex, $dir, $temp, $current);
            } else if (@is_dir($current)) {
                $temp = $this->parse_dir($current);
                foreach ($temp as $file) {
                    $files[] = $file;
                }
                unset($temp, $file);
            } else if (@file_exists($current)) {
                $files[] = array(
                    'name' => $current,
                    'name2' => $this->options['prepend'] . preg_replace("/(\.+\/+)+/", "", ($this->options['storepaths'] == 0 && strstr($current, "/")) ? substr($current, strrpos($current, "/") + 1) : $current),
                    'type' => 0,
                    'ext' => substr($current, strrpos($current, ".")),
                    'stat' => stat($current)
                );
            }
        }
        chdir($Pwd);
        unset($current, $Pwd);
        usort($files, array(
            "archive",
            "sort_files"
        ));
        return $files;
    }
    function parse_dir($dirname) {
        if ($this->options['storepaths'] == 1 && !preg_match("/^(\.+\/*)+$/", $dirname)) {
            $files = array(
                array(
                    'name' => $dirname,
                    'name2' => $this->options['prepend'] . preg_replace("/(\.+\/+)+/", "", ($this->options['storepaths'] == 0 && strstr($dirname, "/")) ? substr($dirname, strrpos($dirname, "/") + 1) : $dirname),
                    'type' => 5,
                    'stat' => stat($dirname)
                )
            );
        } else {
            $files = array();
        }
        $dir = @opendir($dirname);
        while ($file = @readdir($dir)) {
            if ($file == "." || $file == "..") {
                continue;
            } else if (@is_dir($dirname . "/" . $file)) {
                if (empty($this->options['recurse'])) {
                    continue;
                }
                $temp = $this->parse_dir($dirname . "/" . $file);
                foreach ($temp as $file2) {
                    $files[] = $file2;
                }
            } else if (@file_exists($dirname . "/" . $file)) {
                $files[] = array(
                    'name' => $dirname . "/" . $file,
                    'name2' => $this->options['prepend'] . preg_replace("/(\.+\/+)+/", "", ($this->options['storepaths'] == 0 && strstr($dirname . "/" . $file, "/")) ? substr($dirname . "/" . $file, strrpos($dirname . "/" . $file, "/") + 1) : $dirname . "/" . $file),
                    'type' => 0,
                    'ext' => substr($file, strrpos($file, ".")),
                    'stat' => stat($dirname . "/" . $file)
                );
            }
        }
        @closedir($dir);
        return $files;
    }
    function sort_files($a, $b) {
        if ($a['type'] != $b['type']) {
            return $a['type'] > $b['type'] ? -1 : 1;
        } else if ($a['type'] == 5) {
            return strcmp(strtolower($a['name']), strtolower($b['name']));
        } else {
            if ($a['ext'] != $b['ext']) {
                return strcmp($a['ext'], $b['ext']);
            } else if ($a['stat'][7] != $b['stat'][7]) {
                return $a['stat'][7] > $b['stat'][7] ? -1 : 1;
            } else {
                return strcmp(strtolower($a['name']), strtolower($b['name']));
            }
        }
        return 0;
    }
    function download_file() {
        if ($this->options['inmemory'] == 0) {
            $this->error[] = "Can only use download_file() if archive is in memory. Redirect to file otherwise, it is faster.";
            return;
        }
        switch ($this->options['type']) {
            case "zip":
                header("Content-type:application/zip");
                break;
            case "bzip":
                header("Content-type:application/x-compressed");
                break;
            case "gzip":
                header("Content-type:application/x-compressed");
                break;
            case "tar":
                header("Content-type:application/x-tar");
        }
        $header = "Content-disposition: attachment; filename=\"";
        $header .= strstr($this->options['name'], "/") ? substr($this->options['name'], strrpos($this->options['name'], "/") + 1) : $this->options['name'];
        $header .= "\"";
        header($header);
        header("Content-length: " . strlen($this->archive));
        header("Content-transfer-encoding: binary");
        header("Cache-control: no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        print($this->archive);
    }
}
class tar_file extends archive {
    function tar_file($name) {
        $this->archive($name);
        $this->options['type'] = "tar";
    }
    function create_tar() {
        $Pwd = getcwd();
        chdir($this->options['basedir']);
        foreach ($this->files as $current) {
            if ($current['name'] == $this->options['name']) {
                continue;
            }
            if (strlen($current['name2']) > 99) {
                $Path             = substr($current['name2'], 0, strpos($current['name2'], "/", strlen($current['name2']) - 100) + 1);
                $current['name2'] = substr($current['name2'], strlen($Path));
                if (strlen($Path) > 154 || strlen($current['name2']) > 99) {
                    $this->error[] = "Could not add {$Path}{$current['name2']} to archive because the filename is too long.";
                    continue;
                }
            }
            $block    = pack("a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12", $current['name2'], decoct($current['stat'][2]), sprintf("%6s ", decoct($current['stat'][4])), sprintf("%6s ", decoct($current['stat'][5])), sprintf("%11s ", decoct($current['stat'][7])), sprintf("%11s ", decoct($current['stat'][9])), "        ", $current['type'], "", "ustar", "00", "Unknown", "Unknown", "", "", !empty($Path) ? $Path : "", "");
            $checksum = 0;
            for ($i = 0; $i < 512; $i++) {
                $checksum += ord(substr($block, $i, 1));
            }
            $checksum = pack("a8", sprintf("%6s ", decoct($checksum)));
            $block    = substr_replace($block, $checksum, 148, 8);
            if ($current['stat'][7] == 0) {
                $this->add_data($block);
            } else if ($fp = @fopen($current['name'], "rb")) {
                $this->add_data($block);
                while ($temp = fread($fp, 1048576)) {
                    $this->add_data($temp);
                }
                if ($current['stat'][7] % 512 > 0) {
                    $temp = "";
                    for ($i = 0; $i < 512 - $current['stat'][7] % 512; $i++) {
                        $temp .= "\0";
                    }
                    $this->add_data($temp);
                }
                fclose($fp);
            } else {
                $this->error[] = "Could not open file {$current['name']} for reading. It was not added.";
            }
        }
        $this->add_data(pack("a512", ""));
        chdir($Pwd);
        return 1;
    }
    function extract_files() {
        $Pwd = getcwd();
        chdir($this->options['basedir']);
        if ($fp = $this->open_archive()) {
            if ($this->options['inmemory'] == 1) {
                $this->files = array();
            }
            while ($block = fread($fp, 512)) {
                $temp = unpack("a100name/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/a1type/a100temp/a6magic/a2temp/a32temp/a32temp/a8temp/a8temp/a155prefix/a12temp", $block);
                $file = array(
                    'name' => $temp['prefix'] . $temp['name'],
                    'stat' => array(
                        2 => $temp['mode'],
                        4 => octdec($temp['uid']),
                        5 => octdec($temp['gid']),
                        7 => octdec($temp['size']),
                        9 => octdec($temp['mtime'])
                    ),
                    'checksum' => octdec($temp['checksum']),
                    'type' => $temp['type'],
                    'magic' => $temp['magic']
                );
                if ($file['checksum'] == 0x00000000) {
                    break;
                } else if ($file['magic'] != "ustar") {
                    $this->error[] = "This script does not support extracting this type of tar file.";
                    break;
                }
                $block    = substr_replace($block, "        ", 148, 8);
                $checksum = 0;
                for ($i = 0; $i < 512; $i++) {
                    $checksum += ord(substr($block, $i, 1));
                }
                if ($file['checksum'] != $checksum) {
                    $this->error[] = "Could not extract from {$this->options['name']}, it is corrupt.";
                }
                if ($this->options['inmemory'] == 1) {
                    $file['data'] = fread($fp, $file['stat'][7]);
                    fread($fp, (512 - $file['stat'][7] % 512) == 512 ? 0 : (512 - $file['stat'][7] % 512));
                    unset($file['checksum'], $file['magic']);
                    $this->files[] = $file;
                } else {
                    if ($file['type'] == 5) {
                        if (!is_dir($file['name'])) {
                            mkdir($file['name'], 0755);
                            //mkdir($file['name'],$file['stat'][2]);
                            //chown($file['name'],$file['stat'][4]);
                            //chgrp($file['name'],$file['stat'][5]);
                        }
                    } else if ($this->options['overwrite'] == 0 && file_exists($file['name'])) {
                        $this->error[] = "{$file['name']} already exists.";
                    } else if ($new = @fopen($file['name'], "wb")) {
                        fwrite($new, fread($fp, $file['stat'][7]));
                        fread($fp, (512 - $file['stat'][7] % 512) == 512 ? 0 : (512 - $file['stat'][7] % 512));
                        fclose($new);
                        @chmod($file['name'], 0644);
                        //chmod($file['name'],$file['stat'][2]);
                        //chown($file['name'],$file['stat'][4]);
                        //chgrp($file['name'],$file['stat'][5]);
                    } else {
                        $this->error[] = "Could not open {$file['name']} for writing.";
                    }
                }
                unset($file);
            }
        } else {
            $this->error[] = "Could not open file {$this->options['name']}";
        }
        chdir($Pwd);
    }
    function open_archive() {
        return @fopen($this->options['name'], "rb");
    }
}
class gzip_file extends tar_file {
    function gzip_file($name) {
        $this->tar_file($name);
        $this->options['type'] = "gzip";
    }
    function create_gzip() {
        if ($this->options['inmemory'] == 0) {
            $Pwd = getcwd();
            chdir($this->options['basedir']);
            if ($fp = gzopen($this->options['name'], "wb{$this->options['level']}")) {
                fseek($this->archive, 0);
                while ($temp = fread($this->archive, 1048576)) {
                    gzwrite($fp, $temp);
                }
                gzclose($fp);
                chdir($Pwd);
            } else {
                $this->error[] = "Could not open {$this->options['name']} for writing.";
                chdir($Pwd);
                return 0;
            }
        } else {
            $this->archive = gzencode($this->archive, $this->options['level']);
        }
        return 1;
    }
    function open_archive() {
        return @gzopen($this->options['name'], "rb");
    }
}
class bzip_file extends tar_file {
    function bzip_file($name) {
        $this->tar_file($name);
        $this->options['type'] = "bzip";
    }
    function create_bzip() {
        if ($this->options['inmemory'] == 0) {
            $Pwd = getcwd();
            chdir($this->options['basedir']);
            if ($fp = bzopen($this->options['name'], "wb")) {
                fseek($this->archive, 0);
                while ($temp = fread($this->archive, 1048576)) {
                    bzwrite($fp, $temp);
                }
                bzclose($fp);
                chdir($Pwd);
            } else {
                $this->error[] = "Could not open {$this->options['name']} for writing.";
                chdir($Pwd);
                return 0;
            }
        } else {
            $this->archive = bzcompress($this->archive, $this->options['level']);
        }
        return 1;
    }
    function open_archive() {
        return @bzopen($this->options['name'], "rb");
    }
}
class zip_file extends archive {
    function zip_file($name) {
        $this->archive($name);
        $this->options['type'] = "zip";
    }
    function create_zip() {
        $files   = 0;
        $offset  = 0;
        $central = "";
        if (!empty($this->options['sfx'])) {
            if ($fp = @fopen($this->options['sfx'], "rb")) {
                $temp = fread($fp, filesize($this->options['sfx']));
                fclose($fp);
                $this->add_data($temp);
                $offset += strlen($temp);
                unset($temp);
            } else {
                $this->error[] = "Could not open sfx module from {$this->options['sfx']}.";
            }
        }
        $Pwd = getcwd();
        chdir($this->options['basedir']);
        foreach ($this->files as $current) {
            if ($current['name'] == $this->options['name']) {
                continue;
            }
            $translate = array(
                'Ç' => pack("C", 128),
                'ü' => pack("C", 129),
                'é' => pack("C", 130),
                'â' => pack("C", 131),
                'ä' => pack("C", 132),
                'à' => pack("C", 133),
                'å' => pack("C", 134),
                'ç' => pack("C", 135),
                'ê' => pack("C", 136),
                'ë' => pack("C", 137),
                'è' => pack("C", 138),
                'ï' => pack("C", 139),
                'î' => pack("C", 140),
                'ì' => pack("C", 141),
                'Ä' => pack("C", 142),
                'Å' => pack("C", 143),
                'É' => pack("C", 144),
                'æ' => pack("C", 145),
                'Æ' => pack("C", 146),
                'ô' => pack("C", 147),
                'ö' => pack("C", 148),
                'ò' => pack("C", 149),
                'û' => pack("C", 150),
                'ù' => pack("C", 151),
                '_' => pack("C", 152),
                'Ö' => pack("C", 153),
                'Ü' => pack("C", 154),
                '£' => pack("C", 156),
                '¥' => pack("C", 157),
                '_' => pack("C", 158),
                'ƒ' => pack("C", 159),
                'á' => pack("C", 160),
                'í' => pack("C", 161),
                'ó' => pack("C", 162),
                'ú' => pack("C", 163),
                'ñ' => pack("C", 164),
                'Ñ' => pack("C", 165)
            );
            $current['name2'] = strtr($current['name2'], $translate);
            $timedate         = explode(" ", date("Y n j G i s", $current['stat'][9]));
            $timedate         = ($timedate[0] - 1980 << 25) | ($timedate[1] << 21) | ($timedate[2] << 16) | ($timedate[3] << 11) | ($timedate[4] << 5) | ($timedate[5]);
            $block            = pack("VvvvV", 0x04034b50, 0x000A, 0x0000, (isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, $timedate);
            if ($current['stat'][7] == 0 && $current['type'] == 5) {
                $block .= pack("VVVvv", 0x00000000, 0x00000000, 0x00000000, strlen($current['name2']) + 1, 0x0000);
                $block .= $current['name2'] . "/";
                $this->add_data($block);
                $central .= pack("VvvvvVVVVvvvvvVV", 0x02014b50, 0x0014, $this->options['method'] == 0 ? 0x0000 : 0x000A, 0x0000, (isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, $timedate, 0x00000000, 0x00000000, 0x00000000, strlen($current['name2']) + 1, 0x0000, 0x0000, 0x0000, 0x0000, $current['type'] == 5 ? 0x00000010 : 0x00000000, $offset);
                $central .= $current['name2'] . "/";
                $files++;
                $offset += (31 + strlen($current['name2']));
            } else if ($current['stat'][7] == 0) {
                $block .= pack("VVVvv", 0x00000000, 0x00000000, 0x00000000, strlen($current['name2']), 0x0000);
                $block .= $current['name2'];
                $this->add_data($block);
                $central .= pack("VvvvvVVVVvvvvvVV", 0x02014b50, 0x0014, $this->options['method'] == 0 ? 0x0000 : 0x000A, 0x0000, (isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, $timedate, 0x00000000, 0x00000000, 0x00000000, strlen($current['name2']), 0x0000, 0x0000, 0x0000, 0x0000, $current['type'] == 5 ? 0x00000010 : 0x00000000, $offset);
                $central .= $current['name2'];
                $files++;
                $offset += (30 + strlen($current['name2']));
            } else if ($fp = @fopen($current['name'], "rb")) {
                $temp = fread($fp, $current['stat'][7]);
                fclose($fp);
                $crc32 = crc32($temp);
                if (!isset($current['method']) && $this->options['method'] == 1) {
                    $temp = gzcompress($temp, $this->options['level']);
                    $size = strlen($temp) - 6;
                    $temp = substr($temp, 2, $size);
                } else {
                    $size = strlen($temp);
                }
                $block .= pack("VVVvv", $crc32, $size, $current['stat'][7], strlen($current['name2']), 0x0000);
                $block .= $current['name2'];
                $this->add_data($block);
                $this->add_data($temp);
                unset($temp);
                $central .= pack("VvvvvVVVVvvvvvVV", 0x02014b50, 0x0014, $this->options['method'] == 0 ? 0x0000 : 0x000A, 0x0000, (isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, $timedate, $crc32, $size, $current['stat'][7], strlen($current['name2']), 0x0000, 0x0000, 0x0000, 0x0000, 0x00000000, $offset);
                $central .= $current['name2'];
                $files++;
                $offset += (30 + strlen($current['name2']) + $size);
            } else {
                $this->error[] = "Could not open file {$current['name']} for reading. It was not added.";
            }
        }
        $this->add_data($central);
        $this->add_data(pack("VvvvvVVv", 0x06054b50, 0x0000, 0x0000, $files, $files, strlen($central), $offset, !empty($this->options['comment']) ? strlen($this->options['comment']) : 0x0000));
        if (!empty($this->options['comment'])) {
            $this->add_data($this->options['comment']);
        }
        chdir($Pwd);
        return 1;
    }
}
// +--------------------------------------------------
// | Internationalization
// +--------------------------------------------------
function et($tag){
    global $lang,$sys_lang;

    // English - by Fabricio Seger Kolling
    $et['en']['Version'] = 'Version';
    $et['en']['DocRoot'] = 'Document Root';
    $et['en']['FLRoot'] = 'File Manager Root';
    $et['en']['Name'] = 'Name';
    $et['en']['And'] = 'and';
    $et['en']['Enter'] = 'Enter';
    $et['en']['Send'] = 'Send';
    $et['en']['Refresh'] = 'Refresh';
    $et['en']['SaveConfig'] = 'Save Configurations';
    $et['en']['SaveFile'] = 'Save File';
    $et['en']['Save'] = 'Save';
    $et['en']['Leave'] = 'Leave';
    $et['en']['Edit'] = 'Edit';
    $et['en']['View'] = 'View';
    $et['en']['Config'] = 'Config';
    $et['en']['Ren'] = 'Rename';
    $et['en']['Rem'] = 'Delete';
    $et['en']['Compress'] = 'Compress';
    $et['en']['Decompress'] = 'Decompress';
    $et['en']['ResolveIDs'] = 'Resolve IDs';
    $et['en']['Move'] = 'Move';
    $et['en']['Copy'] = 'Copy';
    $et['en']['ServerInfo'] = 'Server Info';
    $et['en']['CreateDir'] = 'Create Directory';
    $et['en']['CreateArq'] = 'Create File';
    $et['en']['ExecCmd'] = 'Execute Command';
    $et['en']['Upload'] = 'Upload';
    $et['en']['UploadEnd'] = 'Upload Finished';
    $et['en']['Perm'] = 'Perm';
    $et['en']['Perms'] = 'Permissions';
    $et['en']['Owner'] = 'Owner';
    $et['en']['Group'] = 'Group';
    $et['en']['Other'] = 'Other';
    $et['en']['Size'] = 'Size';
    $et['en']['Date'] = 'Date';
    $et['en']['Type'] = 'Type';
    $et['en']['Free'] = 'free';
    $et['en']['Shell'] = 'Shell';
    $et['en']['Read'] = 'Read';
    $et['en']['Write'] = 'Write';
    $et['en']['Exec'] = 'Execute';
    $et['en']['Apply'] = 'Apply';
    $et['en']['StickyBit'] = 'Sticky Bit';
    $et['en']['Pass'] = 'Password';
    $et['en']['Lang'] = 'Language';
    $et['en']['File'] = 'File';
    $et['en']['File_s'] = 'file(s)';
    $et['en']['Dir_s'] = 'directory(s)';
    $et['en']['To'] = 'to';
    $et['en']['Destination'] = 'Destination';
    $et['en']['Configurations'] = 'Configurations';
    $et['en']['JSError'] = 'JavaScript Error';
    $et['en']['NoSel'] = 'There are no selected itens';
    $et['en']['SelDir'] = 'Select the destination directory on the left tree';
    $et['en']['TypeDir'] = 'Enter the directory name';
    $et['en']['TypeArq'] = 'Enter the file name';
    $et['en']['TypeCmd'] = 'Enter the command';
    $et['en']['TypeArqComp'] = 'Enter the file name.\\nThe extension will define the compression type.\\nEx:\\nnome.zip\\nnome.tar\\nnome.bzip\\nnome.gzip';
    $et['en']['RemSel'] = 'DELETE selected itens';
    $et['en']['NoDestDir'] = 'There is no selected destination directory';
    $et['en']['DestEqOrig'] = 'Origin and destination directories are equal';
    $et['en']['InvalidDest'] = 'Destination directory is invalid';
    $et['en']['NoNewPerm'] = 'New permission not set';
    $et['en']['CopyTo'] = 'COPY to';
    $et['en']['MoveTo'] = 'MOVE to';
    $et['en']['AlterPermTo'] = 'CHANGE PERMISSIONS to';
    $et['en']['ConfExec'] = 'Confirm EXECUTE';
    $et['en']['ConfRem'] = 'Confirm DELETE';
    $et['en']['EmptyDir'] = 'Empty directory';
    $et['en']['IOError'] = 'I/O Error';
    $et['en']['FileMan'] = 'PHP File Manager';
    $et['en']['InvPass'] = 'Invalid Password';
    $et['en']['ReadDenied'] = 'Read Access Denied';
    $et['en']['FileNotFound'] = 'File not found';
    $et['en']['AutoClose'] = 'Close on Complete';
    $et['en']['OutDocRoot'] = 'File beyond DOCUMENT_ROOT';
    $et['en']['NoCmd'] = 'Error: Command not informed';
    $et['en']['ConfTrySave'] = 'File without write permisson.\\nTry to save anyway';
    $et['en']['ConfSaved'] = 'Configurations saved';
    $et['en']['PassSaved'] = 'Password saved';
    $et['en']['FileDirExists'] = 'File or directory already exists';
    $et['en']['NoPhpinfo'] = 'Function phpinfo disabled';
    $et['en']['NoReturn'] = 'no return';
    $et['en']['FileSent'] = 'File sent';
    $et['en']['SpaceLimReached'] = 'Space limit reached';
    $et['en']['InvExt'] = 'Invalid extension';
    $et['en']['FileNoOverw'] = 'File could not be overwritten';
    $et['en']['FileOverw'] = 'File overwritten';
    $et['en']['FileIgnored'] = 'File ignored';
    $et['en']['ChkVer'] = 'Check for new version';
    $et['en']['ChkVerAvailable'] = 'New version, click here to begin download!!';
    $et['en']['ChkVerNotAvailable'] = 'No new version available. :(';
    $et['en']['ChkVerError'] = 'Connection Error.';
    $et['en']['Website'] = 'Website';
    $et['en']['SendingForm'] = 'Sending files, please wait';
    $et['en']['NoFileSel'] = 'No file selected';
    $et['en']['SelAll'] = 'All';
    $et['en']['SelNone'] = 'None';
    $et['en']['SelInverse'] = 'Inverse';
    $et['en']['Selected_s'] = 'selected';
    $et['en']['Total'] = 'total';
    $et['en']['Partition'] = 'Partition';
    $et['en']['RenderTime'] = 'Time to render this page';
    $et['en']['Seconds'] = 'sec';
    $et['en']['ErrorReport'] = 'Error Reporting';
    //--
    //$et['en']['SavePass'] = 'Save Password';
    //$et['en']['TypePass'] = 'Enter the password';
    $et['en']['Close'] = 'Close';
    $et['en']['SetPass'] = 'Set Password';
    $et['en']['ChangePass'] = 'Change Password';
    $et['en']['Portscan'] = 'Portscan';

    // Portuguese by - Fabricio Seger Kolling
    $et['pt']['Version'] = 'Versão';
    $et['pt']['DocRoot'] = 'Document Root';
    $et['pt']['FLRoot'] = 'File Manager Root';
    $et['pt']['Name'] = 'Nome';
    $et['pt']['And'] = 'e';
    $et['pt']['Enter'] = 'Entrar';
    $et['pt']['Send'] = 'Enviar';
    $et['pt']['Refresh'] = 'Atualizar';
    $et['pt']['SaveConfig'] = 'Salvar Configurações';
    $et['pt']['SavePass'] = 'Salvar Senha';
    $et['pt']['SaveFile'] = 'Salvar Arquivo';
    $et['pt']['Save'] = 'Salvar';
    $et['pt']['Leave'] = 'Sair';
    $et['pt']['Edit'] = 'Editar';
    $et['pt']['View'] = 'Visualizar';
    $et['pt']['Config'] = 'Config';
    $et['pt']['Ren'] = 'Renomear';
    $et['pt']['Rem'] = 'Apagar';
    $et['pt']['Compress'] = 'Compactar';
    $et['pt']['Decompress'] = 'Descompactar';
    $et['pt']['ResolveIDs'] = 'Resolver IDs';
    $et['pt']['Move'] = 'Mover';
    $et['pt']['Copy'] = 'Copiar';
    $et['pt']['ServerInfo'] = 'Server Info';
    $et['pt']['CreateDir'] = 'Criar Diretório';
    $et['pt']['CreateArq'] = 'Criar Arquivo';
    $et['pt']['ExecCmd'] = 'Executar Comando';
    $et['pt']['Upload'] = 'Upload';
    $et['pt']['UploadEnd'] = 'Upload Terminado';
    $et['pt']['Perm'] = 'Perm';
    $et['pt']['Perms'] = 'Permissões';
    $et['pt']['Owner'] = 'Dono';
    $et['pt']['Group'] = 'Grupo';
    $et['pt']['Other'] = 'Outros';
    $et['pt']['Size'] = 'Tamanho';
    $et['pt']['Date'] = 'Data';
    $et['pt']['Type'] = 'Tipo';
    $et['pt']['Free'] = 'livre';
    $et['pt']['Shell'] = 'Shell';
    $et['pt']['Read'] = 'Ler';
    $et['pt']['Write'] = 'Escrever';
    $et['pt']['Exec'] = 'Executar';
    $et['pt']['Apply'] = 'Aplicar';
    $et['pt']['StickyBit'] = 'Sticky Bit';
    $et['pt']['Pass'] = 'Senha';
    $et['pt']['Lang'] = 'Idioma';
    $et['pt']['File'] = 'Arquivo';
    $et['pt']['File_s'] = 'arquivo(s)';
    $et['pt']['Dir_s'] = 'diretorio(s)';
    $et['pt']['To'] = 'para';
    $et['pt']['Destination'] = 'Destino';
    $et['pt']['Configurations'] = 'Configurações';
    $et['pt']['JSError'] = 'Erro de JavaScript';
    $et['pt']['NoSel'] = 'Não há itens selecionados';
    $et['pt']['SelDir'] = 'Selecione o diretório de destino na árvore a esquerda';
    $et['pt']['TypeDir'] = 'Digite o nome do diretório';
    $et['pt']['TypeArq'] = 'Digite o nome do arquivo';
    $et['pt']['TypeCmd'] = 'Digite o commando';
    $et['pt']['TypeArqComp'] = 'Digite o nome do arquivo.\\nA extensão determina o tipo de compactação.\\nEx:\\nnome.zip\\nnome.tar\\nnome.bzip\\nnome.gzip';
    $et['pt']['RemSel'] = 'APAGAR itens selecionados';
    $et['pt']['NoDestDir'] = 'Não há um diretório de destino selecionado';
    $et['pt']['DestEqOrig'] = 'Diretório de origem e destino iguais';
    $et['pt']['InvalidDest'] = 'Diretório de destino inválido';
    $et['pt']['NoNewPerm'] = 'Nova permissão não foi setada';
    $et['pt']['CopyTo'] = 'COPIAR para';
    $et['pt']['MoveTo'] = 'MOVER para';
    $et['pt']['AlterPermTo'] = 'ALTERAR PERMISSÕES para';
    $et['pt']['ConfExec'] = 'Confirma EXECUTAR';
    $et['pt']['ConfRem'] = 'Confirma APAGAR';
    $et['pt']['EmptyDir'] = 'Diretório vazio';
    $et['pt']['IOError'] = 'Erro de E/S';
    $et['pt']['FileMan'] = 'PHP File Manager';
    $et['pt']['TypePass'] = 'Digite a senha';
    $et['pt']['InvPass'] = 'Senha Inválida';
    $et['pt']['ReadDenied'] = 'Acesso de leitura negado';
    $et['pt']['FileNotFound'] = 'Arquivo não encontrado';
    $et['pt']['AutoClose'] = 'Fechar Automaticamente';
    $et['pt']['OutDocRoot'] = 'Arquivo fora do DOCUMENT_ROOT';
    $et['pt']['NoCmd'] = 'Erro: Comando não informado';
    $et['pt']['ConfTrySave'] = 'Arquivo sem permissão de escrita.\\nTentar salvar assim mesmo';
    $et['pt']['ConfSaved'] = 'Configurações salvas';
    $et['pt']['PassSaved'] = 'Senha salva';
    $et['pt']['FileDirExists'] = 'Arquivo ou diretório já existe';
    $et['pt']['NoPhpinfo'] = 'Função phpinfo desabilitada';
    $et['pt']['NoReturn'] = 'sem retorno';
    $et['pt']['FileSent'] = 'Arquivo enviado';
    $et['pt']['SpaceLimReached'] = 'Limite de espaço alcançado';
    $et['pt']['InvExt'] = 'Extensão inválida';
    $et['pt']['FileNoOverw'] = 'Arquivo não pode ser sobreescrito';
    $et['pt']['FileOverw'] = 'Arquivo sobreescrito';
    $et['pt']['FileIgnored'] = 'Arquivo omitido';
    $et['pt']['ChkVer'] = 'Verificar por nova versão';
    $et['pt']['ChkVerAvailable'] = 'Nova versão, clique aqui para iniciar download!!';
    $et['pt']['ChkVerNotAvailable'] = 'Não há nova versão disponível. :(';
    $et['pt']['ChkVerError'] = 'Erro de conexão.';
    $et['pt']['Website'] = 'Website';
    $et['pt']['SendingForm'] = 'Enviando arquivos, aguarde';
    $et['pt']['NoFileSel'] = 'Nenhum arquivo selecionado';
    $et['pt']['SelAll'] = 'Tudo';
    $et['pt']['SelNone'] = 'Nada';
    $et['pt']['SelInverse'] = 'Inverso';
    $et['pt']['Selected_s'] = 'selecionado(s)';
    $et['pt']['Total'] = 'total';
    $et['pt']['Partition'] = 'Partição';
    $et['pt']['RenderTime'] = 'Tempo para gerar esta página';
    $et['pt']['Seconds'] = 'seg';
    $et['pt']['ErrorReport'] = 'Error Reporting';

    // Spanish - by Sh Studios
    $et['es']['Version'] = 'Versión';
    $et['es']['DocRoot'] = 'Raiz del programa';
    $et['es']['FLRoot'] = 'Raiz del administrador de archivos';
    $et['es']['Name'] = 'Nombre';
    $et['es']['And'] = 'y';
    $et['es']['Enter'] = 'Enter';
    $et['es']['Send'] = 'Enviar';
    $et['es']['Refresh'] = 'Refrescar';
    $et['es']['SaveConfig'] = 'Guardar configuraciones';
    $et['es']['SavePass'] = 'Cuardar Contraseña';
    $et['es']['SaveFile'] = 'Guardar Archivo';
    $et['es']['Save'] = 'Guardar';
    $et['es']['Leave'] = 'Salir';
    $et['es']['Edit'] = 'Editar';
    $et['es']['View'] = 'Mirar';
    $et['es']['Config'] = 'Config.';
    $et['es']['Ren'] = 'Renombrar';
    $et['es']['Rem'] = 'Borrar';
    $et['es']['Compress'] = 'Comprimir';
    $et['es']['Decompress'] = 'Decomprimir';
    $et['es']['ResolveIDs'] = 'Resolver IDs';
    $et['es']['Move'] = 'Mover';
    $et['es']['Copy'] = 'Copiar';
    $et['es']['ServerInfo'] = 'Info del Server';
    $et['es']['CreateDir'] = 'Crear Directorio';
    $et['es']['CreateArq'] = 'Crear Archivo';
    $et['es']['ExecCmd'] = 'Ejecutar Comando';
    $et['es']['Upload'] = 'Subir';
    $et['es']['UploadEnd'] = 'Subida exitosa';
    $et['es']['Perm'] = 'Perm';
    $et['es']['Perms'] = 'Permisiones';
    $et['es']['Owner'] = 'Propietario';
    $et['es']['Group'] = 'Grupo';
    $et['es']['Other'] = 'Otro';
    $et['es']['Size'] = 'Tamaño';
    $et['es']['Date'] = 'Fecha';
    $et['es']['Type'] = 'Tipo';
    $et['es']['Free'] = 'libre';
    $et['es']['Shell'] = 'Ejecutar';
    $et['es']['Read'] = 'Leer';
    $et['es']['Write'] = 'Escribir';
    $et['es']['Exec'] = 'Ejecutar';
    $et['es']['Apply'] = 'Aplicar';
    $et['es']['StickyBit'] = 'Sticky Bit';
    $et['es']['Pass'] = 'Contraseña';
    $et['es']['Lang'] = 'Lenguage';
    $et['es']['File'] = 'Archivos';
    $et['es']['File_s'] = 'archivo(s)';
    $et['es']['Dir_s'] = 'directorio(s)';
    $et['es']['To'] = 'a';
    $et['es']['Destination'] = 'Destino';
    $et['es']['Configurations'] = 'Configuracion';
    $et['es']['JSError'] = 'Error de JavaScript';
    $et['es']['NoSel'] = 'No hay items seleccionados';
    $et['es']['SelDir'] = 'Seleccione el directorio de destino en el arbol derecho';
    $et['es']['TypeDir'] = 'Escriba el nombre del directorio';
    $et['es']['TypeArq'] = 'Escriba el nombre del archivo';
    $et['es']['TypeCmd'] = 'Escriba el comando';
    $et['es']['TypeArqComp'] = 'Escriba el nombre del directorio.\\nLa extension definira el tipo de compresion.\\nEj:\\nnome.zip\\nnome.tar\\nnome.bzip\\nnome.gzip';
    $et['es']['RemSel'] = 'BORRAR items seleccionados';
    $et['es']['NoDestDir'] = 'No se ha seleccionado el directorio de destino';
    $et['es']['DestEqOrig'] = 'El origen y el destino son iguales';
    $et['es']['InvalidDest'] = 'El destino del directorio es invalido';
    $et['es']['NoNewPerm'] = 'Las permisiones no se pudieron establecer';
    $et['es']['CopyTo'] = 'COPIAR a';
    $et['es']['MoveTo'] = 'MOVER a';
    $et['es']['AlterPermTo'] = 'CAMBIAR PERMISIONES a';
    $et['es']['ConfExec'] = 'Confirmar EJECUCION';
    $et['es']['ConfRem'] = 'Confirmar BORRADO';
    $et['es']['EmptyDir'] = 'Directorio Vacio';
    $et['es']['IOError'] = 'Error I/O';
    $et['es']['FileMan'] = 'PHP File Manager';
    $et['es']['TypePass'] = 'Escriba la contraseña';
    $et['es']['InvPass'] = 'Contraseña invalida';
    $et['es']['ReadDenied'] = 'Acceso de lectura denegado';
    $et['es']['FileNotFound'] = 'Archivo no encontrado';
    $et['es']['AutoClose'] = 'Cerrar al completar';
    $et['es']['OutDocRoot'] = 'Archivo antes de DOCUMENT_ROOT';
    $et['es']['NoCmd'] = 'Error: No se ha escrito ningun comando';
    $et['es']['ConfTrySave'] = 'Archivo sin permisos de escritura.\\nIntente guardar en otro lugar';
    $et['es']['ConfSaved'] = 'Configuracion Guardada';
    $et['es']['PassSaved'] = 'Contraseña guardada';
    $et['es']['FileDirExists'] = 'Archivo o directorio ya existente';
    $et['es']['NoPhpinfo'] = 'Funcion phpinfo() inhabilitada';
    $et['es']['NoReturn'] = 'sin retorno';
    $et['es']['FileSent'] = 'Archivo enviado';
    $et['es']['SpaceLimReached'] = 'Limite de espacio en disco alcanzado';
    $et['es']['InvExt'] = 'Extension inalida';
    $et['es']['FileNoOverw'] = 'El archivo no pudo ser sobreescrito';
    $et['es']['FileOverw'] = 'Archivo sobreescrito';
    $et['es']['FileIgnored'] = 'Archivo ignorado';
    $et['es']['ChkVer'] = 'Chequear las actualizaciones';
    $et['es']['ChkVerAvailable'] = 'Nueva version, haga click aqui para descargar!!';
    $et['es']['ChkVerNotAvailable'] = 'Su version es la mas reciente.';
    $et['es']['ChkVerError'] = 'Error de coneccion.';
    $et['es']['Website'] = 'Sitio Web';
    $et['es']['SendingForm'] = 'Enviando archivos, espere!';
    $et['es']['NoFileSel'] = 'Ningun archivo seleccionado';
    $et['es']['SelAll'] = 'Todos';
    $et['es']['SelNone'] = 'Ninguno';
    $et['es']['SelInverse'] = 'Inverso';
    $et['es']['Selected_s'] = 'seleccionado';
    $et['es']['Total'] = 'total';
    $et['es']['Partition'] = 'Particion';
    $et['es']['RenderTime'] = 'Generado en';
    $et['es']['Seconds'] = 'seg';
    $et['es']['ErrorReport'] = 'Reporte de error';

    // Korean - by Airplanez
    $et['ko']['Version'] = '버전';
    $et['ko']['DocRoot'] = '웹서버 루트';
    $et['ko']['FLRoot'] = '파일 매니저 루트';
    $et['ko']['Name'] = '이름';
    $et['ko']['Enter'] = '입력';
    $et['ko']['Send'] = '전송';
    $et['ko']['Refresh'] = '새로고침';
    $et['ko']['SaveConfig'] = '환경 저장';
    $et['ko']['SavePass'] = '비밀번호 저장';
    $et['ko']['SaveFile'] = '파일 저장';
    $et['ko']['Save'] = '저장';
    $et['ko']['Leave'] = '나가기';
    $et['ko']['Edit'] = '수정';
    $et['ko']['View'] = '보기';
    $et['ko']['Config'] = '환경';
    $et['ko']['Ren'] = '이름바꾸기';
    $et['ko']['Rem'] = '삭제';
    $et['ko']['Compress'] = '압축하기';
    $et['ko']['Decompress'] = '압축풀기';
    $et['ko']['ResolveIDs'] = '소유자';
    $et['ko']['Move'] = '이동';
    $et['ko']['Copy'] = '복사';
    $et['ko']['ServerInfo'] = '서버 정보';
    $et['ko']['CreateDir'] = '디렉토리 생성';
    $et['ko']['CreateArq'] = '파일 생성';
    $et['ko']['ExecCmd'] = '명령 실행';
    $et['ko']['Upload'] = '업로드';
    $et['ko']['UploadEnd'] = '업로드가 완료되었습니다.';
    $et['ko']['Perm'] = '권한';
    $et['ko']['Perms'] = '권한';
    $et['ko']['Owner'] = '소유자';
    $et['ko']['Group'] = '그룹';
    $et['ko']['Other'] = '모든사용자';
    $et['ko']['Size'] = '크기';
    $et['ko']['Date'] = '날짜';
    $et['ko']['Type'] = '종류';
    $et['ko']['Free'] = '여유';
    $et['ko']['Shell'] = '쉘';
    $et['ko']['Read'] = '읽기';
    $et['ko']['Write'] = '쓰기';
    $et['ko']['Exec'] = '실행';
    $et['ko']['Apply'] = '적용';
    $et['ko']['StickyBit'] = '스티키 비트';
    $et['ko']['Pass'] = '비밀번호';
    $et['ko']['Lang'] = '언어';
    $et['ko']['File'] = '파일';
    $et['ko']['File_s'] = '파일';
    $et['ko']['To'] = '으로';
    $et['ko']['Destination'] = '대상';
    $et['ko']['Configurations'] = '환경';
    $et['ko']['JSError'] = '자바스크립트 오류';
    $et['ko']['NoSel'] = '선택된 것이 없습니다';
    $et['ko']['SelDir'] = '왼쪽리스트에서 대상 디렉토리를 선택하세요';
    $et['ko']['TypeDir'] = '디렉토리명을 입력하세요';
    $et['ko']['TypeArq'] = '파일명을 입력하세요';
    $et['ko']['TypeCmd'] = '명령을 입력하세요';
    $et['ko']['TypeArqComp'] = '파일명을 입력하세요.\\n확장자에 따라 압축형식이 정해집니다.\\n예:\\nnome.zip\\nnome.tar\\nnome.bzip\\nnome.gzip';
    $et['ko']['RemSel'] = '선택된 것을 삭제했습니다';
    $et['ko']['NoDestDir'] = '선택된 대상 디렉토리가 없습니다.';
    $et['ko']['DestEqOrig'] = '원래 디렉토리와 대상 디렉토리가 같습니다';
    $et['ko']['NoNewPerm'] = '새로운 권한이 설정되지 않았습니다';
    $et['ko']['CopyTo'] = '여기에 복사';
    $et['ko']['MoveTo'] = '여기로 이동';
    $et['ko']['AlterPermTo'] = '으로 권한변경';
    $et['ko']['ConfExec'] = '실행 확인';
    $et['ko']['ConfRem'] = '삭제 확인';
    $et['ko']['EmptyDir'] = '빈 디렉토리';
    $et['ko']['IOError'] = '입/출력 오류';
    $et['ko']['FileMan'] = 'PHP 파일 매니저';
    $et['ko']['TypePass'] = '비밀번호를 입력하세요';
    $et['ko']['InvPass'] = '비밀번호가 틀립니다';
    $et['ko']['ReadDenied'] = '읽기가 거부되었습니다';
    $et['ko']['FileNotFound'] = '파일이 없습니다';
    $et['ko']['AutoClose'] = '완료후 닫기';
    $et['ko']['OutDocRoot'] = 'DOCUMENT_ROOT 이내의 파일이 아닙니다';
    $et['ko']['NoCmd'] = '오류: 명령이 실행되지 않았습니다';
    $et['ko']['ConfTrySave'] = '파일에 쓰기 권한이 없습니다.\\n그래도 저장하시겠습니까';
    $et['ko']['ConfSaved'] = '환경이 저장되었습니다';
    $et['ko']['PassSaved'] = '비밀번호 저장';
    $et['ko']['FileDirExists'] = '파일 또는 디렉토리가 이미 존재합니다';
    $et['ko']['NoPhpinfo'] = 'PHPINFO()를 사용할수 없습니다';
    $et['ko']['NoReturn'] = '반환값 없음';
    $et['ko']['FileSent'] = '파일 전송';
    $et['ko']['SpaceLimReached'] = '저장공가 여유가 없습니다';
    $et['ko']['InvExt'] = '유효하지 않은 확장자';
    $et['ko']['FileNoOverw'] = '파일을 덮어 쓸수 없습니다';
    $et['ko']['FileOverw'] = '파일을 덮어 썼습니다';
    $et['ko']['FileIgnored'] = '파일이 무시되었습니다';
    $et['ko']['ChkVer'] = '에서 새버전 확인';
    $et['ko']['ChkVerAvailable'] = '새로운 버전이 있습니다. 다운받으려면 클릭하세요!!';
    $et['ko']['ChkVerNotAvailable'] = '새로운 버전이 없습니다. :(';
    $et['ko']['ChkVerError'] = '연결 오류';
    $et['ko']['Website'] = '웹사이트';
    $et['ko']['SendingForm'] = '파일을 전송중입니다. 기다리세요';
    $et['ko']['NoFileSel'] = '파일이 선택되지 않았습니다';
    $et['ko']['SelAll'] = '모든';
    $et['ko']['SelNone'] = '제로';
    $et['ko']['SelInverse'] = '역';

    // German - by Guido Ogrzal
    $et['de']['Version'] = 'Version';
    $et['de']['DocRoot'] = 'Dokument Wurzelverzeichnis';
    $et['de']['FLRoot'] = 'Dateimanager Wurzelverzeichnis';
    $et['de']['Name'] = 'Name';
    $et['de']['And'] = 'und';
    $et['de']['Enter'] = 'Eintreten';
    $et['de']['Send'] = 'Senden';
    $et['de']['Refresh'] = 'Aktualisieren';
    $et['de']['SaveConfig'] = 'Konfiguration speichern';
    $et['de']['SavePass'] = 'Passwort speichern';
    $et['de']['SaveFile'] = 'Datei speichern';
    $et['de']['Save'] = 'Speichern';
    $et['de']['Leave'] = 'Verlassen';
    $et['de']['Edit'] = 'Bearbeiten';
    $et['de']['View'] = 'Ansehen';
    $et['de']['Config'] = 'Konfigurieren';
    $et['de']['Ren'] = 'Umbenennen';
    $et['de']['Rem'] = 'Löschen';
    $et['de']['Compress'] = 'Komprimieren';
    $et['de']['Decompress'] = 'Dekomprimieren';
    $et['de']['ResolveIDs'] = 'Resolve IDs';
    $et['de']['Move'] = 'Verschieben';
    $et['de']['Copy'] = 'Kopieren';
    $et['de']['ServerInfo'] = 'Server-Info';
    $et['de']['CreateDir'] = 'Neues Verzeichnis';
    $et['de']['CreateArq'] = 'Neue Datei';
    $et['de']['ExecCmd'] = 'Kommando';
    $et['de']['Upload'] = 'Datei hochladen';
    $et['de']['UploadEnd'] = 'Datei hochladen beendet';
    $et['de']['Perm'] = 'Erlaubnis';
    $et['de']['Perms'] = 'Erlaubnis';
    $et['de']['Owner'] = 'Besitzer';
    $et['de']['Group'] = 'Gruppe';
    $et['de']['Other'] = 'Andere';
    $et['de']['Size'] = 'Größe';
    $et['de']['Date'] = 'Datum';
    $et['de']['Type'] = 'Typ';
    $et['de']['Free'] = 'frei';
    $et['de']['Shell'] = 'Shell';
    $et['de']['Read'] = 'Lesen';
    $et['de']['Write'] = 'Schreiben';
    $et['de']['Exec'] = 'Ausführen';
    $et['de']['Apply'] = 'Bestätigen';
    $et['de']['StickyBit'] = 'Sticky Bit';
    $et['de']['Pass'] = 'Passwort';
    $et['de']['Lang'] = 'Sprache';
    $et['de']['File'] = 'Datei';
    $et['de']['File_s'] = 'Datei(en)';
    $et['de']['Dir_s'] = 'Verzeichniss(e)';
    $et['de']['To'] = '-&gt;';
    $et['de']['Destination'] = 'Ziel';
    $et['de']['Configurations'] = 'Konfiguration';
    $et['de']['JSError'] = 'JavaScript Fehler';
    $et['de']['NoSel'] = 'Es gibt keine selektierten Objekte';
    $et['de']['SelDir'] = 'Selektiere das Zielverzeichnis im linken Verzeichnisbaum';
    $et['de']['TypeDir'] = 'Trage den Verzeichnisnamen ein';
    $et['de']['TypeArq'] = 'Trage den Dateinamen ein';
    $et['de']['TypeCmd'] = 'Gib das Kommando ein';
    $et['de']['TypeArqComp'] = 'Trage den Dateinamen ein.\\nDie Dateierweiterung wird den Kompressiontyp bestimmen.\\nBsp.:\\nnome.zip\\nnome.tar\\nnome.bzip\\nnome.gzip';
    $et['de']['RemSel'] = 'LÖSCHE die selektierten Objekte';
    $et['de']['NoDestDir'] = 'Das selektierte Zielverzeichnis existiert nicht';
    $et['de']['DestEqOrig'] = 'Quell- und Zielverzeichnis stimmen überein';
    $et['de']['InvalidDest'] = 'Zielverzeichnis ist ungültig';
    $et['de']['NoNewPerm'] = 'Neue Zugriffserlaubnis konnte nicht gesetzt werden';
    $et['de']['CopyTo'] = 'KOPIERE nach';
    $et['de']['MoveTo'] = 'VERSCHIEBE nach';
    $et['de']['AlterPermTo'] = 'ÄNDERE ZUGRIFFSERLAUBSNIS in';
    $et['de']['ConfExec'] = 'Bestätige AUSFÜHRUNG';
    $et['de']['ConfRem'] = 'Bestätige LÖSCHEN';
    $et['de']['EmptyDir'] = 'Leeres Verzeichnis';
    $et['de']['IOError'] = 'Eingabe/Ausgabe-Fehler';
    $et['de']['FileMan'] = 'PHP File Manager';
    $et['de']['TypePass'] = 'Trage das Passwort ein';
    $et['de']['InvPass'] = 'Ungültiges Passwort';
    $et['de']['ReadDenied'] = 'Lesezugriff verweigert';
    $et['de']['FileNotFound'] = 'Datei nicht gefunden';
    $et['de']['AutoClose'] = 'Schließen, wenn fertig';
    $et['de']['OutDocRoot'] = 'Datei außerhalb von DOCUMENT_ROOT';
    $et['de']['NoCmd'] = 'Fehler: Es wurde kein Kommando eingetragen';
    $et['de']['ConfTrySave'] = 'Keine Schreibberechtigung für die Datei.\\nVersuche trotzdem zu speichern';
    $et['de']['ConfSaved'] = 'Konfiguration gespeichert';
    $et['de']['PassSaved'] = 'Passwort gespeichert';
    $et['de']['FileDirExists'] = 'Datei oder Verzeichnis existiert schon';
    $et['de']['NoPhpinfo'] = 'Funktion phpinfo ist inaktiv';
    $et['de']['NoReturn'] = 'keine Rückgabe';
    $et['de']['FileSent'] = 'Datei wurde gesendet';
    $et['de']['SpaceLimReached'] = 'Verfügbares Speicherlimit wurde erreicht';
    $et['de']['InvExt'] = 'Ungültige Dateiendung';
    $et['de']['FileNoOverw'] = 'Datei kann nicht überschrieben werden';
    $et['de']['FileOverw'] = 'Datei überschrieben';
    $et['de']['FileIgnored'] = 'Datei ignoriert';
    $et['de']['ChkVer'] = 'Prüfe auf neue Version';
    $et['de']['ChkVerAvailable'] = 'Neue Version verfügbar; klicke hier, um den Download zu starten!!';
    $et['de']['ChkVerNotAvailable'] = 'Keine neue Version gefunden. :(';
    $et['de']['ChkVerError'] = 'Verbindungsfehler.';
    $et['de']['Website'] = 'Webseite';
    $et['de']['SendingForm'] = 'Sende Dateien... Bitte warten.';
    $et['de']['NoFileSel'] = 'Keine Datei selektiert';
    $et['de']['SelAll'] = 'Alle';
    $et['de']['SelNone'] = 'Keine';
    $et['de']['SelInverse'] = 'Invertieren';
    $et['de']['Selected_s'] = 'selektiert';
    $et['de']['Total'] = 'Gesamt';
    $et['de']['Partition'] = 'Partition';
    $et['de']['RenderTime'] = 'Zeit, um die Seite anzuzeigen';
    $et['de']['Seconds'] = 's';
    $et['de']['ErrorReport'] = 'Fehlerreport';

    // German - by AXL
    $et['de2']['Version'] = 'Version';
    $et['de2']['DocRoot'] = 'Document Stammverzeichnis';
    $et['de2']['FLRoot'] = 'Datei Manager Stammverzeichnis';
    $et['de2']['Name'] = 'Name';
    $et['de2']['And'] = 'und';
    $et['de2']['Enter'] = 'Enter';
    $et['de2']['Send'] = 'Senden';
    $et['de2']['Refresh'] = 'Aktualisieren';
    $et['de2']['SaveConfig'] = 'Konfiguration speichern';
    $et['de2']['SavePass'] = 'Passwort speichern';
    $et['de2']['SaveFile'] = 'Datei speichern';
    $et['de2']['Save'] = 'Speichern';
    $et['de2']['Leave'] = 'Verlassen';
    $et['de2']['Edit'] = 'Bearb.';
    $et['de2']['View'] = 'Anzeigen';
    $et['de2']['Config'] = 'Konfigurieren';
    $et['de2']['Ren'] = 'Umb.';
    $et['de2']['Rem'] = 'Löschen';
    $et['de2']['Compress'] = 'Komprimieren';
    $et['de2']['Decompress'] = 'De-Komprimieren';
    $et['de2']['ResolveIDs'] = 'IDs auflösen';
    $et['de2']['Move'] = 'Versch.';
    $et['de2']['Copy'] = 'Kopie';
    $et['de2']['ServerInfo'] = 'Server Info';
    $et['de2']['CreateDir'] = 'Verzeichnis erstellen';
    $et['de2']['CreateArq'] = 'Datei erstellen';
    $et['de2']['ExecCmd'] = 'Befehl ausführen';
    $et['de2']['Upload'] = 'Upload';
    $et['de2']['UploadEnd'] = 'Upload abgeschlossen';
    $et['de2']['Perm'] = 'Rechte';
    $et['de2']['Perms'] = 'Rechte';
    $et['de2']['Owner'] = 'Besitzer';
    $et['de2']['Group'] = 'Gruppe';
    $et['de2']['Other'] = 'Andere';
    $et['de2']['Size'] = 'Größe';
    $et['de2']['Date'] = 'Datum';
    $et['de2']['Type'] = 'Typ';
    $et['de2']['Free'] = 'frei';
    $et['de2']['Shell'] = 'Shell';
    $et['de2']['Read'] = 'Read';
    $et['de2']['Write'] = 'Write';
    $et['de2']['Exec'] = 'Execute';
    $et['de2']['Apply'] = 'Anwenden';
    $et['de2']['StickyBit'] = 'Sticky Bit';
    $et['de2']['Pass'] = 'Passwort';
    $et['de2']['Lang'] = 'Sprache';
    $et['de2']['File'] = 'Datei';
    $et['de2']['File_s'] = 'Datei(en)';
    $et['de2']['Dir_s'] = 'Verzeichnis(se)';
    $et['de2']['To'] = 'an';
    $et['de2']['Destination'] = 'Ziel';
    $et['de2']['Configurations'] = 'Konfigurationen';
    $et['de2']['JSError'] = 'JavaScript Fehler';
    $et['de2']['NoSel'] = 'Keine Einträge ausgewählt';
    $et['de2']['SelDir'] = 'Wählen Sie das Zeilverzeichnis im Verzeichnis links';
    $et['de2']['TypeDir'] = 'Geben Sie den Verzeichnisnamen ein';
    $et['de2']['TypeArq'] = 'Geben Sie den Dateinamen ein';
    $et['de2']['TypeCmd'] = 'Geben Sie den Befehl ein';
    $et['de2']['TypeArqComp'] = 'Geben Sie den Dateinamen ein.\\nDie Datei-Extension legt den Kopressionstyp fest.\\nBeispiel:\\nnome.zip\\nnome.tar\\nnome.bzip\\nnome.gzip';
    $et['de2']['RemSel'] = 'Ausgewählte Dateien LÖSCHEN';
    $et['de2']['NoDestDir'] = 'Es wurde kein Zielverzeichnis angegeben';
    $et['de2']['DestEqOrig'] = 'Quell- und Zielverzeichnis sind identisch';
    $et['de2']['InvalidDest'] = 'Zielverzeichnis ungültig';
    $et['de2']['NoNewPerm'] = 'Unzureichende Rechte';
    $et['de2']['CopyTo'] = 'KOPIEREN nach';
    $et['de2']['MoveTo'] = 'VERSCHIEBEN nach';
    $et['de2']['AlterPermTo'] = 'RECHTE ÄNDERN in';
    $et['de2']['ConfExec'] = 'Bestätigung AUSFÜHREN';
    $et['de2']['ConfRem'] = 'Bestätigung LÖSCHEN';
    $et['de2']['EmptyDir'] = 'Leeres Verzeichnis';
    $et['de2']['IOError'] = 'Ein-/Ausgabe-Fehler';
    $et['de2']['FileMan'] = 'PHP File Manager';
    $et['de2']['TypePass'] = 'Bitte geben Sie das Passwort ein';
    $et['de2']['InvPass'] = 'Ungültiges Passwort';
    $et['de2']['ReadDenied'] = 'Leasezugriff verweigert';
    $et['de2']['FileNotFound'] = 'Datei nicht gefunden';
    $et['de2']['AutoClose'] = 'Schliessen nach Beenden';
    $et['de2']['OutDocRoot'] = 'Datei oberhalb DOCUMENT_ROOT';
    $et['de2']['NoCmd'] = 'Fehler: Befehl nicht informed';
    $et['de2']['ConfTrySave'] = 'Datei ohne Schreibberechtigung.\\nTrotzdem versuchen zu speichern';
    $et['de2']['ConfSaved'] = 'Konfigurationen gespeichert';
    $et['de2']['PassSaved'] = 'Passwort gespeichert';
    $et['de2']['FileDirExists'] = 'Datei oder Verzeichnis existiert bereits';
    $et['de2']['NoPhpinfo'] = 'Funktion phpinfo ausgeschaltet';
    $et['de2']['NoReturn'] = 'keine Rückgabe';
    $et['de2']['FileSent'] = 'Datei versandt';
    $et['de2']['SpaceLimReached'] = 'Plattenplatz erschöpft';
    $et['de2']['InvExt'] = 'Ungültige datei-Extension';
    $et['de2']['FileNoOverw'] = 'Datei kann nicht überschrieben werden';
    $et['de2']['FileOverw'] = 'Datei überschrieben';
    $et['de2']['FileIgnored'] = 'Datei ignoriert';
    $et['de2']['ChkVer'] = 'Überprüfe neuer Version';
    $et['de2']['ChkVerAvailable'] = 'Neue Version. Hier klicken für Download!!';
    $et['de2']['ChkVerNotAvailable'] = 'Keine neue Version verfügbar. :(';
    $et['de2']['ChkVerError'] = 'Verbindungsfehler.';
    $et['de2']['Website'] = 'Webseite';
    $et['de2']['SendingForm'] = 'Sende Dateien, bitte warten';
    $et['de2']['NoFileSel'] = 'Keine Dateien ausgewählt';
    $et['de2']['SelAll'] = 'Alle';
    $et['de2']['SelNone'] = 'Keine';
    $et['de2']['SelInverse'] = 'Invers';
    $et['de2']['Selected_s'] = 'ausgewählt';
    $et['de2']['Total'] = 'Total';
    $et['de2']['Partition'] = 'Partition';
    $et['de2']['RenderTime'] = 'Zeit zum Erzeugen der Seite';
    $et['de2']['Seconds'] = 'Sekunden';
    $et['de2']['ErrorReport'] = 'Fehler berichten';

    // German - by Mathias Rothe
    $et['de3']['Version'] = 'Version';
    $et['de3']['DocRoot'] = 'Dokumenten Root';
    $et['de3']['FLRoot'] = 'Datei Manager Root';
    $et['de3']['Name'] = 'Name';
    $et['de3']['And'] = 'und';
    $et['de3']['Enter'] = 'Enter';
    $et['de3']['Send'] = 'Senden';
    $et['de3']['Refresh'] = 'Refresh';
    $et['de3']['SaveConfig'] = 'Konfiguration speichern';
    $et['de3']['SavePass'] = 'Passwort speichern';
    $et['de3']['SaveFile'] = 'Datei speichern';
    $et['de3']['Save'] = 'Speichern';
    $et['de3']['Leave'] = 'Abbrechen';
    $et['de3']['Edit'] = 'Bearbeiten';
    $et['de3']['View'] = 'Anzeigen';
    $et['de3']['Config'] = 'Konfiguration';
    $et['de3']['Ren'] = 'Umbenennen';
    $et['de3']['Rem'] = 'Entfernen';
    $et['de3']['Compress'] = 'Packen';
    $et['de3']['Decompress'] = 'Entpacken';
    $et['de3']['ResolveIDs'] = 'IDs aufloesen';
    $et['de3']['Move'] = 'Verschieben';
    $et['de3']['Copy'] = 'Kopie';
    $et['de3']['ServerInfo'] = 'Server Info';
    $et['de3']['CreateDir'] = 'Neuer Ordner';
    $et['de3']['CreateArq'] = 'Neue Datei';
    $et['de3']['ExecCmd'] = 'Befehl ausfuehren';
    $et['de3']['Upload'] = 'Upload';
    $et['de3']['UploadEnd'] = 'Upload beendet';
    $et['de3']['Perm'] = 'Rechte';
    $et['de3']['Perms'] = 'Rechte';
    $et['de3']['Owner'] = 'Eigent';
    $et['de3']['Group'] = 'Gruppe';
    $et['de3']['Other'] = 'Andere';
    $et['de3']['Size'] = 'Groesse';
    $et['de3']['Date'] = 'Datum';
    $et['de3']['Type'] = 'Typ';
    $et['de3']['Free'] = 'frei';
    $et['de3']['Shell'] = 'Shell';
    $et['de3']['Read'] = 'Lesen';
    $et['de3']['Write'] = 'Schreiben';
    $et['de3']['Exec'] = 'Ausfuehren';
    $et['de3']['Apply'] = 'Bestaetigen';
    $et['de3']['StickyBit'] = 'Sticky Bit';
    $et['de3']['Pass'] = 'Passwort';
    $et['de3']['Lang'] = 'Sprache';
    $et['de3']['File'] = 'Datei';
    $et['de3']['File_s'] = 'Datei(en)';
    $et['de3']['Dir_s'] = 'Ordner';
    $et['de3']['To'] = 'nach';
    $et['de3']['Destination'] = 'Ziel';
    $et['de3']['Configurations'] = 'Konfiguration';
    $et['de3']['JSError'] = 'JavaScript Error';
    $et['de3']['NoSel'] = 'Keine Objekte ausgewaehlt';
    $et['de3']['SelDir'] = 'Waehlen Sie links das Zielverzeichnis aus';
    $et['de3']['TypeDir'] = 'Verzeichnisname eingeben';
    $et['de3']['TypeArq'] = 'Dateiname eingeben';
    $et['de3']['TypeCmd'] = 'Befehl eingeben';
    $et['de3']['TypeArqComp'] = 'Dateinamen eingeben.\\nDie Erweiterung definiert den Archiv-Typ.\\nEx:\\nname.zip\\nname.tar\\nname.bzip\\nname.gzip';
    $et['de3']['RemSel'] = 'Entferne ausgewaehlte Objekte';
    $et['de3']['NoDestDir'] = 'Kein Zielverzeichnis ausgewaehlt';
    $et['de3']['DestEqOrig'] = 'Quelle und Zielverzeichnis sind gleich';
    $et['de3']['InvalidDest'] = 'Zielverzeichnis ungueltig';
    $et['de3']['NoNewPerm'] = 'Neue Rechte nicht gesetzt';
    $et['de3']['CopyTo'] = 'Kopiere nach';
    $et['de3']['MoveTo'] = 'Verschiebe nach';
    $et['de3']['AlterPermTo'] = 'Aendere Rechte zu';
    $et['de3']['ConfExec'] = 'Ausfuehren bestaetigen';
    $et['de3']['ConfRem'] = 'Entfernen bestaetigen';
    $et['de3']['EmptyDir'] = 'Leerer Ordner';
    $et['de3']['IOError'] = 'I/O Fehler';
    $et['de3']['FileMan'] = 'PHP Datei Manager';
    $et['de3']['TypePass'] = 'Bitte Passwort eingeben';
    $et['de3']['InvPass'] = 'Falsches Passwort';
    $et['de3']['ReadDenied'] = 'Kein Lesezugriff';
    $et['de3']['FileNotFound'] = 'Datei nicht gefunden';
    $et['de3']['AutoClose'] = 'Beenden bei Fertigstellung';
    $et['de3']['OutDocRoot'] = 'Datei ausserhalb des DOCUMENT_ROOT';
    $et['de3']['NoCmd'] = 'Fehler: unbekannter Befehl';
    $et['de3']['ConfTrySave'] = 'Datei ohne Schreibrecht.\\nVersuche dennoch zu speichern';
    $et['de3']['ConfSaved'] = 'Konfiguration gespeichert';
    $et['de3']['PassSaved'] = 'Passwort gespeichert';
    $et['de3']['FileDirExists'] = 'Datei oder Verzeichnis existiert bereits';
    $et['de3']['NoPhpinfo'] = 'Funktion phpinfo gesperrt';
    $et['de3']['NoReturn'] = 'kein zurueck';
    $et['de3']['FileSent'] = 'Datei gesendet';
    $et['de3']['SpaceLimReached'] = 'Speicherplatz Grenze erreicht';
    $et['de3']['InvExt'] = 'Ungueltige Erweiterung';
    $et['de3']['FileNoOverw'] = 'Datei konnte nicht ueberschrieben werden';
    $et['de3']['FileOverw'] = 'Datei ueberschrieben';
    $et['de3']['FileIgnored'] = 'Datei ignoriert';
    $et['de3']['ChkVer'] = 'Puefe eine neuere Version';
    $et['de3']['ChkVerAvailable'] = 'Neue Version, hier klicken zum Download!!';
    $et['de3']['ChkVerNotAvailable'] = 'Keine neuere Version vorhanden. :(';
    $et['de3']['ChkVerError'] = 'Verbindungsfehler.';
    $et['de3']['Website'] = 'Website';
    $et['de3']['SendingForm'] = 'Dateien werden gesendet, bitte warten';
    $et['de3']['NoFileSel'] = 'Keine Datei ausgewaehlt';
    $et['de3']['SelAll'] = 'Alle';
    $et['de3']['SelNone'] = 'Keine';
    $et['de3']['SelInverse'] = 'Invertiere';
    $et['de3']['Selected_s'] = 'ausgewaehlt';
    $et['de3']['Total'] = 'gesamt';
    $et['de3']['Partition'] = 'Partition';
    $et['de3']['RenderTime'] = 'Zeit zur Erzeugung dieser Seite';
    $et['de3']['Seconds'] = 'sec';
    $et['de3']['ErrorReport'] = 'Fehlermeldungen';

    // French - by Jean Bilwes
    $et['fr']['Version'] = 'Version';
    $et['fr']['DocRoot'] = 'Racine des documents';
    $et['fr']['FLRoot'] = 'Racine du gestionnaire de fichers';
    $et['fr']['Name'] = 'Nom';
    $et['fr']['And'] = 'et';
    $et['fr']['Enter'] = 'Enter';
    $et['fr']['Send'] = 'Envoyer';
    $et['fr']['Refresh'] = 'Rafraichir';
    $et['fr']['SaveConfig'] = 'Enregistrer la Configuration';
    $et['fr']['SavePass'] = 'Enregistrer le mot de passe';
    $et['fr']['SaveFile'] = 'Enregistrer le fichier';
    $et['fr']['Save'] = 'Enregistrer';
    $et['fr']['Leave'] = 'Quitter';
    $et['fr']['Edit'] = 'Modifier';
    $et['fr']['View'] = 'Voir';
    $et['fr']['Config'] = 'Config';
    $et['fr']['Ren'] = 'Renommer';
    $et['fr']['Rem'] = 'Detruire';
    $et['fr']['Compress'] = 'Compresser';
    $et['fr']['Decompress'] = 'Decompresser';
    $et['fr']['ResolveIDs'] = 'Resoudre les IDs';
    $et['fr']['Move'] = 'Déplacer';
    $et['fr']['Copy'] = 'Copier';
    $et['fr']['ServerInfo'] = 'info du sreveur';
    $et['fr']['CreateDir'] = 'Créer un répertoire';
    $et['fr']['CreateArq'] = 'Créer un fichier';
    $et['fr']['ExecCmd'] = 'Executer une Commande';
    $et['fr']['Upload'] = 'Téléversement(upload)';
    $et['fr']['UploadEnd'] = 'Téléversement Fini';
    $et['fr']['Perm'] = 'Perm';
    $et['fr']['Perms'] = 'Permissions';
    $et['fr']['Owner'] = 'Propriétaire';
    $et['fr']['Group'] = 'Groupe';
    $et['fr']['Other'] = 'Autre';
    $et['fr']['Size'] = 'Taille';
    $et['fr']['Date'] = 'Date';
    $et['fr']['Type'] = 'Type';
    $et['fr']['Free'] = 'libre';
    $et['fr']['Shell'] = 'Shell';
    $et['fr']['Read'] = 'Lecture';
    $et['fr']['Write'] = 'Ecriture';
    $et['fr']['Exec'] = 'Executer';
    $et['fr']['Apply'] = 'Appliquer';
    $et['fr']['StickyBit'] = 'Sticky Bit';
    $et['fr']['Pass'] = 'Mot de passe';
    $et['fr']['Lang'] = 'Langage';
    $et['fr']['File'] = 'Fichier';
    $et['fr']['File_s'] = 'fichier(s)';
    $et['fr']['Dir_s'] = 'répertoire(s)';
    $et['fr']['To'] = 'à';
    $et['fr']['Destination'] = 'Destination';
    $et['fr']['Configurations'] = 'Configurations';
    $et['fr']['JSError'] = 'Erreur JavaScript';
    $et['fr']['NoSel'] = 'Rien n\'est sélectionné';
    $et['fr']['SelDir'] = 'Selectionnez le répertoire de destination dans le panneau gauche';
    $et['fr']['TypeDir'] = 'Entrer le nom du répertoire';
    $et['fr']['TypeArq'] = 'Entrer le nom du fichier';
    $et['fr']['TypeCmd'] = 'Entrer la commande';
    $et['fr']['TypeArqComp'] = 'Entrer le nom du fichier.\\nL\'extension définira le type de compression.\\nEx:\\nnome.zip\\nnome.tar\\nnome.bzip\\nnome.gzip';
    $et['fr']['RemSel'] = 'EFFACER les objets sélectionnés';
    $et['fr']['NoDestDir'] = 'Aucun répertoire de destination n\'est sélectionné';
    $et['fr']['DestEqOrig'] = 'Les répertoires source et destination sont identiques';
    $et['fr']['InvalidDest'] = 'Le répertoire de destination est invalide';
    $et['fr']['NoNewPerm'] = 'Nouvelle permission non établie';
    $et['fr']['CopyTo'] = 'COPIER vers';
    $et['fr']['MoveTo'] = 'DEPLACER vers';
    $et['fr']['AlterPermTo'] = 'CHANGER LES PERMISSIONS';
    $et['fr']['ConfExec'] = 'Confirmer l\'EXECUTION';
    $et['fr']['ConfRem'] = 'Confirmer la DESTRUCTION';
    $et['fr']['EmptyDir'] = 'Répertoire vide';
    $et['fr']['IOError'] = 'I/O Error';
    $et['fr']['FileMan'] = 'PHP File Manager';
    $et['fr']['TypePass'] = 'Entrer le mot de passe';
    $et['fr']['InvPass'] = 'Mot de passe invalide';
    $et['fr']['ReadDenied'] = 'Droit de lecture refusé';
    $et['fr']['FileNotFound'] = 'Fichier introuvable';
    $et['fr']['AutoClose'] = 'Fermer sur fin';
    $et['fr']['OutDocRoot'] = 'Fichier au delà de DOCUMENT_ROOT';
    $et['fr']['NoCmd'] = 'Erreur: Commande non renseignée';
    $et['fr']['ConfTrySave'] = 'Fichier sans permission d\'écriture.\\nJ\'essaie de l\'enregister';
    $et['fr']['ConfSaved'] = 'Configurations enreristrée';
    $et['fr']['PassSaved'] = 'Mot de passe enreristré';
    $et['fr']['FileDirExists'] = 'Le fichier ou le répertoire existe déjà';
    $et['fr']['NoPhpinfo'] = 'Function phpinfo désactivée';
    $et['fr']['NoReturn'] = 'pas de retour';
    $et['fr']['FileSent'] = 'Fichier envoyé';
    $et['fr']['SpaceLimReached'] = 'Espace maxi atteint';
    $et['fr']['InvExt'] = 'Extension invalide';
    $et['fr']['FileNoOverw'] = 'Le fichier ne peut pas etre écrasé';
    $et['fr']['FileOverw'] = 'Fichier écrasé';
    $et['fr']['FileIgnored'] = 'Fichier ignoré';
    $et['fr']['ChkVer'] = 'Verifier nouvelle version';
    $et['fr']['ChkVerAvailable'] = 'Nouvelle version, cliquer ici pour la téléchager!!';
    $et['fr']['ChkVerNotAvailable'] = 'Aucune mise a jour de disponible. :(';
    $et['fr']['ChkVerError'] = 'Erreur de connection.';
    $et['fr']['Website'] = 'siteweb';
    $et['fr']['SendingForm'] = 'Envoi des fichiers en cours, Patienter';
    $et['fr']['NoFileSel'] = 'Aucun fichier sélectionné';
    $et['fr']['SelAll'] = 'Tous';
    $et['fr']['SelNone'] = 'Aucun';
    $et['fr']['SelInverse'] = 'Inverser';
    $et['fr']['Selected_s'] = 'selectioné';
    $et['fr']['Total'] = 'total';
    $et['fr']['Partition'] = 'Partition';
    $et['fr']['RenderTime'] = 'Temps pour afficher cette page';
    $et['fr']['Seconds'] = 'sec';
    $et['fr']['ErrorReport'] = 'Rapport d\'erreur';

    // French - by Sharky
    $et['fr2']['Version'] = 'Version';
    $et['fr2']['DocRoot'] = 'Racine document';
    $et['fr2']['FLRoot'] = 'Gestion des fichiers racine';
    $et['fr2']['Name'] = 'Nom';
    $et['fr2']['And'] = 'et';
    $et['fr2']['Enter'] = 'Entrer';
    $et['fr2']['Send'] = 'Envoi';
    $et['fr2']['Refresh'] = 'Rafraîchir';
    $et['fr2']['SaveConfig'] = 'Sauver configurations';
    $et['fr2']['SavePass'] = 'Sauver mot de passe';
    $et['fr2']['SaveFile'] = 'Sauver fichier';
    $et['fr2']['Save'] = 'Sauver';
    $et['fr2']['Leave'] = 'Permission';
    $et['fr2']['Edit'] = 'Éditer';
    $et['fr2']['View'] = 'Afficher';
    $et['fr2']['Config'] = 'config';
    $et['fr2']['Ren'] = 'Renommer';
    $et['fr2']['Rem'] = 'Effacer';
    $et['fr2']['Compress'] = 'Compresser';
    $et['fr2']['Decompress'] = 'Décompresser';
    $et['fr2']['ResolveIDs'] = 'Résoudre ID';
    $et['fr2']['Move'] = 'Déplacer';
    $et['fr2']['Copy'] = 'Copier';
    $et['fr2']['ServerInfo'] = 'Information Serveur';
    $et['fr2']['CreateDir'] = 'Créer un répertoire';
    $et['fr2']['CreateArq'] = 'Créer un fichier';
    $et['fr2']['ExecCmd'] = 'Executé une commande';
    $et['fr2']['Upload'] = 'Transférer';
    $et['fr2']['UploadEnd'] = 'Transfert terminé';
    $et['fr2']['Perm'] = 'Perm';
    $et['fr2']['Perms'] = 'Permissions';
    $et['fr2']['Owner'] = 'Propriétaire';
    $et['fr2']['Group'] = 'Groupe';
    $et['fr2']['Other'] = 'Autre';
    $et['fr2']['Size'] = 'Taille';
    $et['fr2']['Date'] = 'date';
    $et['fr2']['Type'] = 'Type';
    $et['fr2']['Free'] = 'Libre';
    $et['fr2']['Shell'] = 'Shell';
    $et['fr2']['Read'] = 'lecture';
    $et['fr2']['Write'] = 'écriture';
    $et['fr2']['Exec'] = 'Execute';
    $et['fr2']['Apply'] = 'Appliquer';
    $et['fr2']['StickyBit'] = 'Bit figer';
    $et['fr2']['Pass'] = 'mot de passe';
    $et['fr2']['Lang'] = 'Language';
    $et['fr2']['File'] = 'Fichier';
    $et['fr2']['File_s'] = 'fichier(s)';
    $et['fr2']['Dir_s'] = 'répertoire(s)';
    $et['fr2']['To'] = 'à';
    $et['fr2']['Destination'] = 'Destination';
    $et['fr2']['Configurations'] = 'Configurations';
    $et['fr2']['JSError'] = 'Erreur JavaScript';
    $et['fr2']['NoSel'] = 'Il n\'y a pas d\'objets sélectionnés';
    $et['fr2']['SelDir'] = 'Sélectionnez le répertoire de destination sur l\'arborescence de gauche';
    $et['fr2']['TypeDir'] = 'Entrez le nom du répertoire';
    $et['fr2']['TypeArq'] = 'Entrez le nom du fichier';
    $et['fr2']['TypeCmd'] = 'Entrez la commande';
    $et['fr2']['TypeArqComp'] = 'Entrez le fichier.\\nL\'extension définira le type de compression.\\nEx:\\nnom.zip\\nnom.tar\\nnom.bzip\\nnom.gzip';
    $et['fr2']['RemSel'] = 'EFFACEZ l\'objet sélectionné';
    $et['fr2']['NoDestDir'] = 'Il n\'y a aucun répertoire de destination sélectionné';
    $et['fr2']['DestEqOrig'] = 'Origine et répertoires de destination sont identique';
    $et['fr2']['InvalidDest'] = 'Répertoire de destination est invalide';
    $et['fr2']['NoNewPerm'] = 'Nouvelle autorisation n\'a pas été configuré';
    $et['fr2']['CopyTo'] = 'COPIE dans';
    $et['fr2']['MoveTo'] = 'DÉPLACER dans';
    $et['fr2']['AlterPermTo'] = 'CHANGER PERMISSIONS dans';
    $et['fr2']['ConfExec'] = 'Confirmer EXECUTE';
    $et['fr2']['ConfRem'] = 'Confirmer EFFACER';
    $et['fr2']['EmptyDir'] = 'Répertoire vide';
    $et['fr2']['IOError'] = 'I/O Erreur';
    $et['fr2']['FileMan'] = 'Gestion de fichiers PHP';
    $et['fr2']['TypePass'] = 'Entrer le mot de passe';
    $et['fr2']['InvPass'] = 'Mot de passe invalide';
    $et['fr2']['ReadDenied'] = 'Accès en lecture refuser';
    $et['fr2']['FileNotFound'] = 'Fichier non-trouvé';
    $et['fr2']['AutoClose'] = 'Fermez a la fin';
    $et['fr2']['OutDocRoot'] = 'Fichier au-delà DOCUMENT_ROOT';
    $et['fr2']['NoCmd'] = 'Erreur: Commande inconnue';
    $et['fr2']['ConfTrySave'] = 'Fichier sans permission d\'écriture.\\nEssayez de sauver';
    $et['fr2']['ConfSaved'] = 'Configurations sauvée';
    $et['fr2']['PassSaved'] = 'Mot de passe sauvé';
    $et['fr2']['FileDirExists'] = 'Fichier ou répertoire déjà existant';
    $et['fr2']['NoPhpinfo'] = 'Function phpinfo désactivé';
    $et['fr2']['NoReturn'] = 'sans retour possible';
    $et['fr2']['FileSent'] = 'Fichier envoyé';
    $et['fr2']['SpaceLimReached'] = 'Limite de d\'espace atteint';
    $et['fr2']['InvExt'] = 'Extension invalide';
    $et['fr2']['FileNoOverw'] = 'Fichier ne peut pas être écrasé';
    $et['fr2']['FileOverw'] = 'Fichier écrasé';
    $et['fr2']['FileIgnored'] = 'Fichier ignoré';
    $et['fr2']['ChkVer'] = 'Check nouvelle version';
    $et['fr2']['ChkVerAvailable'] = 'Nouvelle version, cliquez ici pour commencer le téléchargement!!';
    $et['fr2']['ChkVerNotAvailable'] = 'Aucune nouvelle version disponible. :(';
    $et['fr2']['ChkVerError'] = 'Erreur de connection.';
    $et['fr2']['Website'] = 'Site Web';
    $et['fr2']['SendingForm'] = 'Envoye de fichier, s\'il vous plaît patientez';
    $et['fr2']['NoFileSel'] = 'Aucun fichier sélectionné';
    $et['fr2']['SelAll'] = 'Tout';
    $et['fr2']['SelNone'] = 'Aucuns';
    $et['fr2']['SelInverse'] = 'Inverser';
    $et['fr2']['Selected_s'] = 'sélectionné';
    $et['fr2']['Total'] = 'total';
    $et['fr2']['Partition'] = 'Partition';
    $et['fr2']['RenderTime'] = 'Temps pour afficher la page';
    $et['fr2']['Seconds'] = 'sec';
    $et['fr2']['ErrorReport'] = 'Liste des erreurs';

    // French - by Michel Lainey
    $et['fr3']['Version'] = 'Version';
    $et['fr3']['DocRoot'] = 'Racine Document';
    $et['fr3']['FLRoot'] = 'Racine File Manager';
    $et['fr3']['Name'] = 'Nom';
    $et['fr3']['And'] = 'et';
    $et['fr3']['Enter'] = 'Valider';
    $et['fr3']['Send'] = 'Envoyer';
    $et['fr3']['Refresh'] = 'Raffraichir';
    $et['fr3']['SaveConfig'] = 'Sauvegarder Config';
    $et['fr3']['SavePass'] = 'Sauvegarder Password';
    $et['fr3']['SaveFile'] = 'Sauvegarder Fichier';
    $et['fr3']['Save'] = 'Sauvegarder';
    $et['fr3']['Leave'] = 'Quitter';
    $et['fr3']['Edit'] = 'Editer';
    $et['fr3']['View'] = 'Visualiser';
    $et['fr3']['Config'] = 'Config';
    $et['fr3']['Ren'] = 'Renommer';
    $et['fr3']['Rem'] = 'Supprimer';
    $et['fr3']['Compress'] = 'Compresser';
    $et['fr3']['Decompress'] = 'Décompresser';
    $et['fr3']['ResolveIDs'] = 'Resoudre IDs';
    $et['fr3']['Move'] = 'Déplacer';
    $et['fr3']['Copy'] = 'Copier';
    $et['fr3']['ServerInfo'] = 'Server Info';
    $et['fr3']['CreateDir'] = 'Créer Répertoire';
    $et['fr3']['CreateArq'] = 'Créer Fichier';
    $et['fr3']['ExecCmd'] = 'Executer Commande';
    $et['fr3']['Upload'] = 'Upload';
    $et['fr3']['UploadEnd'] = 'Upload Fini';
    $et['fr3']['Perm'] = 'Perm';
    $et['fr3']['Perms'] = 'Permissions';
    $et['fr3']['Owner'] = 'Propriétaire';
    $et['fr3']['Group'] = 'Groupe';
    $et['fr3']['Other'] = 'Autres';
    $et['fr3']['Size'] = 'Taille';
    $et['fr3']['Date'] = 'Date';
    $et['fr3']['Type'] = 'Type';
    $et['fr3']['Free'] = 'libre';
    $et['fr3']['Shell'] = 'Shell';
    $et['fr3']['Read'] = 'Lecture';
    $et['fr3']['Write'] = 'Ecriture';
    $et['fr3']['Exec'] = 'Execute';
    $et['fr3']['Apply'] = 'Application';
    $et['fr3']['StickyBit'] = 'Sticky Bit';
    $et['fr3']['Pass'] = 'Password';
    $et['fr3']['Lang'] = 'Language';
    $et['fr3']['File'] = 'Fichier';
    $et['fr3']['File_s'] = 'fichier(s)';
    $et['fr3']['Dir_s'] = 'répertoire(s)';
    $et['fr3']['To'] = 'à';
    $et['fr3']['Destination'] = 'Destination';
    $et['fr3']['Configurations'] = 'Configurations';
    $et['fr3']['JSError'] = 'Erreur JavaScript';
    $et['fr3']['NoSel'] = 'Aucun élément sélectionné';
    $et['fr3']['SelDir'] = "Sélectionner le répertoire de destination dans l'arboresence de gauchethe destination directory on the left tree";
    $et['fr3']['TypeDir'] = 'Indiquer le nom du répertoire';
    $et['fr3']['TypeArq'] = 'Indiquer le nom du fichier';
    $et['fr3']['TypeCmd'] = 'Entrer une commande';
    $et['fr3']['TypeArqComp'] = "Indiquer le nom du fichier.\\nL'extension définira le type de compression.\\nEx:\\nnome.zip\\nnome.tar\\nnome.bzip\\nnome.gzip";
    $et['fr3']['RemSel'] = "SUPPRIMER l'élément sélectionné";
    $et['fr3']['NoDestDir'] = "Il n'y a pas de répertoire destination sélectionné";
    $et['fr3']['DestEqOrig'] = 'Répertoire Origine et Destination sont identiques';
    $et['fr3']['InvalidDest'] = 'Le répertoire de destination est invalide';
    $et['fr3']['NoNewPerm'] = 'Nouvelle permission non appliquée';
    $et['fr3']['CopyTo'] = 'COPIER vers';
    $et['fr3']['MoveTo'] = 'DEPLACER vers';
    $et['fr3']['AlterPermTo'] = 'CHANGER LES PERMISSIONS vers';
    $et['fr3']['ConfExec'] = 'Confirmer EXECUTION';
    $et['fr3']['ConfRem'] = 'Confirmer SUPPRESSION';
    $et['fr3']['EmptyDir'] = 'Répertoire vide';
    $et['fr3']['IOError'] = 'Erreur entrée/sortie';
    $et['fr3']['FileMan'] = 'PHP File Manager';
    $et['fr3']['TypePass'] = 'Saisir le mot de passe';
    $et['fr3']['InvPass'] = 'Mot de passe invalide';
    $et['fr3']['ReadDenied'] = 'Accès en lecture refusé';
    $et['fr3']['FileNotFound'] = 'Fichier non trouvé';
    $et['fr3']['AutoClose'] = 'Fermeture en fin de traitement';
    $et['fr3']['OutDocRoot'] = 'Fichier en dessous de DOCUMENT_ROOT';
    $et['fr3']['NoCmd'] = 'Erreur : Commande non renseignée';
    $et['fr3']['ConfTrySave'] = "Fichier sans permission d'écriture.\\nTenter de sauver malgré tout";
    $et['fr3']['ConfSaved'] = 'Configurations sauvegardée';
    $et['fr3']['PassSaved'] = 'Password sauvegardé';
    $et['fr3']['FileDirExists'] = 'Fichier ou répertoire déjà existant';
    $et['fr3']['NoPhpinfo'] = 'Fonction phpinfo disactivée';
    $et['fr3']['NoReturn'] = 'pas de retour';
    $et['fr3']['FileSent'] = 'Fichier envoyé';
    $et['fr3']['SpaceLimReached'] = 'Capacité maximale atteinte';
    $et['fr3']['InvExt'] = 'Extension invalide';
    $et['fr3']['FileNoOverw'] = 'Fichier ne pouvant être remplacé';
    $et['fr3']['FileOverw'] = 'Fichier remplacé';
    $et['fr3']['FileIgnored'] = 'Fichier ignoré';
    $et['fr3']['ChkVer'] = 'Vérifier nouvelle version';
    $et['fr3']['ChkVerAvailable'] = 'Nouvelle version, cliquer ici pour commencer le téléchargement !';
    $et['fr3']['ChkVerNotAvailable'] = 'Pas de nouvelle version disponible. :(';
    $et['fr3']['ChkVerError'] = 'Erreur de connection.';
    $et['fr3']['Website'] = 'Site Web';
    $et['fr3']['SendingForm'] = "Fichiers en cours d'envoi, merci de patienter";
    $et['fr3']['NoFileSel'] = 'Pas de fichier sélectionné';
    $et['fr3']['SelAll'] = 'Tous';
    $et['fr3']['SelNone'] = 'Aucun';
    $et['fr3']['SelInverse'] = 'Inverser';
    $et['fr3']['Selected_s'] = 'sélectionné';
    $et['fr3']['Total'] = 'total';
    $et['fr3']['Partition'] = 'Partition';
    $et['fr3']['RenderTime'] = 'Temps nécessaire pour obtenir cette page';
    $et['fr3']['Seconds'] = 'sec';
    $et['fr3']['ErrorReport'] = 'Erreur de compte rendu';

    // Dutch - by Leon Buijs
    $et['nl']['Version'] = 'Versie';
    $et['nl']['DocRoot'] = 'Document Root';
    $et['nl']['FLRoot'] = 'File Manager Root';
    $et['nl']['Name'] = 'Naam';
    $et['nl']['And'] = 'en';
    $et['nl']['Enter'] = 'Enter';
    $et['nl']['Send'] = 'Verzend';
    $et['nl']['Refresh'] = 'Vernieuw';
    $et['nl']['SaveConfig'] = 'Configuratie opslaan';
    $et['nl']['SavePass'] = 'Wachtwoord opslaan';
    $et['nl']['SaveFile'] = 'Bestand opslaan';
    $et['nl']['Save'] = 'Opslaan';
    $et['nl']['Leave'] = 'Verlaten';
    $et['nl']['Edit'] = 'Wijzigen';
    $et['nl']['View'] = 'Toon';
    $et['nl']['Config'] = 'Configuratie';
    $et['nl']['Ren'] = 'Naam wijzigen';
    $et['nl']['Rem'] = 'Verwijderen';
    $et['nl']['Compress'] = 'Comprimeren';
    $et['nl']['Decompress'] = 'Decomprimeren';
    $et['nl']['ResolveIDs'] = 'Resolve IDs';
    $et['nl']['Move'] = 'Verplaats';
    $et['nl']['Copy'] = 'Kopieer';
    $et['nl']['ServerInfo'] = 'Serverinformatie';
    $et['nl']['CreateDir'] = 'Nieuwe map';
    $et['nl']['CreateArq'] = 'Nieuw bestand';
    $et['nl']['ExecCmd'] = 'Commando uitvoeren';
    $et['nl']['Upload'] = 'Upload';
    $et['nl']['UploadEnd'] = 'Upload voltooid';
    $et['nl']['Perm'] = 'Rechten';
    $et['nl']['Perms'] = 'Rechten';
    $et['nl']['Owner'] = 'Eigenaar';
    $et['nl']['Group'] = 'Groep';
    $et['nl']['Other'] = 'Anderen';
    $et['nl']['Size'] = 'Grootte';
    $et['nl']['Date'] = 'Datum';
    $et['nl']['Type'] = 'Type';
    $et['nl']['Free'] = 'free';
    $et['nl']['Shell'] = 'Shell';
    $et['nl']['Read'] = 'Lezen';
    $et['nl']['Write'] = 'Schrijven';
    $et['nl']['Exec'] = 'Uitvoeren';
    $et['nl']['Apply'] = 'Toepassen';
    $et['nl']['StickyBit'] = 'Sticky Bit';
    $et['nl']['Pass'] = 'Wachtwoord';
    $et['nl']['Lang'] = 'Taal';
    $et['nl']['File'] = 'Bestand';
    $et['nl']['File_s'] = 'bestand(en)';
    $et['nl']['Dir_s'] = 'map(pen)';
    $et['nl']['To'] = 'naar';
    $et['nl']['Destination'] = 'Bestemming';
    $et['nl']['Configurations'] = 'Instellingen';
    $et['nl']['JSError'] = 'Javascriptfout';
    $et['nl']['NoSel'] = 'Er zijn geen bestanden geselecteerd';
    $et['nl']['SelDir'] = 'Kies de bestemming in de boom aan de linker kant';
    $et['nl']['TypeDir'] = 'Voer de mapnaam in';
    $et['nl']['TypeArq'] = 'Voer de bestandsnaam in';
    $et['nl']['TypeCmd'] = 'Voer het commando in';
    $et['nl']['TypeArqComp'] = 'Voer de bestandsnaam in.\\nDe extensie zal het compressietype bepalen.\\nEx:\\nnome.zip\\nnome.tar\\nnome.bzip\\nnome.gzip';
    $et['nl']['RemSel'] = 'VERWIJDER geselecteerde itens';
    $et['nl']['NoDestDir'] = 'Er is geen doelmap geselecteerd';
    $et['nl']['DestEqOrig'] = 'Bron- en doelmap zijn hetzelfde';
    $et['nl']['InvalidDest'] = 'Doelmap is ongeldig';
    $et['nl']['NoNewPerm'] = 'Nieuwe rechten niet geset';
    $et['nl']['CopyTo'] = 'KOPIEER naar';
    $et['nl']['MoveTo'] = 'VERPLAATS naar';
    $et['nl']['AlterPermTo'] = 'VERANDER RECHTEN in';
    $et['nl']['ConfExec'] = 'Bevestig UITVOEREN';
    $et['nl']['ConfRem'] = 'Bevestig VERWIJDEREN';
    $et['nl']['EmptyDir'] = 'Lege map';
    $et['nl']['IOError'] = 'I/O Error';
    $et['nl']['FileMan'] = 'PHP File Manager';
    $et['nl']['TypePass'] = 'Voer het wachtwoord in';
    $et['nl']['InvPass'] = 'Ongeldig wachtwoord';
    $et['nl']['ReadDenied'] = 'Leestoegang ontzegd';
    $et['nl']['FileNotFound'] = 'Bestand niet gevonden';
    $et['nl']['AutoClose'] = 'Sluit na voltooien';
    $et['nl']['OutDocRoot'] = 'Bestand buiten DOCUMENT_ROOT';
    $et['nl']['NoCmd'] = 'Error: Command not informed';
    $et['nl']['ConfTrySave'] = 'Bestand zonder schrijfrechten.\\nProbeer een andere manier';
    $et['nl']['ConfSaved'] = 'Instellingen opgeslagen';
    $et['nl']['PassSaved'] = 'Wachtwoord opgeslagen';
    $et['nl']['FileDirExists'] = 'Bestand of map bestaat al';
    $et['nl']['NoPhpinfo'] = 'Functie \'phpinfo\' is uitgeschakeld';
    $et['nl']['NoReturn'] = 'no return';
    $et['nl']['FileSent'] = 'Bestand verzonden';
    $et['nl']['SpaceLimReached'] = 'Opslagruimtelimiet bereikt';
    $et['nl']['InvExt'] = 'Ongeldige extensie';
    $et['nl']['FileNoOverw'] = 'Bestand kan niet worden overgeschreven';
    $et['nl']['FileOverw'] = 'Bestand overgeschreven';
    $et['nl']['FileIgnored'] = 'Bestand genegeerd';
    $et['nl']['ChkVer'] = 'Controleer nieuwe versie';
    $et['nl']['ChkVerAvailable'] = 'Nieuwe versie, klik hier om de download te starten';
    $et['nl']['ChkVerNotAvailable'] = 'Geen nieuwe versie beschikbaar';
    $et['nl']['ChkVerError'] = 'Verbindingsfout.';
    $et['nl']['Website'] = 'Website';
    $et['nl']['SendingForm'] = 'Bestanden worden verzonden. Even geduld...';
    $et['nl']['NoFileSel'] = 'Geen bestanden geselecteerd';
    $et['nl']['SelAll'] = 'Alles';
    $et['nl']['SelNone'] = 'Geen';
    $et['nl']['SelInverse'] = 'Keer om';
    $et['nl']['Selected_s'] = 'geselecteerd';
    $et['nl']['Total'] = 'totaal';
    $et['nl']['Partition'] = 'Partitie';
    $et['nl']['RenderTime'] = 'Tijd voor maken van deze pagina';
    $et['nl']['Seconds'] = 'sec';
    $et['nl']['ErrorReport'] = 'Foutenrapport';

    // Italian - by Valerio Capello
    $et['it']['Version'] = 'Versione';
    $et['it']['DocRoot'] = 'Document Root';
    $et['it']['FLRoot'] = 'File Manager Root';
    $et['it']['Name'] = 'Nome';
    $et['it']['And'] = 'e';
    $et['it']['Enter'] = 'Immetti';
    $et['it']['Send'] = 'Invia';
    $et['it']['Refresh'] = 'Aggiorna';
    $et['it']['SaveConfig'] = 'Salva la Configurazione';
    $et['it']['SavePass'] = 'Salva la Password';
    $et['it']['SaveFile'] = 'Salva il File';
    $et['it']['Save'] = 'Salva';
    $et['it']['Leave'] = 'Abbandona';
    $et['it']['Edit'] = 'Modifica';
    $et['it']['View'] = 'Guarda';
    $et['it']['Config'] = 'Configurazione';
    $et['it']['Ren'] = 'Rinomina';
    $et['it']['Rem'] = 'Elimina';
    $et['it']['Compress'] = 'Comprimi';
    $et['it']['Decompress'] = 'Decomprimi';
    $et['it']['ResolveIDs'] = 'Risolvi IDs';
    $et['it']['Move'] = 'Sposta';
    $et['it']['Copy'] = 'Copia';
    $et['it']['ServerInfo'] = 'Informazioni sul Server';
    $et['it']['CreateDir'] = 'Crea Directory';
    $et['it']['CreateArq'] = 'Crea File';
    $et['it']['ExecCmd'] = 'Esegui Comando';
    $et['it']['Upload'] = 'Carica';
    $et['it']['UploadEnd'] = 'Caricamento terminato';
    $et['it']['Perm'] = 'Perm';
    $et['it']['Perms'] = 'Permessi';
    $et['it']['Owner'] = 'Proprietario';
    $et['it']['Group'] = 'Gruppo';
    $et['it']['Other'] = 'Altri';
    $et['it']['Size'] = 'Dimensioni';
    $et['it']['Date'] = 'Data';
    $et['it']['Type'] = 'Tipo';
    $et['it']['Free'] = 'liberi';
    $et['it']['Shell'] = 'Shell';
    $et['it']['Read'] = 'Lettura';
    $et['it']['Write'] = 'Scrittura';
    $et['it']['Exec'] = 'Esecuzione';
    $et['it']['Apply'] = 'Applica';
    $et['it']['StickyBit'] = 'Sticky Bit';
    $et['it']['Pass'] = 'Password';
    $et['it']['Lang'] = 'Lingua';
    $et['it']['File'] = 'File';
    $et['it']['File_s'] = 'file';
    $et['it']['Dir_s'] = 'directory';
    $et['it']['To'] = 'a';
    $et['it']['Destination'] = 'Destinazione';
    $et['it']['Configurations'] = 'Configurazione';
    $et['it']['JSError'] = 'Errore JavaScript';
    $et['it']['NoSel'] = 'Non ci sono elementi selezionati';
    $et['it']['SelDir'] = 'Scegli la directory di destinazione';
    $et['it']['TypeDir'] = 'Inserisci il nome della directory';
    $et['it']['TypeArq'] = 'Inserisci il nome del file';
    $et['it']['TypeCmd'] = 'Inserisci il comando';
    $et['it']['TypeArqComp'] = 'Inserisci il nome del file.\\nLa estensione definirà il tipo di compressione.\\nEsempio:\\nnome.zip\\nnome.tar\\nnome.bzip\\nnome.gzip';
    $et['it']['RemSel'] = 'ELIMINA gli elementi selezionati';
    $et['it']['NoDestDir'] = 'LA directory di destinazione non è stata selezionata';
    $et['it']['DestEqOrig'] = 'La directory di origine e di destinazione sono la stessa';
    $et['it']['InvalidDest'] = 'La directory di destinazione non è valida';
    $et['it']['NoNewPerm'] = 'Nuovi permessi non attivati';
    $et['it']['CopyTo'] = 'COPIA in';
    $et['it']['MoveTo'] = 'SPOSTA in';
    $et['it']['AlterPermTo'] = 'CAMBIA I PERMESSI: ';
    $et['it']['ConfExec'] = 'Conferma ESECUZIONE';
    $et['it']['ConfRem'] = 'Conferma ELIMINAZIONE';
    $et['it']['EmptyDir'] = 'Directory vuota';
    $et['it']['IOError'] = 'Errore di I/O';
    $et['it']['FileMan'] = 'PHP File Manager';
    $et['it']['TypePass'] = 'Immetti la password';
    $et['it']['InvPass'] = 'Password non valida';
    $et['it']['ReadDenied'] = 'Permesso di lettura negato';
    $et['it']['FileNotFound'] = 'File non trovato';
    $et['it']['AutoClose'] = 'Chiudi la finestra al termine';
    $et['it']['OutDocRoot'] = 'File oltre DOCUMENT_ROOT';
    $et['it']['NoCmd'] = 'Errore: Comando non informato';
    $et['it']['ConfTrySave'] = 'File senza permesso di scrittura.\\nProvo a salvare comunque';
    $et['it']['ConfSaved'] = 'Configurazione salvata';
    $et['it']['PassSaved'] = 'Password salvata';
    $et['it']['FileDirExists'] = 'Il file o la directory esiste già';
    $et['it']['NoPhpinfo'] = 'La funzione phpinfo è disabilitata';
    $et['it']['NoReturn'] = 'senza Return';
    $et['it']['FileSent'] = 'File inviato';
    $et['it']['SpaceLimReached'] = 'è stato raggiunto il limite di spazio disponibile';
    $et['it']['InvExt'] = 'Estensione non valida';
    $et['it']['FileNoOverw'] = 'Il file non può essere sovrascritto';
    $et['it']['FileOverw'] = 'File sovrascritto';
    $et['it']['FileIgnored'] = 'File ignorato';
    $et['it']['ChkVer'] = 'Controlla se è disponibile una nuova versione';
    $et['it']['ChkVerAvailable'] = 'è disponibile una nuova versione: premi qui per scaricarla.';
    $et['it']['ChkVerNotAvailable'] = 'Non è disponibile nessuna nuova versione. :(';
    $et['it']['ChkVerError'] = 'Errore di connessione.';
    $et['it']['Website'] = 'Sito Web';
    $et['it']['SendingForm'] = 'Invio file, attendere prego';
    $et['it']['NoFileSel'] = 'Nessun file selezionato';
    $et['it']['SelAll'] = 'Tutti';
    $et['it']['SelNone'] = 'Nessuno';
    $et['it']['SelInverse'] = 'Inverti';
    $et['it']['Selected_s'] = 'selezionato';
    $et['it']['Total'] = 'totali';
    $et['it']['Partition'] = 'Partizione';
    $et['it']['RenderTime'] = 'Tempo per elaborare questa pagina';
    $et['it']['Seconds'] = 'sec';
    $et['it']['ErrorReport'] = 'Error Reporting';

    // Italian - by Federico Corrà
    $et['it2']['Version'] = 'Versione';
    $et['it2']['DocRoot'] = 'Root Documenti';
    $et['it2']['FLRoot'] = 'Root del File Manager';
    $et['it2']['Name'] = 'Nome';
    $et['it2']['And'] = 'e';
    $et['it2']['Enter'] = 'Invio';
    $et['it2']['Send'] = 'Spedisci';
    $et['it2']['Refresh'] = 'Aggiorna';
    $et['it2']['SaveConfig'] = 'Salva configurazioni';
    $et['it2']['SavePass'] = 'Salva password';
    $et['it2']['SaveFile'] = 'Salva file';
    $et['it2']['Save'] = 'Salva';
    $et['it2']['Leave'] = 'Esci';
    $et['it2']['Edit'] = 'Modifica';
    $et['it2']['View'] = 'Visualizza';
    $et['it2']['Config'] = 'Configura';
    $et['it2']['Ren'] = 'Rinomina';
    $et['it2']['Rem'] = 'Cancella';
    $et['it2']['Compress'] = 'Comprimi';
    $et['it2']['Decompress'] = 'Decomprimi';
    $et['it2']['ResolveIDs'] = 'Risolvi ID';
    $et['it2']['Move'] = 'Muovi';
    $et['it2']['Copy'] = 'Copia';
    $et['it2']['ServerInfo'] = 'Server info';
    $et['it2']['CreateDir'] = 'Crea cartella';
    $et['it2']['CreateArq'] = 'Crea file';
    $et['it2']['ExecCmd'] = 'Esegui comando';
    $et['it2']['Upload'] = 'Upload';
    $et['it2']['UploadEnd'] = 'Upload terminato';
    $et['it2']['Perm'] = 'Perm';
    $et['it2']['Perms'] = 'Permessi';
    $et['it2']['Owner'] = 'Owner';
    $et['it2']['Group'] = 'Grouppo';
    $et['it2']['Other'] = 'Altro';
    $et['it2']['Size'] = 'Dimensione';
    $et['it2']['Date'] = 'Data';
    $et['it2']['Type'] = 'Tipo';
    $et['it2']['Free'] = 'liberi';
    $et['it2']['Shell'] = 'Shell';
    $et['it2']['Read'] = 'Lettura';
    $et['it2']['Write'] = 'Scrittura';
    $et['it2']['Exec'] = 'Esecuzione';
    $et['it2']['Apply'] = 'Applica';
    $et['it2']['StickyBit'] = 'Sticky Bit';
    $et['it2']['Pass'] = 'Password';
    $et['it2']['Lang'] = 'Lingua';
    $et['it2']['File'] = 'File';
    $et['it2']['File_s'] = 'file';
    $et['it2']['Dir_s'] = 'cartella';
    $et['it2']['To'] = 'a';
    $et['it2']['Destination'] = 'Destinazione';
    $et['it2']['Configurations'] = 'Configurazioni';
    $et['it2']['JSError'] = 'Errore JavaScript';
    $et['it2']['NoSel'] = 'Nessun item selezionato';
    $et['it2']['SelDir'] = 'Scegli la cartella di destinazione sull\'albero a sinistra';
    $et['it2']['TypeDir'] = 'Inserisci il nome della cartella';
    $et['it2']['TypeArq'] = 'Inserisci il nome del file';
    $et['it2']['TypeCmd'] = 'Inserisci il comando';
    $et['it2']['TypeArqComp'] = 'Inserisci il nome del file.\\nL\'estensione definirà le modalità di compressione.\\nEx:\\nnome.zip\\nnome.tar\\nnome.bzip\\nnome.gzip';
    $et['it2']['RemSel'] = 'ELIMINA gli item selezionati';
    $et['it2']['NoDestDir'] = 'Non è stata selezionata la cartella di destinazione';
    $et['it2']['DestEqOrig'] = 'La cartella di origine e di destinazione coincidono';
    $et['it2']['InvalidDest'] = 'La cartella di destinazione non è valida';
    $et['it2']['NoNewPerm'] = 'Nuovo permesso non definito';
    $et['it2']['CopyTo'] = 'COPIA in';
    $et['it2']['MoveTo'] = 'MUOVI in';
    $et['it2']['AlterPermTo'] = 'CAMBIA PERMESSI in';
    $et['it2']['ConfExec'] = 'Conferma ESECUZIONE';
    $et['it2']['ConfRem'] = 'Conferma CANCELLA';
    $et['it2']['EmptyDir'] = 'Cartella Vuota';
    $et['it2']['IOError'] = 'Errore I/O';
    $et['it2']['FileMan'] = 'PHP File Manager';
    $et['it2']['TypePass'] = 'Inserisci la password';
    $et['it2']['InvPass'] = 'Password non valida';
    $et['it2']['ReadDenied'] = 'Accesso in lettura non consentito';
    $et['it2']['FileNotFound'] = 'File non trovato';
    $et['it2']['AutoClose'] = 'Chiudi dopo aver completato';
    $et['it2']['OutDocRoot'] = 'File oltre DOCUMENT_ROOT';
    $et['it2']['NoCmd'] = 'Errore: comando non informato';
    $et['it2']['ConfTrySave'] = 'Accesso in scrittura non consentito.\\nProva a salvare comunque';
    $et['it2']['ConfSaved'] = 'Configurazioni salvate';
    $et['it2']['PassSaved'] = 'Password salvate';
    $et['it2']['FileDirExists'] = 'Il file o la cartella esiste già';
    $et['it2']['NoPhpinfo'] = 'Funzione phpinfo disabilitata';
    $et['it2']['NoReturn'] = 'Nessun ritorno';
    $et['it2']['FileSent'] = 'File spedito';
    $et['it2']['SpaceLimReached'] = 'Limite di spazio raggiunto';
    $et['it2']['InvExt'] = 'Estensione non valida';
    $et['it2']['FileNoOverw'] = 'Il file non potrebbe essere sovrascritto';
    $et['it2']['FileOverw'] = 'File sovrascritto';
    $et['it2']['FileIgnored'] = 'File ignorato';
    $et['it2']['ChkVer'] = 'Check nuova versione';
    $et['it2']['ChkVerAvailable'] = 'Nuova versione, clicca qui per iniziare il download!!';
    $et['it2']['ChkVerNotAvailable'] = 'Nessuna nuova versione disponibile. :(';
    $et['it2']['ChkVerError'] = 'Errore di connessione.';
    $et['it2']['Website'] = 'Sito Web';
    $et['it2']['SendingForm'] = 'Invio file, prego attendi';
    $et['it2']['NoFileSel'] = 'Nessun file selezionato';
    $et['it2']['SelAll'] = 'Tutti';
    $et['it2']['SelNone'] = 'Nessuno';
    $et['it2']['SelInverse'] = 'Inverti';
    $et['it2']['Selected_s'] = 'selezionati';
    $et['it2']['Total'] = 'totale';
    $et['it2']['Partition'] = 'Partizione';
    $et['it2']['RenderTime'] = 'Tempo per renderizzare questa pagina';
    $et['it2']['Seconds'] = 'sec';
    $et['it2']['ErrorReport'] = 'Report errori';

    // Italian - by Luca Zorzi
    $et['it3']['Version'] = 'Versione';
    $et['it3']['DocRoot'] = 'Document Root';
    $et['it3']['FLRoot'] = 'Root del File Manager';
    $et['it3']['Name'] = 'Nome';
    $et['it3']['And'] = 'e';
    $et['it3']['Enter'] = 'Invio';
    $et['it3']['Send'] = 'Invia';
    $et['it3']['Refresh'] = 'Aggiorna';
    $et['it3']['SaveConfig'] = 'Salva le impostazioni';
    $et['it3']['SavePass'] = 'Salva la Password';
    $et['it3']['SaveFile'] = 'Salva il File';
    $et['it3']['Save'] = 'Salva';
    $et['it3']['Leave'] = 'Annulla';
    $et['it3']['Edit'] = 'Modifica';
    $et['it3']['View'] = 'Guarda';
    $et['it3']['Config'] = 'Impostazioni';
    $et['it3']['Ren'] = 'Rinomina';
    $et['it3']['Rem'] = 'Elimina';
    $et['it3']['Compress'] = 'Comprimi';
    $et['it3']['Decompress'] = 'Decomprimi';
    $et['it3']['ResolveIDs'] = 'Risolvi ID';
    $et['it3']['Move'] = 'Sposta';
    $et['it3']['Copy'] = 'Copia';
    $et['it3']['ServerInfo'] = 'Server Info';
    $et['it3']['CreateDir'] = 'Crea Cartella';
    $et['it3']['CreateArq'] = 'Crea File';
    $et['it3']['ExecCmd'] = 'Esegui Comando';
    $et['it3']['Upload'] = 'Upload';
    $et['it3']['UploadEnd'] = 'Upload completato';
    $et['it3']['Perm'] = 'Perm';
    $et['it3']['Perms'] = 'Permessi';
    $et['it3']['Owner'] = 'Proprietario';
    $et['it3']['Group'] = 'Gruppo';
    $et['it3']['Other'] = 'Altri';
    $et['it3']['Size'] = 'Dimensione';
    $et['it3']['Date'] = 'Data';
    $et['it3']['Type'] = 'Tipo';
    $et['it3']['Free'] = 'libero';
    $et['it3']['Shell'] = 'Shell';
    $et['it3']['Read'] = 'Lettura';
    $et['it3']['Write'] = 'Scruttura';
    $et['it3']['Exec'] = 'Esecuzione';
    $et['it3']['Apply'] = 'Applica';
    $et['it3']['StickyBit'] = 'Bit Sticky';
    $et['it3']['Pass'] = 'Password';
    $et['it3']['Lang'] = 'Lingua';
    $et['it3']['File'] = 'File';
    $et['it3']['File_s'] = 'file';
    $et['it3']['Dir_s'] = 'cartella/e';
    $et['it3']['To'] = 'a';
    $et['it3']['Destination'] = 'Destinazione';
    $et['it3']['Configurations'] = 'Configurazioni';
    $et['it3']['JSError'] = 'Errore JavaScript';
    $et['it3']['NoSel'] = 'Non ci sono elementi selezioneti';
    $et['it3']['SelDir'] = 'Scegli la cartella di destinazione nell\'elenco a sinistra';
    $et['it3']['TypeDir'] = 'Inserisci il nome della cartella';
    $et['it3']['TypeArq'] = 'Inserisci il nome del file';
    $et['it3']['TypeCmd'] = 'Inserisci il comando';
    $et['it3']['TypeArqComp'] = 'Inserisci il nome del file.\\nIl nome definir &agrave; il tipo della compressione .\\nEs:\\nnome.zip\\nnome.tar\\nnome.bzip\\nnome.gzip';
    $et['it3']['RemSel'] = 'ELIMINA gli elementi selezionati';
    $et['it3']['NoDestDir'] = 'Non hai selezionato la cartella di destinazione';
    $et['it3']['DestEqOrig'] = 'La cartella di origine e destinazione &egrave; la stessa';
    $et['it3']['InvalidDest'] = 'La cartella di destinazione non &egrave; valida';
    $et['it3']['NoNewPerm'] = 'Nuovi permessi non impostati';
    $et['it3']['CopyTo'] = 'COPIA in';
    $et['it3']['MoveTo'] = 'SPOSTA in';
    $et['it3']['AlterPermTo'] = 'CAMBIA I PERMESSI a';
    $et['it3']['ConfExec'] = 'Conferma ESECUZIONE';
    $et['it3']['ConfRem'] = 'Conferma ELIMINAZIONE';
    $et['it3']['EmptyDir'] = 'CArtella vuota';
    $et['it3']['IOError'] = 'Errore di I/O';
    $et['it3']['FileMan'] = 'PHP File Manager';
    $et['it3']['TypePass'] = 'Inserisci la password';
    $et['it3']['InvPass'] = 'Password errata';
    $et['it3']['ReadDenied'] = 'Accesso in lettura negato';
    $et['it3']['FileNotFound'] = 'File non trovato';
    $et['it3']['AutoClose'] = 'Chiudi alla fine';
    $et['it3']['OutDocRoot'] = 'File fuori dalla DOCUMENT_ROOT';
    $et['it3']['NoCmd'] = 'Errore: Comando non informato';
    $et['it3']['ConfTrySave'] = 'File senza il permesso di scrittura.\\nProvare a salvarlo comunque';
    $et['it3']['ConfSaved'] = 'Configurazione salvata';
    $et['it3']['PassSaved'] = 'Password salvata';
    $et['it3']['FileDirExists'] = 'Il file o la cartella esiste gi&agrave;';
    $et['it3']['NoPhpinfo'] = 'Funzione phpinfo disabilitata';
    $et['it3']['NoReturn'] = 'no return';
    $et['it3']['FileSent'] = 'File inviato';
    $et['it3']['SpaceLimReached'] = 'Limite di spazio raggiunto';
    $et['it3']['InvExt'] = 'Estensione non valida';
    $et['it3']['FileNoOverw'] = 'Il file non pu&ograve; essere sovrascritto';
    $et['it3']['FileOverw'] = 'File sovrascritto';
    $et['it3']['FileIgnored'] = 'File ignorato';
    $et['it3']['ChkVer'] = 'Controlla la presnza di una nuova versione';
    $et['it3']['ChkVerAvailable'] = 'Nuova versione, clicca qui per avviare il download!!';
    $et['it3']['ChkVerNotAvailable'] = 'Nessuna nuova versione disponibile. :(';
    $et['it3']['ChkVerError'] = 'Errore di connessione.';
    $et['it3']['Website'] = 'Sito';
    $et['it3']['SendingForm'] = 'Invio dei file, attendi';
    $et['it3']['NoFileSel'] = 'Nessun file selezionato';
    $et['it3']['SelAll'] = 'Tutti';
    $et['it3']['SelNone'] = 'Nessuno';
    $et['it3']['SelInverse'] = 'Inverti selezione';
    $et['it3']['Selected_s'] = 'selezionato';
    $et['it3']['Total'] = 'totale';
    $et['it3']['Partition'] = 'Partizione';
    $et['it3']['RenderTime'] = 'Tempo di generazione';
    $et['it3']['Seconds'] = 'sec';
    $et['it3']['ErrorReport'] = 'Error Reporting';

    // Italian - by Gianni
    $et['it4']['Version'] = 'Versione';
    $et['it4']['DocRoot'] = 'Root documenti';
    $et['it4']['FLRoot'] = 'Root file manager';
    $et['it4']['Name'] = 'Nome';
    $et['it4']['And'] = 'e';
    $et['it4']['Enter'] = 'Entra';
    $et['it4']['Send'] = 'Invia';
    $et['it4']['Refresh'] = 'Aggiorna';
    $et['it4']['SaveConfig'] = 'Salva configurazioni';
    $et['it4']['SavePass'] = 'Salva password';
    $et['it4']['SaveFile'] = 'Salva file';
    $et['it4']['Save'] = 'Salva';
    $et['it4']['Leave'] = 'Esci';
    $et['it4']['Edit'] = 'Modifica';
    $et['it4']['View'] = 'Vedi';
    $et['it4']['Config'] = 'Preferenze';
    $et['it4']['Ren'] = 'Rinomina';
    $et['it4']['Rem'] = 'Cancella';
    $et['it4']['Compress'] = 'Comprimi';
    $et['it4']['Decompress'] = 'Decomprimi';
    $et['it4']['ResolveIDs'] = 'Risolvi IDs';
    $et['it4']['Move'] = 'Sposta';
    $et['it4']['Copy'] = 'Copia';
    $et['it4']['ServerInfo'] = 'Versione PHP';
    $et['it4']['CreateDir'] = 'Crea directory';
    $et['it4']['CreateArq'] = 'Crea file';
    $et['it4']['ExecCmd'] = 'Esegui comando';
    $et['it4']['Upload'] = 'Upload';
    $et['it4']['UploadEnd'] = 'Upload terminato';
    $et['it4']['Perm'] = 'Perm';
    $et['it4']['Perms'] = 'Permessi';
    $et['it4']['Owner'] = 'Proprietario';
    $et['it4']['Group'] = 'Gruppo';
    $et['it4']['Other'] = 'Altro';
    $et['it4']['Size'] = 'Dimensione';
    $et['it4']['Date'] = 'Data';
    $et['it4']['Type'] = 'Tipo';
    $et['it4']['Free'] = 'liberi';
    $et['it4']['Shell'] = 'Shell';
    $et['it4']['Read'] = 'Lettura';
    $et['it4']['Write'] = 'Scrittura';
    $et['it4']['Exec'] = 'Esecuzione';
    $et['it4']['Apply'] = 'Applica';
    $et['it4']['StickyBit'] = 'Sticky Bit';
    $et['it4']['Pass'] = 'Password';
    $et['it4']['Lang'] = 'Lingua';
    $et['it4']['File'] = 'File';
    $et['it4']['File_s'] = 'files';
    $et['it4']['Dir_s'] = 'directory';
    $et['it4']['To'] = 'in';
    $et['it4']['Destination'] = 'Destinazione';
    $et['it4']['Configurations'] = 'Preferenze';
    $et['it4']['JSError'] = 'Errore JavaScript';
    $et['it4']['NoSel'] = 'Non ci sono elementi selezionati';
    $et['it4']['SelDir'] = 'Seleziona una directory di destinazione a sinistra';
    $et['it4']['TypeDir'] = 'Inserisci il nome della directory';
    $et['it4']['TypeArq'] = 'Inserisci il nome del file';
    $et['it4']['TypeCmd'] = 'Inserisci il comando';
    $et['it4']['TypeArqComp'] = 'Inserisci il nome del file e tipo di compressione\\n(.Zip .Tar .Bzip .Gzip)';
    $et['it4']['RemSel'] = 'Cancella gli elementi selezionati';
    $et['it4']['NoDestDir'] = 'Seleziona una directory di destinazione';
    $et['it4']['DestEqOrig'] = 'Origine e destinazione sono uguali';
    $et['it4']['InvalidDest'] = 'Directory di destinazione non valida';
    $et['it4']['NoNewPerm'] = 'Nuovi permessi non impostati';
    $et['it4']['CopyTo'] = 'Copia in';
    $et['it4']['MoveTo'] = 'Sposta in';
    $et['it4']['AlterPermTo'] = 'Cambia permessi in';
    $et['it4']['ConfExec'] = 'Conferma esecuzione';
    $et['it4']['ConfRem'] = 'Conferma eliminazione';
    $et['it4']['EmptyDir'] = 'Directory Vuota';
    $et['it4']['IOError'] = 'Errore I/O';
    $et['it4']['FileMan'] = 'PHP File Manager';
    $et['it4']['TypePass'] = 'Inserisci la password';
    $et['it4']['InvPass'] = 'Password non valida';
    $et['it4']['ReadDenied'] = 'Accesso in lettura negato';
    $et['it4']['FileNotFound'] = 'File non trovato';
    $et['it4']['AutoClose'] = 'Chiudi al termine';
    $et['it4']['OutDocRoot'] = 'File fuori dalla Root documenti';
    $et['it4']['NoCmd'] = 'Errore: comando non informato';
    $et['it4']['ConfTrySave'] = 'File senza permessi di scrittura.\\nRiprova a salvare';
    $et['it4']['ConfSaved'] = 'Preferenze salvate';
    $et['it4']['PassSaved'] = 'Password salvata';
    $et['it4']['FileDirExists'] = 'Il file o la directory esistono già';
    $et['it4']['NoPhpinfo'] = 'Funzione phpinfo disabilitata';
    $et['it4']['NoReturn'] = 'Nessun ritorno';
    $et['it4']['FileSent'] = 'File inviato';
    $et['it4']['SpaceLimReached'] = 'Raggiunto spazio limite';
    $et['it4']['InvExt'] = 'Estensione non valida';
    $et['it4']['FileNoOverw'] = 'Il file non può essere sovrascritto';
    $et['it4']['FileOverw'] = 'File sovrascritto';
    $et['it4']['FileIgnored'] = 'File ignorato';
    $et['it4']['ChkVer'] = 'Controlla aggiornamenti';
    $et['it4']['ChkVerAvailable'] = 'Nuova versione, click qui per effettuare il download!';
    $et['it4']['ChkVerNotAvailable'] = 'Nessuna nuova versione';
    $et['it4']['ChkVerError'] = 'Errore di connessione';
    $et['it4']['Website'] = 'Sito';
    $et['it4']['SendingForm'] = 'Invio files, attendere...';
    $et['it4']['NoFileSel'] = 'Nessun file selezionato';
    $et['it4']['SelAll'] = 'Tutti';
    $et['it4']['SelNone'] = 'Nessuno';
    $et['it4']['SelInverse'] = 'Inverti';
    $et['it4']['Selected_s'] = 'selezionati';
    $et['it4']['Total'] = 'totale';
    $et['it4']['Partition'] = 'Partizione';
    $et['it4']['RenderTime'] = 'Tempo per il render di questa pagina';
    $et['it4']['Seconds'] = 'sec';
    $et['it4']['ErrorReport'] = 'Report errori';

    // Turkish - by Necdet Yazilimlari
    $et['tr']['Version'] = 'Versiyon';
    $et['tr']['DocRoot'] = 'Kok dosya';
    $et['tr']['FLRoot'] = 'Kok dosya yoneticisi';
    $et['tr']['Name'] = 'Isim';
    $et['tr']['And'] = 've';
    $et['tr']['Enter'] = 'Giris';
    $et['tr']['Send'] = 'Yolla';
    $et['tr']['Refresh'] = 'Yenile';
    $et['tr']['SaveConfig'] = 'Ayarlari kaydet';
    $et['tr']['SavePass'] = 'Parolayi kaydet';
    $et['tr']['SaveFile'] = 'Dosyayi kaydet';
    $et['tr']['Save'] = 'Kaydet';
    $et['tr']['Leave'] = 'Ayril';
    $et['tr']['Edit'] = 'Duzenle';
    $et['tr']['View'] = 'Goster';
    $et['tr']['Config'] = 'Yapilandirma';
    $et['tr']['Ren'] = 'Yeniden adlandir';
    $et['tr']['Rem'] = 'Sil';
    $et['tr']['Compress'] = '.Zip';
    $et['tr']['Decompress'] = '.ZipCoz';
    $et['tr']['ResolveIDs'] = 'Kimlikleri coz';
    $et['tr']['Move'] = 'Tasi';
    $et['tr']['Copy'] = 'Kopyala';
    $et['tr']['ServerInfo'] = 'Sunucu Bilgisi';
    $et['tr']['CreateDir'] = 'Dizin olustur';
    $et['tr']['CreateArq'] = 'Dosya olusutur';
    $et['tr']['ExecCmd'] = 'Komut calistir';
    $et['tr']['Upload'] = 'Dosya yukle';
    $et['tr']['UploadEnd'] = 'Yukleme tamamlandi';
    $et['tr']['Perm'] = 'Izinler';
    $et['tr']['Perms'] = 'Izinler';
    $et['tr']['Owner'] = 'Sahip';
    $et['tr']['Group'] = 'Grup';
    $et['tr']['Other'] = 'Diger';
    $et['tr']['Size'] = 'Boyut';
    $et['tr']['Date'] = 'Tarih';
    $et['tr']['Type'] = 'Tip';
    $et['tr']['Free'] = 'Bos';
    $et['tr']['Shell'] = 'Kabuk';
    $et['tr']['Read'] = 'Oku';
    $et['tr']['Write'] = 'Yaz';
    $et['tr']['Exec'] = 'Calistir';
    $et['tr']['Apply'] = 'Uygula';
    $et['tr']['StickyBit'] = 'Sabit bit';
    $et['tr']['Pass'] = 'Parola';
    $et['tr']['Lang'] = 'Dil';
    $et['tr']['File'] = 'Dosya';
    $et['tr']['File_s'] = 'Dosya(lar)';
    $et['tr']['Dir_s'] = 'Dizin(ler)';
    $et['tr']['To'] = 'icin';
    $et['tr']['Destination'] = 'Hedef';
    $et['tr']['Configurations'] = 'Yapilandirmalar';
    $et['tr']['JSError'] = 'JavaScript hatasi';
    $et['tr']['NoSel'] = 'Secilen oge yok';
    $et['tr']['SelDir'] = 'Soldaki hedef dizin agaci secin';
    $et['tr']['TypeDir'] = 'Dizin adini girin';
    $et['tr']['TypeArq'] = 'Dosya adini girin';
    $et['tr']['TypeCmd'] = 'Komut girin';
    $et['tr']['TypeArqComp'] = 'Dosya ismini yazdiktan sonra sonuna .zip ekleyin';
    $et['tr']['RemSel'] = 'Secili ogeleri sil';
    $et['tr']['NoDestDir'] = 'Secili dizin yok';
    $et['tr']['DestEqOrig'] = 'Kokenli ve esit gidis rehberi';
    $et['tr']['InvalidDest'] = 'Hedef dizin gecersiz';
    $et['tr']['NoNewPerm'] = 'Izinler uygun degil';
    $et['tr']['CopyTo'] = 'Kopya icin';
    $et['tr']['MoveTo'] = 'Tasi icin';
    $et['tr']['AlterPermTo'] = 'Permission secin';
    $et['tr']['ConfExec'] = 'Yapilandirmayi onayla';
    $et['tr']['ConfRem'] = 'Simeyi onayla';
    $et['tr']['EmptyDir'] = 'Dizin bos';
    $et['tr']['IOError'] = 'Hata';
    $et['tr']['FileMan'] = 'Necdet_Yazilimlari';
    $et['tr']['TypePass'] = 'Parolayi girin';
    $et['tr']['InvPass'] = 'Gecersiz parola';
    $et['tr']['ReadDenied'] = 'Okumaya erisim engellendi';
    $et['tr']['FileNotFound'] = 'Dosya bulunamadi';
    $et['tr']['AutoClose'] = 'Otomatik kapat';
    $et['tr']['OutDocRoot'] = 'Kok klasor disindaki dosya';
    $et['tr']['NoCmd'] = 'Hata: Komut haberdar degil';
    $et['tr']['ConfTrySave'] = 'Dosya yazma izniniz yok. Yine de kaydetmeyi deneyebilirsiniz.';
    $et['tr']['ConfSaved'] = 'Ayarlar kaydedildi';
    $et['tr']['PassSaved'] = 'Parola kaydedildi';
    $et['tr']['FileDirExists'] = 'Dosya veya dizin zaten var';
    $et['tr']['NoPhpinfo'] = 'Php fonksiyon bilgisi devre disi';
    $et['tr']['NoReturn'] = 'Deger dondurmuyor';
    $et['tr']['FileSent'] = 'Dosya gonderildi';
    $et['tr']['SpaceLimReached'] = 'Disk limitine ulasildi';
    $et['tr']['InvExt'] = 'Gecersiz uzanti';
    $et['tr']['FileNoOverw'] = 'Dosya degistirilemiyor';
    $et['tr']['FileOverw'] = 'Dosya degistiribiliyor';
    $et['tr']['FileIgnored'] = 'Dosya kabul edildi';
    $et['tr']['ChkVer'] = 'Yeni versiyonu kontrol et';
    $et['tr']['ChkVerAvailable'] = 'Yeni surum bulundu. Indirmek icin buraya tiklayin.';
    $et['tr']['ChkVerNotAvailable'] = 'Yeni surum bulunamadi.';
    $et['tr']['ChkVerError'] = 'Baglanti hatasi';
    $et['tr']['Website'] = 'Website';
    $et['tr']['SendingForm'] = 'Dosyalar gonderiliyor, lutfen bekleyin';
    $et['tr']['NoFileSel'] = 'Secili dosya yok';
    $et['tr']['SelAll'] = 'Hepsi';
    $et['tr']['SelNone'] = 'Hicbiri';
    $et['tr']['SelInverse'] = 'Ters';
    $et['tr']['Selected_s'] = 'Secili oge(ler)';
    $et['tr']['Total'] = 'Toplam';
    $et['tr']['Partition'] = 'Bolme';
    $et['tr']['RenderTime'] = 'Olusturuluyor';
    $et['tr']['Seconds'] = 'Saniye';
    $et['tr']['ErrorReport'] = 'Hata raporu';

    // Россия - Евгений Рашев
    $et['ru']['Version']='Версия';
    $et['ru']['DocRoot']='Документ Root ';
    $et['ru']['FLRoot']='Файловый менеджер';
    $et['ru']['Name']='Имя';
    $et['ru']['And']='и';
    $et['ru']['Enter']='Enter';
    $et['ru']['Send']='Отправить';
    $et['ru']['Refresh']='Обновить';
    $et['ru']['SaveConfig']='Сохранить конфигурацию';
    $et['ru']['SavePass']='Сохранить пароль';
    $et['ru']['SaveFile']='Сохранить файл ';
    $et['ru']['Save']='Сохранить';
    $et['ru']['Leave']='Оставь';
    $et['ru']['Edit']='Изменить';
    $et['ru']['View']='Просмотр';
    $et['ru']['Config']='Настройки';
    $et['ru']['Ren']='Переименовать';
    $et['ru']['Rem']='Удалить';
    $et['ru']['Compress']='Сжать';
    $et['ru']['Decompress']='Распаковать';
    $et['ru']['ResolveIDs']='Определять id';
    $et['ru']['Move']='Переместить';
    $et['ru']['Copy']='Копировать';
    $et['ru']['ServerInfo']='Инфо о сервере';
    $et['ru']['CreateDir']='Создать папку';
    $et['ru']['CreateArq']='Создайте файл ';
    $et['ru']['ExecCmd']='Выполнить';
    $et['ru']['Upload']='Загрузить';
    $et['ru']['UploadEnd']='Загружено';
    $et['ru']['Perm']='Права';
    $et['ru']['Perms']='Разрешения';
    $et['ru']['Owner']='Владелец';
    $et['ru']['Group']='Группа';
    $et['ru']['Other']='Другие';
    $et['ru']['Size']='Размер';
    $et['ru']['Date']='Дата';
    $et['ru']['Type']='Тип';
    $et['ru']['Free']='Свободно';
    $et['ru']['Shell']='Shell';
    $et['ru']['Read']='Читать';
    $et['ru']['Write']='Писать';
    $et['ru']['Exec']='Выполнять';
    $et['ru']['Apply']='Применить';
    $et['ru']['StickyBit']='StickyBit';
    $et['ru']['Pass']='Пароль';
    $et['ru']['Lang']='Язык';
    $et['ru']['File']='Файл';
    $et['ru']['File_s']='Файл..';
    $et['ru']['Dir_s']='Пап..';
    $et['ru']['To']='в';
    $et['ru']['Destination']='Назначение';
    $et['ru']['Configurations']='Конфигурация';
    $et['ru']['JSError']='Ошибка JavaScript';
    $et['ru']['NoSel']='нет выбранных элементов';
    $et['ru']['SelDir']='Выберите папку назначения на левом дереве ';
    $et['ru']['TypeDir']='Введите имя каталога ';
    $et['ru']['TypeArq']='Введите имя файла';
    $et['ru']['TypeCmd']='Введите команду ';
    $et['ru']['TypeArqComp']='Введите имя файла ,расширение\\n это позволит определить тип сжатия \\n Пример:.. \\n nome.zip \\n nome.tar \\n nome.bzip \\n nome.gzip ';
    $et['ru']['RemSel']='Удалить выбранные элементы';
    $et['ru']['NoDestDir']='нет выбранного каталога назначения';
    $et['ru']['DestEqOrig']='Происхождение и назначение каталогов равны ';
    $et['ru']['InvalidDest']='Назначение каталога недействительно';
    $et['ru']['NoNewPerm']='Новые разрешения не установлены';
    $et['ru']['CopyTo']='Копировать в ';
    $et['ru']['MoveTo']='Переместить в';
    $et['ru']['AlterPermTo']='Изменение разрешений в ';
    $et['ru']['ConfExec']='Подтвердить ВЫПОЛНИТЬ ';
    $et['ru']['ConfRem']='Подтвердить УДАЛЕНИЕ';
    $et['ru']['EmptyDir']='Пустой каталог ';
    $et['ru']['IOError']='I/O Error';
    $et['ru']['FileMan']='PHP Файловый менеджер ';
    $et['ru']['TypePass']='Введите пароль';
    $et['ru']['InvPass']='Неверный пароль';
    $et['ru']['ReadDenied']='Доступ запрещен ';
    $et['ru']['FileNotFound']='Файл не найден';
    $et['ru']['AutoClose']='Закрыть полностью ';
    $et['ru']['OutDocRoot']='Файлы за пределами DOCUMENT_ROOT';
    $et['ru']['NoCmd']='Ошибка: Не поддерживаемая команда';
    $et['ru']['ConfTrySave']='Файл без прав на запись. \\n Сохранить в любом случае. ';
    $et['ru']['ConfSaved']='Конфигурация сохранена';
    $et['ru']['PassSaved']='Пароль сохранен';
    $et['ru']['FileDirExists']='Файл или каталог уже существует';
    $et['ru']['NoPhpinfo']='Функция PHPInfo отключена';
    $et['ru']['NoReturn']='Нет возврата';
    $et['ru']['FileSent']='Файл отправлен';
    $et['ru']['SpaceLimReached']='Достигнут предел Пространства';
    $et['ru']['InvExt']='Неверное расширение';
    $et['ru']['FileNoOverw']='Файл не может быть перезаписан ';
    $et['ru']['FileOverw']='Файл перезаписывается';
    $et['ru']['FileIgnored']='Файл игнорируется';
    $et['ru']['ChkVer']='Проверить обновление';
    $et['ru']['ChkVerAvailable']=' Доступна новая версия, нажмите здесь, чтобы начать загрузку! ';
    $et['ru']['ChkVerNotAvailable']='Нет новой версии. :(';
    $et['ru']['ChkVerError']='Ошибка подключения. ';
    $et['ru']['Website']='Сайт';
    $et['ru']['SendingForm']='Отправка файлов, пожалуйста, подождите ';
    $et['ru']['NoFileSel']='Нет выбранных файлов';
    $et['ru']['SelAll']='Выделить все';
    $et['ru']['SelNone']='Отмена';
    $et['ru']['SelInverse']='Обратить';
    $et['ru']['Selected_s']='Выбран';
    $et['ru']['Total']='Всего';
    $et['ru']['Partition']='Раздел';
    $et['ru']['RenderTime']='Скрипт выполнен за';
    $et['ru']['Seconds']='Секунд';
    $et['ru']['ErrorReport']='Отчет об ошибках';

    // Catalan - by Pere Borràs AKA @Norl
    $et['ca']['Version'] = 'Versió';
    $et['ca']['DocRoot'] = 'Arrel del programa';
    $et['ca']['FLRoot'] = 'Arrel de l`administrador d`arxius';
    $et['ca']['Name'] = 'Nom';
    $et['ca']['And'] = 'i';
    $et['ca']['Enter'] = 'Entrar';
    $et['ca']['Send'] = 'Enviar';
    $et['ca']['Refresh'] = 'Refrescar';
    $et['ca']['SaveConfig'] = 'Desar configuracions';
    $et['ca']['SavePass'] = 'Desar clau';
    $et['ca']['SaveFile'] = 'Desar Arxiu';
    $et['ca']['Save'] = 'Desar';
    $et['ca']['Leave'] = 'Sortir';
    $et['ca']['Edit'] = 'Editar';
    $et['ca']['View'] = 'Mirar';
    $et['ca']['Config'] = 'Config.';
    $et['ca']['Ren'] = 'Canviar nom';
    $et['ca']['Rem'] = 'Esborrar';
    $et['ca']['Compress'] = 'Comprimir';
    $et['ca']['Decompress'] = 'Descomprimir';
    $et['ca']['ResolveIDs'] = 'Resoldre IDs';
    $et['ca']['Move'] = 'Moure';
    $et['ca']['Copy'] = 'Copiar';
    $et['ca']['ServerInfo'] = 'Info del Server';
    $et['ca']['CreateDir'] = 'Crear Directori';
    $et['ca']['CreateArq'] = 'Crear Arxiu';
    $et['ca']['ExecCmd'] = 'Executar Comandament';
    $et['ca']['Upload'] = 'Pujar';
    $et['ca']['UploadEnd'] = 'Pujat amb èxit';
    $et['ca']['Perm'] = 'Perm';
    $et['ca']['Perms'] = 'Permisos';
    $et['ca']['Owner'] = 'Propietari';
    $et['ca']['Group'] = 'Grup';
    $et['ca']['Other'] = 'Altre';
    $et['ca']['Size'] = 'Tamany';
    $et['ca']['Date'] = 'Data';
    $et['ca']['Type'] = 'Tipus';
    $et['ca']['Free'] = 'lliure';
    $et['ca']['Shell'] = 'Executar';
    $et['ca']['Read'] = 'Llegir';
    $et['ca']['Write'] = 'Escriure';
    $et['ca']['Exec'] = 'Executar';
    $et['ca']['Apply'] = 'Aplicar';
    $et['ca']['StickyBit'] = 'Sticky Bit';
    $et['ca']['Pass'] = 'Clau';
    $et['ca']['Lang'] = 'Llenguatje';
    $et['ca']['File'] = 'Arxius';
    $et['ca']['File_s'] = 'arxiu(s)';
    $et['ca']['Dir_s'] = 'directori(s)';
    $et['ca']['To'] = 'a';
    $et['ca']['Destination'] = 'Destí';
    $et['ca']['Configurations'] = 'Configuracions';
    $et['ca']['JSError'] = 'Error de JavaScript';
    $et['ca']['NoSel'] = 'No hi ha items seleccionats';
    $et['ca']['SelDir'] = 'Seleccioneu el directori de destí a l`arbre de la dreta';
    $et['ca']['TypeDir'] = 'Escrigui el nom del directori';
    $et['ca']['TypeArq'] = 'Escrigui el nom de l`arxiu';
    $et['ca']['TypeCmd'] = 'Escrigui el comandament';
    $et['ca']['TypeArqComp'] = 'Escrigui el nombre del directorio.\\nL`extensió definirà el tipus de compressió.\\nEx:\\nnom.zip\\nnom.tar\\nnom.bzip\\nnom.gzip';
    $et['ca']['RemSel'] = 'ESBORRAR items seleccionats';
    $et['ca']['NoDestDir'] = 'No s`ha seleccionat el directori de destí';
    $et['ca']['DestEqOrig'] = 'L`origen i el destí són iguals';
    $et['ca']['InvalidDest'] = 'El destí del directori és invàlid';
    $et['ca']['NoNewPerm'] = 'Els permisos no s`han pogut establir';
    $et['ca']['CopyTo'] = 'COPIAR a';
    $et['ca']['MoveTo'] = 'MOURE a';
    $et['ca']['AlterPermTo'] = 'CAMBIAR PERMISOS a';
    $et['ca']['ConfExec'] = 'Confirmar EXECUCIÓ';
    $et['ca']['ConfRem'] = 'Confirmar ESBORRAT';
    $et['ca']['EmptyDir'] = 'Directori buit';
    $et['ca']['IOError'] = 'Error I/O';
    $et['ca']['FileMan'] = 'PHP File Manager';
    $et['ca']['TypePass'] = 'Escrigui la clau';
    $et['ca']['InvPass'] = 'Clau invàlida';
    $et['ca']['ReadDenied'] = 'Accés de lectura denegat';
    $et['ca']['FileNotFound'] = 'Arxiu no trobat';
    $et['ca']['AutoClose'] = 'Tancar al completar';
    $et['ca']['OutDocRoot'] = 'Arxiu abans de DOCUMENT_ROOT';
    $et['ca']['NoCmd'] = 'Error: No s`ha escrit cap comandament';
    $et['ca']['ConfTrySave'] = 'Arxiu sense permisos d`escriptura.\\nIntenteu desar a un altre lloc';
    $et['ca']['ConfSaved'] = 'Configuració Desada';
    $et['ca']['PassSaved'] = 'Clau desada';
    $et['ca']['FileDirExists'] = 'Arxiu o directori ja existent';
    $et['ca']['NoPhpinfo'] = 'Funció phpinfo() no habilitada';
    $et['ca']['NoReturn'] = 'sense retorn';
    $et['ca']['FileSent'] = 'Arxiu enviat';
    $et['ca']['SpaceLimReached'] = 'Límit d`espaci al disc assolit';
    $et['ca']['InvExt'] = 'Extensió no vàlida';
    $et['ca']['FileNoOverw'] = 'L`arxiu no ha pogut ser sobreescrit';
    $et['ca']['FileOverw'] = 'Arxiu sobreescrit';
    $et['ca']['FileIgnored'] = 'Arxiu ignorat';
    $et['ca']['ChkVer'] = 'Revisar les actualitzacions';
    $et['ca']['ChkVerAvailable'] = 'Nova versió, feu clic aquí per descarregar';
    $et['ca']['ChkVerNotAvailable'] = 'La vostra versió és la més recent.';
    $et['ca']['ChkVerError'] = 'Error de connexió.';
    $et['ca']['Website'] = 'Lloc Web';
    $et['ca']['SendingForm'] = 'Enviant arxius, esperi';
    $et['ca']['NoFileSel'] = 'Cap arxiu seleccionat';
    $et['ca']['SelAll'] = 'Tots';
    $et['ca']['SelNone'] = 'Cap';
    $et['ca']['SelInverse'] = 'Invers';
    $et['ca']['Selected_s'] = 'seleccionat';
    $et['ca']['Total'] = 'total';
    $et['ca']['Partition'] = 'Partició';
    $et['ca']['RenderTime'] = 'Generat en';
    $et['ca']['Seconds'] = 'seg';
    $et['ca']['ErrorReport'] = 'Informe d`error';

    if (!strlen($lang)) $lang = $sys_lang;
    if (isset($et[$lang][$tag])) return html_encode($et[$lang][$tag]);
    else if (isset($et['en'][$tag])) return html_encode($et['en'][$tag]);
    else return "[$tag]"; // So we can know what is missing
}
// +--------------------------------------------------
// | THE END
// +--------------------------------------------------
?>