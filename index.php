<?php
include("init.php");
include("stuff.php");

session_start();
header("Content-Type: text/html; charset=utf8");

if (!isset($_SESSION["user"])) {
  $q = login();
  load_view("header",["content" => $q, "navigation" =>"login"]);
  exit;
}

$q = "";
$menuactive = $_GET["m"];

switch($_GET["m"]) {
  case "ledger":
    $uid = intval($_SESSION["user"]["id"]);
    $q .= show_ledger($uid);
    break;
  case "logout":
    session_destroy();
    setcookie("autologin","",time()-9001,"/kdv/");
    header("Location: ".BASE_URL);
    break;
  case "registration":
    $uid = intval($_SESSION["user"]["id"]);
    $q.= show_registration($uid);
    break;
  case "wire_transfer":
    $q.=wire_transfer($_SESSION["user"]["id"]);
    break;
  case "add_payment":
    $q.=add_payment($_SESSION["user"]["id"], BASE_URL."?m=ledger");
    break;
  case "productlist":
    $q.=productlist([  [ "Kaufen", "?m=add_payment&product_id=%d" ]  ]);
    break;
  case "product":
    $prod = sql("SELECT * FROM products WHERE id = ?", [ $_GET["id"] ], 1);
    if (!$prod) break;
    $q.= "<h2>".ent($prod["name"])."</h2>";
    $q.= "<img src='".BASE_URL."/productimages/".$prod["id"].".jpg'>";
    break;
  case "storno":
    $q.= storno_payment($_SESSION["user"]["id"], $_GET["payment_id"]);
    break;
  case "": case null:
    $products = sql("select *,max(rank) mr,max(x.counter) mc from(
    SELECT *,15 rank,0 counter FROM products WHERE (flags & 1) = 1
    UNION
    SELECT  p.*,count(l.id)*8000/(timestampdiff(MINUTE,max(l.timestamp),now())) + if(p.flags&1,20,0) rank,
    count(l.id) counter 
    FROM ledger l LEFT OUTER JOIN products p ON l.product_id=p.id WHERE user_id = ? GROUP BY p.id 
    
    ) x
    group by id
    ORDER BY max(rank) DESC LIMIT 18 ", [ $_SESSION["user"]["id"] ]);
    
    $q.= get_view("welcome_page", [ "name" => ent($_SESSION["user"]["fullname"]), "apitoken"=>$_SESSION["user"]["apitoken"], "def_products" => $products ]);
    break;
  default:
    header("HTTP/1.1 404 Not Found");
    $q.= "<h2>Nicht gefunden</h2>";
    break;
}

load_view("header", ["navigation" => "main", "menuactive" => $menuactive, "content"=>$q, "msgbar" => $_SESSION["msgbar"] ]);
$_SESSION["msgbar"] = [];

