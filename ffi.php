<?php

const ADDRESS = "breadix.ru";
const PORT = 19132;

ini_set('memory_limit', '-1');
set_time_limit(-1); //Maximum execution time of 0 seconds exceeded
require_once('vendor/autoload.php');
use pocketmine\entity\Attribute;
use pocketmine\item\Item;
Attribute::init();
Item::init();
define("ENDIANNESS", (pack("d", 1) === "\77\360\0\0\0\0\00" ? 0x00 : 0x01));
define("INT32_MASK", is_int(0xffffffff) ? 0xffffffff : -1);
require_once("./proxyface.php");
$logger = new Logger();
$iface = new Proxyface($logger, "./libproxy.so");

function sendMessage($msg){
    global $iface;
    $pk = new \pocketmine\network\mcpe\protocol\TextPacket();
    $pk->type = 0;
    $pk->message = "§f[§5§lproxy§r§f] §r§l§6" . $msg;
    $pk->encode();
    $iface->getProxy()->SendToClient($pk->buffer, strlen($pk->buffer));
}

function sendHotbar($msg){
    global $iface;
    $pk = new \pocketmine\network\mcpe\protocol\TextPacket();
    $pk->type = 4;
    $pk->message = $msg;
    $pk->encode();
    $iface->getProxy()->SendToClient($pk->buffer, strlen($pk->buffer));
}

function sf() {
   sleep(999999); //sosi hui
}
register_shutdown_function('sf');

$isc = false;
$sp = -1;
$gm = false;
$rcnt = 0;
$scnt = 0;
$tk = 0;
$cpu = "0%";

$iface->getProxy()->SetTicker(40, function() use (&$isc, &$rcnt, &$scnt, &$tk, &$cpu, &$iface, &$sp){
    if(!$isc) return;
    if($tk++ == 20){
        $tk = 0;
        $cpu =  str_replace("", "", str_replace( "", "",shell_exec("ps -p " . getmypid() . ""))) . "";
    }
    if($sp != -1) $iface->getProxy()->SetMovementSpeed($sp);

$iface->getProxy()->SubscribeOnClientDisconnected(function($reason) use(&$isc){
    $isc = false;
    echo "client disconnected {$reason}\n";
});

$iface->getProxy()->SubscribeOnServerDisconnected(function($reason) use(&$isc){
    $isc = false;
    echo "server disconnected {$reason}\n";
});

$iface->subscribeOnServerPayloadRecvEvent(function($payload, $len) use(&$logger, &$iface, &$isc, &$rcnt, &$scnt){
	$rcnt++;
    if(ord($payload[0]) == 0x3a) {
        $isc = true;
    } else if(ord($payload[0]) == 0x0b) {
        $pk = new \pocketmine\network\mcpe\protocol\StartGamePacket();
        $pk->buffer = substr($payload, 1);
        $pk->decode();

        $spawn = new \pocketmine\math\Vector3($pk->x, $pk->y, $pk->z);
		//example
    }
    return true;
});

$iface->subscribeOnClientPayloadSendEvent(function($payload, $len) use(&$logger, &$iface, &$rcnt, &$scnt, &$isc, &$gm, &$sp){
	$scnt++;
	if(ord($payload[0]) == 0x37) return false;
    if(ord($payload[0]) == 0x09) {
        $pk = new \pocketmine\network\mcpe\protocol\TextPacket();
        $pk->buffer = substr($payload, 1);
        $pk->decode();
        if(count(($args = explode(" ", $pk->message))) > 0) {
            if($args[0] == ".tr"){
                if(strpos($args[1], ":") !== false){
                    if(count($args) > 1) {
                        foreach($args as $arg){
                            if($arg == "+rpbypass") {
                                $iface->getProxy()->SetRPDownloadBypass(true);
                                sendMessage("Трансфер на §r" . $args[1] . " §l§6с обходом §r§eзагрузки ресурс-пака");
                                sleep(2);
                                break;
                            } else if($arg == "+pcbypass") {
                                $iface->getProxy()->SetDeviceOS(2);
                                $iface->getProxy()->SetInputMode(2);
                                sendMessage("Трансфер на §r" . $args[1] . " §l§6с обходом §r§eанти-пк");
                                $iface->getProxy()->SendToClient($pk->buffer, strlen($pk->buffer));
                                sleep(2);
                                break;
                            }
                        }
                    }
                    $isc = false;
                    $scnt = 0;
                    $rcnt = 0;
                    $iface->getProxy()->LiveTransfer($args[1]);
                    return false;
                }
            } else if($args[0] == ".setgm"){
                $pk = new \pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket();
                $pk->gamemode = (int) !$gm;
                $gm = !$gm;
                $pk->encode();
                $iface->getProxy()->SendToClient($pk->buffer, strlen($pk->buffer));
                sendMessage("Установлен режим игры §r" . ($gm ? "CREATIVE" : "SURVIVAL"));
                return false;
            } else if($args[0] == ".sp"){
                if(!isset($args[1])) $args[1] = 0.1;
                $sp = (float) $args[1];

                sendMessage("Установлена скорость §r" . $sp);
                $iface->getProxy()->SetMovementSpeed($sp);
                if($sp == 0.1) $sp = -1;
                return false;
            } else if($args[0] == ".esp"){
                $iface->getProxy()->ToggleTracer(1);
                sendMessage("Трейсеры");
                return false;
           } else if($args[0] == ".hb"){
                $iface->getProxy()->ToggleHitbox(1.1, 1.8);
                sendMessage("Хитбокс");
                return false;
            }
        }
    } 
    return true; //or false if drop
});
//$iface->getProxy()->SetNickname("proxxxy");
//$iface->getProxy()->SetClientID(-956249315208516521);
//$iface->getProxy()->SetUUID("");
$iface->getProxy()->SetRPDownloadBypass(true);
$iface->getProxy()->SetInputMode(2);
$iface->getProxy()->SetDeviceOS(1);
$iface->getProxy()->SetDeviceModel("XIAOMI redmi note 8");
$iface->startTo(Proxyface::createAddress(ADDRESS, PORT));
