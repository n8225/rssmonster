<?php

//usage: curl -X POST -H 'Content-Type: application/json; charset=utf-8' -d '{"jsonrpc": "2.0","request": "debug"}' http://openreaderurl/json.php
//usage: http POST http://openreaderurl/json.php jsonrpc="2.0" request="debug" -b

include 'config.php';

//debugging and error message
$debug = array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5);
$error = array('response'=>"Incorrect JSON message");

//header type is json
header('Content-Type: application/json');
$arr = json_decode(file_get_contents('php://input'), true);

//if json argument isn't given exit
if ($arr[jsonrpc] != "2.0") { 
  echo json_encode($error);
  //exit;
 }

//debug message
if ($arr[request] == "debug") { 
  echo json_encode($debug);
}

//usage curl -X POST -H 'Content-Type: application/json; charset=utf-8' -d '{"jsonrpc": "2.0","request": "read-status", "value": "1"}' http://openreaderurl/json.php
if ($arr[request] == "read-status") {
  $sql = "SELECT status from articles WHERE id = $arr[value]";
  $result = mysql_query($sql);
  echo json_encode(mysql_result($result,0));
}

if ($arr[request] == "read-content") {
  $sql = "SELECT content from articles WHERE id = $arr[value]";
  $result = mysql_query($sql);
  echo json_encode(mysql_result($result,0));
}

//usage curl -X POST -H 'Content-Type: application/json; charset=utf-8' -d '{"jsonrpc": "2.0","update": "read-status", "value": "1"}' http://openreaderurl/json.php
if ($arr[update] == "read-status") {
  $sql = "UPDATE articles set status = 'read' WHERE id = $arr[value]";
  $result = mysql_query($sql);
  echo json_encode("done");
}

//http POST http://192.168.0.111/phppaper/json.php jsonrpc="2.0" update="mark-all-as-read" -b
if ($arr[update] == "mark-all-as-read") {
  if (!empty($arr[input_feed]) && empty($arr[input_category])) {
    $sql = "UPDATE articles set status = 'read' WHERE feed_id = (SELECT id FROM `feeds` WHERE name = '$arr[input_feed]')";
  } elseif (!empty($arr[input_category]) && empty($arr[input_feed])) {
    $sql = "UPDATE articles set status = 'read' WHERE feed_id in (SELECT id FROM `feeds` WHERE category = '$arr[input_category]')";
  } else {
    $sql = "UPDATE articles set status = 'read'";
  }
  $result = mysql_query($sql);
  echo json_encode("done");
}

if ($arr[request] == "count-all") {
  $sql=mysql_query("SELECT count(*) as count FROM articles WHERE status = 'unread'");
  while($r[]=mysql_fetch_array($sql));
  echo json_encode($r);
}

if ($arr[request] == "overview-categories") {
  $sql=mysql_query("SELECT category, count(*) as count FROM articles t1 LEFT JOIN feeds t2 ON t1.feed_id = t2.id WHERE category <> '' AND status = 'unread' GROUP BY category ORDER BY category");
  while($r[]=mysql_fetch_array($sql));
  echo json_encode($r);
}

if ($arr[request] == "overview-feeds") {
  $sql=mysql_query("SELECT name, count(*) as count FROM articles t1 LEFT JOIN feeds t2 ON t1.feed_id = t2.id WHERE t1.status = 'unread' GROUP BY name ORDER BY name");
  while($r[]=mysql_fetch_array($sql));
  echo json_encode($r);
}

//http POST http://192.168.0.111/phppaper/json.php jsonrpc="2.0" request="get-all-articles" offset="0" postnumbers="10" -b
if ($arr[request] == "get-all-articles") {
  if (!empty($arr[input_feed]) && empty($arr[input_category])) {
    $sql=mysql_query("SELECT t1.id, status, t1.url, subject, content, publish_date, name as feed_name FROM articles t1 LEFT JOIN feeds t2 ON t1.feed_id = t2.id WHERE status = 'unread'
    AND feed_id = (SELECT id FROM `feeds` WHERE name = '$arr[input_feed]')
    ORDER BY publish_date DESC LIMIT $arr[offset], $arr[postnumbers]");
  } elseif (!empty($arr[input_category]) && empty($arr[input_feed])) {
    $sql=mysql_query("SELECT t1.id, status, t1.url, subject, content, publish_date, name as feed_name FROM articles t1 LEFT JOIN feeds t2 ON t1.feed_id = t2.id WHERE t1.status = 'unread'
    AND feed_id in (SELECT id FROM `feeds` WHERE category = '$arr[input_category]')
    ORDER BY publish_date DESC LIMIT $arr[offset], $arr[postnumbers]");
  } elseif (!empty($arr[article_id])) {
    $sql=mysql_query("SELECT t1.id, status, t1.url, subject, content, publish_date, name as feed_name FROM articles t1 LEFT JOIN feeds t2 ON t1.feed_id = t2.id WHERE 
t1.id = '$arr[article_id]'");
  } else {
    $sql=mysql_query("SELECT t1.id, status, t1.url, subject, content, publish_date, name as feed_name FROM articles t1 LEFT JOIN feeds t2 ON t1.feed_id = t2.id WHERE t1.status = 'unread' ORDER BY publish_date DESC LIMIT $arr[offset], $arr[postnumbers]");
  }
  while($r[]=mysql_fetch_array($sql));
  $r = array_filter($r);

  if (empty($r)) {
    echo json_encode("no-results");
  } else {
    echo json_encode($r);
  }
}

mysql_close($con);

?>