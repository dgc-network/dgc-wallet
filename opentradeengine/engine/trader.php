<?php
/**
 * Trader object used for accessing variables
 *
 * Open Trade Engine
 */
class Trader {
    private $ID = null;
    private $balances = [];
    private $referrer = null;
    private $points = 0;

    function __construct($ID=false) {
        if (!$ID) {
            $this->setupTrader($ID);
        }
    }

    //fetch trader by ID, for internal use only (not parameterized)
    public function setupTrader($ID)
    {
        //set ID of trader
        $this->ID = $ID;

        //get a connection
        //$connection = connectionFactory::getConnection();
        global $wpdb;
        $connection = $wpdb;

        //retrieve balances
        $result = $connection->query("SELECT `{$wpdb->base_prefix}Currencies`.`Symbol` AS 'symbol', `{$wpdb->base_prefix}TraderCurrencies`.`Balance` AS 'balance',".
            " `{$wpdb->base_prefix}TraderCurrencies`.`HeldBalance` AS 'heldBalance', `{$wpdb->base_prefix}TraderCurrencies`.`PendingBalance` AS 'pendingBalance' FROM".
            " `{$wpdb->base_prefix}TraderCurrencies` LEFT JOIN `{$wpdb->base_prefix}Currencies` ON `{$wpdb->base_prefix}TraderCurrencies`.`Currency` = `{$wpdb->base_prefix}Currencies`.`ID` WHERE `{$wpdb->base_prefix}TraderCurrencies`.`Trader` = $ID");
/*
        if(!$result)
        {
            throw new Exception("Could not fetch trader currencies.".$connection->error);
        }

        while($row = $result->fetch_assoc())
        {
            //set values from database
            $this->balances[$row['symbol']] = ['balance'=>$row['balance'], 'heldBalance'=>$row['heldBalance'],
                'pendingBalance'=>$row['pendingBalance']];
        }
        $result->close();
*/
        $result = $wpdb->get_results("SELECT `{$wpdb->base_prefix}Currencies`.`Symbol` AS 'symbol', `{$wpdb->base_prefix}TraderCurrencies`.`Balance` AS 'balance',".
            " `{$wpdb->base_prefix}TraderCurrencies`.`HeldBalance` AS 'heldBalance', `{$wpdb->base_prefix}TraderCurrencies`.`PendingBalance` AS 'pendingBalance' FROM".
            " `{$wpdb->base_prefix}TraderCurrencies` LEFT JOIN `{$wpdb->base_prefix}Currencies` ON `{$wpdb->base_prefix}TraderCurrencies`.`Currency` = `{$wpdb->base_prefix}Currencies`.`ID` WHERE `{$wpdb->base_prefix}TraderCurrencies`.`Trader` = $ID", ARRAY_A);
        
        foreach ($result as $row) {
            $this->balances[$row['symbol']] = [
                'balance'=>$row['balance'], 
                'heldBalance'=>$row['heldBalance'],
                'pendingBalance'=>$row['pendingBalance']
            ];
        }

        //get referral information
        $referralResult = $connection->query("Select `Referrer`, `Points` FROM `{$wpdb->base_prefix}Traders` WHERE `ID` = $ID");
/*
        if($row = $referralResult->fetch_assoc()) {
            $this->referrer = $row['Referrer'];
            $this->points = $row['Points'];
        }
*/

        $row = $wpdb->get_row("Select `Referrer`, `Points` FROM `{$wpdb->base_prefix}Traders` WHERE `ID` = $ID", ARRAY_A);
        $this->referrer = $row['Referrer'];
        $this->points = $row['Points'];

    }
    
    public function getPoints()
    {
        return $this->points;
    }
    
    public function getReferrer()
    {
        return $this->referrer;
    }
    
    public function getID()
    {
        return $this->ID;
    }
    
    //get balance based on symbol
    public function getBalance($symbol)
    {    
        return $this->balances[$symbol]['balance'];
    }
    
    //get balance based on symbol
    public function getHeldBalance($symbol)
    {    
        return $this->heldBalances[$symbol]['heldBalance'];
    }
    
    //get the user's pending deposit balance
    public function getPendingBalance($symbol, $side)
    {    
        return $this->pendingBalances[$symbol]['pendingBalance'];
    }
}