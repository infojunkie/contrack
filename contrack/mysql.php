<?php

// Shims to old-style mysql() functions.
function mysql_pconnect($host,$user,$passwd) {
  return mysqli_connect('p:'.$host,$user,$passwd);
}

function mysql_errno() {
  global $contrack_connection;
  return mysqli_errno($contrack_connection);
}

function mysql_error() {
  global $contrack_connection;
  return mysqli_error($contrack_connection);
}

function mysql_fetch_array($result) {
  return mysqli_fetch_array($result);
}

function mysql_fetch_assoc($result) {
  return mysqli_fetch_assoc($result);
}

function mysql_fetch_row($result) {
  return mysqli_fetch_row($result);
}

function mysql_insert_id() {
  global $contrack_connection;
  return mysqli_insert_id($contrack_connection);
}

function mysql_num_rows($result) {
  return mysqli_num_rows($result);
}

function mysql_query($sql) {
  global $contrack_connection;
  return mysqli_query($contrack_connection,$sql);
}

function mysql_select_db($dbname) {
  global $contrack_connection;
  return mysqli_select_db($contrack_connection,$dbname);
}

function mysql_real_escape_string($data) {
  global $contrack_connection;
  return mysqli_real_escape_string($contrack_connection,$data);
}

?>