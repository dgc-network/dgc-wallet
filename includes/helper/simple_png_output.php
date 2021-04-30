<?php

    include('phpqrcode.php');
    
    // outputs image directly into browser, as PNG stream
    //QRcode::png('PHP QR Code :)');
    QRcode::png(dgc_wallet()->wallet_admin->qrcode_address);