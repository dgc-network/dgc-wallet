<?php
/**
 * Represents an order for both sides of orderbook
 *
 * Open Trade Engine
 */

class Order {
    private $price;
    private $quantity;
    private $type;
    private $side;
    private $owner;
    private $ID;
    private $TS;
    private $symbol;
    private $tempQuantity;
    private $feePercent;
    
    //Constructor
    function __construct($setPrice, $setQuantity, $setType, $setSide, $setOwner, $setSymbol, $setFeePercent=0) {
       $this->price = $setPrice;
       $this->quantity = $setQuantity;
       $this->type = $setType;
       $this->side = $setSide;
       $this->owner = $setOwner;
       $this->symbol = $setSymbol;
       $this->feePercent= $setFeePercent;
    }
    
    //getters 
    function getFee() {
        return $this->feePercent;
    }
    
    function getID() {
        return $this->ID;
    }
    
    function getPrice() {
        return $this->price;
    }
    
    function getQuantity() {
        return $this->quantity;
    }
    
    function getTempQuantity() {
         return $this->tempQuantity;
    }
    
    function getTempTotal() {
        return $this->tempQuantity * $this->price;
    }
    
    function getTimestamp() {
        return $this->TS;
    }
    
    function getType() {
        return $this->type;
    }
    
    function getSide() {
        return $this->side;
    }
        
    function getOwner() {
        return $this->owner;
    }
    
    function getSymbol() {
        return $this->symbol;
    }
    
    function getTotal() {
        $total = $this->quantity * $this->price;
        return $total;
    }

    //updates quantity in database as well as in object
    function updateQuantity($newQuantity) {
        //setup the connection
        //$connection = connectionFactory::getConnection();
        //global $wpdb;
        //$connection = $wpdb;

        global $wpdb;
        $table_name = $wpdb->prefix . $this->symbol.$this->side.'s';
        $wpdb_collate = $wpdb->collate;
          
        $sql =
            "CREATE TABLE {$table_name} (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `Price` decimal(16,8) unsigned NOT NULL,
            `Quantity` decimal(16,8) unsigned NOT NULL,
            `Type` varchar(10) NOT NULL,
            `Owner` int(11) NOT NULL,
            `Symbol` int(11) NOT NULL,
            `FeePercent` decimal(6,3) NOT NULL DEFAULT '0.000',
            PRIMARY KEY (`ID`),
            KEY `{$table_name}_Traders_ID_fk` (`Owner`),
            KEY `{$table_name}_Symbols_ID_fk` (`Symbol`),
            CONSTRAINT `{$table_name}_Symbols_ID_fk` FOREIGN KEY (`Symbol`) REFERENCES `Symbols` (`ID`),
            CONSTRAINT `{$table_name}_Traders_ID_fk` FOREIGN KEY (`owner`) REFERENCES `Traders` (`ID`)
            )
            COLLATE {$wpdb_collate}";
    
        //require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta( $sql );


        //update trade balances in database
        //$connection->query("UPDATE `$this->symbol".$this->side."s` SET `Quantity`=" .$newQuantity. " WHERE `ID`=" .$this->ID);
        $wpdb->query("UPDATE `{$table_name}` SET `Quantity`=" .$newQuantity. " WHERE `ID`=" .$this->ID);

        $this->setQuantity($newQuantity);
    }

    //setters
    //sets quantity in database and object, internal use only
    function setQuantity($newQuantity) {
        $this->quantity = $newQuantity;
    }
    
    //sets a temporary quantity for use in displaying a combined order, internal use only
    function setTempQuantity($newQuantity) {
         $this->tempQuantity = $newQuantity;
    }

    //submission timestamp
    function setTimestamp($newTS) {
        $this->TS = $newTS;
    }
    
    function setID($newID) {
        $this->ID = $newID;
    }
    
    //Check if orders are equal
    function compareTo($order) {
        //used to calculate if two orders are equal
        $requiredAccuracy = 0.00000001;
        
        if(abs($order->getPrice() - $this->price) < $requiredAccuracy) {
            //orders are equal so return 0
            return 0;
        } else if($order->getPrice() > $this->price) {
            //return one if the order being compared is greater than this order
            return 1;
        } else if($order->getPrice() < $this->price) {
            //return negative one if the order being checked is less than this one
            return -1;
        }
    }
}