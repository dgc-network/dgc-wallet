<?php

    include('phpqrcode.php');
    
    // outputs image directly into browser, as PNG stream
    QRcode::png('PHP QR Code :)');
    //$qrcode_address = dgc_wallet()->wallet_admin->qrcode_address;
    //QRcode::png($qrcode_address);