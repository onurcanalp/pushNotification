 *
 * Push Notification
 *
 * m: mesaj
 * d: display(açılmasını istediğimiz ekran)
 *      0 - Kategoriler
 *      1 - Sepet
 *      2 - Son görüntülenen ürünler
 *      3 - Profil
 *      4 - Bilgi Merkezi
 *      5 - Sipariş Listesi
 *      6 - Hediye Çekleri
 *      7 - Kupon Kodları
 *      8 - Destek Listesi
 *      9 - Kampanya
 *      10 - Ürün
 *      11 - Sipariş
 *      12 - Destek Detayı
 * i: id (varsa id)
 * badge: ios için üstteki rakam
 */
function sendPushNotification($mesaj = "", $display = 0, $devices = array(), $id = 0, $badge = 0){

    requireFile("class.pushNotification.php");
    $pushObj = new pushNotification();

    //hatalı display geldi ise default profili açtıralım
    $display = (int)$display;
    if($display > 12){
        $display = 0;
        $id = 0;
    }

    //hatalı array(device_id - device_type) gönderildiyse tümüne gönderelim
    if (empty($devices) || !is_array($devices)){
        $devices = $pushObj->getAllDevices();
    }


    $parameters['m'] = $mesaj;
    $parameters['d'] = $display;

    if($id > 0){
        $parameters['i'] = $id;
    }

    //parametrelere ekleyelim, sonra prepareAndSend metodunda android ise temizliyoruz.
    if($badge > 0){
        $parameters['badge'] = $badge;
    }

    $result = $pushObj->prepareAndSend($parameters,$devices);

    $success = true;
    $failCount = 0;

    if(isset($result['android']) && $result['android']->failure > 0){
        $success = false;
        $failCount += $result['android']->failure;
    }

    if(isset($result['ios']) && $result['ios']->failure > 0){
        $success = false;
        $failCount += $result['ios']->failure;
    }


    unset($pushObj, $parameters);

    if($success){
        //Tümü başarılı
        return true;
    }
    else{
        //biri veya bir kaçı hatalı gitti.. Hatalı giden adeti döndürelim
        return $failCount;
    }
}
