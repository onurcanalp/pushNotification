pushNotification
================

Apple &amp; Android Push Notifications

include("includes/functions.php");
//$devices => array("asdf...","qwert..."....);
if($devices){
    sendPushNotification("Mesajınız Yanıtlandı", 12, $devices, $message_id);
}
